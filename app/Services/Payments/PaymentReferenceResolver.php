<?php

namespace App\Services\Payments;

use App\Enums\EventRegistrationStatus;
use App\Enums\PaymentStatus;
use App\Enums\ShopOrderStatus;
use App\Models\EmailLog;
use App\Models\EventRegistration;
use App\Models\Member;
use App\Models\MembershipPayment;
use App\Models\ShopOrder;
use App\Support\PaymentReferencePrefix;
use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Works out what a deposit in the club account was actually paying for.
 *
 * The club mints three families of reference — PREFIX-M{entry} for match
 * entries, PREFIX-YYYYMMDD-NNNN for membership payments and PREFIX-SHP-… for
 * shop orders — and members quote them back at us imperfectly. The most common
 * failure by far is a member reusing the reference they were given when they
 * first joined for every payment they make afterwards, so a reference that
 * resolves cleanly still tells you the wrong thing.
 *
 * The strategy is therefore two-sided:
 *
 *   1. Read the reference as generously as possible, and validate every
 *      reading against the database instead of trusting the parse. A reference
 *      whose separators the bank ate ("PPRCM1489") is ambiguous on paper but
 *      usually has exactly one reading that corresponds to a real entry.
 *   2. Once *anybody* is identified, list everything they still owe. That is
 *      what rescues the reused-reference case, and it is why nothing here ever
 *      settles a payment on its own — it hands the admin a ranked shortlist.
 */
class PaymentReferenceResolver
{
    /** Names/records to pull back per lookup, so a vague line can't run away with the page. */
    protected const LOOKUP_LIMIT = 25;

    /** @var int Total candidates returned. */
    protected const RESULT_LIMIT = 30;

    /**
     * @return array<int, PaymentMatch>
     */
    public function resolve(string $raw): array
    {
        $narration = BankNarration::make($raw);

        if ($narration->normalised() === '') {
            return [];
        }

        $prefix = PaymentReferencePrefix::get();
        $tokens = $narration->referenceTokens($prefix);

        /** @var array<string, PaymentMatch> $matches */
        $matches = [];

        /** @var array<int, string> $subjects member id => confidence of the identification */
        $subjects = [];

        foreach ($tokens as $token) {
            foreach ($this->fromMatchEntryToken($token, $prefix) as $match) {
                $this->collect($matches, $match);
            }

            // Direct string lookup on the stored payment_reference column,
            // for the rare case where the id-based parse cannot reconstruct
            // what the bank actually saw (imported legacy data, hand-edited
            // references, format changes older than the current parser).
            foreach ($this->fromMatchEntryReferenceColumn($token, $prefix) as $match) {
                $this->collect($matches, $match);
            }

            foreach ($this->fromMembershipToken($token, $prefix) as $match) {
                $this->collect($matches, $match);
            }

            foreach ($this->fromShopToken($token, $prefix) as $match) {
                $this->collect($matches, $match);
            }

            // A membership number identifies a person but never a payable, so
            // it only seeds the "what do they owe" pass below.
            foreach ($this->membersByNumber($token, $prefix) as $member) {
                $subjects[$member->id] = PaymentMatch::EXACT;
            }
        }

        $certain = false;

        foreach ($matches as $match) {
            if ($match->memberId !== null) {
                $subjects[$match->memberId] ??= PaymentMatch::EXACT;
            }

            $certain = $certain || $match->isExact();
        }

        // Only fall back to the payer's name once the reference has failed to
        // produce a certain answer. "PPRC-M13-95 J NEL" must not drag in every
        // Nel on the books alongside the entry it already found — but
        // "PL FOURIE - PPRC-M12", a reference the member never finished typing,
        // is identified far better by the name than by the fragment.
        if (! $certain) {
            foreach ($this->fromGuestNames($narration, $prefix) as $match) {
                $this->collect($matches, $match);
            }

            foreach ($this->membersByName($narration, $prefix) as $member) {
                $subjects[$member->id] ??= PaymentMatch::POSSIBLE;
            }
        }

        foreach ($this->openPayablesFor($subjects) as $match) {
            $this->collect($matches, $match);
        }

        // Last resort: the reference resolved to nothing in the database at
        // all, but we may have emailed it to somebody once. That email is
        // proof of ownership even when the record it was about is gone —
        // the whole reason `payments:trace` reads the email log.
        if ($this->hasActionable($matches) === false) {
            foreach ($this->fromEmailLog($tokens, $prefix) as $match) {
                $this->collect($matches, $match);
            }
        }

        return $this->rank($matches);
    }

    /**
     * @param  array<string, PaymentMatch>  $matches
     */
    protected function hasActionable(array $matches): bool
    {
        foreach ($matches as $match) {
            if ($match->kind !== PaymentMatch::IDENTIFICATION) {
                return true;
            }
        }

        return false;
    }

    // -----------------------------------------------------------------
    // Reference families
    // -----------------------------------------------------------------

    /**
     * @return array<int, PaymentMatch>
     */
    protected function fromMatchEntryToken(string $token, string $prefix): array
    {
        $readings = $this->matchEntryReadings($token);

        if ($readings === []) {
            return [];
        }

        $entries = EventRegistration::query()
            ->with(['event', 'member.user'])
            ->whereIn('id', array_column($readings, 'id'))
            ->get()
            ->keyBy('id');

        /** @var array<int, EventRegistration> $survivors */
        $survivors = [];

        foreach ($readings as $reading) {
            $entry = $entries->get($reading['id']);

            if (! $entry) {
                continue;
            }

            // A legacy reference carries the match id as well, which is exactly
            // what lets us throw out the wrong splits of a dash-less reference.
            if ($reading['event_id'] !== null && (int) $entry->event_id !== $reading['event_id']) {
                continue;
            }

            $survivors[(int) $entry->id] = $entry;
        }

        if ($survivors === []) {
            return [];
        }

        $sole = count($survivors) === 1;

        return array_map(fn (EventRegistration $entry) => $this->describeEntry(
            $entry,
            $sole ? PaymentMatch::EXACT : PaymentMatch::LIKELY,
            $sole
                ? 'Match entry reference '.$prefix.'-'.$token
                : 'Reference '.$prefix.'-'.$token.' lost its separators — one of '
                    .count($survivors).' entries it could mean',
        ), array_values($survivors));
    }

    /**
     * Every entry id a match-entry token could be referring to.
     *
     * @return array<int, array{id: int, event_id: int|null}>
     */
    protected function matchEntryReadings(string $token): array
    {
        // "M14-103" — the legacy match-and-entry form with its separator
        // intact, so there is nothing left to guess.
        if (preg_match('/^M(\d+)-(\d+)$/', $token, $found) === 1) {
            return [['id' => (int) $found[2], 'event_id' => (int) $found[1]]];
        }

        if (preg_match('/^M(\d+)$/', $token, $found) !== 1) {
            return [];
        }

        $digits = $found[1];

        // The current form is PREFIX-M{entry}. Where the bank has eaten the
        // dashes the same digits could also be the legacy PREFIX-M{match}-{entry},
        // so offer every split and let the database rule out the impossible.
        $readings = [['id' => (int) $digits, 'event_id' => null]];

        for ($at = 1; $at < strlen($digits); $at++) {
            $readings[] = [
                'id' => (int) substr($digits, $at),
                'event_id' => (int) substr($digits, 0, $at),
            ];
        }

        return $readings;
    }

    /**
     * Direct string lookup on `event_registrations.payment_reference`.
     *
     * Belt to `fromMatchEntryToken`'s braces: the id-based parse is the
     * common case, this handles the awkward cases the parser cannot reach —
     * imported entries where the stored reference doesn't derive from the
     * entry's own id, hand-edited references, and any historical format the
     * current parser has since forgotten. Cheap: one indexed lookup per
     * canonicalised reference.
     *
     * @return array<int, PaymentMatch>
     */
    protected function fromMatchEntryReferenceColumn(string $token, string $prefix): array
    {
        if (! Schema::hasColumn('event_registrations', 'payment_reference')) {
            return [];
        }

        $entries = EventRegistration::query()
            ->with(['event', 'member.user'])
            ->whereIn('payment_reference', $this->matchEntryReferenceCandidates($token, $prefix))
            ->limit(self::LOOKUP_LIMIT)
            ->get();

        return $entries
            ->map(fn (EventRegistration $entry) => $this->describeEntry(
                $entry,
                PaymentMatch::EXACT,
                'Match entry reference '.$entry->paymentReference(),
            ))
            ->all();
    }

    /**
     * Every canonicalised reference string a match-entry token could stand
     * for. Includes the raw dash form and — when the bank ate the M's dash —
     * a version with it restored, so `PPRC-M1252` still hits an entry stored
     * as `PPRC-M12-52`.
     *
     * @return array<int, string>
     */
    protected function matchEntryReferenceCandidates(string $token, string $prefix): array
    {
        $candidates = [$prefix.'-'.$token];

        // "M1252" — legacy match-and-entry glued together. Offer every split
        // as a candidate reference; the query throws out the misses.
        if (preg_match('/^M(\d+)$/', $token, $found) === 1) {
            $digits = $found[1];

            for ($at = 1; $at < strlen($digits); $at++) {
                $candidates[] = sprintf(
                    '%s-M%s-%s',
                    $prefix,
                    substr($digits, 0, $at),
                    substr($digits, $at),
                );
            }
        }

        return array_values(array_unique($candidates));
    }

    /**
     * @return array<int, PaymentMatch>
     */
    protected function fromMembershipToken(string $token, string $prefix): array
    {
        $payments = MembershipPayment::query()
            ->withTrashed()
            ->with([
                'membership.member.user',
                'membership.memberWithTrashed.user',
                'membership.membershipType',
            ])
            ->whereIn('reference', $this->membershipReferences($token, $prefix))
            ->limit(self::LOOKUP_LIMIT)
            ->get();

        return $payments
            ->map(function (MembershipPayment $payment) {
                // A soft-deleted payment predates its removal — the bank
                // deposit against it is real, but there is no live row for
                // the recon to settle against. Identify the payer and step
                // aside; the admin decides what to do.
                if ($payment->trashed()) {
                    return $this->describeRemovedMembershipPayment($payment);
                }

                return $this->describeMembershipPayment(
                    $payment,
                    PaymentMatch::EXACT,
                    'Membership payment reference '.$payment->reference,
                );
            })
            ->all();
    }

    /**
     * @return array<int, string>
     */
    protected function membershipReferences(string $token, string $prefix): array
    {
        $references = [$prefix.'-'.$token];

        // "PPRCMEM20260502-0002" — an older reference format carried a MEM
        // marker the current one drops. Try it both ways.
        if (str_starts_with($token, 'MEM')) {
            $token = trim(substr($token, 3), '-');
            $references[] = $prefix.'-'.$token;
        }

        // "PPRC202607250001" — dashes gone from PREFIX-YYYYMMDD-NNNN, still
        // recoverable because the date block is always eight digits.
        if (preg_match('/^(\d{8})(\d{4})$/', $token, $found) === 1) {
            $references[] = $prefix.'-'.$found[1].'-'.$found[2];
        }

        // "20260105-6" — the trailing sequence lost its zero padding.
        if (preg_match('/^(\d{8})-(\d{1,4})$/', $token, $found) === 1) {
            $references[] = $prefix.'-'.$found[1].'-'.str_pad($found[2], 4, '0', STR_PAD_LEFT);
        }

        return array_values(array_unique($references));
    }

    /**
     * @return array<int, PaymentMatch>
     */
    protected function fromShopToken(string $token, string $prefix): array
    {
        if (! str_starts_with($token, 'SHP')) {
            return [];
        }

        return ShopOrder::query()
            ->with('user')
            ->where('eft_reference', $prefix.'-'.$token)
            ->limit(self::LOOKUP_LIMIT)
            ->get()
            ->map(fn (ShopOrder $order) => new PaymentMatch(
                kind: PaymentMatch::SHOP_ORDER,
                id: (int) $order->id,
                confidence: PaymentMatch::EXACT,
                reason: 'Shop order reference '.$order->eft_reference,
                who: $order->ship_to_name ?: ($order->user?->name ?? 'Shop order'),
                what: 'Shop order #'.$order->id,
                amountCents: (int) $order->total_cents,
                settled: $order->status !== ShopOrderStatus::PendingPayment,
                reference: $order->eft_reference,
                settledNote: $order->status !== ShopOrderStatus::PendingPayment
                    ? 'Order is no longer awaiting payment'
                    : null,
                email: $order->user?->email,
            ))
            ->all();
    }

    /**
     * Last-resort identification via the email log.
     *
     * Every reference we mint gets emailed to somebody — as a payment
     * request, an entry confirmation, or a receipt — so the email log is a
     * durable record of who a reference belongs to. When the underlying row
     * has been deleted (hard-deleted match entry, cleaned-up import, moved
     * on) the email is often the only trace left, and it is exactly what the
     * `payments:trace` command has always used. The recon needs it too.
     *
     * Returns one identification hit per distinct recipient, so a reference
     * that was mailed to a member and cc'd to admin surfaces as the member,
     * not four times over.
     *
     * @param  array<int, string>  $tokens
     * @return array<int, PaymentMatch>
     */
    protected function fromEmailLog(array $tokens, string $prefix): array
    {
        if ($tokens === [] || ! Schema::hasTable('email_logs')) {
            return [];
        }

        $references = [];
        foreach ($tokens as $token) {
            foreach ($this->matchEntryReferenceCandidates($token, $prefix) as $reference) {
                $references[$reference] = true;
            }
            foreach ($this->membershipReferences($token, $prefix) as $reference) {
                $references[$reference] = true;
            }
            $references[$prefix.'-'.$token] = true;
        }

        $references = array_keys($references);
        if ($references === []) {
            return [];
        }

        $hasBody = Schema::hasColumn('email_logs', 'body_html');

        $rows = EmailLog::query()
            ->where(function (Builder $query) use ($references, $hasBody) {
                foreach ($references as $reference) {
                    $query->orWhere('subject', 'like', '%'.$reference.'%');

                    if ($hasBody) {
                        $query->orWhere('body_html', 'like', '%'.$reference.'%');
                    }
                }
            })
            ->orderByDesc('sent_at')
            ->limit(self::LOOKUP_LIMIT * 2)
            ->get(['id', 'to_email', 'to_name', 'subject', 'body_html', 'sent_at']);

        /** @var array<string, PaymentMatch> $byRecipient */
        $byRecipient = [];

        foreach ($rows as $row) {
            $reference = $this->firstReferenceFound($row, $references);

            if ($reference === null) {
                continue;
            }

            $email = trim((string) $row->to_email);
            $name = trim((string) ($row->to_name ?? '')) ?: $email;

            if ($email === '') {
                continue;
            }

            $key = strtolower($email).'|'.$reference;

            if (isset($byRecipient[$key])) {
                continue;
            }

            $sentOn = $row->sent_at?->format('d M Y') ?? 'an unknown date';

            $byRecipient[$key] = new PaymentMatch(
                kind: PaymentMatch::IDENTIFICATION,
                id: (int) $row->id,
                confidence: PaymentMatch::INFO,
                reason: 'Reference '.$reference.' was emailed to '.$name.' on '.$sentOn,
                who: $name,
                what: 'Reference emailed — no live record to settle',
                amountCents: 0,
                settled: true,
                reference: $reference,
                settledNote: 'The record this reference was minted for is no longer in the system. '
                    .'Identify the payer by hand and settle against whatever they now owe.',
                email: $email,
            );
        }

        return array_values($byRecipient);
    }

    /**
     * The first of our reference candidates that appears in the email's
     * subject or (optionally) body. Deals with the mail template quoting the
     * reference in different forms across different mailables.
     *
     * @param  array<int, string>  $references
     */
    protected function firstReferenceFound(EmailLog $row, array $references): ?string
    {
        $subject = (string) ($row->subject ?? '');
        $body = (string) ($row->body_html ?? '');

        foreach ($references as $reference) {
            if ($reference === '') {
                continue;
            }

            if (stripos($subject, $reference) !== false || stripos($body, $reference) !== false) {
                return $reference;
            }
        }

        return null;
    }

    // -----------------------------------------------------------------
    // People
    // -----------------------------------------------------------------

    /**
     * @return Collection<int, Member>
     */
    protected function membersByNumber(string $token, string $prefix): Collection
    {
        return Member::query()
            ->with('user')
            ->whereIn('membership_number', array_unique([$prefix.'-'.$token, $token]))
            ->limit(self::LOOKUP_LIMIT)
            ->get();
    }

    /**
     * @return Collection<int, Member>
     */
    protected function membersByName(BankNarration $narration, string $prefix = ''): Collection
    {
        $words = $narration->nameWords($prefix);

        if ($words === []) {
            return new Collection;
        }

        return Member::query()
            ->with('user')
            ->where(function (Builder $query) use ($words) {
                foreach ($words as $word) {
                    foreach (['first_name', 'last_name', 'known_as'] as $column) {
                        $query->orWhere(...$this->likeArguments($column, $word));
                    }
                }
            })
            ->limit(self::LOOKUP_LIMIT)
            ->get();
    }

    /**
     * Guests have no member record, so the only thing a name can be matched
     * against is the entry itself.
     *
     * @return array<int, PaymentMatch>
     */
    protected function fromGuestNames(BankNarration $narration, string $prefix = ''): array
    {
        $words = $narration->nameWords($prefix);

        if ($words === []) {
            return [];
        }

        return EventRegistration::query()
            ->with('event')
            ->whereNull('member_id')
            ->whereNull('paid_at')
            ->where('status', '!=', EventRegistrationStatus::Cancelled->value)
            ->where(function (Builder $query) use ($words) {
                foreach ($words as $word) {
                    $query->orWhere(...$this->likeArguments('guest_name', $word));
                }
            })
            ->limit(self::LOOKUP_LIMIT)
            ->get()
            ->filter(fn (EventRegistration $entry) => $entry->awaitingPayment())
            ->map(fn (EventRegistration $entry) => $this->describeEntry(
                $entry,
                PaymentMatch::POSSIBLE,
                'Guest name on the payment looks like this entry',
            ))
            ->values()
            ->all();
    }

    /**
     * Everything the identified people still owe.
     *
     * This is the pass that saves the reused-reference case: the reference told
     * us who paid, and this tells us what they could plausibly have been paying
     * for even though they quoted the wrong thing.
     *
     * @param  array<int, string>  $subjects  member id => confidence
     * @return array<int, PaymentMatch>
     */
    protected function openPayablesFor(array $subjects): array
    {
        if ($subjects === []) {
            return [];
        }

        $memberIds = array_keys($subjects);
        $matches = [];

        $entries = EventRegistration::query()
            ->with(['event', 'member.user'])
            ->whereIn('member_id', $memberIds)
            ->whereNull('paid_at')
            ->where('status', '!=', EventRegistrationStatus::Cancelled->value)
            ->limit(self::RESULT_LIMIT)
            ->get()
            ->filter(fn (EventRegistration $entry) => $entry->awaitingPayment());

        foreach ($entries as $entry) {
            $matches[] = $this->describeEntry(
                $entry,
                $this->downgrade($subjects[(int) $entry->member_id] ?? PaymentMatch::POSSIBLE),
                'This shooter still owes for this match',
            );
        }

        $payments = MembershipPayment::query()
            ->with([
                'membership.member.user',
                'membership.memberWithTrashed.user',
                'membership.membershipType',
            ])
            ->whereIn('status', [PaymentStatus::Pending->value, PaymentStatus::Submitted->value])
            ->whereHas('membership', fn (Builder $query) => $query->whereIn('member_id', $memberIds))
            ->limit(self::RESULT_LIMIT)
            ->get();

        foreach ($payments as $payment) {
            $memberId = $payment->payerMember()?->id;

            $matches[] = $this->describeMembershipPayment(
                $payment,
                $this->downgrade($subjects[$memberId] ?? PaymentMatch::POSSIBLE),
                'This member has a membership payment outstanding',
            );
        }

        return $matches;
    }

    // -----------------------------------------------------------------
    // Describing candidates
    // -----------------------------------------------------------------

    protected function describeEntry(EventRegistration $entry, string $confidence, string $reason): PaymentMatch
    {
        $note = match (true) {
            $entry->paid_at !== null => 'Already marked paid on '.$entry->paid_at->format('d M Y'),
            $entry->is_saprf_entry => 'Entry is paid through SAPRF',
            $entry->isWaived() => 'Free / waived entry',
            $entry->outstandingCents() === 0 => 'Nothing outstanding on this entry',
            default => null,
        };

        return new PaymentMatch(
            kind: PaymentMatch::MATCH_ENTRY,
            id: (int) $entry->id,
            confidence: $confidence,
            reason: $reason,
            who: $entry->shooterName(),
            what: trim(($entry->event?->title ?? 'Match entry')
                .($entry->event?->start_date ? ' — '.$entry->event->start_date->format('d M Y') : '')),
            amountCents: $entry->outstandingCents(),
            settled: $note !== null,
            reference: $entry->paymentReference(),
            settledNote: $note,
            email: $entry->payerEmail(),
            memberId: $entry->member_id !== null ? (int) $entry->member_id : null,
        );
    }

    /**
     * A membership payment that has been soft-deleted. Kept as an
     * identification hit so the treasurer can see whose reference this was
     * even though there is no live payment row to reopen and settle.
     */
    protected function describeRemovedMembershipPayment(MembershipPayment $payment): PaymentMatch
    {
        return new PaymentMatch(
            kind: PaymentMatch::IDENTIFICATION,
            id: (int) $payment->id,
            confidence: PaymentMatch::INFO,
            reason: 'Membership payment reference '.$payment->reference,
            who: $payment->payerName(),
            what: 'Membership payment (removed)',
            amountCents: (int) ($payment->amount_cents ?? 0),
            settled: true,
            reference: $payment->reference,
            settledNote: 'This membership payment was removed on '
                .$payment->deleted_at?->format('d M Y')
                .' — the deposit needs recording against something else.',
            email: $payment->payerMember()?->user?->email,
            memberId: $payment->payerMember()?->id,
        );
    }

    protected function describeMembershipPayment(MembershipPayment $payment, string $confidence, string $reason): PaymentMatch
    {
        $open = in_array($payment->status, [PaymentStatus::Pending, PaymentStatus::Submitted], true);

        $type = $payment->membership?->membershipType?->name
            ?? $payment->membership?->membership_type_name_snapshot;

        return new PaymentMatch(
            kind: PaymentMatch::MEMBERSHIP_PAYMENT,
            id: (int) $payment->id,
            confidence: $confidence,
            reason: $reason,
            who: $payment->payerName(),
            what: 'Membership'.($type ? ' — '.$type : ''),
            amountCents: (int) ($payment->amount_cents ?? 0),
            settled: ! $open,
            reference: $payment->reference,
            settledNote: $open ? null : 'Payment is already '.strtolower((string) $payment->status?->label()),
            email: $payment->payerMember()?->user?->email,
            memberId: $payment->payerMember()?->id,
        );
    }

    // -----------------------------------------------------------------
    // Plumbing
    // -----------------------------------------------------------------

    /**
     * Case-insensitive "contains" that behaves the same on Postgres, MySQL and
     * SQLite, which matters because bank narrations arrive shouting.
     *
     * @return array{0: Expression, 1: string, 2: string}
     */
    protected function likeArguments(string $column, string $word): array
    {
        return [
            DB::raw('lower('.$column.')'),
            'like',
            '%'.mb_strtolower($word).'%',
        ];
    }

    /**
     * A person identified beyond doubt still only makes their *other* open
     * items a likely explanation, never a certain one.
     */
    protected function downgrade(string $confidence): string
    {
        return $confidence === PaymentMatch::EXACT
            ? PaymentMatch::LIKELY
            : PaymentMatch::POSSIBLE;
    }

    /**
     * @param  array<string, PaymentMatch>  $matches
     */
    protected function collect(array &$matches, PaymentMatch $match): void
    {
        $existing = $matches[$match->key()] ?? null;

        // The same record can be reached by several routes — keep whichever
        // explanation we're most sure of.
        if ($existing !== null && $existing->weight() >= $match->weight()) {
            return;
        }

        $matches[$match->key()] = $match;
    }

    /**
     * @param  array<string, PaymentMatch>  $matches
     * @return array<int, PaymentMatch>
     */
    protected function rank(array $matches): array
    {
        $ranked = array_values($matches);

        usort($ranked, function (PaymentMatch $a, PaymentMatch $b) {
            return [$b->weight(), $a->settled ? 1 : 0, $b->amountCents]
                <=> [$a->weight(), $b->settled ? 1 : 0, $a->amountCents];
        });

        return array_slice($ranked, 0, self::RESULT_LIMIT);
    }
}

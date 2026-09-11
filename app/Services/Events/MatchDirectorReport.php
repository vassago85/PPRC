<?php

namespace App\Services\Events;

use App\Enums\EventRegistrationStatus;
use App\Models\Event;
use App\Models\EventRegistration;
use Illuminate\Support\Collection;

/**
 * Financial reconciliation for a single match, from the match director's point
 * of view. Classifies every (non-cancelled) entry and works out how much the
 * director should be paid out.
 *
 * Payout model (per the club's rule): every paid entry counts toward the
 * payout, whether or not they attended. The club keeps a fixed levy per
 * paying shooter; optionally it also keeps the non-member surcharge (the
 * gap between the guest fee and the member rate). The director receives
 * the rest of the EFT fees the club is holding.
 *
 * Classifications:
 *   - payout   : owes a fee and paid -> counts toward the payout.
 *   - awaiting : owes a fee but not yet marked paid -> outstanding.
 *   - free     : nothing to pay (ExCo / comped / SAPRF / waived).
 */
class MatchDirectorReport
{
    public const PAYOUT = 'payout';

    public const CREDIT = 'credit';

    public const AWAITING = 'awaiting';

    public const FREE = 'free';

    public function __construct(
        public Event $event,
        public int $levyCents = 0,
        public bool $keepNonMemberDifference = false,
    ) {}

    /**
     * Every non-cancelled entry, classified, sorted by squad then name.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function rows(): Collection
    {
        return $this->event->registrations()
            ->with(['member.user.roles'])
            ->get()
            ->reject(fn (EventRegistration $r) => $r->status === EventRegistrationStatus::Cancelled)
            ->map(function (EventRegistration $r) {
                $fee = (int) ($r->effectiveFeeCents() ?? 0);
                $paid = $r->paid_at !== null;
                $attended = (bool) $r->attended;
                $memberRate = $this->memberEquivalentCents($r);
                $clubPremium = $this->clubPremiumCents($fee, $memberRate);

                return [
                    'id' => $r->id,
                    'name' => $r->shooterName(),
                    'is_member' => $r->member_id !== null,
                    'division' => $r->division,
                    'category' => $r->category,
                    'fee_cents' => $fee,
                    'member_rate_cents' => $memberRate,
                    'club_premium_cents' => $clubPremium,
                    'credit_applied_cents' => $r->creditAppliedCents(),
                    'outstanding_cents' => $r->outstandingCents(),
                    'paid' => $paid,
                    'is_cash' => $r->isCashPayment(),
                    'attended' => $attended,
                    'reference' => $r->paymentReference(),
                    'classification' => $this->classify($fee, $paid),
                ];
            })
            ->sortBy(fn (array $row) => mb_strtolower($row['name']))
            ->values();
    }

    private function classify(int $fee, bool $paid): string
    {
        if ($fee <= 0) {
            return self::FREE;
        }

        if (! $paid) {
            return self::AWAITING;
        }

        return self::PAYOUT;
    }

    /**
     * What a member (or junior) would have paid for this entry — the rate the
     * director is paid at when the club keeps the non-member difference.
     */
    private function memberEquivalentCents(EventRegistration $registration): int
    {
        if ($registration->is_junior) {
            return (int) ($this->event->juniorPriceCents() ?? 0);
        }

        return (int) ($this->event->memberPriceCents() ?? 0);
    }

    private function clubPremiumCents(int $fee, int $memberRate): int
    {
        if (! $this->keepNonMemberDifference || $fee <= 0) {
            return 0;
        }

        return max(0, $fee - $memberRate);
    }

    /**
     * Headline totals for the report.
     *
     * @return array<string, int>
     */
    public function summary(): array
    {
        $rows = $this->rows();

        $payout = $rows->where('classification', self::PAYOUT);
        $awaiting = $rows->where('classification', self::AWAITING);

        // Split the payable (paid) fees by how they were paid. EFT sits
        // in the club account and is owed to the director; cash was handed to
        // the director on the day, so it isn't part of what the club owes.
        $eftPayout = $payout->where('is_cash', false);
        $cashPayout = $payout->where('is_cash', true);

        $payoutBaseCents = (int) $payout->sum('fee_cents');
        $eftBaseCents = (int) $eftPayout->sum('fee_cents');
        $cashBaseCents = (int) $cashPayout->sum('fee_cents');
        $payoutCount = $payout->count();
        $clubPremiumCents = (int) $payout->sum('club_premium_cents');

        // The club's per-head levy (and optional non-member surcharge) apply
        // to every paying shooter, regardless of how they paid. Both are
        // recovered from the EFT pot the club is holding.
        $levyTotalCents = $this->levyCents * $payoutCount;
        $directorPayoutCents = max(0, $eftBaseCents - $levyTotalCents - $clubPremiumCents);

        return [
            'entries_total' => $rows->count(),
            'attended_count' => $rows->where('attended', true)->count(),
            'payout_count' => $payoutCount,
            'cash_count' => $cashPayout->count(),
            'credit_count' => 0,
            'awaiting_count' => $awaiting->count(),
            'free_count' => $rows->where('classification', self::FREE)->count(),

            'gross_collected_cents' => (int) $rows->where('paid', true)->sum('fee_cents'),
            'payout_base_cents' => $payoutBaseCents,
            'eft_base_cents' => $eftBaseCents,
            'cash_base_cents' => $cashBaseCents,
            'credit_cents' => 0,
            // What is still to come in, net of any fee already settled from a
            // transferred credit — chasing the full fee would double-bill.
            'outstanding_cents' => (int) $awaiting->sum('outstanding_cents'),
            // Part of the gross above that arrived as a credit carried over
            // from another match rather than as new money into the account.
            'credit_funded_cents' => (int) $rows->sum('credit_applied_cents'),

            'levy_cents' => $this->levyCents,
            'levy_total_cents' => $levyTotalCents,
            'club_premium_cents' => $clubPremiumCents,
            'director_payout_cents' => $directorPayoutCents,
        ];
    }
}

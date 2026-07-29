<?php

namespace App\Services\Events;

use App\Enums\EventRegistrationStatus;
use App\Enums\MatchCreditStatus;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\MatchCredit;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Moves a shooter's money between matches when they have paid for one match and
 * cannot shoot it.
 *
 * Everything routes through the match-credit ledger rather than editing an
 * entry's `event_id`, for three reasons:
 *
 *  - The payment reference the shooter quoted on their EFT is derived from the
 *    event id, so a moved entry stops matching the bank statement.
 *  - Silently re-pointing an entry rewrites the financials of both matches with
 *    no record of why they changed.
 *  - The club's payout rule already works this way: MatchDirectorReport treats
 *    a paid shooter who did not shoot as held credit, not director payout.
 *
 * So a transfer is always: release the old entry to a credit, then spend that
 * credit on the new match.
 */
class MatchEntryTransferService
{
    /**
     * Take a paid entry off its match and hold what they paid as a credit.
     *
     * The entry is cancelled rather than deleted, which drops it out of the
     * match report while leaving the history intact.
     */
    public function release(EventRegistration $entry, ?string $reason = null, ?User $by = null): MatchCredit
    {
        $entry->loadMissing(['event', 'member.user']);

        $value = (int) ($entry->effectiveFeeCents() ?? 0);

        if ($entry->status === EventRegistrationStatus::Cancelled) {
            throw ValidationException::withMessages([
                'entry' => 'This entry is already cancelled.',
            ]);
        }

        if ($entry->paid_at === null) {
            throw ValidationException::withMessages([
                'entry' => 'Only a paid entry can be released as a credit. Withdraw the entry instead.',
            ]);
        }

        if ($value <= 0) {
            throw ValidationException::withMessages([
                'entry' => 'This entry cost nothing, so there is no money to carry over.',
            ]);
        }

        if (MatchCredit::query()->where('source_registration_id', $entry->id)->exists()) {
            throw ValidationException::withMessages([
                'entry' => 'A credit has already been raised from this entry.',
            ]);
        }

        return DB::transaction(function () use ($entry, $reason, $by, $value) {
            $entry->update(['status' => EventRegistrationStatus::Cancelled]);

            return MatchCredit::create([
                'member_id' => $entry->member_id,
                'payee_name' => $entry->shooterName(),
                'payee_email' => $entry->payerEmail(),
                'amount_cents' => $value,
                'reason' => $reason ?: 'Transferred from '.($entry->event?->title ?? 'another match'),
                'source_event_id' => $entry->event_id,
                'source_registration_id' => $entry->id,
                'status' => MatchCreditStatus::Available->value,
                'created_by_user_id' => $by?->id ?? auth()->id(),
            ]);
        });
    }

    /**
     * Release a paid entry and immediately spend the credit on another match.
     */
    public function transfer(EventRegistration $entry, Event $target, ?string $reason = null, ?User $by = null): EventRegistration
    {
        if ($target->id === $entry->event_id) {
            throw ValidationException::withMessages([
                'event_id' => 'That is the match they are already entered in.',
            ]);
        }

        return DB::transaction(function () use ($entry, $target, $reason, $by) {
            $credit = $this->release($entry, $reason, $by);

            return $this->applyToEvent($credit, $target, $by);
        });
    }

    /**
     * Spend a credit on a match, entering the payee if they are not on the list
     * yet. Returns the entry the credit was applied to.
     */
    public function applyToEvent(MatchCredit $credit, Event $event, ?User $by = null): EventRegistration
    {
        return DB::transaction(function () use ($credit, $event, $by) {
            $entry = $this->entryFor($credit, $event);

            $this->settle($credit, $entry, $by);

            return $entry->refresh();
        });
    }

    /**
     * Put a credit against a specific entry: covers as much of the fee as the
     * credit stretches to, and leaves the rest owing.
     */
    public function settle(MatchCredit $credit, EventRegistration $entry, ?User $by = null): void
    {
        if (! $credit->isAvailable()) {
            throw ValidationException::withMessages([
                'credit' => 'That credit has already been used.',
            ]);
        }

        $entry->loadMissing(['event', 'member.user']);

        $outstanding = $entry->outstandingCents();

        if ($entry->paid_at !== null || $outstanding <= 0) {
            throw ValidationException::withMessages([
                'entry' => 'That entry has nothing left to settle.',
            ]);
        }

        $applied = min($credit->amount_cents, $outstanding);
        $leftover = $credit->amount_cents - $applied;

        DB::transaction(function () use ($credit, $entry, $by, $applied, $leftover, $outstanding) {
            $attributes = ['credit_applied_cents' => $entry->creditAppliedCents() + $applied];

            if ($applied >= $outstanding) {
                $attributes['paid_at'] = now();
                $attributes['marked_paid_by_user_id'] = $by?->id ?? auth()->id();

                if (in_array($entry->status, [
                    EventRegistrationStatus::Registered,
                    EventRegistrationStatus::Waitlisted,
                ], true)) {
                    $attributes['status'] = EventRegistrationStatus::Confirmed;
                }
            }

            $entry->update($attributes);

            $credit->update([
                'status' => MatchCreditStatus::Used->value,
                'used_event_id' => $entry->event_id,
                'used_registration_id' => $entry->id,
                'used_at' => now(),
            ]);

            // A credit worth more than the new match keeps its balance rather
            // than being swallowed. Splitting into a fresh row keeps every
            // credit atomically either spent or not, which is all the status
            // enum can express.
            if ($leftover > 0) {
                MatchCredit::create([
                    'member_id' => $credit->member_id,
                    'payee_name' => $credit->payee_name,
                    'payee_email' => $credit->payee_email,
                    'amount_cents' => $leftover,
                    'reason' => 'Balance after '.($entry->event?->title ?? 'a match'),
                    'source_event_id' => $credit->source_event_id,
                    'source_registration_id' => $credit->source_registration_id,
                    'status' => MatchCreditStatus::Available->value,
                    'created_by_user_id' => $by?->id ?? auth()->id(),
                ]);
            }
        });
    }

    /**
     * Undo a settlement: pull the credit back off the entry it paid for and make
     * it available again.
     *
     * If the credit was what settled the entry, the entry goes back to owing —
     * otherwise reinstating would hand the shooter their money back while their
     * place stayed paid for. An admin who also took cash can simply mark it paid
     * again.
     */
    public function unsettle(MatchCredit $credit, ?User $by = null): void
    {
        if ($credit->used_registration_id === null) {
            throw ValidationException::withMessages([
                'credit' => 'This credit was not applied to an entry, so there is nothing to undo.',
            ]);
        }

        $entry = $credit->usedRegistration;

        if (! $entry) {
            throw ValidationException::withMessages([
                'credit' => 'The entry this credit paid for no longer exists.',
            ]);
        }

        DB::transaction(function () use ($credit, $entry, $by) {
            $remaining = max(0, $entry->creditAppliedCents() - $credit->amount_cents);

            $attributes = ['credit_applied_cents' => $remaining];

            $entry->credit_applied_cents = $remaining;

            if ($entry->paid_at !== null && $entry->outstandingCents() > 0) {
                $attributes['paid_at'] = null;
                $attributes['marked_paid_by_user_id'] = null;

                if ($entry->status === EventRegistrationStatus::Confirmed) {
                    $attributes['status'] = EventRegistrationStatus::Registered;
                }
            }

            $entry->update($attributes);

            $credit->update([
                'status' => MatchCreditStatus::Available->value,
                'used_event_id' => null,
                'used_registration_id' => null,
                'used_at' => null,
                'created_by_user_id' => $credit->created_by_user_id ?? $by?->id ?? auth()->id(),
            ]);
        });
    }

    /**
     * Every available credit the payer of this entry is holding, so an admin can
     * settle an unpaid entry from money the club already has.
     *
     * @return Collection<int, MatchCredit>
     */
    public function creditsFor(EventRegistration $entry): Collection
    {
        $email = $entry->payerEmail();

        if ($entry->member_id === null && ! filled($email)) {
            return new Collection;
        }

        return MatchCredit::query()
            ->available()
            ->where(function ($query) use ($entry, $email) {
                if ($entry->member_id !== null) {
                    $query->orWhere('member_id', $entry->member_id);
                }

                if (filled($email)) {
                    $query->orWhere('payee_email', $email);
                }
            })
            ->orderBy('created_at')
            ->get();
    }

    /**
     * The entry on the target match that the credit should settle: their
     * existing one if they are already on the list, otherwise a new one.
     */
    private function entryFor(MatchCredit $credit, Event $event): EventRegistration
    {
        $existing = $this->existingEntry($credit, $event);

        if ($existing && $existing->status !== EventRegistrationStatus::Cancelled) {
            return $existing;
        }

        $attributes = [
            'status' => EventRegistrationStatus::Registered,
            'registered_at' => now(),
        ];

        // event_registrations is uniquely indexed on (event_id, member_id) and
        // (event_id, guest_email), so a previous withdrawal has to be revived
        // in place rather than inserted alongside.
        if ($existing) {
            $existing->update($attributes);

            return $existing->refresh();
        }

        return $event->registrations()->create($attributes + [
            'member_id' => $credit->member_id,
            'guest_name' => $credit->member_id ? null : $credit->payeeName(),
            'guest_email' => $credit->member_id ? null : $credit->payee_email,
        ]);
    }

    private function existingEntry(MatchCredit $credit, Event $event): ?EventRegistration
    {
        $query = $event->registrations();

        if ($credit->member_id !== null) {
            return $query->where('member_id', $credit->member_id)->first();
        }

        if (! filled($credit->payee_email)) {
            return null;
        }

        return $query->where('guest_email', $credit->payee_email)->first();
    }
}

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
 * Payout model (per the club's rule): the club keeps a fixed levy per paying
 * shooter who actually shot; the director receives the rest of those fees.
 *
 * Classifications:
 *   - payout   : owes a fee, paid, AND attended -> counts toward the payout.
 *   - credit   : owes a fee and paid but did NOT attend -> money is held as a
 *                credit for the shooter's next match, not paid out now.
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

                return [
                    'id' => $r->id,
                    'name' => $r->shooterName(),
                    'is_member' => $r->member_id !== null,
                    'division' => $r->division,
                    'category' => $r->category,
                    'fee_cents' => $fee,
                    'paid' => $paid,
                    'is_cash' => $r->isCashPayment(),
                    'attended' => $attended,
                    'reference' => $r->paymentReference(),
                    'classification' => $this->classify($fee, $paid, $attended),
                ];
            })
            ->sortBy(fn (array $row) => mb_strtolower($row['name']))
            ->values();
    }

    private function classify(int $fee, bool $paid, bool $attended): string
    {
        if ($fee <= 0) {
            return self::FREE;
        }

        if (! $paid) {
            return self::AWAITING;
        }

        return $attended ? self::PAYOUT : self::CREDIT;
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
        $credit = $rows->where('classification', self::CREDIT);
        $awaiting = $rows->where('classification', self::AWAITING);

        // Split the payable (paid + shot) fees by how they were paid. EFT sits
        // in the club account and is owed to the director; cash was handed to
        // the director on the day, so it isn't part of what the club owes.
        $eftPayout = $payout->where('is_cash', false);
        $cashPayout = $payout->where('is_cash', true);

        $payoutBaseCents = (int) $payout->sum('fee_cents');
        $eftBaseCents = (int) $eftPayout->sum('fee_cents');
        $cashBaseCents = (int) $cashPayout->sum('fee_cents');
        $payoutCount = $payout->count();

        // The club's per-head levy applies to every paying shooter who shot,
        // regardless of how they paid. It's recovered from the EFT pot the
        // club is holding, so the director payout comes off the EFT base.
        $levyTotalCents = $this->levyCents * $payoutCount;
        $directorPayoutCents = max(0, $eftBaseCents - $levyTotalCents);

        return [
            'entries_total' => $rows->count(),
            'attended_count' => $rows->where('attended', true)->count(),
            'payout_count' => $payoutCount,
            'cash_count' => $cashPayout->count(),
            'credit_count' => $credit->count(),
            'awaiting_count' => $awaiting->count(),
            'free_count' => $rows->where('classification', self::FREE)->count(),

            'gross_collected_cents' => (int) $rows->where('paid', true)->sum('fee_cents'),
            'payout_base_cents' => $payoutBaseCents,
            'eft_base_cents' => $eftBaseCents,
            'cash_base_cents' => $cashBaseCents,
            'credit_cents' => (int) $credit->sum('fee_cents'),
            'outstanding_cents' => (int) $awaiting->sum('fee_cents'),

            'levy_cents' => $this->levyCents,
            'levy_total_cents' => $levyTotalCents,
            'director_payout_cents' => $directorPayoutCents,
        ];
    }
}

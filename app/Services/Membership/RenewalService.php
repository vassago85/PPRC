<?php

namespace App\Services\Membership;

use App\Enums\MembershipStatus;
use App\Enums\RenewalSource;
use App\Events\RenewalCreated;
use App\Models\Member;
use App\Models\Membership;
use App\Models\MembershipType;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class RenewalService
{
    public function __construct(
        protected MembershipIssuer $issuer,
        protected MembershipTypeService $typeService,
    ) {}

    /**
     * Renew a member into the given type, stacking expiry when the previous
     * membership is still within the renewal window.
     *
     * WP SSMM rule: if the member's current period_end is in the future (or
     * within `renewal_window_days` of $asOf), the new period starts the day
     * after the previous period_end so the member doesn't lose paid time.
     * Otherwise the new period starts from $asOf.
     */
    public function renew(Member $member, MembershipType $type, ?Carbon $asOf = null, ?RenewalSource $source = null): Membership
    {
        $asOf ??= Carbon::now();
        $windowDays = (int) config('membership.renewal_window_days', 60);

        $previous = $member->memberships()
            ->whereIn('status', [
                MembershipStatus::Active->value,
                MembershipStatus::Expired->value,
            ])
            ->orderByDesc('period_end')
            ->first();

        $start = $this->resolveStartDate($previous, $asOf, $windowDays);

        return DB::transaction(function () use ($member, $type, $start, $source) {
            $membership = $this->issuer->issue($member, $type, $start, $source);

            if ($type->allows_sub_members && $membership->status === MembershipStatus::Active) {
                $this->issuer->autoRenewLinkedFreeSubMembers(
                    $member,
                    Carbon::parse($membership->period_start),
                    $membership->period_end ? Carbon::parse($membership->period_end) : Carbon::parse($membership->period_start)->addMonths($type->duration_months)->subDay(),
                );
            }

            RenewalCreated::dispatch($member, $membership);

            return $membership;
        });
    }

    /**
     * Determine the new period start date, implementing renewal-window stacking.
     */
    protected function resolveStartDate(?Membership $previous, Carbon $asOf, int $windowDays): Carbon
    {
        if (! $previous || ! $previous->period_end) {
            return $asOf;
        }

        $previousEnd = Carbon::parse($previous->period_end);

        // Previous expiry is in the future, or within the window of today: stack.
        $windowBoundary = $asOf->copy()->subDays($windowDays);
        if ($previousEnd->gte($windowBoundary)) {
            return $previousEnd->copy()->addDay();
        }

        return $asOf;
    }
}

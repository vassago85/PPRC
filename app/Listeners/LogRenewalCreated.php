<?php

namespace App\Listeners;

use App\Events\RenewalCreated;
use Illuminate\Support\Facades\Log;

class LogRenewalCreated
{
    public function handle(RenewalCreated $event): void
    {
        $member = $event->member;
        $membership = $event->membership;

        Log::channel('single')->info('Membership renewal created', [
            'member_id' => $member->id,
            'member_name' => $member->fullName(),
            'membership_number' => $member->membership_number,
            'membership_id' => $membership->id,
            'type' => $membership->membership_type_name_snapshot,
            'status' => $membership->status->value,
            'period_start' => $membership->period_start?->toDateString(),
            'period_end' => $membership->period_end?->toDateString(),
        ]);
    }
}

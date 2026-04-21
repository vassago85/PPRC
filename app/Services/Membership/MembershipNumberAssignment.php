<?php

namespace App\Services\Membership;

use App\Enums\MembershipStatus;
use App\Models\Membership;

class MembershipNumberAssignment
{
    public function __construct(
        protected MembershipNumberAllocator $allocator,
    ) {}

    /**
     * When a membership is active, assign a club number once if the type allows
     * and the member does not already have one.
     */
    public function syncForActiveMembership(Membership $membership): void
    {
        if ($membership->status !== MembershipStatus::Active) {
            return;
        }

        $membership->loadMissing(['member', 'membershipType']);

        $member = $membership->member;
        $type = $membership->membershipType;

        if (! $member || ! $type) {
            return;
        }

        if (! $type->assign_membership_number_on_approval) {
            return;
        }

        if (filled($member->membership_number)) {
            return;
        }

        $this->allocator->assignNextTo($member);
    }
}

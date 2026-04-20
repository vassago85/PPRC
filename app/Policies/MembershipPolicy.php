<?php

namespace App\Policies;

use App\Models\Membership;
use App\Models\User;

class MembershipPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('memberships.manage');
    }

    public function view(User $user, Membership $membership): bool
    {
        if ($user->can('memberships.manage')) {
            return true;
        }

        return $membership->member->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->can('memberships.manage') || $user->member !== null;
    }

    public function renew(User $user, Membership $membership): bool
    {
        if ($user->can('memberships.renew')) {
            return true;
        }

        return $membership->member->user_id === $user->id;
    }

    public function approve(User $user): bool
    {
        return $user->can('memberships.manage');
    }

    public function recordPayment(User $user): bool
    {
        return $user->can('memberships.payment.record') || $user->can('payments.eft.confirm');
    }
}

<?php

namespace App\Policies;

use App\Models\Member;
use App\Models\User;

class MemberPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('members.view');
    }

    public function view(User $user, Member $member): bool
    {
        return $user->can('members.view') || $user->id === $member->user_id;
    }

    public function update(User $user, Member $member): bool
    {
        if ($user->can('members.update')) {
            return true;
        }

        return $user->id === $member->user_id;
    }

    public function updateNotes(User $user): bool
    {
        return $user->can('members.notes');
    }

    public function import(User $user): bool
    {
        return $user->can('members.import');
    }

    public function delete(User $user, Member $member): bool
    {
        return $user->can('members.update') && $user->id !== $member->user_id;
    }
}

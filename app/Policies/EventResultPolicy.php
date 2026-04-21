<?php

namespace App\Policies;

use App\Models\EventResult;
use App\Models\User;

class EventResultPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('results.view');
    }

    public function view(User $user, EventResult $result): bool
    {
        return $user->can('results.view');
    }

    public function create(User $user): bool
    {
        return $user->can('results.manage');
    }

    public function update(User $user, EventResult $result): bool
    {
        return $user->can('results.manage');
    }

    public function delete(User $user, EventResult $result): bool
    {
        return $user->can('results.manage');
    }

    public function publish(User $user, EventResult $result): bool
    {
        return $user->can('results.publish');
    }
}

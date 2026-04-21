<?php

namespace App\Policies;

use App\Models\Event;
use App\Models\User;

class EventPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('events.view');
    }

    public function view(User $user, Event $event): bool
    {
        return $user->can('events.view');
    }

    public function create(User $user): bool
    {
        return $user->can('events.manage');
    }

    public function update(User $user, Event $event): bool
    {
        if (! $user->can('events.manage')) {
            return false;
        }

        // A plain match_director who does NOT have the wildcard events.manage
        // will still pass the check above if they hold events.manage. If we
        // ever introduce an "assigned event director only" rule this is where
        // it lands — for now any holder of events.manage can edit any event.
        return true;
    }

    public function delete(User $user, Event $event): bool
    {
        return $user->can('events.manage');
    }

    public function publish(User $user, Event $event): bool
    {
        return $user->can('events.publish');
    }

    public function manageRegistrations(User $user, Event $event): bool
    {
        return $user->can('events.registrations.manage');
    }

    public function manageAttendance(User $user, Event $event): bool
    {
        return $user->can('events.attendance.manage');
    }
}

<?php

namespace App\Filament\Admin\Support;

use App\Enums\MembershipStatus;
use App\Models\Membership;
use Filament\Notifications\Notification;

/**
 * Setting a membership to Active while its period has already ended looks like
 * it worked, then members:check-expiry silently flips it back to Expired
 * overnight. Rather than leave the admin to discover that the next morning, say
 * so at the moment they save.
 */
class LapsedActivationWarning
{
    public static function notify(Membership $membership): void
    {
        if (! static::applies($membership)) {
            return;
        }

        Notification::make()
            ->warning()
            ->title('This membership has already lapsed')
            ->body('The period ended on '.$membership->period_end->format('d M Y').
                ', so the nightly expiry check will set it back to Expired. '.
                'Move the period end into the future to keep it active.')
            ->persistent()
            ->send();
    }

    public static function applies(Membership $membership): bool
    {
        return $membership->status === MembershipStatus::Active && $membership->isLapsed();
    }
}

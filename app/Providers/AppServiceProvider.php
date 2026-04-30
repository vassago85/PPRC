<?php

namespace App\Providers;

use App\Listeners\LogSentEmail;
use App\Models\User;
use Illuminate\Auth\Events\Login;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Capture every outbound email into email_logs for audit + idempotency
        // (used by the welcome-invite sender to skip already-contacted members).
        // The listener itself is idempotent on message_id, so even if the mail
        // stack fires MessageSent twice for the same delivery (some Symfony
        // transports do), only one email_logs row is written.
        Event::listen(MessageSent::class, LogSentEmail::class);

        // CSV / SSMM imports: confirming access with the issued password is enough;
        // no PIN flow for those accounts.
        Event::listen(Login::class, function (Login $event): void {
            $user = $event->user;
            if (! $user instanceof User) {
                return;
            }
            if ($user->created_via_import && ! $user->hasVerifiedEmail()) {
                $user->markEmailAsVerified();
            }
        });
    }
}

<?php

namespace App\Providers;

use App\Events\RenewalCreated;
use App\Listeners\LogRenewalCreated;
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
        Event::listen(MessageSent::class, LogSentEmail::class);
        Event::listen(RenewalCreated::class, LogRenewalCreated::class);

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

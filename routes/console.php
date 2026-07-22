<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('memberships:age-sub-members')->dailyAt('02:00');
Schedule::command('members:check-expiry')->dailyAt('03:00');

// Renewal reminders go out shortly after the daily expiry check so anyone
// flipped to "expired" overnight gets their lapsed reminder the same day.
// Throttled per-member (14 days) inside the command; safe to run daily.
Schedule::command('members:send-renewal-reminders --sleep=2')
    ->dailyAt('04:00')
    ->withoutOverlapping();

// Nudge incomplete signups to finish, then archive the ones that never do.
// Runs weekly (Mondays) — the nudge-then-grace cadence doesn't need daily
// churn, and it keeps the "Members to onboard" queue from filling with
// long-abandoned registrations.
Schedule::command('members:cleanup-stale-signups --sleep=2')
    ->weeklyOn(1, '05:00')
    ->withoutOverlapping();

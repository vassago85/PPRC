<?php

use App\Enums\MemberStatus;
use App\Mail\FinishSignupReminderMail;
use App\Models\Member;
use App\Models\Membership;
use App\Models\MembershipType;
use App\Models\User;
use App\Services\Membership\MemberService;
use App\Services\Membership\MembershipIssuer;
use App\Services\Membership\StaleSignupProcessor;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    Config::set('membership.stale_signup_months', 6);
    Config::set('membership.stale_signup_grace_days', 14);
    Mail::fake();
});

/** Create a member of a given status whose account is $monthsAgo months old. */
function staleMember(string $status, int $monthsAgo, array $attrs = []): Member
{
    $user = User::factory()->create(['email_verified_at' => now()->subMonths($monthsAgo)]);

    $member = Member::factory()->create(array_merge([
        'user_id' => $user->id,
        'status' => $status,
    ], $attrs));

    // Back-date creation directly in the DB so Eloquent's timestamp handling
    // doesn't stamp it back to "now".
    Member::withTrashed()->whereKey($member->id)->update([
        'created_at' => now()->subMonths($monthsAgo),
    ]);

    return $member->fresh();
}

function runCleanup(bool $dryRun = false): array
{
    return app(StaleSignupProcessor::class)->process(dryRun: $dryRun);
}

it('nudges a stale unverified signup and stamps the reminder, without archiving', function () {
    $member = staleMember(MemberStatus::Unverified->value, 7);

    $stats = runCleanup();

    expect($stats['nudged'])->toBe(1);
    expect($stats['archived'])->toBe(0);
    expect($member->fresh()->status)->toBe(MemberStatus::Unverified);
    expect($member->fresh()->signup_reminder_sent_at)->not->toBeNull();

    Mail::assertSent(FinishSignupReminderMail::class, fn ($m) => $m->variant === 'verify');
});

it('nudges a verified member who never chose a membership', function () {
    $member = staleMember(MemberStatus::Pending->value, 7); // no membership rows

    $stats = runCleanup();

    expect($stats['nudged'])->toBe(1);
    Mail::assertSent(FinishSignupReminderMail::class, fn ($m) => $m->variant === 'choose');
});

it('archives a stale signup that was already nudged and ignored past the grace window', function () {
    $member = staleMember(MemberStatus::Unverified->value, 7);
    $member->forceFill(['signup_reminder_sent_at' => now()->subDays(20)])->saveQuietly();

    $stats = runCleanup();

    expect($stats['archived'])->toBe(1);
    expect($stats['nudged'])->toBe(0);
    expect($member->fresh()->status)->toBe(MemberStatus::Abandoned);
    Mail::assertNothingSent();
});

it('waits (does not archive) while still inside the grace window', function () {
    $member = staleMember(MemberStatus::Pending->value, 7);
    $member->forceFill(['signup_reminder_sent_at' => now()->subDays(3)])->saveQuietly();

    $stats = runCleanup();

    expect($stats['skipped'])->toBe(1);
    expect($stats['archived'])->toBe(0);
    expect($member->fresh()->status)->toBe(MemberStatus::Pending);
});

it('leaves fresh signups alone', function () {
    $member = staleMember(MemberStatus::Pending->value, 1); // only a month old

    $stats = runCleanup();

    expect($stats['candidates'])->toBe(0);
    expect($member->fresh()->status)->toBe(MemberStatus::Pending);
    Mail::assertNothingSent();
});

it('never touches a pending member who has already started an application', function () {
    $member = staleMember(MemberStatus::Pending->value, 8);
    Membership::factory()->create(['member_id' => $member->id]);

    $stats = runCleanup();

    expect($stats['candidates'])->toBe(0);
    expect($member->fresh()->status)->toBe(MemberStatus::Pending);
});

it('never touches active, expired or suspended members', function () {
    $active = staleMember(MemberStatus::Active->value, 12);
    $expired = staleMember(MemberStatus::Expired->value, 12);
    $suspended = staleMember(MemberStatus::Suspended->value, 12);

    $stats = runCleanup();

    expect($stats['candidates'])->toBe(0);
    expect($active->fresh()->status)->toBe(MemberStatus::Active);
    expect($expired->fresh()->status)->toBe(MemberStatus::Expired);
    expect($suspended->fresh()->status)->toBe(MemberStatus::Suspended);
});

it('changes nothing on a dry run', function () {
    $member = staleMember(MemberStatus::Unverified->value, 7);

    $stats = runCleanup(dryRun: true);

    expect($stats['nudged'])->toBe(1);
    expect($member->fresh()->status)->toBe(MemberStatus::Unverified);
    expect($member->fresh()->signup_reminder_sent_at)->toBeNull();
    Mail::assertNothingSent();
});

it('revives an abandoned member back to pending when they finally verify', function () {
    $member = staleMember(MemberStatus::Abandoned->value, 8);

    app(MemberService::class)->markVerified($member);

    expect($member->fresh()->status)->toBe(MemberStatus::Pending);
});

it('revives an abandoned member back to pending when they start an application', function () {
    $this->seed(\Database\Seeders\MembershipTypesSeeder::class);
    $type = MembershipType::where('slug', 'full-member')->first();

    $member = staleMember(MemberStatus::Abandoned->value, 8);

    app(MembershipIssuer::class)->issue($member, $type);

    expect($member->fresh()->status)->toBe(MemberStatus::Pending);
});

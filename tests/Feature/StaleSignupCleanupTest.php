<?php

use App\Enums\MemberLifecycle;
use App\Enums\MembershipStatus;
use App\Enums\MemberStanding;
use App\Mail\FinishSignupReminderMail;
use App\Models\Member;
use App\Models\Membership;
use App\Models\MembershipType;
use App\Models\User;
use App\Services\Membership\MemberService;
use App\Services\Membership\MembershipIssuer;
use App\Services\Membership\StaleSignupProcessor;
use Database\Seeders\MembershipTypesSeeder;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    Config::set('membership.stale_signup_months', 6);
    Config::set('membership.stale_signup_grace_days', 14);
    Config::set('membership.stale_unpaid_signup_days', 30);
    Mail::fake();
});

/**
 * Back-date a membership row so the "unpaid for N days" clock, which runs from
 * when the payment was requested, has something to bite on. Written straight to
 * the DB so Eloquent doesn't stamp created_at back to now.
 */
function ageMembership(Membership $membership, int $days): void
{
    Membership::withTrashed()->whereKey($membership->id)->update([
        'created_at' => now()->subDays($days),
    ]);
}

/**
 * A member whose account is $monthsAgo months old.
 *
 * `$verified` decides which stale cohort they land in: an unconfirmed email
 * makes them "awaiting email", a confirmed one with no membership row makes them
 * "awaiting choice". That distinction is now read off the user account rather
 * than stored on the member.
 */
function staleMember(int $monthsAgo, bool $verified = true, array $attrs = []): Member
{
    $user = User::factory()->create([
        'email_verified_at' => $verified ? now()->subMonths($monthsAgo) : null,
    ]);

    $member = Member::factory()->create(array_merge([
        'user_id' => $user->id,
        'lifecycle' => MemberLifecycle::Pending,
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
    $member = staleMember(7, verified: false);

    $stats = runCleanup();

    expect($stats['nudged'])->toBe(1);
    expect($stats['archived'])->toBe(0);
    expect($member->fresh()->standing())->toBe(MemberStanding::AwaitingEmail);
    expect($member->fresh()->signup_reminder_sent_at)->not->toBeNull();

    Mail::assertSent(FinishSignupReminderMail::class, fn ($m) => $m->variant === 'verify');
});

it('nudges a verified member who never chose a membership', function () {
    $member = staleMember(7); // verified, no membership rows

    $stats = runCleanup();

    expect($stats['nudged'])->toBe(1);
    expect($member->fresh()->standing())->toBe(MemberStanding::AwaitingChoice);
    Mail::assertSent(FinishSignupReminderMail::class, fn ($m) => $m->variant === 'choose');
});

it('archives a stale signup that was already nudged and ignored past the grace window', function () {
    $member = staleMember(7, verified: false);
    $member->forceFill(['signup_reminder_sent_at' => now()->subDays(20)])->saveQuietly();

    $stats = runCleanup();

    expect($stats['archived'])->toBe(1);
    expect($stats['nudged'])->toBe(0);
    Mail::assertNothingSent();

    $member = $member->fresh();
    expect($member->standing())->toBe(MemberStanding::Abandoned);
    // Archiving must not move them out of Pending — it only stops the chasing.
    expect($member->lifecycle)->toBe(MemberLifecycle::Pending);
});

it('drops an archived signup out of the onboarding queue but keeps it findable', function () {
    $member = staleMember(7, verified: false);
    $member->forceFill(['signup_reminder_sent_at' => now()->subDays(20)])->saveQuietly();

    runCleanup();

    expect(Member::query()->pending()->whereKey($member->id)->exists())->toBeFalse();
    expect(Member::query()->abandoned()->whereKey($member->id)->exists())->toBeTrue();
});

it('waits (does not archive) while still inside the grace window', function () {
    $member = staleMember(7);
    $member->forceFill(['signup_reminder_sent_at' => now()->subDays(3)])->saveQuietly();

    $stats = runCleanup();

    expect($stats['skipped'])->toBe(1);
    expect($stats['archived'])->toBe(0);
    expect($member->fresh()->isAbandoned())->toBeFalse();
});

it('leaves fresh signups alone', function () {
    $member = staleMember(1); // only a month old

    $stats = runCleanup();

    expect($stats['candidates'])->toBe(0);
    expect($member->fresh()->isAbandoned())->toBeFalse();
    Mail::assertNothingSent();
});

it('leaves a recently chosen but unpaid application alone (inside the 30-day clock)', function () {
    $member = staleMember(8);
    Membership::factory()->create([
        'member_id' => $member->id,
        'status' => MembershipStatus::PendingPayment,
    ]); // created now → payment is only just outstanding

    $stats = runCleanup();

    expect($stats['candidates'])->toBe(0);
    expect($member->fresh()->standing())->toBe(MemberStanding::AwaitingPayment);
});

it('nudges a member who chose a membership but has not paid for over 30 days', function () {
    $member = staleMember(8);
    $membership = Membership::factory()->create([
        'member_id' => $member->id,
        'status' => MembershipStatus::PendingPayment,
    ]);
    ageMembership($membership, days: 45);

    $stats = runCleanup();

    expect($stats['nudged'])->toBe(1)
        ->and($stats['archived'])->toBe(0)
        ->and($member->fresh()->standing())->toBe(MemberStanding::AwaitingPayment);

    Mail::assertSent(FinishSignupReminderMail::class, fn ($m) => $m->variant === 'pay');
});

it('archives an unpaid application that was nudged and still not paid past the grace window', function () {
    $member = staleMember(8);
    $membership = Membership::factory()->create([
        'member_id' => $member->id,
        'status' => MembershipStatus::PendingPayment,
    ]);
    ageMembership($membership, days: 45);
    $member->forceFill(['signup_reminder_sent_at' => now()->subDays(20)])->saveQuietly();

    $stats = runCleanup();

    expect($stats['archived'])->toBe(1)
        ->and($stats['nudged'])->toBe(0)
        ->and($member->fresh()->standing())->toBe(MemberStanding::Abandoned)
        ->and($member->fresh()->lifecycle)->toBe(MemberLifecycle::Pending);

    Mail::assertNothingSent();
});

it('gives a member who just chose a membership a fresh clock, not the spent choose-nudge', function () {
    $this->seed(MembershipTypesSeeder::class);
    $type = MembershipType::where('slug', 'full-member')->first();

    // Nudged weeks ago as "choose", never acted — then finally picks a type.
    $member = staleMember(8);
    $member->forceFill(['signup_reminder_sent_at' => now()->subDays(20)])->saveQuietly();

    app(MembershipIssuer::class)->issue($member, $type);

    // The choose-nudge stamp is cleared, so they are not instantly archived;
    // the 30-day unpaid clock starts from the membership they just created.
    expect($member->fresh()->signup_reminder_sent_at)->toBeNull();

    $stats = runCleanup();

    expect($stats['archived'])->toBe(0)
        ->and($stats['candidates'])->toBe(0); // membership is brand new
});

it('never touches active, expired or suspended members', function () {
    $active = staleMember(12, attrs: ['lifecycle' => MemberLifecycle::Active]);
    $expired = staleMember(12, attrs: ['lifecycle' => MemberLifecycle::Expired]);
    $suspended = staleMember(12, attrs: [
        'lifecycle' => MemberLifecycle::Active,
        'suspended_at' => now(),
    ]);

    $stats = runCleanup();

    expect($stats['candidates'])->toBe(0);
    expect($active->fresh()->lifecycle)->toBe(MemberLifecycle::Active);
    expect($expired->fresh()->lifecycle)->toBe(MemberLifecycle::Expired);
    expect($suspended->fresh()->standing())->toBe(MemberStanding::Suspended);
});

it('changes nothing on a dry run', function () {
    $member = staleMember(7, verified: false);

    $stats = runCleanup(dryRun: true);

    expect($stats['nudged'])->toBe(1);
    expect($member->fresh()->isAbandoned())->toBeFalse();
    expect($member->fresh()->signup_reminder_sent_at)->toBeNull();
    Mail::assertNothingSent();
});

it('revives an abandoned member back into the queue when they finally verify', function () {
    $member = staleMember(8, verified: false, attrs: ['abandoned_at' => now()->subDay()]);

    app(MemberService::class)->markVerified($member);

    $member = $member->fresh();
    expect($member->isAbandoned())->toBeFalse();
    expect(Member::query()->pending()->whereKey($member->id)->exists())->toBeTrue();
});

it('revives an abandoned member back into the queue when they start an application', function () {
    $this->seed(MembershipTypesSeeder::class);
    $type = MembershipType::where('slug', 'full-member')->first();

    $member = staleMember(8, attrs: ['abandoned_at' => now()->subDay()]);

    app(MembershipIssuer::class)->issue($member, $type);

    $member = $member->fresh();
    expect($member->isAbandoned())->toBeFalse();
    expect($member->standing())->toBe(MemberStanding::AwaitingPayment);
});

<?php

use App\Enums\MemberLifecycle;
use App\Enums\MembershipStatus;
use App\Enums\MemberStanding;
use App\Enums\MemberStatus;
use App\Models\Member;
use App\Models\Membership;
use App\Models\User;
use App\Services\Membership\MemberService;
use Illuminate\Support\Facades\Config;

/**
 * The canonical lifecycle: four stored states, with suspension and abandonment
 * as flags over the top and the rest derived from facts we already hold.
 */

// ---------------------------------------------------------------------------
// Derived standing
// ---------------------------------------------------------------------------

it('tells a pending member apart by what they are actually waiting on', function () {
    $awaitingEmail = Member::factory()->awaitingEmail()->create();

    $awaitingChoice = Member::factory()->create();

    $awaitingPayment = Member::factory()->create();
    Membership::factory()->create([
        'member_id' => $awaitingPayment->id,
        'status' => MembershipStatus::PendingPayment,
    ]);

    expect($awaitingEmail->standing())->toBe(MemberStanding::AwaitingEmail)
        ->and($awaitingChoice->standing())->toBe(MemberStanding::AwaitingChoice)
        ->and($awaitingPayment->fresh()->standing())->toBe(MemberStanding::AwaitingPayment);

    // All three are one lifecycle state; only the display splits them.
    foreach ([$awaitingEmail, $awaitingChoice, $awaitingPayment] as $member) {
        expect($member->fresh()->lifecycle)->toBe(MemberLifecycle::Pending);
    }
});

it('shows a suspension over the lifecycle without replacing it', function () {
    $member = Member::factory()->active()->suspended()->create();

    expect($member->standing())->toBe(MemberStanding::Suspended)
        ->and($member->lifecycle)->toBe(MemberLifecycle::Active)
        ->and($member->isActiveMember())->toBeFalse();
});

it('reveals the underlying lifecycle again when a suspension is lifted', function () {
    $member = Member::factory()->active()->suspended()->create();

    $member->update(['suspended_at' => null]);

    expect($member->fresh()->standing())->toBe(MemberStanding::Active)
        ->and($member->fresh()->isActiveMember())->toBeTrue();
});

it('treats resignation as terminal, ahead of any suspension', function () {
    $member = Member::factory()->resigned()->suspended()->create();

    expect($member->standing())->toBe(MemberStanding::Resigned);
});

it('derives long lapsed from the expiry date rather than storing it', function () {
    Config::set('membership.long_lapsed_months', 6);

    $recent = Member::factory()->expired(now()->subMonth()->toDateString())->create();
    $ancient = Member::factory()->expired(now()->subYear()->toDateString())->create();

    expect($recent->standing())->toBe(MemberStanding::Expired)
        ->and($ancient->standing())->toBe(MemberStanding::LongLapsed)
        // Both are the same stored lifecycle; only how long ago differs.
        ->and($recent->lifecycle)->toBe(MemberLifecycle::Expired)
        ->and($ancient->lifecycle)->toBe(MemberLifecycle::Expired);
});

// ---------------------------------------------------------------------------
// Lifecycle buckets
// ---------------------------------------------------------------------------

it('puts every member in exactly one of the headline buckets', function () {
    Member::factory()->count(3)->active()->create();
    Member::factory()->count(2)->create();                  // pending
    Member::factory()->count(4)->abandoned()->create();
    Member::factory()->expired(now()->subMonth()->toDateString())->create();
    Member::factory()->resigned()->create();
    Member::factory()->active()->suspended()->create();

    expect(Member::query()->active()->count())->toBe(3)
        ->and(Member::query()->pending()->count())->toBe(2)
        ->and(Member::query()->abandoned()->count())->toBe(4)
        ->and(Member::query()->expired()->count())->toBe(1)
        ->and(Member::query()->resigned()->count())->toBe(1)
        ->and(Member::query()->suspended()->count())->toBe(1);

    // The buckets partition the member base: 3 + 2 + 4 + 1 + 1 + 1 = 12.
    expect(Member::count())->toBe(12);
});

it('keeps suspended members out of the active bucket so they cannot read as paid up', function () {
    Member::factory()->count(2)->active()->create();
    Member::factory()->active()->suspended()->create();

    expect(Member::query()->active()->count())->toBe(2);
});

it('never lists one member under two tabs at once', function () {
    // A suspension outranks everything else on display, so a member who is both
    // suspended and an abandoned signup belongs to the Suspended tab only.
    Member::factory()->abandoned()->suspended()->create();

    expect(Member::query()->suspended()->count())->toBe(1)
        ->and(Member::query()->abandoned()->count())->toBe(0)
        ->and(Member::query()->pending()->count())->toBe(0);
});

it('splits the pending bucket into what each member is waiting on', function () {
    Member::factory()->count(2)->awaitingEmail()->create();
    Member::factory()->count(3)->create();
    $withApplication = Member::factory()->create();
    Membership::factory()->create([
        'member_id' => $withApplication->id,
        'status' => MembershipStatus::PendingPayment,
    ]);

    expect(Member::query()->awaitingEmail()->count())->toBe(2)
        ->and(Member::query()->awaitingChoice()->count())->toBe(3)
        ->and(Member::query()->awaitingPayment()->count())->toBe(1)
        // The three sub-buckets add up to the pending bucket exactly.
        ->and(Member::query()->pending()->count())->toBe(6);
});

it('treats the onboarding queue as pending members who have confirmed their email', function () {
    Member::factory()->count(4)->awaitingEmail()->create();
    Member::factory()->count(3)->create();

    // The 4 who never confirmed cannot be moved along by the club, so onboarding
    // is only the 3 who did — pending minus awaiting-email, and the two together
    // reconcile back to the whole pending bucket.
    expect(Member::query()->needsOnboarding()->count())->toBe(3)
        ->and(Member::query()->awaitingEmail()->count())->toBe(4)
        ->and(Member::query()->needsOnboarding()->count() + Member::query()->awaitingEmail()->count())
        ->toBe(Member::query()->pending()->count());
});

it('only chases renewals nobody has started yet', function () {
    Config::set('membership.renewal_due_days', 30);

    $due = Member::factory()->active(now()->addDays(10)->toDateString())->create();
    $alreadyRenewing = Member::factory()->active(now()->addDays(10)->toDateString())->create();
    Membership::factory()->create([
        'member_id' => $alreadyRenewing->id,
        'status' => MembershipStatus::PendingPayment,
    ]);
    Member::factory()->active(now()->addMonths(6)->toDateString())->create();

    $ids = Member::query()->renewalDue()->pluck('id');

    expect($ids)->toHaveCount(1)->and($ids->first())->toBe($due->id);
});

it('separates the winnable-back list from the long lapsed', function () {
    Config::set('membership.recently_lapsed_days', 60);
    Config::set('membership.long_lapsed_months', 6);

    $recent = Member::factory()->expired(now()->subDays(10)->toDateString())->create();
    $ancient = Member::factory()->expired(now()->subYear()->toDateString())->create();

    expect(Member::query()->recentlyLapsed()->pluck('id')->all())->toBe([$recent->id])
        ->and(Member::query()->longLapsed()->pluck('id')->all())->toBe([$ancient->id]);
});

it('counts the same number for a bucket wherever it is asked', function () {
    Member::factory()->count(5)->active()->create();
    Member::factory()->count(3)->create();
    Member::factory()->count(2)->abandoned()->create();

    // The Members list tab, the dashboard card and the nav badge all call the
    // same scope, so there is one number rather than three that drift.
    $pending = Member::query()->pending()->count();

    expect($pending)->toBe(3)
        ->and(Member::query()->pending()->count())->toBe($pending)
        ->and(Member::query()->where('lifecycle', MemberLifecycle::Pending->value)->count())->toBe(5);
});

// ---------------------------------------------------------------------------
// Legacy column mirror
// ---------------------------------------------------------------------------

it('keeps the deprecated status column truthful for every lifecycle', function (
    Member $member,
    MemberStatus $expected,
) {
    expect($member->fresh()->status)->toBe($expected);
})->with([
    'active' => fn () => [Member::factory()->active()->create(), MemberStatus::Active],
    'resigned' => fn () => [Member::factory()->resigned()->create(), MemberStatus::Resigned],
    'suspended' => fn () => [Member::factory()->active()->suspended()->create(), MemberStatus::Suspended],
    'abandoned' => fn () => [Member::factory()->abandoned()->create(), MemberStatus::Abandoned],
    'unverified' => fn () => [Member::factory()->awaitingEmail()->create(), MemberStatus::Unverified],
    'pending' => fn () => [Member::factory()->create(), MemberStatus::Pending],
    'expired' => fn () => [
        Member::factory()->expired(now()->subMonth()->toDateString())->create(),
        MemberStatus::Expired,
    ],
    'inactive' => fn () => [
        Member::factory()->expired(now()->subYear()->toDateString())->create(),
        MemberStatus::Inactive,
    ],
]);

it('updates the legacy column when the lifecycle moves', function () {
    $member = Member::factory()->create();
    expect($member->status)->toBe(MemberStatus::Pending);

    $member->update(['lifecycle' => MemberLifecycle::Active]);

    expect($member->fresh()->status)->toBe(MemberStatus::Active);
});

// ---------------------------------------------------------------------------
// Legacy import vocabulary
// ---------------------------------------------------------------------------

it('maps the old eight statuses onto the new columns', function () {
    expect(MemberLifecycle::mapLegacy('active')['lifecycle'])->toBe('active')
        ->and(MemberLifecycle::mapLegacy('pending')['lifecycle'])->toBe('pending')
        ->and(MemberLifecycle::mapLegacy('unverified')['lifecycle'])->toBe('pending')
        ->and(MemberLifecycle::mapLegacy('expired')['lifecycle'])->toBe('expired')
        ->and(MemberLifecycle::mapLegacy('inactive')['lifecycle'])->toBe('expired')
        ->and(MemberLifecycle::mapLegacy('resigned')['lifecycle'])->toBe('resigned');
});

it('moves an abandoned signup to a flag while leaving it pending', function () {
    $mapped = MemberLifecycle::mapLegacy('abandoned');

    expect($mapped['lifecycle'])->toBe('pending')
        ->and($mapped['abandoned_at'])->not->toBeNull();
});

it('recovers the lifecycle under a legacy suspension from the expiry date', function () {
    $stillPaidUp = MemberLifecycle::mapLegacy('suspended', now()->addMonths(3)->toDateString());
    $alreadyLapsed = MemberLifecycle::mapLegacy('suspended', now()->subMonths(3)->toDateString());

    expect($stillPaidUp['lifecycle'])->toBe('active')
        ->and($alreadyLapsed['lifecycle'])->toBe('expired')
        ->and($stillPaidUp['suspended_at'])->not->toBeNull()
        ->and($alreadyLapsed['suspended_at'])->not->toBeNull();
});

// ---------------------------------------------------------------------------
// Transitions through the services
// ---------------------------------------------------------------------------

it('starts a new registration as pending awaiting their email', function () {
    $user = User::factory()->unverified()->create();

    $member = app(MemberService::class)->register($user);

    expect($member->lifecycle)->toBe(MemberLifecycle::Pending)
        ->and($member->standing())->toBe(MemberStanding::AwaitingEmail);
});

it('moves a member to awaiting choice once they confirm their email', function () {
    $user = User::factory()->unverified()->create();
    $member = app(MemberService::class)->register($user);

    $user->markEmailAsVerified();

    expect($member->fresh()->standing())->toBe(MemberStanding::AwaitingChoice);
});

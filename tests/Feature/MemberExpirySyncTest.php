<?php

use App\Enums\MemberLifecycle;
use App\Enums\MembershipStatus;
use App\Enums\MemberStanding;
use App\Models\Member;
use App\Models\Membership;

/**
 * A membership that was made active without going through MemberService —
 * legacy imported data, or a direct database edit. Written with the query
 * builder so no model events fire and the member is left behind, which is
 * exactly the broken state the command has to repair.
 *
 * @param  array<string, mixed>  $memberAttributes
 */
function memberStrandedBehindMembership(
    MemberLifecycle $lifecycle,
    ?Carbon\Carbon $periodEnd,
    array $memberAttributes = [],
): Member {
    $member = Member::factory()->create();
    $membership = Membership::factory()->create(['member_id' => $member->id]);

    Membership::query()->whereKey($membership->id)->update([
        'status' => MembershipStatus::Active->value,
        'period_end' => $periodEnd?->toDateString(),
    ]);

    Member::query()->whereKey($member->id)->update(array_merge([
        'lifecycle' => $lifecycle->value,
        'expiry_date' => now()->subMonths(2)->toDateString(),
    ], $memberAttributes));

    return $member->fresh();
}

it('repairs a member left expired despite holding a current membership', function () {
    $member = memberStrandedBehindMembership(MemberLifecycle::Expired, now()->addYear());

    $this->artisan('members:check-expiry')->assertSuccessful();

    $member->refresh();

    expect($member->lifecycle)->toBe(MemberLifecycle::Active)
        ->and($member->expiry_date->toDateString())->toBe(now()->addYear()->toDateString());
});

it('clears the expiry date when the membership never expires', function () {
    $member = memberStrandedBehindMembership(MemberLifecycle::Expired, null);

    $this->artisan('members:check-expiry')->assertSuccessful();

    $member->refresh();

    expect($member->lifecycle)->toBe(MemberLifecycle::Active)
        ->and($member->expiry_date)->toBeNull();
});

it('keeps a suspension in place while repairing the lifecycle underneath it', function () {
    $member = memberStrandedBehindMembership(
        MemberLifecycle::Expired,
        now()->addYear(),
        ['suspended_at' => now()->subMonth()],
    );

    $this->artisan('members:check-expiry')->assertSuccessful();

    $member->refresh();

    // The suspension is what a human sees, and it survives untouched...
    expect($member->standing())->toBe(MemberStanding::Suspended);
    // ...while the lifecycle underneath is corrected, so lifting the suspension
    // reveals the right answer instead of leaving an admin to guess.
    expect($member->lifecycle)->toBe(MemberLifecycle::Active);
});

it('does not grant member rates to a suspended member holding a current membership', function () {
    $member = memberStrandedBehindMembership(
        MemberLifecycle::Active,
        now()->addYear(),
        ['suspended_at' => now()],
    );

    expect($member->isActiveMember())->toBeFalse();
});

it('never revives a resigned member from an active membership row', function () {
    $member = memberStrandedBehindMembership(MemberLifecycle::Resigned, now()->addYear());

    $this->artisan('members:check-expiry')->assertSuccessful();

    expect($member->fresh()->lifecycle)->toBe(MemberLifecycle::Resigned);
});

it('still expires a member whose membership has genuinely lapsed', function () {
    $member = memberStrandedBehindMembership(MemberLifecycle::Active, now()->subMonth());

    $this->artisan('members:check-expiry')->assertSuccessful();

    expect($member->fresh()->lifecycle)->toBe(MemberLifecycle::Expired)
        ->and($member->memberships()->first()->status)->toBe(MembershipStatus::Expired);
});

it('reads a long-lapsed member off the expiry date rather than a stored status', function () {
    $member = memberStrandedBehindMembership(
        MemberLifecycle::Active,
        now()->subYear(),
        ['expiry_date' => now()->subYear()->toDateString()],
    );

    $this->artisan('members:check-expiry')->assertSuccessful();

    $member->refresh();

    expect($member->lifecycle)->toBe(MemberLifecycle::Expired)
        ->and($member->standing())->toBe(MemberStanding::LongLapsed)
        ->and(Member::query()->longLapsed()->whereKey($member->id)->exists())->toBeTrue()
        // Long lapsed is past the winnable-back window, so it must not show up
        // in the renewal chase list.
        ->and(Member::query()->recentlyLapsed()->whereKey($member->id)->exists())->toBeFalse();
});

it('lets a lifetime membership win over a dated one held at the same time', function () {
    $member = memberStrandedBehindMembership(MemberLifecycle::Expired, now()->addYear());

    $lifetime = Membership::factory()->create(['member_id' => $member->id]);
    Membership::query()->whereKey($lifetime->id)->update([
        'status' => MembershipStatus::Active->value,
        'period_end' => null,
    ]);

    $this->artisan('members:check-expiry')->assertSuccessful();

    expect($member->fresh()->expiry_date)->toBeNull();
});

it('changes nothing on a dry run', function () {
    $member = memberStrandedBehindMembership(MemberLifecycle::Expired, now()->addYear());

    $this->artisan('members:check-expiry', ['--dry-run' => true])->assertSuccessful();

    $member->refresh();

    expect($member->lifecycle)->toBe(MemberLifecycle::Expired)
        ->and($member->expiry_date->toDateString())->toBe(now()->subMonths(2)->toDateString());
});

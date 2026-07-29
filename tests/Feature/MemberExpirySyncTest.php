<?php

use App\Enums\MembershipStatus;
use App\Enums\MemberStatus;
use App\Models\Member;
use App\Models\Membership;

/**
 * A membership that was made active without going through MemberService —
 * legacy imported data, or a direct database edit. Written with the query
 * builder so no model events fire and the member is left behind, which is
 * exactly the broken state the command has to repair.
 */
function memberStrandedBehindMembership(MemberStatus $memberStatus, ?Carbon\Carbon $periodEnd): Member
{
    $member = Member::factory()->create();
    $membership = Membership::factory()->create(['member_id' => $member->id]);

    Membership::query()->whereKey($membership->id)->update([
        'status' => MembershipStatus::Active->value,
        'period_end' => $periodEnd?->toDateString(),
    ]);

    Member::query()->whereKey($member->id)->update([
        'status' => $memberStatus->value,
        'expiry_date' => now()->subMonths(2)->toDateString(),
    ]);

    return $member->fresh();
}

it('repairs a member left expired despite holding a current membership', function () {
    $member = memberStrandedBehindMembership(MemberStatus::Expired, now()->addYear());

    $this->artisan('members:check-expiry')->assertSuccessful();

    $member->refresh();

    expect($member->status)->toBe(MemberStatus::Active)
        ->and($member->expiry_date->toDateString())->toBe(now()->addYear()->toDateString());
});

it('clears the expiry date when the membership never expires', function () {
    $member = memberStrandedBehindMembership(MemberStatus::Expired, null);

    $this->artisan('members:check-expiry')->assertSuccessful();

    $member->refresh();

    expect($member->status)->toBe(MemberStatus::Active)
        ->and($member->expiry_date)->toBeNull();
});

it('leaves a suspended member suspended even with a current membership', function () {
    $member = memberStrandedBehindMembership(MemberStatus::Suspended, now()->addYear());

    $this->artisan('members:check-expiry')->assertSuccessful();

    expect($member->fresh()->status)->toBe(MemberStatus::Suspended);
});

it('still expires a member whose membership has genuinely lapsed', function () {
    $member = memberStrandedBehindMembership(MemberStatus::Active, now()->subMonth());

    $this->artisan('members:check-expiry')->assertSuccessful();

    expect($member->fresh()->status)->toBe(MemberStatus::Expired)
        ->and($member->memberships()->first()->status)->toBe(MembershipStatus::Expired);
});

it('lets a lifetime membership win over a dated one held at the same time', function () {
    $member = memberStrandedBehindMembership(MemberStatus::Expired, now()->addYear());

    $lifetime = Membership::factory()->create(['member_id' => $member->id]);
    Membership::query()->whereKey($lifetime->id)->update([
        'status' => MembershipStatus::Active->value,
        'period_end' => null,
    ]);

    $this->artisan('members:check-expiry')->assertSuccessful();

    expect($member->fresh()->expiry_date)->toBeNull();
});

it('changes nothing on a dry run', function () {
    $member = memberStrandedBehindMembership(MemberStatus::Expired, now()->addYear());

    $this->artisan('members:check-expiry', ['--dry-run' => true])->assertSuccessful();

    $member->refresh();

    expect($member->status)->toBe(MemberStatus::Expired)
        ->and($member->expiry_date->toDateString())->toBe(now()->subMonths(2)->toDateString());
});

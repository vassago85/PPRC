<?php

use App\Enums\MembershipStatus;
use App\Models\Member;
use App\Models\Membership;
use Illuminate\Support\Facades\Config;

beforeEach(function () {
    Config::set('membership.number_prefix', 'PPRC-');
    Config::set('membership.number_pad_length', 4);
});

/*
|--------------------------------------------------------------------------
| Member number display normalisation (items 1 & 4)
|--------------------------------------------------------------------------
*/

it('normalises inconsistent zero-padding for display without touching the stored value', function () {
    $member = Member::factory()->create(['membership_number' => 'PPRC-00181']);

    // Display is normalised to the standard 4-digit width...
    expect($member->formattedMembershipNumber())->toBe('PPRC-0181');
    // ...but the stored value is left exactly as imported.
    expect($member->fresh()->membership_number)->toBe('PPRC-00181');
});

it('keeps already-consistent numbers and pads numbers above 100 the same way', function () {
    expect(Member::factory()->create(['membership_number' => 'PPRC-0100'])->formattedMembershipNumber())
        ->toBe('PPRC-0100');
    expect(Member::factory()->create(['membership_number' => 'PPRC-181'])->formattedMembershipNumber())
        ->toBe('PPRC-0181');
    expect(Member::factory()->create(['membership_number' => 'PPRC-1234'])->formattedMembershipNumber())
        ->toBe('PPRC-1234');
});

it('leaves legacy/non-standard numbers untouched', function () {
    expect(Member::factory()->create(['membership_number' => 'PPRC-2019-0032'])->formattedMembershipNumber())
        ->toBe('PPRC-2019-0032');
    expect(Member::factory()->create(['membership_number' => 'LEGACY'])->formattedMembershipNumber())
        ->toBe('LEGACY');
});

it('returns null when no number has been assigned yet (pending payment)', function () {
    expect(Member::factory()->create(['membership_number' => null])->formattedMembershipNumber())
        ->toBeNull();
});

/*
|--------------------------------------------------------------------------
| Lifetime memberships (item 2)
|--------------------------------------------------------------------------
*/

it('treats a null period end as lifetime', function () {
    $m = Membership::factory()->create(['period_end' => null]);

    expect($m->isLifetime())->toBeTrue();
});

it('treats a far-future placeholder end date as lifetime', function () {
    $m = Membership::factory()->create([
        'membership_type_name_snapshot' => 'Standard Membership',
        'period_start' => now(),
        'period_end' => now()->addYears(100),
    ]);

    expect($m->isLifetime())->toBeTrue();
});

it('treats a life-member type as lifetime even with a stored end date', function () {
    $m = Membership::factory()->create([
        'membership_type_slug_snapshot' => 'life-member',
        'membership_type_name_snapshot' => 'Life Member',
        'period_end' => now()->addYears(100),
    ]);

    expect($m->isLifetime())->toBeTrue();
});

it('does not treat a normal annual membership as lifetime', function () {
    $m = Membership::factory()->create([
        'membership_type_name_snapshot' => 'Standard Membership',
        'period_start' => now(),
        'period_end' => now()->addYear(),
    ]);

    expect($m->isLifetime())->toBeFalse();
});

/*
|--------------------------------------------------------------------------
| Superseded (re-signup) memberships (item 3)
|--------------------------------------------------------------------------
*/

it('flags a cancelled membership superseded by an overlapping active one', function () {
    $member = Member::factory()->create();

    $cancelled = Membership::factory()->create([
        'member_id' => $member->id,
        'status' => MembershipStatus::Cancelled,
        'period_start' => now()->startOfYear(),
        'period_end' => now()->endOfYear(),
    ]);

    Membership::factory()->create([
        'member_id' => $member->id,
        'status' => MembershipStatus::Active,
        'period_start' => now()->startOfYear(),
        'period_end' => now()->endOfYear(),
    ]);

    expect($cancelled->fresh()->isSuperseded())->toBeTrue();
});

it('does not flag a standalone cancelled membership as superseded', function () {
    $member = Member::factory()->create();

    $cancelled = Membership::factory()->create([
        'member_id' => $member->id,
        'status' => MembershipStatus::Cancelled,
        'period_start' => now()->startOfYear(),
        'period_end' => now()->endOfYear(),
    ]);

    expect($cancelled->isSuperseded())->toBeFalse();
});

it('never flags a non-cancelled membership as superseded', function () {
    $member = Member::factory()->create();

    $active = Membership::factory()->create([
        'member_id' => $member->id,
        'status' => MembershipStatus::Active,
    ]);

    Membership::factory()->create([
        'member_id' => $member->id,
        'status' => MembershipStatus::Cancelled,
    ]);

    expect($active->isSuperseded())->toBeFalse();
});

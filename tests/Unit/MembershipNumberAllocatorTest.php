<?php

use App\Enums\MembershipStatus;
use App\Models\Member;
use App\Models\Membership;
use App\Models\MembershipType;
use App\Models\User;
use App\Services\Membership\MembershipNumberAllocator;
use Database\Seeders\MembershipTypesSeeder;
use Illuminate\Support\Facades\Config;

beforeEach(function () {
    $this->seed(MembershipTypesSeeder::class);
});

it('allocates starting number when none exist', function () {
    Config::set('membership.number_start', 100);
    Config::set('membership.number_pad_length', null);

    $user = User::factory()->create();
    $member = Member::factory()->create(['user_id' => $user->id, 'membership_number' => null]);

    app(MembershipNumberAllocator::class)->assignNextTo($member);

    expect($member->fresh()->membership_number)->toBe('100');
});

it('uses max plus one including soft-deleted members', function () {
    Config::set('membership.number_start', 1);
    Config::set('membership.number_pad_length', null);

    $u1 = User::factory()->create();
    $u2 = User::factory()->create();
    $u3 = User::factory()->create();

    $gone = Member::factory()->create(['user_id' => $u1->id, 'membership_number' => '7']);
    $gone->delete();

    Member::factory()->create(['user_id' => $u2->id, 'membership_number' => '12']);

    $new = Member::factory()->create(['user_id' => $u3->id, 'membership_number' => null]);
    app(MembershipNumberAllocator::class)->assignNextTo($new);

    expect($new->fresh()->membership_number)->toBe('13');
});

it('pads when configured', function () {
    Config::set('membership.number_start', 1);
    Config::set('membership.number_pad_length', 5);

    $user = User::factory()->create();
    $member = Member::factory()->create(['user_id' => $user->id, 'membership_number' => null]);

    app(MembershipNumberAllocator::class)->assignNextTo($member);

    expect($member->fresh()->membership_number)->toBe('00001');
});

it('assigns on membership activation when type allows', function () {
    Config::set('membership.number_start', 500);
    Config::set('membership.number_pad_length', null);

    $type = MembershipType::where('slug', 'full-member')->first();
    expect($type->assign_membership_number_on_approval)->toBeTrue();

    $user = User::factory()->create();
    $member = Member::factory()->create(['user_id' => $user->id, 'membership_number' => null]);

    Membership::create([
        'member_id' => $member->id,
        'membership_type_id' => $type->id,
        'period_start' => now()->subDay(),
        'period_end' => now()->addYear(),
        'status' => MembershipStatus::Active,
        'price_cents_snapshot' => $type->price_cents,
        'membership_type_slug_snapshot' => $type->slug,
        'membership_type_name_snapshot' => $type->name,
    ]);

    expect($member->fresh()->membership_number)->toBe('500');
});

it('does not overwrite an existing membership number', function () {
    Config::set('membership.number_start', 1);

    $type = MembershipType::where('slug', 'full-member')->first();
    $user = User::factory()->create();
    $member = Member::factory()->create(['user_id' => $user->id, 'membership_number' => '999']);

    Membership::create([
        'member_id' => $member->id,
        'membership_type_id' => $type->id,
        'period_start' => now()->subDay(),
        'period_end' => now()->addYear(),
        'status' => MembershipStatus::Active,
        'price_cents_snapshot' => $type->price_cents,
        'membership_type_slug_snapshot' => $type->slug,
        'membership_type_name_snapshot' => $type->name,
    ]);

    expect($member->fresh()->membership_number)->toBe('999');
});

it('skips assignment when type disables auto number', function () {
    $type = MembershipType::where('slug', 'full-member')->first();
    $type->update(['assign_membership_number_on_approval' => false]);

    $user = User::factory()->create();
    $member = Member::factory()->create(['user_id' => $user->id, 'membership_number' => null]);

    Membership::create([
        'member_id' => $member->id,
        'membership_type_id' => $type->id,
        'period_start' => now()->subDay(),
        'period_end' => now()->addYear(),
        'status' => MembershipStatus::Active,
        'price_cents_snapshot' => $type->price_cents,
        'membership_type_slug_snapshot' => $type->slug,
        'membership_type_name_snapshot' => $type->name,
    ]);

    expect($member->fresh()->membership_number)->toBeNull();
});

it('ignores non-numeric legacy numbers when computing max', function () {
    Config::set('membership.number_start', 1);

    $u1 = User::factory()->create();
    Member::factory()->create(['user_id' => $u1->id, 'membership_number' => 'LEGACY']);

    $u2 = User::factory()->create();
    $new = Member::factory()->create(['user_id' => $u2->id, 'membership_number' => null]);
    app(MembershipNumberAllocator::class)->assignNextTo($new);

    expect($new->fresh()->membership_number)->toBe('1');
});

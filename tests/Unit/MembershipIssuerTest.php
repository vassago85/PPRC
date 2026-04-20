<?php

use App\Enums\MembershipStatus;
use App\Models\Member;
use App\Models\Membership;
use App\Models\MembershipType;
use App\Models\User;
use App\Services\Membership\MembershipIssuer;
use Database\Seeders\MembershipTypesSeeder;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->seed(MembershipTypesSeeder::class);
    $this->issuer = app(MembershipIssuer::class);
});

function makeMember(array $attrs = []): Member
{
    $user = User::factory()->create();

    return Member::factory()->create(array_merge([
        'user_id' => $user->id,
        'first_name' => 'Test',
        'last_name' => 'Shooter',
    ], $attrs));
}

it('issues a free junior membership when the parent has an active membership', function () {
    $parent = makeMember();
    $full = MembershipType::where('slug', 'full-member')->first();
    Membership::create([
        'member_id' => $parent->id,
        'membership_type_id' => $full->id,
        'period_start' => now()->subMonth(),
        'period_end' => now()->addMonths(11),
        'status' => MembershipStatus::Active,
        'price_cents_snapshot' => $full->price_cents,
        'membership_type_slug_snapshot' => $full->slug,
        'membership_type_name_snapshot' => $full->name,
    ]);

    $junior = makeMember([
        'linked_adult_member_id' => $parent->id,
        'date_of_birth' => now()->subYears(12),
    ]);

    $juniorType = MembershipType::where('slug', 'junior')->first();

    $m = $this->issuer->issue($junior, $juniorType);

    expect($m->price_cents_snapshot)->toBe(0)
        ->and($m->status)->toBe(MembershipStatus::PendingApproval);
});

it('rejects a junior not linked to an adult member', function () {
    $orphan = makeMember(['date_of_birth' => now()->subYears(12)]);
    $juniorType = MembershipType::where('slug', 'junior')->first();

    $this->issuer->issue($orphan, $juniorType);
})->throws(ValidationException::class);

it('caps juniors per parent at max_per_parent (4)', function () {
    $parent = makeMember();
    $juniorType = MembershipType::where('slug', 'junior')->first();

    for ($i = 0; $i < 4; $i++) {
        $kid = makeMember([
            'linked_adult_member_id' => $parent->id,
            'date_of_birth' => now()->subYears(10 + $i),
        ]);
        $this->issuer->issue($kid, $juniorType);
    }

    $fifth = makeMember([
        'linked_adult_member_id' => $parent->id,
        'date_of_birth' => now()->subYears(9),
    ]);

    $this->issuer->issue($fifth, $juniorType);
})->throws(ValidationException::class);

it('keeps spouse priced even when linked to an active member', function () {
    $parent = makeMember();
    $spouse = makeMember(['linked_adult_member_id' => $parent->id]);
    $spouseType = MembershipType::where('slug', 'spouse')->first();

    $m = $this->issuer->issue($spouse, $spouseType);

    expect($m->price_cents_snapshot)->toBe($spouseType->price_cents);
});

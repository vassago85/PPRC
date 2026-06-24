<?php

use App\Enums\RenewalSource;
use App\Mail\MembershipApprovedMail;
use App\Models\Member;
use App\Models\Membership;

it('treats a membership with a renewal source as a renewal', function () {
    $member = Member::factory()->create();
    $membership = Membership::factory()->create([
        'member_id' => $member->id,
        'renewal_source' => RenewalSource::Reminder,
    ]);

    expect($membership->isRenewal())->toBeTrue();
});

it('treats a first-time membership as not a renewal', function () {
    $member = Member::factory()->create();
    $membership = Membership::factory()->create([
        'member_id' => $member->id,
        'renewal_source' => null,
    ]);

    expect($membership->isRenewal())->toBeFalse();
});

it('detects a renewal when an earlier active membership exists', function () {
    $member = Member::factory()->create();

    Membership::factory()->create([
        'member_id' => $member->id,
        'status' => 'expired',
        'created_at' => now()->subYear(),
    ]);

    $current = Membership::factory()->create([
        'member_id' => $member->id,
        'renewal_source' => null,
        'created_at' => now(),
    ]);

    expect($current->isRenewal())->toBeTrue();
});

it('uses the renewal subject line for renewals', function () {
    $member = Member::factory()->create();
    $membership = Membership::factory()->create([
        'member_id' => $member->id,
        'renewal_source' => RenewalSource::MemberInitiated,
    ]);

    $envelope = (new MembershipApprovedMail($member, $membership))->envelope();

    expect($envelope->subject)->toBe('Your PPRC membership has been renewed');
});

it('uses the approved subject line for first-time joins', function () {
    $member = Member::factory()->create();
    $membership = Membership::factory()->create([
        'member_id' => $member->id,
        'renewal_source' => null,
    ]);

    $envelope = (new MembershipApprovedMail($member, $membership))->envelope();

    expect($envelope->subject)->toBe('Your PPRC membership has been approved');
});

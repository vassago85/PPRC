<?php

use App\Models\Member;
use App\Models\Membership;
use App\Models\MembershipPayment;
use App\Models\User;

function makePayment(Member $member): MembershipPayment
{
    $membership = Membership::factory()->create(['member_id' => $member->id]);

    return MembershipPayment::create([
        'membership_id' => $membership->id,
        'provider' => 'manual_eft',
        'status' => 'pending',
        'amount_cents' => 40000,
        'currency' => 'ZAR',
        'reference' => 'PPRC-TEST-0001',
    ]);
}

it('shows the member full name', function () {
    $member = Member::factory()->create(['first_name' => 'Wynand', 'last_name' => 'Louw']);
    $payment = makePayment($member);

    expect($payment->payerName())->toBe('Wynand Louw');
});

it('still identifies a soft-deleted member', function () {
    $member = Member::factory()->create(['first_name' => 'Shan', 'last_name' => 'de Wit']);
    $payment = makePayment($member);

    $member->delete();
    $payment->refresh()->load('membership');

    expect($payment->payerName())->toBe('Shan de Wit (removed)');
});

it('falls back to the linked account name when the member has no name', function () {
    $user = User::factory()->create(['name' => 'Account Holder']);
    $member = Member::factory()->create([
        'user_id' => $user->id,
        'first_name' => '',
        'last_name' => '',
    ]);
    $payment = makePayment($member);

    expect($payment->payerName())->toBe('Account Holder');
});

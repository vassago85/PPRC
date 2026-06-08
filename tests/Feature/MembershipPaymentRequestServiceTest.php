<?php

use App\Enums\MembershipStatus;
use App\Enums\PaymentStatus;
use App\Mail\MembershipPaymentRequestMail;
use App\Models\Member;
use App\Models\Membership;
use App\Models\MembershipPayment;
use App\Models\MembershipType;
use App\Models\User;
use App\Services\Membership\MembershipPaymentRequestService;
use Database\Seeders\MembershipTypesSeeder;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->seed(MembershipTypesSeeder::class);
    $this->service = app(MembershipPaymentRequestService::class);
});

it('sends a payment request email with an existing pending payment', function () {
    Mail::fake();

    $user = User::factory()->create(['email' => 'payer@example.com']);
    $member = Member::factory()->create(['user_id' => $user->id]);
    $type = MembershipType::where('slug', 'full-member')->first();

    $membership = Membership::create([
        'member_id' => $member->id,
        'membership_type_id' => $type->id,
        'period_start' => now(),
        'period_end' => now()->addYear(),
        'status' => MembershipStatus::PendingPayment,
        'price_cents_snapshot' => $type->price_cents,
        'membership_type_slug_snapshot' => $type->slug,
        'membership_type_name_snapshot' => $type->name,
    ]);

    $payment = MembershipPayment::create([
        'membership_id' => $membership->id,
        'provider' => 'manual_eft',
        'status' => PaymentStatus::Pending,
        'amount_cents' => $type->price_cents,
        'currency' => 'ZAR',
        'reference' => 'PPRC-TEST-001',
    ]);

    $result = $this->service->send($membership);

    expect($result->id)->toBe($payment->id);

    Mail::assertSent(MembershipPaymentRequestMail::class, function (MembershipPaymentRequestMail $mail) use ($user, $payment) {
        return $mail->hasTo($user->email)
            && $mail->payment->reference === $payment->reference
            && $mail->isReminder === true;
    });
});

it('creates a pending payment when none exists before sending', function () {
    Mail::fake();

    $user = User::factory()->create();
    $member = Member::factory()->create(['user_id' => $user->id]);
    $type = MembershipType::where('slug', 'full-member')->first();

    $membership = Membership::create([
        'member_id' => $member->id,
        'membership_type_id' => $type->id,
        'period_start' => now(),
        'period_end' => now()->addYear(),
        'status' => MembershipStatus::PendingPayment,
        'price_cents_snapshot' => $type->price_cents,
        'membership_type_slug_snapshot' => $type->slug,
        'membership_type_name_snapshot' => $type->name,
    ]);

    expect($membership->payments()->count())->toBe(0);

    $payment = $this->service->send($membership);

    expect($payment->reference)->not->toBeEmpty()
        ->and($payment->status)->toBe(PaymentStatus::Pending)
        ->and($membership->payments()->count())->toBe(1);

    Mail::assertSent(MembershipPaymentRequestMail::class);
});

it('rejects payment requests for memberships not awaiting payment', function () {
    Mail::fake();

    $user = User::factory()->create();
    $member = Member::factory()->create(['user_id' => $user->id]);
    $type = MembershipType::where('slug', 'full-member')->first();

    $membership = Membership::create([
        'member_id' => $member->id,
        'membership_type_id' => $type->id,
        'period_start' => now(),
        'period_end' => now()->addYear(),
        'status' => MembershipStatus::Active,
        'price_cents_snapshot' => $type->price_cents,
        'membership_type_slug_snapshot' => $type->slug,
        'membership_type_name_snapshot' => $type->name,
    ]);

    $this->service->send($membership);
})->throws(ValidationException::class);

it('rejects payment requests when the member has no email', function () {
    Mail::fake();

    $user = User::factory()->create(['email' => '']);
    $member = Member::factory()->create(['user_id' => $user->id]);
    $type = MembershipType::where('slug', 'full-member')->first();

    $membership = Membership::create([
        'member_id' => $member->id,
        'membership_type_id' => $type->id,
        'period_start' => now(),
        'period_end' => now()->addYear(),
        'status' => MembershipStatus::PendingPayment,
        'price_cents_snapshot' => $type->price_cents,
        'membership_type_slug_snapshot' => $type->slug,
        'membership_type_name_snapshot' => $type->name,
    ]);

    $this->service->send($membership);
})->throws(ValidationException::class);

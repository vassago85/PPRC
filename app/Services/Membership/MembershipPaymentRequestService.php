<?php

namespace App\Services\Membership;

use App\Enums\MembershipStatus;
use App\Enums\PaymentProvider;
use App\Enums\PaymentStatus;
use App\Mail\MembershipPaymentRequestMail;
use App\Models\Membership;
use App\Models\MembershipPayment;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class MembershipPaymentRequestService
{
    public function __construct(
        protected PaymentReferenceGenerator $refGenerator,
    ) {}

    public function send(Membership $membership, bool $isReminder = true): MembershipPayment
    {
        $membership->loadMissing(['member.user', 'membershipType']);

        if ($membership->status !== MembershipStatus::PendingPayment) {
            throw ValidationException::withMessages([
                'membership' => 'Payment requests can only be sent for memberships awaiting payment.',
            ]);
        }

        $member = $membership->member;
        $email = $member?->user?->email;

        if (! filled($email)) {
            throw ValidationException::withMessages([
                'email' => 'This member has no portal account email on file.',
            ]);
        }

        $payment = $this->ensurePendingPayment($membership);

        Mail::to($email, $member->fullName())->send(
            new MembershipPaymentRequestMail($member, $membership, $payment, $isReminder),
        );

        return $payment;
    }

    public function ensurePendingPayment(Membership $membership): MembershipPayment
    {
        $existing = $membership->payments()
            ->where('status', PaymentStatus::Pending)
            ->latest('created_at')
            ->first();

        if ($existing) {
            return $existing;
        }

        return MembershipPayment::create([
            'membership_id' => $membership->id,
            'provider' => PaymentProvider::ManualEft->value,
            'status' => PaymentStatus::Pending->value,
            'amount_cents' => $membership->price_cents_snapshot
                ?? $membership->membershipType?->price_cents
                ?? 0,
            'currency' => 'ZAR',
            'reference' => $this->refGenerator->generate(),
        ]);
    }
}

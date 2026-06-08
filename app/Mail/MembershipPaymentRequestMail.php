<?php

namespace App\Mail;

use App\Models\Member;
use App\Models\Membership;
use App\Models\MembershipPayment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MembershipPaymentRequestMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Member $member,
        public Membership $membership,
        public MembershipPayment $payment,
        public bool $isReminder = false,
    ) {}

    public function envelope(): Envelope
    {
        $subject = $this->isReminder
            ? 'Reminder: PPRC membership payment — pay or upload proof'
            : 'PPRC membership payment — your banking details';

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        $this->membership->loadMissing('membershipType');

        return new Content(
            view: 'emails.membership-payment-request',
            with: [
                'member' => $this->member,
                'membership' => $this->membership,
                'payment' => $this->payment,
                'typeName' => $this->membership->membership_type_name_snapshot
                    ?? $this->membership->membershipType?->name
                    ?? 'Member',
                'portalUrl' => url('/portal/membership'),
                'bankName' => (string) \App\Models\SiteSetting::get('payments.bank.bank', ''),
                'accountName' => (string) \App\Models\SiteSetting::get('payments.bank.account_name', ''),
                'accountNumber' => (string) \App\Models\SiteSetting::get('payments.bank.account_number', ''),
                'branchCode' => (string) \App\Models\SiteSetting::get('payments.bank.branch_code', ''),
                'accountType' => (string) \App\Models\SiteSetting::get('payments.bank.account_type', 'cheque'),
                'bankNotes' => (string) \App\Models\SiteSetting::get('payments.bank.notes', ''),
                'isReminder' => $this->isReminder,
            ],
        );
    }
}

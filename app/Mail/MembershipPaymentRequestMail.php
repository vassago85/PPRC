<?php

namespace App\Mail;

use App\Models\Member;
use App\Models\Membership;
use App\Models\MembershipPayment;
use App\Models\SiteSetting;
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
        // Carrying the reference in the subject gives the member somewhere to
        // find it once they're in their banking app and no longer reading the
        // email, which is where the wrong reference usually gets typed.
        $reference = filled($this->payment->reference)
            ? ' — use ref '.$this->payment->reference
            : '';

        $subject = $this->isReminder
            ? 'Reminder: PPRC membership payment'.$reference
            : 'PPRC membership payment'.$reference;

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
                'bankName' => (string) SiteSetting::get('payments.bank.bank', ''),
                'accountName' => (string) SiteSetting::get('payments.bank.account_name', ''),
                'accountNumber' => (string) SiteSetting::get('payments.bank.account_number', ''),
                'branchCode' => (string) SiteSetting::get('payments.bank.branch_code', ''),
                'accountType' => (string) SiteSetting::get('payments.bank.account_type', 'cheque'),
                'bankNotes' => (string) SiteSetting::get('payments.bank.notes', ''),
                'isReminder' => $this->isReminder,
            ],
        );
    }
}

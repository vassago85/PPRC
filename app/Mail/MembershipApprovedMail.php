<?php

namespace App\Mail;

use App\Models\Member;
use App\Models\Membership;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MembershipApprovedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Member $member,
        public Membership $membership,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your PPRC membership has been approved',
        );
    }

    public function content(): Content
    {
        $this->membership->loadMissing('membershipType');

        return new Content(
            view: 'emails.membership-approved',
            with: [
                'member' => $this->member,
                'membership' => $this->membership,
                'typeName' => $this->membership->membership_type_name_snapshot
                    ?? $this->membership->membershipType?->name
                    ?? 'Member',
                'portalUrl' => url('/portal'),
            ],
        );
    }
}

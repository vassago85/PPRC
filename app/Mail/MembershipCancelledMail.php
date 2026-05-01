<?php

namespace App\Mail;

use App\Models\Member;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Sent after a member self-cancels via the link in their renewal reminder.
 * Goes to the member (proof) and CC'd to the membership secretary so the
 * SAPRF / paper records can be updated.
 */
class MembershipCancelledMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Member $member) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your PPRC membership has been cancelled');
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.membership-cancelled',
            with: [
                'member' => $this->member,
                'reason' => $this->member->resignation_reason,
                'resignedAt' => $this->member->resigned_at,
            ],
        );
    }
}

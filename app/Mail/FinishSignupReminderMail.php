<?php

namespace App\Mail;

use App\Models\Member;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * A one-time nudge for people who started signing up but never finished —
 * either they never confirmed their email ('verify') or they confirmed it
 * but never chose a membership ('choose'). Sent by the stale-signup cleanup
 * before the account is archived.
 */
class FinishSignupReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param string $variant 'verify' (email never confirmed) or 'choose' (no membership chosen)
     */
    public function __construct(
        public Member $member,
        public string $variant,
    ) {}

    public function envelope(): Envelope
    {
        $subject = match ($this->variant) {
            'verify' => 'Finish setting up your PPRC account',
            default => 'One step left to join PPRC',
        };

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.finish-signup-reminder',
            with: [
                'member' => $this->member,
                'variant' => $this->variant,
                'actionUrl' => $this->variant === 'verify'
                    ? url('/login')
                    : url('/portal/membership'),
            ],
        );
    }
}

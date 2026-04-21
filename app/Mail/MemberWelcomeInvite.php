<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Sent to a member (existing User record imported from the legacy SSMM
 * platform) to invite them to claim their account on the new site. Carries
 * a password-reset URL so the recipient sets their own password on first
 * click — we never email plaintext credentials.
 */
class MemberWelcomeInvite extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $setupUrl,
        public ?string $firstName = null,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Welcome to Pretoria Precision Rifle Club — claim your account',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.member-welcome-invite',
            with: [
                'user' => $this->user,
                'setupUrl' => $this->setupUrl,
                'firstName' => $this->firstName ?: $this->user->name,
            ],
        );
    }
}

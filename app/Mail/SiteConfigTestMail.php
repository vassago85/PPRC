<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Sent from Site settings → “Send test email” to verify Mailgun/SMTP and
 * from-address configuration without affecting members.
 */
class SiteConfigTestMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public ?User $triggeredBy = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'PPRC — email delivery test',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.site-config-test',
            with: [
                'triggeredBy' => $this->triggeredBy,
                'sentAt' => now()->timezone(config('app.timezone'))->format('Y-m-d H:i:s T'),
                'appUrl' => (string) config('app.url'),
                'mailer' => (string) config('mail.default'),
            ],
        );
    }
}

<?php

namespace App\Mail;

use App\Models\Event;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EventGuestRegistrationPinMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Event $event,
        public string $pin,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your PPRC match registration code',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.event-guest-registration-pin',
        );
    }
}

<?php

namespace App\Mail;

use App\Models\Event;
use App\Models\EventRegistration;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * A free-text message an admin sends to a filtered group of a match's
 * entrants (e.g. all, paid-only, awaiting payment). Subject and body are
 * supplied per send.
 */
class MatchEntrantMessageMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Event $event,
        public string $subjectLine,
        public string $body,
        public EventRegistration $registration,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->subjectLine);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.match-entrant-message',
            with: [
                'event' => $this->event,
                'subjectLine' => $this->subjectLine,
                'body' => $this->body,
                'firstName' => $this->registration->payerFirstName(),
                'matchUrl' => $this->event->slug
                    ? route('matches.show', ['event' => $this->event->slug])
                    : url('/matches'),
            ],
        );
    }
}

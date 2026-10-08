<?php

namespace App\Mail;

use App\Enums\AttendanceResponse;
use App\Models\Event;
use App\Models\EventRegistration;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

/**
 * Pre-match "are you still shooting?" email with three signed-URL buttons —
 * still shooting / unsure / withdraw. Signed URLs mean guests without portal
 * accounts can respond with one click, and the admin gets back a tally for
 * squadding without chasing anyone.
 */
class MatchAttendanceCheckMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * The signed URL for each response has a 60-day lifetime. Any match
     * schedule will have come and gone long before then — but the window
     * is long enough that an admin re-sending the check after a reschedule
     * doesn't have to worry about expiry.
     */
    private const SIGNATURE_LIFETIME_DAYS = 60;

    public function __construct(
        public Event $event,
        public EventRegistration $registration,
        public ?string $note = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Are you still shooting '.$this->event->title.'?',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.match-attendance-check',
            with: [
                'event' => $this->event,
                'firstName' => $this->registration->payerFirstName(),
                'note' => $this->note,
                'stillShootingUrl' => $this->signedUrlFor(AttendanceResponse::StillShooting),
                'unsureUrl' => $this->signedUrlFor(AttendanceResponse::Unsure),
                'withdrawUrl' => $this->signedUrlFor(AttendanceResponse::Withdrawn),
                'matchUrl' => $this->event->slug
                    ? route('matches.show', ['event' => $this->event->slug])
                    : url('/matches'),
            ],
        );
    }

    private function signedUrlFor(AttendanceResponse $response): string
    {
        return URL::temporarySignedRoute(
            'matches.attendance.record',
            now()->addDays(self::SIGNATURE_LIFETIME_DAYS),
            [
                'registration' => $this->registration->id,
                'response' => $response->value,
            ],
        );
    }
}

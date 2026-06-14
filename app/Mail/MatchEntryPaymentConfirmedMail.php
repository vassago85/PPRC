<?php

namespace App\Mail;

use App\Models\EventRegistration;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MatchEntryPaymentConfirmedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public EventRegistration $registration,
    ) {}

    public function envelope(): Envelope
    {
        $title = $this->registration->event?->title ?? 'PPRC match';

        return new Envelope(subject: "Payment received for {$title}");
    }

    public function content(): Content
    {
        $registration = $this->registration;
        $registration->loadMissing('event');
        $event = $registration->event;

        return new Content(
            view: 'emails.match-entry-payment-confirmed',
            with: [
                'registration' => $registration,
                'event' => $event,
                'firstName' => $registration->payerFirstName(),
                'amountCents' => (int) ($registration->effectiveFeeCents() ?? 0),
                'reference' => $registration->paymentReference(),
                'paidOn' => $registration->paid_at,
                'matchUrl' => $event ? route('matches.show', ['event' => $event->slug]) : url('/matches'),
            ],
        );
    }
}

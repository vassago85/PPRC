<?php

namespace App\Mail;

use App\Models\EventRegistration;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * "Your refund has been issued" — sent when an admin uses the Withdraw &
 * refund action on a paid entry. Short, informational: amount, method, the
 * entry being cancelled, and a note that it may take a day or two to reflect
 * for EFT refunds.
 */
class MatchEntryRefundIssuedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public EventRegistration $registration,
    ) {}

    public function envelope(): Envelope
    {
        $title = $this->registration->event?->title ?? 'PPRC match';

        return new Envelope(subject: "Refund issued for {$title}");
    }

    public function content(): Content
    {
        $registration = $this->registration;
        $registration->loadMissing('event');

        return new Content(
            view: 'emails.match-entry-refund-issued',
            with: [
                'registration' => $registration,
                'event' => $registration->event,
                'firstName' => $registration->payerFirstName(),
                'amountCents' => $registration->refundedAmountCents(),
                'method' => $registration->refunded_method,
                'refundedOn' => $registration->refunded_at,
                'note' => $registration->refunded_note,
                'reference' => $registration->paymentReference(),
            ],
        );
    }
}

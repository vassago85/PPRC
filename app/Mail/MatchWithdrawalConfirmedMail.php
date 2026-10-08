<?php

namespace App\Mail;

use App\Enums\MatchPaymentMethod;
use App\Models\EventRegistration;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Confirmation sent to a shooter whose entry an admin has just withdrawn from a
 * match. Covers both halves of the refund flow:
 *
 *   - **Cash refunds** (handed back from the match-day float): the money is
 *     already in the shooter's pocket. Email reads as a receipt.
 *   - **EFT refunds**: money hasn't moved yet. It will go out in the next
 *     weekly match-payment batch — the email makes that clear so the shooter
 *     doesn't chase the bank the next morning.
 *
 * Only one email is sent in the EFT case; no second "money has now left"
 * email fires when the admin later marks it paid. The shooter will see the
 * EFT land in their account.
 */
class MatchWithdrawalConfirmedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public EventRegistration $registration,
    ) {}

    public function envelope(): Envelope
    {
        $title = $this->registration->event?->title ?? 'PPRC match';

        return new Envelope(
            subject: $this->isCashRefund()
                ? "Refund issued for {$title}"
                : "Withdrawal confirmed for {$title} — refund to follow",
        );
    }

    public function content(): Content
    {
        $registration = $this->registration;
        $registration->loadMissing('event');

        return new Content(
            view: 'emails.match-withdrawal-confirmed',
            with: [
                'registration' => $registration,
                'event' => $registration->event,
                'firstName' => $registration->payerFirstName(),
                'amountCents' => $registration->refundedAmountCents(),
                'method' => $registration->refunded_method,
                'recordedOn' => $registration->refunded_at,
                'note' => $registration->refunded_note,
                'reference' => $registration->paymentReference(),
                'isCashRefund' => $this->isCashRefund(),
            ],
        );
    }

    protected function isCashRefund(): bool
    {
        return $this->registration->refunded_method === MatchPaymentMethod::Cash;
    }
}

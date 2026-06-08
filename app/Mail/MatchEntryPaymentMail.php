<?php

namespace App\Mail;

use App\Models\EventRegistration;
use App\Models\SiteSetting;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MatchEntryPaymentMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public EventRegistration $registration,
        public bool $isReminder = false,
    ) {}

    public function envelope(): Envelope
    {
        $title = $this->registration->event?->title ?? 'PPRC match';

        $subject = $this->isReminder
            ? "Reminder: payment for {$title}"
            : "Payment details for {$title}";

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        $registration = $this->registration;
        $registration->loadMissing('event');
        $event = $registration->event;

        return new Content(
            view: 'emails.match-entry-payment',
            with: [
                'registration' => $registration,
                'event' => $event,
                'firstName' => $registration->payerFirstName(),
                'amountCents' => (int) ($registration->effectiveFeeCents() ?? 0),
                'reference' => $registration->paymentReference(),
                'matchUrl' => $event ? route('matches.show', ['event' => $event->slug]) : url('/matches'),
                'bankName' => (string) SiteSetting::get('payments.bank.bank', ''),
                'accountName' => (string) SiteSetting::get('payments.bank.account_name', ''),
                'accountNumber' => (string) SiteSetting::get('payments.bank.account_number', ''),
                'branchCode' => (string) SiteSetting::get('payments.bank.branch_code', ''),
                'accountType' => (string) SiteSetting::get('payments.bank.account_type', 'cheque'),
                'bankNotes' => (string) SiteSetting::get('payments.bank.notes', ''),
                'isReminder' => $this->isReminder,
            ],
        );
    }
}

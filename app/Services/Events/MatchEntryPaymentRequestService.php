<?php

namespace App\Services\Events;

use App\Mail\MatchEntryPaymentConfirmedMail;
use App\Mail\MatchEntryPaymentMail;
use App\Models\EventRegistration;
use App\Support\MailThrottle;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class MatchEntryPaymentRequestService
{
    /**
     * Email a single entry their match payment details (banking info, amount
     * owed and a stable reference). Throws a ValidationException when the entry
     * owes nothing (waived / SAPRF / free) or has no email on file.
     *
     * Pass $queueUntil to queue the email for later delivery (used by bulk
     * sends to stagger delivery); otherwise it is sent immediately.
     */
    public function send(EventRegistration $registration, bool $isReminder = false, ?\DateTimeInterface $queueUntil = null): void
    {
        $registration->loadMissing(['event', 'member.user']);

        $email = $registration->payerEmail();

        if (! filled($email)) {
            throw ValidationException::withMessages([
                'email' => 'This entry has no email address on file.',
            ]);
        }

        if ($registration->is_saprf_entry) {
            throw ValidationException::withMessages([
                'entry' => 'SAPRF entries pay through the SAPRF portal, not PPRC.',
            ]);
        }

        if ($registration->isWaived() || (int) ($registration->effectiveFeeCents() ?? 0) <= 0) {
            throw ValidationException::withMessages([
                'entry' => 'This entry has no fee to pay (free / comped / ExCo).',
            ]);
        }

        $pending = Mail::to($email, $registration->shooterName());

        if ($queueUntil !== null) {
            $pending->later($queueUntil, new MatchEntryPaymentMail($registration, $isReminder));

            return;
        }

        $pending->send(new MatchEntryPaymentMail($registration, $isReminder));
    }

    /**
     * Email the entry to confirm their fee has been received and their spot is
     * secured. Returns false (without sending) when there's no email on file.
     *
     * Pass $queueUntil to queue delivery for later (used by bulk sends to
     * stagger delivery); otherwise it is sent immediately.
     */
    public function sendConfirmation(EventRegistration $registration, ?\DateTimeInterface $queueUntil = null): bool
    {
        $registration->loadMissing(['event', 'member.user']);

        $email = $registration->payerEmail();

        if (! filled($email)) {
            return false;
        }

        $pending = Mail::to($email, $registration->shooterName());

        if ($queueUntil !== null) {
            $pending->later($queueUntil, new MatchEntryPaymentConfirmedMail($registration));
        } else {
            $pending->send(new MatchEntryPaymentConfirmedMail($registration));
        }

        return true;
    }

    /**
     * Send to many entries, silently skipping any that don't owe a payment.
     * Emails are queued with staggered delays so Mailgun isn't hit with a
     * burst.
     *
     * @param  iterable<EventRegistration>  $registrations
     * @return array{sent: int, skipped: int}
     */
    public function sendBulk(iterable $registrations, bool $isReminder = false): array
    {
        $sent = 0;
        $skipped = 0;

        foreach ($registrations as $registration) {
            try {
                $this->send($registration, $isReminder, MailThrottle::delayFor($sent));
                $sent++;
            } catch (ValidationException) {
                $skipped++;
            }
        }

        return ['sent' => $sent, 'skipped' => $skipped];
    }
}

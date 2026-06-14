<?php

namespace App\Services\Events;

use App\Mail\MatchEntryPaymentConfirmedMail;
use App\Mail\MatchEntryPaymentMail;
use App\Models\EventRegistration;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class MatchEntryPaymentRequestService
{
    /**
     * Email a single entry their match payment details (banking info, amount
     * owed and a stable reference). Throws a ValidationException when the entry
     * owes nothing (waived / SAPRF / free) or has no email on file.
     */
    public function send(EventRegistration $registration, bool $isReminder = false): void
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

        Mail::to($email, $registration->shooterName())->send(
            new MatchEntryPaymentMail($registration, $isReminder),
        );
    }

    /**
     * Email the entry to confirm their fee has been received and their spot is
     * secured. Returns false (without sending) when there's no email on file.
     */
    public function sendConfirmation(EventRegistration $registration): bool
    {
        $registration->loadMissing(['event', 'member.user']);

        $email = $registration->payerEmail();

        if (! filled($email)) {
            return false;
        }

        Mail::to($email, $registration->shooterName())->send(
            new MatchEntryPaymentConfirmedMail($registration),
        );

        return true;
    }

    /**
     * Send to many entries, silently skipping any that don't owe a payment.
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
                $this->send($registration, $isReminder);
                $sent++;
            } catch (ValidationException) {
                $skipped++;
            }
        }

        return ['sent' => $sent, 'skipped' => $skipped];
    }
}

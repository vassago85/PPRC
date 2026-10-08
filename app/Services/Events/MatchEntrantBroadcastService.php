<?php

namespace App\Services\Events;

use App\Enums\MatchEntryAudience;
use App\Mail\MatchAttendanceCheckMail;
use App\Mail\MatchEntrantMessageMail;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Support\MailThrottle;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class MatchEntrantBroadcastService
{
    /**
     * Queue a custom message to every entry in the given audience that has an
     * email on file. Entries without an email are skipped. Messages are
     * queued with staggered delays so Mailgun receives a steady trickle
     * rather than one big burst.
     *
     * @return array{sent: int, skipped: int}
     */
    public function send(Event $event, MatchEntryAudience $audience, string $subject, string $body): array
    {
        $sent = 0;
        $skipped = 0;

        foreach ($audience->filter($event) as $registration) {
            /** @var EventRegistration $registration */
            $email = $registration->payerEmail();

            if (! filled($email)) {
                $skipped++;

                continue;
            }

            Mail::to($email, $registration->shooterName())->later(
                MailThrottle::delayFor($sent),
                new MatchEntrantMessageMail($event, $subject, $body, $registration),
            );

            $sent++;
        }

        return ['sent' => $sent, 'skipped' => $skipped];
    }

    /**
     * Email the match's WhatsApp group invite link to every entry in the given
     * audience. When $skipAlreadySent is on, entries that already have a
     * whatsapp_link_sent_at stamp are left alone — so running this again a
     * day later only tops up late signups rather than spamming everyone.
     *
     * Each sent entry is stamped with whatsapp_link_sent_at as soon as its
     * mail is queued, which is good enough: the sync-queue in tests runs
     * immediately, and in prod the stamp means "we have told them", not "they
     * have definitely received it".
     *
     * @return array{sent: int, skipped: int, already: int}
     *
     * @throws ValidationException when the event has no WhatsApp link set.
     */
    public function sendWhatsAppLink(
        Event $event,
        MatchEntryAudience $audience,
        bool $skipAlreadySent = true,
        ?string $note = null,
    ): array {
        $url = trim((string) $event->whatsapp_group_url);

        if ($url === '') {
            throw ValidationException::withMessages([
                'whatsapp_group_url' => 'This match has no WhatsApp group link set yet.',
            ]);
        }

        $subject = 'WhatsApp group – '.$event->title;

        $body = 'Join the match WhatsApp group for squad info, weather and day-of updates.';

        if (filled($note)) {
            $body .= "\n\n".trim($note);
        }

        $sent = 0;
        $skipped = 0;
        $already = 0;

        foreach ($audience->filter($event) as $registration) {
            /** @var EventRegistration $registration */
            if ($skipAlreadySent && $registration->whatsapp_link_sent_at !== null) {
                $already++;

                continue;
            }

            $email = $registration->payerEmail();

            if (! filled($email)) {
                $skipped++;

                continue;
            }

            Mail::to($email, $registration->shooterName())->later(
                MailThrottle::delayFor($sent),
                new MatchEntrantMessageMail($event, $subject, $body, $registration, $url),
            );

            // Stamp immediately so a quick second run of this action (e.g. the
            // admin clicked twice, or new entries came in a day later) skips
            // shooters who have already had the invite.
            $registration->forceFill(['whatsapp_link_sent_at' => now()])->save();

            $sent++;
        }

        return ['sent' => $sent, 'skipped' => $skipped, 'already' => $already];
    }

    /**
     * Email a "are you still shooting?" check with three signed-URL response
     * buttons (still shooting / unsure / withdraw) to every entry in the
     * chosen audience. Mirrors sendWhatsAppLink's top-up semantics: when
     * $skipAlreadySent is on, entries with `attendance_check_sent_at` set
     * are left alone so running this again a day later only catches new
     * signups rather than re-asking everyone.
     *
     * @return array{sent: int, skipped: int, already: int}
     */
    public function sendAttendanceCheck(
        Event $event,
        MatchEntryAudience $audience,
        bool $skipAlreadySent = true,
        ?string $note = null,
    ): array {
        $sent = 0;
        $skipped = 0;
        $already = 0;

        foreach ($audience->filter($event) as $registration) {
            /** @var EventRegistration $registration */
            if ($skipAlreadySent && $registration->attendance_check_sent_at !== null) {
                $already++;

                continue;
            }

            $email = $registration->payerEmail();

            if (! filled($email)) {
                $skipped++;

                continue;
            }

            Mail::to($email, $registration->shooterName())->later(
                MailThrottle::delayFor($sent),
                new MatchAttendanceCheckMail($event, $registration, $note),
            );

            // Stamp immediately so a re-run only picks up late entries.
            $registration->forceFill(['attendance_check_sent_at' => now()])->save();

            $sent++;
        }

        return ['sent' => $sent, 'skipped' => $skipped, 'already' => $already];
    }
}

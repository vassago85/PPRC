<?php

namespace App\Services\Events;

use App\Enums\MatchEntryAudience;
use App\Mail\MatchEntrantMessageMail;
use App\Models\Event;
use App\Models\EventRegistration;
use Illuminate\Support\Facades\Mail;

class MatchEntrantBroadcastService
{
    /**
     * Send a custom message to every entry in the given audience that has an
     * email on file. Entries without an email are skipped.
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

            Mail::to($email, $registration->shooterName())->send(
                new MatchEntrantMessageMail($event, $subject, $body, $registration),
            );

            $sent++;
        }

        return ['sent' => $sent, 'skipped' => $skipped];
    }
}

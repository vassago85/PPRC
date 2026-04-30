<?php

namespace App\Listeners;

use App\Models\EmailLog;
use App\Models\User;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Facades\Log;

/**
 * Writes a row to email_logs for every successfully dispatched email so the
 * committee has an audit trail and the welcome-invite flow can check whether
 * a given address has already been contacted. Failures inside this listener
 * must never break real email delivery, so we swallow exceptions and log
 * them to the Laravel log channel instead.
 */
class LogSentEmail
{
    public function handle(MessageSent $event): void
    {
        try {
            $message = $event->message;

            $to = $this->addresses($message->getTo());
            $from = $this->addresses($message->getFrom());
            $primaryTo = $to[0] ?? null;
            $primaryFrom = $from[0] ?? null;

            if (! $primaryTo) {
                return;
            }

            $messageId = $message->getHeaders()->get('Message-ID')?->getBodyAsString();

            // Idempotency guard. Some mail-driver paths (Symfony transports
            // wrapped by Laravel) fire MessageSent twice for a single send,
            // which previously produced duplicate "sent" rows for every
            // welcome email. Only the first observation per Message-ID wins.
            if ($messageId && EmailLog::query()
                ->where('message_id', $messageId)
                ->where('status', EmailLog::STATUS_SENT)
                ->exists()) {
                return;
            }

            $mailableClass = $event->data['__laravel_mailable'] ?? null;
            if (is_object($mailableClass)) {
                $mailableClass = get_class($mailableClass);
            }

            EmailLog::create([
                'user_id' => $this->resolveUserId($primaryTo['email']),
                'to_email' => $primaryTo['email'],
                'to_name' => $primaryTo['name'],
                'from_email' => $primaryFrom['email'] ?? null,
                'from_name' => $primaryFrom['name'] ?? null,
                'subject' => $message->getSubject(),
                'mailable_class' => is_string($mailableClass) ? $mailableClass : null,
                'status' => EmailLog::STATUS_SENT,
                'context' => [
                    'cc' => $this->addresses($message->getCc()),
                    'bcc' => $this->addresses($message->getBcc()),
                    'reply_to' => $this->addresses($message->getReplyTo()),
                ],
                'message_id' => $messageId,
                'sent_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('LogSentEmail listener failed', [
                'error' => $e->getMessage(),
                'subject' => optional($event->message)->getSubject(),
            ]);
        }
    }

    /**
     * Normalise Symfony Mime Address[] into simple [[email, name], ...] arrays.
     *
     * @param  array<int, \Symfony\Component\Mime\Address>  $addresses
     * @return array<int, array{email: string, name: string|null}>
     */
    private function addresses(array $addresses): array
    {
        return array_values(array_map(fn ($a) => [
            'email' => $a->getAddress(),
            'name' => $a->getName() ?: null,
        ], $addresses));
    }

    private function resolveUserId(string $email): ?int
    {
        return User::query()->where('email', $email)->value('id');
    }
}

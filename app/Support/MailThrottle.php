<?php

namespace App\Support;

use DateTimeInterface;

/**
 * Spaces out queued bulk emails so a large send trickles into Mailgun as a
 * steady stream instead of a single burst (which trips Mailgun's rate limits
 * and hurts deliverability). Each recipient is queued with an increasing
 * delay based on its position in the batch.
 */
class MailThrottle
{
    /** Small head-start so nothing fires inside the web request itself. */
    public const INITIAL_DELAY_SECONDS = 5;

    /** Gap between consecutive messages (~15 emails/minute). */
    public const SECONDS_BETWEEN = 4;

    public static function delayFor(int $index): DateTimeInterface
    {
        $seconds = self::INITIAL_DELAY_SECONDS + (max(0, $index) * self::SECONDS_BETWEEN);

        return now()->addSeconds($seconds);
    }
}

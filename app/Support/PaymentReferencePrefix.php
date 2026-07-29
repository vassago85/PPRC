<?php

namespace App\Support;

use App\Models\SiteSetting;

/**
 * The club's EFT reference prefix (e.g. "PPRC").
 *
 * Everything that mints or reads a reference resolves it here so the
 * generator, the match-entry reference and the reconciliation resolver can
 * never disagree about what the club's references look like.
 */
class PaymentReferencePrefix
{
    public static function get(): string
    {
        try {
            $prefix = trim((string) SiteSetting::get('payments.bank.reference_prefix', ''));
        } catch (\Throwable) {
            // Settings table not migrated yet — fall through to config.
            $prefix = '';
        }

        if ($prefix === '') {
            $prefix = (string) config('membership.payment_ref_prefix', 'PPRC');
        }

        return self::sanitize($prefix);
    }

    public static function sanitize(string $prefix): string
    {
        $clean = strtoupper(trim((string) preg_replace('/[^A-Za-z0-9-]+/', '', $prefix), '-'));

        return $clean !== '' ? $clean : 'PPRC';
    }
}

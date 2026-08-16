<?php

namespace App\Support;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;

/**
 * Disk resolver for sensitive payment proofs (bank POP images/PDFs for
 * memberships, event entries and shop orders).
 *
 * Unlike {@see MediaDisk} — which serves public assets like banners and
 * product images — proofs contain member banking/PII and must never be
 * publicly URL-reachable. We therefore always store them on the private
 * `local` disk (storage/app/private, which is NOT symlinked into /public)
 * and hand them to admins through short-lived signed temporary URLs.
 *
 * The `local` disk has `serve => true`, so `temporaryUrl()` produces a signed,
 * expiring route to stream the file rather than a permanent public link.
 */
class ProofDisk
{
    public const NAME = 'local';

    public static function name(): string
    {
        return self::NAME;
    }

    public static function disk(): Filesystem
    {
        return Storage::disk(self::NAME);
    }

    /**
     * A short-lived signed URL for an admin to view a stored proof, or null
     * when the path is empty or a URL cannot be generated.
     */
    public static function url(?string $path, int $minutes = 15): ?string
    {
        if (! $path) {
            return null;
        }

        $disk = self::disk();

        try {
            if (method_exists($disk, 'temporaryUrl')) {
                return $disk->temporaryUrl($path, now()->addMinutes($minutes));
            }
        } catch (\Throwable) {
            // Fall through — some drivers/configs can't mint temporary URLs.
        }

        return null;
    }
}

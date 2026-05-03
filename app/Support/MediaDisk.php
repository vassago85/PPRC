<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

class MediaDisk
{
    protected static ?bool $s3Ready = null;

    public static function name(): string
    {
        return self::s3Available() ? 's3' : 'media';
    }

    /**
     * When S3/R2 is active, return a proxy URL through the app's own domain
     * instead of the raw R2 pub-*.r2.dev URL, which Brave and ad blockers
     * refuse to resolve. The MediaProxyController streams the file from R2.
     */
    public static function url(string $path): string
    {
        if (self::s3Available()) {
            return url('/media/'.ltrim($path, '/'));
        }

        return Storage::disk('media')->url($path);
    }

    public static function s3Available(): bool
    {
        if (self::$s3Ready !== null) {
            return self::$s3Ready;
        }

        $bucket = config('filesystems.disks.s3.bucket');
        $endpoint = config('filesystems.disks.s3.endpoint');
        $key = config('filesystems.disks.s3.key');

        self::$s3Ready = filled($bucket) && filled($key) && filled($endpoint);

        return self::$s3Ready;
    }

    public static function flush(): void
    {
        self::$s3Ready = null;
    }
}

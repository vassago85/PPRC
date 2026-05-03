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

    public static function url(string $path): string
    {
        return Storage::disk(self::name())->url($path);
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

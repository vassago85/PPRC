<?php

namespace App\Providers;

use App\Listeners\LogSentEmail;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Filament/Livewire file fields: temporary uploads must hit this app over
        // HTTPS. If this followed FILESYSTEM_DISK=s3, Livewire would presign PUTs
        // to the S3 endpoint (e.g. http://pprc-minio:9000), which browsers block
        // (mixed content + Docker-internal hostnames). Final files still use
        // Filament's ->disk('s3') after save.
        config([
            'livewire.temporary_file_upload.disk' => env('LIVEWIRE_TEMPORARY_FILE_UPLOAD_DISK', 'local'),
        ]);

        // Capture every outbound email into email_logs for audit + idempotency
        // (used by the welcome-invite sender to skip already-contacted members).
        Event::listen(MessageSent::class, LogSentEmail::class);
    }
}

<?php

namespace App\Providers;

use App\Models\SiteSetting;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Throwable;

/**
 * Overrides certain config() values from the database at boot time, so that
 * changes made in the Admin → Site settings screen take effect without having
 * to redeploy or edit .env.
 *
 * We guard with a schema check because this provider boots on every artisan
 * call, including `migrate` where the site_settings table may not exist yet.
 */
class RuntimeConfigServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        try {
            if (! Schema::hasTable('site_settings')) {
                return;
            }
        } catch (Throwable) {
            return;
        }

        try {
            $this->applyMailConfig();
            $this->applyStorageConfig();
            $this->applyPaystackConfig();
            $this->applyContactConfig();
        } catch (Throwable) {
            // Never allow a bad / missing setting to crash the framework boot.
        }
    }

    protected function applyMailConfig(): void
    {
        $transport = (string) SiteSetting::get('mail.transport', '');
        $domain = SiteSetting::get('mail.mailgun.domain');
        $secret = SiteSetting::get('mail.mailgun.secret');
        $endpoint = SiteSetting::get('mail.mailgun.endpoint');
        $fromAddress = SiteSetting::get('mail.from.address');
        $fromName = SiteSetting::get('mail.from.name');

        $smtpHost = SiteSetting::get('mail.smtp.host');
        $smtpPort = SiteSetting::get('mail.smtp.port');
        $smtpUser = SiteSetting::get('mail.smtp.username');
        $smtpPass = SiteSetting::get('mail.smtp.password');
        $smtpEnc = SiteSetting::get('mail.smtp.encryption');

        if (filled($domain)) {
            config(['services.mailgun.domain' => $domain]);
        }

        if (filled($secret)) {
            config(['services.mailgun.secret' => $secret]);
        }

        if (filled($endpoint)) {
            config(['services.mailgun.endpoint' => $endpoint]);
        }

        if (filled($fromAddress)) {
            config([
                'mail.from.address' => $fromAddress,
                'mail.from.name' => $fromName ?: config('mail.from.name'),
            ]);
        }

        $smtpMailer = config('mail.mailers.smtp', []);
        if (filled($smtpHost)) {
            $smtpMailer['host'] = $smtpHost;
        }
        if (filled($smtpPort)) {
            $smtpMailer['port'] = (int) $smtpPort;
        }
        if (filled($smtpUser)) {
            $smtpMailer['username'] = $smtpUser;
        }
        if (filled($smtpPass)) {
            $smtpMailer['password'] = $smtpPass;
        }
        if ($smtpEnc !== null && $smtpEnc !== '') {
            $smtpMailer['encryption'] = $smtpEnc;
        }
        config(['mail.mailers.smtp' => $smtpMailer]);

        $mailgunReady = filled($domain) && filled($secret);
        $smtpReady = filled($smtpHost);

        $default = match ($transport) {
            'log' => 'log',
            'smtp' => $smtpReady ? 'smtp' : null,
            'mailgun' => $mailgunReady ? 'mailgun' : null,
            default => null,
        };

        if ($default === null) {
            if ($mailgunReady) {
                $default = 'mailgun';
            } elseif ($smtpReady) {
                $default = 'smtp';
            }
        }

        if ($default !== null) {
            config(['mail.default' => $default]);
        }
    }

    protected function applyStorageConfig(): void
    {
        $endpoint = SiteSetting::get('storage.s3.endpoint');
        $region = SiteSetting::get('storage.s3.region');
        $bucket = SiteSetting::get('storage.s3.bucket');
        $accessKey = SiteSetting::get('storage.s3.access_key');
        $secretKey = SiteSetting::get('storage.s3.secret_key');
        $url = SiteSetting::get('storage.s3.url');
        $usePathStyle = SiteSetting::get('storage.s3.use_path_style');

        $disk = config('filesystems.disks.s3', []);

        if (filled($endpoint)) {
            $disk['endpoint'] = $endpoint;
        }
        if (filled($region)) {
            $disk['region'] = $region;
        }
        if (filled($bucket)) {
            $disk['bucket'] = $bucket;
        }
        if (filled($accessKey)) {
            $disk['key'] = $accessKey;
        }
        if (filled($secretKey)) {
            $disk['secret'] = $secretKey;
        }
        if (filled($url)) {
            $disk['url'] = $url;
            // Presigned S3 uploads (e.g. Livewire temp if disk were s3) use the internal
            // endpoint in the signature; swap scheme/host for the browser to this HTTPS URL.
            $disk['temporary_url'] = rtrim($url, '/');
        }
        if ($usePathStyle !== null) {
            $disk['use_path_style_endpoint'] = (bool) $usePathStyle;
        }

        config(['filesystems.disks.s3' => $disk]);
    }

    protected function applyPaystackConfig(): void
    {
        $public = SiteSetting::get('payments.paystack.public_key');
        $secret = SiteSetting::get('payments.paystack.secret_key');
        $webhook = SiteSetting::get('payments.paystack.webhook_secret');
        $currency = SiteSetting::get('payments.paystack.currency');

        $paystack = config('services.paystack', []);

        if (filled($public)) {
            $paystack['public_key'] = $public;
        }
        if (filled($secret)) {
            $paystack['secret_key'] = $secret;
        }
        if (filled($webhook)) {
            $paystack['webhook_secret'] = $webhook;
        }
        if (filled($currency)) {
            $paystack['currency'] = $currency;
        }

        config(['services.paystack' => $paystack]);
    }

    protected function applyContactConfig(): void
    {
        $email = SiteSetting::get('contact.email');

        if (filled($email)) {
            config(['club.contact.email' => $email]);
        }
    }
}

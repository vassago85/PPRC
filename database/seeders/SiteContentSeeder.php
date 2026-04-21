<?php

namespace Database\Seeders;

use App\Models\Announcement;
use App\Models\ExcoMember;
use App\Models\SiteSetting;
use Illuminate\Database\Seeder;

class SiteContentSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedAnnouncements();
        $this->seedExco();
        $this->seedContactSettings();
        $this->seedIntegrationSettings();
    }

    /**
     * No announcements are seeded by default — the live site does not have
     * a blog/news stream we can mirror 1:1, so we leave this empty and let
     * the committee post their own news via the admin.
     */
    protected function seedAnnouncements(): void
    {
        // Intentionally empty.
    }

    /**
     * Current Executive Committee — 2026 AGM (10 February 2026), section 11
     * Elections. Six unopposed nominees; outgoing ExCo (Dirk Pio, JC Robertson,
     * Leon Goosen) stepped aside. Names and titles are taken verbatim from the
     * official minutes the club supplied.
     *
     * Public /about pulls `is_current` rows only. Re-seeding updates positions
     * in place and retires any stale titles (e.g. old "Vice Chairman" wording).
     */
    protected function seedExco(): void
    {
        $rows = [
            ['full_name' => 'Warren Britnell',    'position' => 'Chairman',     'sort_order' => 10, 'is_current' => true],
            ['full_name' => 'Paul Charsley',     'position' => 'Vice Chair',   'sort_order' => 20, 'is_current' => true],
            ['full_name' => 'Natasha Britnell',  'position' => 'Treasurer',    'sort_order' => 30, 'is_current' => true],
            ['full_name' => 'Coenie van Tonder', 'position' => 'Secretary',    'sort_order' => 40, 'is_current' => true],
            ['full_name' => 'Sean Swarts',       'position' => 'Marketing',    'sort_order' => 50, 'is_current' => true],
            ['full_name' => 'Eddie Kinnear',     'position' => 'Club Captain', 'sort_order' => 60, 'is_current' => true],
        ];

        $positions = array_column($rows, 'position');

        ExcoMember::query()
            ->where('is_current', true)
            ->whereNotIn('position', $positions)
            ->update(['is_current' => false]);

        foreach ($rows as $r) {
            ExcoMember::updateOrCreate(
                ['position' => $r['position']],
                $r,
            );
        }
    }

    protected function seedContactSettings(): void
    {
        // Contact details sourced from https://pretoriaprc.co.za/contact/
        // Phone number intentionally not seeded — public contact is the form
        // on /contact, which emails contact.email.
        SiteSetting::put('contact.email', 'info@pretoriaprc.co.za', ['group' => 'contact', 'label' => 'Contact email']);
        SiteSetting::put('contact.physical_address', 'Pretoria, Gauteng, South Africa', ['group' => 'contact', 'label' => 'Physical address']);
        SiteSetting::put('contact.social.facebook', '', ['group' => 'contact', 'label' => 'Facebook URL']);
        SiteSetting::put('contact.social.instagram', '', ['group' => 'contact', 'label' => 'Instagram URL']);
        SiteSetting::put('payments.bank.account_name', 'Pretoria Precision Rifle Club', ['group' => 'payments', 'label' => 'Bank account name']);
        SiteSetting::put('payments.bank.bank', 'FNB', ['group' => 'payments', 'label' => 'Bank']);
        SiteSetting::put('payments.bank.account_number', '0000000000', ['group' => 'payments', 'label' => 'Account number']);
        SiteSetting::put('payments.bank.branch_code', '250655', ['group' => 'payments', 'label' => 'Branch code']);
        SiteSetting::put('payments.bank.reference_format', 'PPRC-MEM-{id}', ['group' => 'payments', 'label' => 'Reference format']);
        SiteSetting::put('payments.bank.account_type', 'cheque', ['group' => 'payments', 'label' => 'Account type']);
        SiteSetting::put('payments.bank.notes', '', ['group' => 'payments', 'label' => 'Bank notes']);
        SiteSetting::put('contact.social.whatsapp', '', ['group' => 'contact', 'label' => 'WhatsApp link']);
    }

    /**
     * Ensure rows exist for every integration key the admin settings page
     * writes to, so the Site settings form mounts cleanly on a fresh install.
     * Values are intentionally empty — real credentials are entered via the
     * admin UI (or imported from .env on first boot).
     */
    protected function seedIntegrationSettings(): void
    {
        $rows = [
            // Mail / Mailgun
            ['mail.from.address',        'mail',     'From address',            false],
            ['mail.from.name',           'mail',     'From name',               false],
            ['mail.mailgun.domain',      'mail',     'Mailgun domain',          false],
            ['mail.mailgun.endpoint',    'mail',     'Mailgun endpoint',        false],
            ['mail.mailgun.secret',      'mail',     'Mailgun secret',          true],

            // Storage / S3 / MinIO
            ['storage.s3.endpoint',       'storage', 'S3 endpoint',             false],
            ['storage.s3.region',         'storage', 'S3 region',               false],
            ['storage.s3.bucket',         'storage', 'S3 bucket',               false],
            ['storage.s3.url',            'storage', 'S3 public URL',           false],
            ['storage.s3.use_path_style', 'storage', 'Use path style',          false],
            ['storage.s3.access_key',     'storage', 'S3 access key',           true],
            ['storage.s3.secret_key',     'storage', 'S3 secret key',           true],

            // Paystack
            ['payments.paystack.public_key',     'payments', 'Paystack public key',     false],
            ['payments.paystack.currency',       'payments', 'Paystack currency',       false],
            ['payments.paystack.secret_key',     'payments', 'Paystack secret key',     true],
            ['payments.paystack.webhook_secret', 'payments', 'Paystack webhook secret', true],
        ];

        foreach ($rows as [$key, $group, $label, $isSecret]) {
            $exists = SiteSetting::query()->where('key', $key)->exists();
            if ($exists) {
                continue;
            }

            $default = match (true) {
                $key === 'mail.from.name'             => 'PPRC',
                $key === 'mail.mailgun.endpoint'      => 'api.mailgun.net',
                $key === 'storage.s3.region'          => 'us-east-1',
                $key === 'storage.s3.use_path_style'  => true,
                $key === 'payments.paystack.currency' => 'ZAR',
                default                               => '',
            };

            SiteSetting::put($key, $default, [
                'group' => $group,
                'label' => $label,
                'is_secret' => $isSecret,
            ]);
        }
    }
}

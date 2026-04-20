<?php

namespace Database\Seeders;

use App\Models\Announcement;
use App\Models\ExcoMember;
use App\Models\Faq;
use App\Models\HomepageSection;
use App\Models\Page;
use App\Models\SiteSetting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SiteContentSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedHomepage();
        $this->seedPages();
        $this->seedAnnouncements();
        $this->seedFaqs();
        $this->seedExco();
        $this->seedContactSettings();
        $this->seedIntegrationSettings();
    }

    /**
     * The homepage is hand-built in resources/views/site/home.blade.php so the
     * visible copy stays 1:1 with pretoriaprc.co.za. We intentionally do NOT
     * seed HomepageSection rows here — the admin can still create optional
     * sections via Filament if they want to extend the page later, but we
     * never want the hand-crafted landing page to be overridden by seed data
     * that contains placeholder stats.
     */
    protected function seedHomepage(): void
    {
        // Remove any previously-seeded demo sections (fabricated stats etc.)
        // so a re-seed on an existing database cleans them out.
        HomepageSection::query()
            ->whereIn('key', ['hero', 'stats', 'features', 'events_teaser', 'cta'])
            ->delete();
    }

    protected function seedPages(): void
    {
        $pages = [
            [
                // Content pulled from https://pretoriaprc.co.za/about/
                'slug' => 'about',
                'title' => 'About PPRC',
                'subtitle' => 'Started in 2023 by precision rifle shooters, for precision rifle shooters.',
                'excerpt' => 'A precision rifle club based in Pretoria, Gauteng.',
                'body' => <<<'HTML'
<p>Welcome to Pretoria Precision Rifle Club (PPRC).</p>
<p>We are a Precision Rifle Club based in Pretoria, Gauteng that was started in 2023 by Precision Rifle Shooters, for Precision Rifle Shooters.</p>
<p>Our vision is to create a family/club environment for existing and future precision rifle shooters, with the purpose of uniting together and building a sustainable environment where every shooter can grow, belong and compete in this wonderful sport of PRS.</p>
HTML,
                'is_published' => true,
                'published_at' => now(),
                'show_in_nav' => true,
                'nav_sort_order' => 10,
            ],
            [
                // The public pretoriaprc.co.za/membership page only exposes a
                // portal widget; it does not publish tier pricing. We keep
                // the public membership page minimal and push members to the
                // portal for details.
                'slug' => 'membership',
                'title' => 'Membership',
                'subtitle' => 'Join Pretoria Precision Rifle Club.',
                'excerpt' => 'Register an account and a committee member will approve your application.',
                'body' => <<<'HTML'
<p>Membership at PPRC is managed through the member portal. Register an account, choose a membership option and submit your application — a committee member will approve it and issue your membership number.</p>
<p>Payment is by bank EFT (with proof of payment uploaded to the portal) or by card via Paystack.</p>
HTML,
                'is_published' => true,
                'published_at' => now(),
                'show_in_nav' => true,
                'nav_sort_order' => 20,
            ],
        ];

        foreach ($pages as $p) {
            Page::updateOrCreate(['slug' => $p['slug']], $p);
        }

        // The /contact URL is served by the dedicated Site\ContactController
        // (form + email delivery), so any previously-seeded CMS page must go.
        Page::where('slug', 'contact')->delete();
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
     * Keep FAQs minimal and factual. We only seed questions whose answers can
     * be stated without inventing club policy. The committee can add more via
     * the admin once they've confirmed the specifics.
     */
    protected function seedFaqs(): void
    {
        $rows = [
            [
                'category' => 'membership',
                'question' => 'How do I join PPRC?',
                'answer' => 'Register an account on the website, choose a membership option, and a committee member will review and approve your application.',
                'sort_order' => 10,
            ],
        ];

        foreach ($rows as $r) {
            Faq::updateOrCreate(['question' => $r['question']], $r);
        }
    }

    /**
     * Committee members extracted from https://pretoriaprc.co.za/about/
     * Only positions that actually appear on the live site are seeded.
     */
    protected function seedExco(): void
    {
        // Wipe any previously-seeded placeholder committee rows so the live
        // list always matches the source site on a re-seed.
        ExcoMember::query()
            ->whereIn('position', ['Chairperson', 'Vice Chairperson', 'Treasurer', 'Membership Secretary', 'Events Coordinator'])
            ->delete();

        $rows = [
            ['full_name' => 'Dirk Pio',         'position' => 'Chairman',            'sort_order' => 10, 'is_current' => true],
            ['full_name' => 'Warren Britnell',  'position' => 'Vice Chairman',       'sort_order' => 20, 'is_current' => true],
            ['full_name' => 'JC Robertson',     'position' => 'Secretary',           'sort_order' => 30, 'is_current' => true],
            ['full_name' => 'Leon Goosen',      'position' => 'Marketing & Tech',    'sort_order' => 40, 'is_current' => true],
        ];

        foreach ($rows as $r) {
            ExcoMember::updateOrCreate(['position' => $r['position']], $r);
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

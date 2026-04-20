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
    }

    protected function seedHomepage(): void
    {
        $sections = [
            [
                'key' => 'hero',
                'type' => 'hero',
                'eyebrow' => 'Pretoria Precision Rifle Club',
                'title' => 'Precision. Discipline. Community.',
                'subtitle' => 'A home for serious precision rifle shooters in Gauteng. Matches, training, and camaraderie — under one club.',
                'cta_label' => 'Join the club',
                'cta_url' => '/membership',
                'sort_order' => 10,
            ],
            [
                'key' => 'stats',
                'type' => 'stats',
                'title' => 'The club at a glance',
                'meta' => [
                    'items' => [
                        ['value' => 'Since 2019', 'label' => 'Founded'],
                        ['value' => '150+', 'label' => 'Active members'],
                        ['value' => '12+', 'label' => 'Matches a year'],
                        ['value' => 'SAPRF', 'label' => 'Affiliated'],
                    ],
                ],
                'sort_order' => 20,
            ],
            [
                'key' => 'features',
                'type' => 'feature_grid',
                'eyebrow' => 'What we do',
                'title' => 'Built for precision rifle',
                'meta' => [
                    'items' => [
                        ['title' => 'Monthly matches', 'body' => 'Club-level precision matches with structured stages, scorekeeping, and placings.'],
                        ['title' => 'Training days', 'body' => 'Coached sessions for new and developing shooters to build the fundamentals.'],
                        ['title' => 'SAPRF events', 'body' => 'Host venue for selected South African Precision Rifle Federation matches.'],
                        ['title' => 'Safe, structured range', 'body' => 'Qualified RSOs, verified member list, and clear safety protocols on every relay.'],
                    ],
                ],
                'sort_order' => 30,
            ],
            [
                'key' => 'events_teaser',
                'type' => 'events_teaser',
                'eyebrow' => 'Upcoming',
                'title' => 'Next on the calendar',
                'subtitle' => 'Club matches, SAPRF events, and training days.',
                'cta_label' => 'See all events',
                'cta_url' => '/events',
                'sort_order' => 40,
            ],
            [
                'key' => 'cta',
                'type' => 'cta',
                'title' => 'Ready to shoot with us?',
                'subtitle' => 'Become a member and book your first relay.',
                'cta_label' => 'Membership options',
                'cta_url' => '/membership',
                'sort_order' => 50,
            ],
        ];

        foreach ($sections as $s) {
            HomepageSection::updateOrCreate(['key' => $s['key']], $s);
        }
    }

    protected function seedPages(): void
    {
        $pages = [
            [
                'slug' => 'about',
                'title' => 'About PPRC',
                'subtitle' => 'Precision rifle, the way it should be done.',
                'excerpt' => 'PPRC is a member-run club focused on safe, disciplined, competitive precision rifle shooting in Pretoria.',
                'body' => <<<'HTML'
<p>Pretoria Precision Rifle Club (PPRC) is a community of precision rifle shooters based in Gauteng. We run monthly club matches, host SAPRF events, and provide coached training for members looking to build their skills in precision rifle.</p>
<p>The club is run by an elected committee. Membership numbers are issued on approval and are the same numbers used for match registration, SAPRF affiliation and internal record-keeping.</p>
<h2>What to expect</h2>
<ul>
    <li>Safe, RSO-led relays with structured stages.</li>
    <li>Fair matches — scorecards, placings, and transparent results.</li>
    <li>A welcoming club with space for first-timers and seasoned PRS shooters.</li>
</ul>
HTML,
                'is_published' => true,
                'published_at' => now(),
                'show_in_nav' => true,
                'nav_sort_order' => 10,
            ],
            [
                'slug' => 'membership',
                'title' => 'Membership',
                'subtitle' => 'Join a serious precision rifle community.',
                'excerpt' => 'Full members, spouses, pensioners, and juniors — pick the tier that fits you.',
                'body' => <<<'HTML'
<p>PPRC offers a small, focused set of membership tiers. All new memberships are reviewed and approved by the committee before a membership number is issued.</p>
<ul>
    <li><strong>Full member</strong> — adult shooter, full voting rights.</li>
    <li><strong>Spouse</strong> — partner of an existing full member.</li>
    <li><strong>Pensioner</strong> — reduced rate for members 65+.</li>
    <li><strong>Junior</strong> — under 18, free while a parent is an active member (max 4 juniors per parent).</li>
    <li><strong>Life member</strong> — honorary, awarded by the committee.</li>
</ul>
<p>Payment is by bank EFT (with proof of payment uploaded to the portal) or by card via Paystack.</p>
HTML,
                'is_published' => true,
                'published_at' => now(),
                'show_in_nav' => true,
                'nav_sort_order' => 20,
            ],
            [
                'slug' => 'contact',
                'title' => 'Contact us',
                'subtitle' => 'Match enquiries, membership queries, or range visits.',
                'body' => <<<'HTML'
<p>Have a question about a match, membership, or a visiting-shooter day pass? Use the contact form and the committee will route it to the right person.</p>
HTML,
                'is_published' => true,
                'published_at' => now(),
                'show_in_nav' => true,
                'nav_sort_order' => 90,
            ],
        ];

        foreach ($pages as $p) {
            Page::updateOrCreate(['slug' => $p['slug']], $p);
        }
    }

    protected function seedAnnouncements(): void
    {
        $rows = [
            [
                'slug' => 'welcome-to-the-new-site',
                'title' => 'Welcome to the new PPRC site',
                'excerpt' => 'We\'ve moved to a new member portal. Renew online, upload proof of payment, and see your membership history in one place.',
                'body' => '<p>Over the next few weeks all existing members will have accounts migrated across. Watch your inbox for a password-setup email from the committee.</p>',
                'is_published' => true,
                'is_pinned' => true,
                'published_at' => now(),
            ],
        ];

        foreach ($rows as $r) {
            Announcement::updateOrCreate(['slug' => $r['slug']], $r);
        }
    }

    protected function seedFaqs(): void
    {
        $rows = [
            ['category' => 'membership', 'question' => 'How do I join PPRC?', 'answer' => 'Register an account, pick a membership tier, pay, and upload proof of payment. A committee member will review and approve your application.', 'sort_order' => 10],
            ['category' => 'membership', 'question' => 'Are juniors free?', 'answer' => 'Yes. Juniors under 18 are free while a parent holds an active full membership. A maximum of four juniors per parent applies.', 'sort_order' => 20],
            ['category' => 'matches',    'question' => 'Can non-members shoot club matches?', 'answer' => 'Yes. Non-members pay a higher match fee and must complete a safety briefing before their first relay.', 'sort_order' => 10],
            ['category' => 'matches',    'question' => 'Does PPRC host SAPRF events?', 'answer' => 'Yes. PPRC hosts selected SAPRF matches. SAPRF-affiliated shooters pay the SAPRF-member rate after verification.', 'sort_order' => 20],
            ['category' => 'safety',     'question' => 'What does a typical match day look like?', 'answer' => 'Safety briefing, relay assignments, stages shot in rotation with RSO oversight, and a prize-giving at the end.', 'sort_order' => 10],
        ];

        foreach ($rows as $r) {
            Faq::updateOrCreate(['question' => $r['question']], $r);
        }
    }

    protected function seedExco(): void
    {
        $rows = [
            ['full_name' => 'Chairperson',           'position' => 'Chairperson',           'sort_order' => 10, 'is_current' => true, 'bio' => 'Leads the committee and represents PPRC externally.'],
            ['full_name' => 'Vice Chairperson',      'position' => 'Vice Chairperson',      'sort_order' => 20, 'is_current' => true],
            ['full_name' => 'Treasurer',             'position' => 'Treasurer',             'sort_order' => 30, 'is_current' => true, 'bio' => 'Looks after the club\'s finances, banking and reporting.'],
            ['full_name' => 'Secretary',             'position' => 'Secretary',             'sort_order' => 40, 'is_current' => true],
            ['full_name' => 'Membership Secretary',  'position' => 'Membership Secretary',  'sort_order' => 50, 'is_current' => true, 'bio' => 'Processes applications, approvals and renewals.'],
            ['full_name' => 'Events Coordinator',    'position' => 'Events Coordinator',    'sort_order' => 60, 'is_current' => true],
        ];

        foreach ($rows as $r) {
            ExcoMember::updateOrCreate(['position' => $r['position']], $r);
        }
    }

    protected function seedContactSettings(): void
    {
        SiteSetting::put('contact.email', 'info@pretoriaprc.co.za', ['group' => 'contact', 'label' => 'Contact email']);
        SiteSetting::put('contact.phone', '+27 00 000 0000', ['group' => 'contact', 'label' => 'Contact phone']);
        SiteSetting::put('contact.physical_address', 'Pretoria, Gauteng, South Africa', ['group' => 'contact', 'label' => 'Physical address']);
        SiteSetting::put('contact.social.facebook', '', ['group' => 'contact', 'label' => 'Facebook URL']);
        SiteSetting::put('contact.social.instagram', '', ['group' => 'contact', 'label' => 'Instagram URL']);
        SiteSetting::put('payments.bank.account_name', 'Pretoria Precision Rifle Club', ['group' => 'payments', 'label' => 'Bank account name']);
        SiteSetting::put('payments.bank.bank', 'FNB', ['group' => 'payments', 'label' => 'Bank']);
        SiteSetting::put('payments.bank.account_number', '0000000000', ['group' => 'payments', 'label' => 'Account number']);
        SiteSetting::put('payments.bank.branch_code', '250655', ['group' => 'payments', 'label' => 'Branch code']);
        SiteSetting::put('payments.bank.reference_format', 'PPRC-MEM-{id}', ['group' => 'payments', 'label' => 'Reference format']);
    }
}

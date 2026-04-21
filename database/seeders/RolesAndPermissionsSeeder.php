<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Role model for PPRC — aligned with the elected ExCo positions from the
     * 2026 AGM (Chairman, Vice Chair, Treasurer, Secretary, Marketing, Club
     * Captain) plus operational / support roles.
     *
     *  - developer             Charsley Digital: full access incl. integrations/secrets.
     *  - chairperson           Elected (Chairman): finances (bank details, annual prices), role assignment, destructive actions.
     *  - vice_chair            Elected: deputises for the Chair. Same access as chairperson except role assignment + integration secrets.
     *  - treasurer             Elected: confirm/reject EFT proofs, reconcile Paystack, refunds.
     *  - secretary             Elected: CMS, announcements, Exco page, contact info, enquiries.
     *  - marketing             Elected: announcements, gallery, homepage content, social-facing pages.
     *  - club_captain          Elected: day-to-day ops and match coordination. Reports to Chair / Vice Chair, first point of contact for member concerns.
     *  - membership_secretary  Operational: approve memberships, assign numbers, SAPRF whitelist, member imports.
     *  - match_director        Operational: create/publish events, manage registrations + attendance, upload results.
     *  - admin                 Any committee member: events, results, galleries, shop (day-to-day ops).
     *  - member                Paid member: portal only (no Spatie perms; gated by policies + auth scope).
     *
     * A user can hold multiple roles (Spatie supports it). Chairperson / Vice
     * Chair / developer are the only roles that can reassign other roles.
     */
    /**
     * Obsolete permission names removed from the catalogue. We delete them on
     * each run so reseed cleans them out on environments that had older
     * versions of this seeder applied.
     */
    private const RETIRED_PERMISSIONS = [
        'content.pages.manage',
        'content.home.manage',
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Permission::whereIn('name', self::RETIRED_PERMISSIONS)->delete();

        // Permission catalogue -----------------------------------------------
        $groups = [
            // membership domain
            'members' => [
                'members.view', 'members.update', 'members.notes',
                'members.import', 'members.delete',
                'members.submembers.manage',
                'saprf.whitelist.manage',
            ],
            'memberships' => [
                'memberships.types.manage',
                'memberships.manage',
                'memberships.approve',
                'memberships.number.assign',
                'memberships.renew',
            ],
            // finance
            'payments' => [
                'payments.view',
                'payments.paystack.init',
                'payments.eft.confirm',
                'payments.refund.request',
                'payments.annual_price.change',
                'payments.bank_details.manage',
            ],
            // enquiries / messaging
            'enquiries' => [
                'enquiries.view', 'enquiries.reply', 'enquiries.assign',
                'enquiries.close', 'enquiries.view_internal',
            ],
            // events
            'events' => [
                'events.view', 'events.manage', 'events.publish',
                'events.registrations.manage', 'events.attendance.manage',
            ],
            // results
            'results' => [
                'results.view', 'results.manage', 'results.publish',
            ],
            // galleries
            'galleries' => [
                'galleries.view', 'galleries.manage', 'galleries.publish',
            ],
            // shop
            'shop' => [
                'shop.products.manage', 'shop.orders.view', 'shop.orders.manage',
            ],
            // public content (announcements + FAQs + committee roster + contact
            // info). The generic CMS (pages / homepage_sections) was removed —
            // the public site is hand-crafted Blade backed by real domain data.
            'content' => [
                'content.announcements.manage',
                'content.faqs.manage',
                'content.exco.manage',
                'content.contact.manage',
            ],
            // system
            'settings' => [
                'settings.site.manage',
                'settings.integrations.manage',
                'settings.roles.assign',
            ],
            // user management (login accounts, role assignment)
            'users' => [
                'users.view',
                'users.manage',
            ],
        ];

        foreach ($groups as $list) {
            foreach ($list as $p) {
                Permission::findOrCreate($p, 'web');
            }
        }

        // Role definitions ---------------------------------------------------
        $developer = Role::findOrCreate('developer', 'web');
        $chairperson = Role::findOrCreate('chairperson', 'web');
        $viceChair = Role::findOrCreate('vice_chair', 'web');
        $treasurer = Role::findOrCreate('treasurer', 'web');
        $secretary = Role::findOrCreate('secretary', 'web');
        $marketing = Role::findOrCreate('marketing', 'web');
        $clubCaptain = Role::findOrCreate('club_captain', 'web');
        $membershipSecretary = Role::findOrCreate('membership_secretary', 'web');
        $matchDirector = Role::findOrCreate('match_director', 'web');
        $admin = Role::findOrCreate('admin', 'web');
        Role::findOrCreate('member', 'web');

        // Developer: everything.
        $developer->syncPermissions(Permission::all());

        // Chairperson: everything except pure dev/integration secrets.
        $chairperson->syncPermissions(
            Permission::whereNotIn('name', [
                'settings.integrations.manage',
            ])->get()
        );

        // Vice Chair: deputises for the Chair. Same broad access as chairperson
        // but without the ability to change integration secrets or reassign
        // roles — those are reserved for chairperson + developer so there's
        // always exactly one person with final authority over account access.
        $viceChair->syncPermissions(
            Permission::whereNotIn('name', [
                'settings.integrations.manage',
                'settings.roles.assign',
                'users.manage',
            ])->get()
        );

        // Treasurer: finance-heavy, read-only on most other domains.
        $treasurer->syncPermissions([
            'payments.view', 'payments.paystack.init', 'payments.eft.confirm',
            'payments.refund.request',
            'members.view',
            'memberships.manage', 'memberships.renew',
            'events.view', 'results.view',
            'shop.orders.view',
        ]);

        // Secretary: announcements, FAQs, committee roster, enquiries.
        $secretary->syncPermissions([
            'content.announcements.manage', 'content.faqs.manage',
            'content.exco.manage', 'content.contact.manage',
            'enquiries.view', 'enquiries.reply', 'enquiries.assign',
            'enquiries.close', 'enquiries.view_internal',
            'members.view',
            'events.view', 'results.view', 'galleries.view',
        ]);

        // Marketing: public-facing comms and imagery. Owns announcements,
        // FAQs, committee roster, galleries, and can see events/results to
        // promote them. Deliberately no member PII or finance access.
        $marketing->syncPermissions([
            'content.announcements.manage',
            'content.faqs.manage',
            'content.exco.manage',
            'galleries.view', 'galleries.manage', 'galleries.publish',
            'events.view', 'results.view',
        ]);

        // Club Captain: day-to-day ops and match coordination. Reports to the
        // Chair / Vice Chair and is the first point of contact for member
        // concerns, so they need to see members, handle enquiries, and run
        // the match lifecycle alongside the Match Director.
        $clubCaptain->syncPermissions([
            'members.view',
            'memberships.manage', 'memberships.renew',
            'events.view', 'events.manage', 'events.publish',
            'events.registrations.manage', 'events.attendance.manage',
            'results.view', 'results.manage', 'results.publish',
            'galleries.view', 'galleries.manage', 'galleries.publish',
            'enquiries.view', 'enquiries.reply', 'enquiries.assign',
            'enquiries.close',
            'content.announcements.manage',
        ]);

        // Membership secretary: member lifecycle + SAPRF whitelist.
        $membershipSecretary->syncPermissions([
            'members.view', 'members.update', 'members.notes',
            'members.import', 'members.submembers.manage',
            'saprf.whitelist.manage',
            'memberships.manage', 'memberships.approve',
            'memberships.number.assign', 'memberships.renew',
            'memberships.types.manage',
            'payments.view', 'payments.eft.confirm',
            'enquiries.view', 'enquiries.reply',
        ]);

        // Match director: owns the match lifecycle end-to-end. Can create and
        // publish events, manage registrations and attendance on match day,
        // and upload/publish results. Gets members.view so they can see who
        // entered and attach results to the right member. Deliberately has
        // no access to finance, CMS, shop, or member editing.
        $matchDirector->syncPermissions([
            'members.view',
            'events.view', 'events.manage', 'events.publish',
            'events.registrations.manage', 'events.attendance.manage',
            'results.view', 'results.manage', 'results.publish',
        ]);

        // Admin: day-to-day ops across events/results/galleries/shop/CMS support.
        $admin->syncPermissions([
            'members.view',
            'memberships.manage', 'memberships.renew',
            'events.view', 'events.manage', 'events.publish',
            'events.registrations.manage', 'events.attendance.manage',
            'results.view', 'results.manage', 'results.publish',
            'galleries.view', 'galleries.manage', 'galleries.publish',
            'shop.products.manage', 'shop.orders.view', 'shop.orders.manage',
            'content.announcements.manage', 'content.faqs.manage',
            'enquiries.view', 'enquiries.reply',
        ]);
    }
}

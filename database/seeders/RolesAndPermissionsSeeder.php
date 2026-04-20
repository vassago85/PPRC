<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Role model for PPRC:
     *  - developer          Charsley Digital: full access incl. integrations/secrets.
     *  - chairperson        Elected: finances (bank details, annual prices), role assignment, destructive actions.
     *  - treasurer          Elected: confirm/reject EFT proofs, reconcile Paystack, refunds.
     *  - secretary          Elected: CMS, announcements, Exco page, contact info, enquiries.
     *  - membership_secretary  Elected: approve memberships, assign numbers, SAPRF whitelist, member imports.
     *  - admin              Any committee member: events, results, galleries, shop (day-to-day ops).
     *  - member             Paid member: portal only (no Spatie perms; gated by policies + auth scope).
     *
     * A user can hold multiple roles (Spatie supports it). Chairperson/developer
     * are the only roles that can reassign other roles.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

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
            // public CMS
            'content' => [
                'content.pages.manage',
                'content.home.manage',
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
        ];

        foreach ($groups as $list) {
            foreach ($list as $p) {
                Permission::findOrCreate($p, 'web');
            }
        }

        // Role definitions ---------------------------------------------------
        $developer = Role::findOrCreate('developer', 'web');
        $chairperson = Role::findOrCreate('chairperson', 'web');
        $treasurer = Role::findOrCreate('treasurer', 'web');
        $secretary = Role::findOrCreate('secretary', 'web');
        $membershipSecretary = Role::findOrCreate('membership_secretary', 'web');
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

        // Treasurer: finance-heavy, read-only on most other domains.
        $treasurer->syncPermissions([
            'payments.view', 'payments.paystack.init', 'payments.eft.confirm',
            'payments.refund.request',
            'members.view',
            'memberships.manage', 'memberships.renew',
            'events.view', 'results.view',
            'shop.orders.view',
        ]);

        // Secretary: CMS + announcements + enquiries.
        $secretary->syncPermissions([
            'content.pages.manage', 'content.home.manage',
            'content.announcements.manage', 'content.faqs.manage',
            'content.exco.manage', 'content.contact.manage',
            'enquiries.view', 'enquiries.reply', 'enquiries.assign',
            'enquiries.close', 'enquiries.view_internal',
            'members.view',
            'events.view', 'results.view', 'galleries.view',
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

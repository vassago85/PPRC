<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Role model for PPRC — elected ExCo and operational seats still exist as
     * labels (for clarity, reporting, and free-event-entry rules), but the
     * **permission model is intentionally flat**:
     *
     *  - Every Filament committee role (vice_chair, treasurer, secretary,
     *    marketing, club_captain, membership_secretary, match_director, admin)
     *    receives the same broad admin permission bundle.
     *  - **Chairperson** gets that bundle plus `settings.roles.assign`, which
     *    gates assigning Spatie roles to users and managing the public Exco
     *    roster (see `UserForm` + `ExcoMemberResource`).
     *  - **Developer** keeps full access including `settings.integrations.manage`
     *    (Mailgun / S3 / Paystack secrets in Site settings).
     *  - **member** remains a portal-only role with no admin permissions.
     *
     * Spatie still supports multiple roles per user; only the Chair (and
     * developer) may change role assignments.
     */
    /**
     * Obsolete permission names removed from the catalogue. We delete them on
     * each run so reseed cleans them out on environments that had older
     * versions of this seeder applied.
     */
    private const RETIRED_PERMISSIONS = [
        'content.pages.manage',
        'content.home.manage',
        'content.faqs.manage',
        'content.exco.manage',
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
            // public content (announcements + committee roster + contact info).
            // The generic CMS (pages / homepage_sections) and DB-backed FAQs
            // were removed — the public site is hand-crafted Blade backed by
            // real domain data.
            'content' => [
                'content.announcements.manage',
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

        $developer->syncPermissions(Permission::all());

        // Chair: same as general committee admin, plus assigning Spatie roles /
        // managing the public Exco roster (`settings.roles.assign`). No raw
        // integration secrets — those stay with `developer` only.
        $chairPermissions = Permission::query()
            ->whereNotIn('name', [
                'settings.integrations.manage',
            ])
            ->get();

        $chairperson->syncPermissions($chairPermissions);

        // All other committee admin roles share one flat bundle.
        $generalCommitteeAdmin = Permission::query()
            ->whereNotIn('name', [
                'settings.integrations.manage',
                'settings.roles.assign',
            ])
            ->get();

        foreach ([
            $viceChair,
            $treasurer,
            $secretary,
            $marketing,
            $clubCaptain,
            $membershipSecretary,
            $matchDirector,
            $admin,
        ] as $role) {
            $role->syncPermissions($generalCommitteeAdmin);
        }
    }
}

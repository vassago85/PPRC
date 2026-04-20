<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Permissions are grouped by bounded context. `developer` and `admin` both
     * receive the full set; admins rotate when the PPRC board rotates, so they
     * must be operationally equal. `member` is the default self-service role
     * and holds no Spatie permissions (portal access is gated by route
     * middleware + model policies scoped to auth()->id()).
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'members.view', 'members.update', 'members.notes', 'members.import',
            'memberships.types.manage',
            'memberships.manage', 'memberships.renew', 'memberships.payment.record',
            'payments.paystack.init', 'payments.eft.confirm', 'payments.refund.request',
            'enquiries.view', 'enquiries.reply', 'enquiries.assign', 'enquiries.close', 'enquiries.view_internal',
            'events.view', 'events.manage', 'events.publish',
            'events.registrations.manage', 'events.attendance.manage',
            'results.view', 'results.manage', 'results.publish',
            'galleries.view', 'galleries.manage', 'galleries.publish',
            'shop.products.manage', 'shop.orders.view', 'shop.orders.manage',
            'content.pages.manage', 'content.home.manage',
            'content.announcements.manage', 'content.faqs.manage',
            'content.exco.manage',
            'settings.site.manage',
            'settings.integrations.manage',
        ];

        foreach ($permissions as $p) {
            Permission::findOrCreate($p, 'web');
        }

        $developer = Role::findOrCreate('developer', 'web');
        $admin = Role::findOrCreate('admin', 'web');
        Role::findOrCreate('member', 'web');

        $developer->syncPermissions(Permission::all());
        $admin->syncPermissions(Permission::all());
    }
}

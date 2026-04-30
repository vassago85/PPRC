<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Re-introduce `content.exco.manage` and grant it to every committee admin
 * role. Editing the public ExCo roster is a content task (it's what shows on
 * /about), so it should not be locked behind `settings.roles.assign`.
 *
 * The seeder is the source of truth; this migration just brings live envs
 * into line without requiring a full re-seed.
 */
return new class extends Migration
{
    public function up(): void
    {
        $perm = Permission::findOrCreate('content.exco.manage', 'web');

        $roles = [
            'developer',
            'chairperson',
            'vice_chair',
            'treasurer',
            'secretary',
            'marketing',
            'club_captain',
            'membership_secretary',
            'match_director',
            'admin',
        ];

        foreach ($roles as $name) {
            $role = Role::where('name', $name)->where('guard_name', 'web')->first();
            if ($role && ! $role->hasPermissionTo($perm)) {
                $role->givePermissionTo($perm);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        $perm = Permission::where('name', 'content.exco.manage')->first();
        if ($perm) {
            $perm->delete();
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};

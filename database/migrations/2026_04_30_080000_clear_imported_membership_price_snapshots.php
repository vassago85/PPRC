<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The SSMM importer copied each MembershipType's *current* price_cents into
 * price_cents_snapshot for every imported row. The snapshot is meant to be
 * the price the member actually paid — but the SSMM CSV doesn't include
 * payment amounts, and the type's price_cents has since been corrected,
 * so those snapshots are wrong (they show inflated "R 1,500" etc. for
 * legacy members who never paid that).
 *
 * Set the snapshot to NULL for memberships whose member came in via import
 * (users.created_via_import = true). The price column then renders as
 * "— (current R xxx)" — i.e. unknown historical paid price, current
 * type price for reference. Future memberships record the real amount.
 */
return new class extends Migration
{
    public function up(): void
    {
        $importedMemberIds = DB::table('users')
            ->join('members', 'members.user_id', '=', 'users.id')
            ->where('users.created_via_import', true)
            ->pluck('members.id');

        if ($importedMemberIds->isEmpty()) {
            return;
        }

        DB::table('memberships')
            ->whereIn('member_id', $importedMemberIds)
            ->update(['price_cents_snapshot' => null]);
    }

    public function down(): void
    {
        // Cannot restore the (incorrect) snapshot data; leaving as null.
    }
};

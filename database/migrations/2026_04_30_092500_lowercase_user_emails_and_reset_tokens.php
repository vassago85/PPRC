<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Password reset tokens are keyed by the user's email column verbatim, but
 * Fortify lowercases the email on form submit. Mixed-case rows in
 * users.email therefore caused valid reset links to fail with "invalid
 * token" because the broker couldn't find the matching row.
 *
 * This pass:
 *   1. Lowercases every users.email in place (skipping any row that would
 *      collide with another lowercased address — left for manual review).
 *   2. Truncates password_reset_tokens since any in-flight token is now
 *      keyed by stale (mixed-case) email and would never match anyway. The
 *      affected user just clicks "forgot password" again.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')
            ->select(['id', 'email'])
            ->whereNotNull('email')
            ->orderBy('id')
            ->each(function ($user) {
                $lower = strtolower(trim((string) $user->email));
                if ($lower === $user->email) {
                    return;
                }

                $collision = DB::table('users')
                    ->whereRaw('LOWER(email) = ?', [$lower])
                    ->where('id', '!=', $user->id)
                    ->exists();

                if ($collision) {
                    // Two rows would end up identical. Leave both as-is so a
                    // committee admin can resolve the duplicate manually.
                    return;
                }

                DB::table('users')->where('id', $user->id)->update(['email' => $lower]);
            });

        if (Schema::hasTable('password_reset_tokens')) {
            DB::table('password_reset_tokens')->delete();
        }
    }

    public function down(): void
    {
        // Cannot recover original casing. Leave normalised.
    }
};

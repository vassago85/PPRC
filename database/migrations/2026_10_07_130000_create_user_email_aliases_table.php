<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Additional login email addresses tied to a single user. When two member
 * records get merged, the loser's email lands here so the surviving user can
 * sign in and reset their password with either address. The primary address
 * stays on users.email — these are secondary / alias addresses only.
 *
 * must_pick_primary_email_at on users lets us nudge the user, on their next
 * login after a merge, to confirm which address should be their primary.
 * Picking swaps the chosen alias with users.email and clears the flag.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_email_aliases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();
            // Email is unique across the aliases AND across users.email in app
            // code (AliasAwareUserProvider / the merge service enforce it) so
            // a login lookup can never resolve to two accounts.
            $table->string('email')->unique();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->index(['user_id']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('must_pick_primary_email_at')->nullable()->after('email_verified_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('must_pick_primary_email_at');
        });

        Schema::dropIfExists('user_email_aliases');
    }
};

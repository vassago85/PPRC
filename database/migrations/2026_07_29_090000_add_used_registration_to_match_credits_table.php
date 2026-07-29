<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Records which entry actually consumed a credit. `used_event_id` only said
 * which match it went to, which is not enough to reconcile a transfer: two
 * shooters can each spend a credit on the same match, and a credit can be
 * spent on an entry that was already on the list.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('match_credits', function (Blueprint $table) {
            $table->foreignId('used_registration_id')
                ->nullable()
                ->after('used_event_id')
                ->constrained('event_registrations')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('match_credits', function (Blueprint $table) {
            $table->dropConstrainedForeignId('used_registration_id');
        });
    }
};

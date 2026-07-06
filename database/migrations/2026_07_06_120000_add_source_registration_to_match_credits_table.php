<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Links a match credit back to the specific entry it was raised from (e.g. a
 * paid no-show on the match report). Lets the report show "credit logged" and
 * prevents logging the same no-show twice.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('match_credits', function (Blueprint $table) {
            $table->foreignId('source_registration_id')
                ->nullable()
                ->after('source_event_id')
                ->constrained('event_registrations')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('match_credits', function (Blueprint $table) {
            $table->dropConstrainedForeignId('source_registration_id');
        });
    }
};

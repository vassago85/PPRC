<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-entry fee override on event_registrations.
 *
 * null = charge the event price_cents (default behaviour)
 * 0    = waived (typical for ExCo / other committee positions — they don't
 *        pay for PPRC events per the 2026 AGM)
 * >0   = a custom per-entry price (e.g. a specific member discount)
 *
 * Kept as a nullable override column (instead of a boolean "is_waived") so
 * we can support custom pricing later without another migration.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_registrations', function (Blueprint $table) {
            $table->unsignedInteger('fee_cents')->nullable()->after('firing_order');
        });
    }

    public function down(): void
    {
        Schema::table('event_registrations', function (Blueprint $table) {
            $table->dropColumn('fee_cents');
        });
    }
};

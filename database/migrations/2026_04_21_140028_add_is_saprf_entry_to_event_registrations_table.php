<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SAPRF-sanctioned shooters pay through the SAPRF website, not PPRC. We still
 * want them loaded onto the match (for squadding, attendance, results) but we
 * don't charge them an entry fee on our side. Flag those rows so the UI can
 * badge them and the fee resolver can skip PPRC billing.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_registrations', function (Blueprint $table) {
            $table->boolean('is_saprf_entry')->default(false)->after('fee_cents');
        });
    }

    public function down(): void
    {
        Schema::table('event_registrations', function (Blueprint $table) {
            $table->dropColumn('is_saprf_entry');
        });
    }
};

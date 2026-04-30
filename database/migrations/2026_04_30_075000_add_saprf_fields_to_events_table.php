<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SAPRF-sanctioned matches: PPRC hosts the day but registration & fees go
 * through the SAPRF portal. Flag the match so:
 *   - the public match page shows a SAPRF banner + registration link
 *   - non-PPRC SAPRF members can sign up here without paying PPRC
 *     (their entry is_saprf_entry=true, fee=0, since they pay SAPRF directly)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->boolean('is_saprf_match')->default(false)->after('non_member_price_cents');
            $table->string('saprf_url')->nullable()->after('is_saprf_match');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn(['is_saprf_match', 'saprf_url']);
        });
    }
};

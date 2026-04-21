<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Events get:
 *  - banner_path: optional hero image shown on the match page (stored on s3).
 *  - member_price_cents / non_member_price_cents: PPRC normally charges two
 *    different entry fees per match. The legacy single price_cents column
 *    stays for back-compat but new matches should use the tiered fields.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->string('banner_path')->nullable()->after('description');
            $table->unsignedInteger('member_price_cents')->nullable()->after('price_cents');
            $table->unsignedInteger('non_member_price_cents')->nullable()->after('member_price_cents');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn(['banner_path', 'member_price_cents', 'non_member_price_cents']);
        });
    }
};

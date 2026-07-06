<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * How a match entry's fee was paid — EFT into the club account, or cash handed
 * to the match director on the day. This drives the payout report: EFT money
 * sits with the club (owed to the director), cash is already in the director's
 * hands. Null is treated as EFT (the default assumption for existing entries).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_registrations', function (Blueprint $table) {
            $table->string('payment_method')->nullable()->after('paid_at');
        });
    }

    public function down(): void
    {
        Schema::table('event_registrations', function (Blueprint $table) {
            $table->dropColumn('payment_method');
        });
    }
};

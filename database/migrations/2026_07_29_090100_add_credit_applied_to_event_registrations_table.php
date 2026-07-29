<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * How much of this entry's fee was settled with a match credit rather than new
 * money.
 *
 * `paid_at` is all-or-nothing, so a credit that only part-covers a dearer match
 * had nowhere to live: the entry would either look fully paid or be chased for
 * the whole fee instead of the shortfall. Holding the credited portion here
 * keeps two different questions separable — what the shooter still owes
 * (fee minus this), and what the match director is owed for the head (the full
 * fee, since the club is holding the credited part either way).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_registrations', function (Blueprint $table) {
            $table->integer('credit_applied_cents')
                ->default(0)
                ->after('fee_cents');
        });
    }

    public function down(): void
    {
        Schema::table('event_registrations', function (Blueprint $table) {
            $table->dropColumn('credit_applied_cents');
        });
    }
};

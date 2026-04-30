<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `price_cents_snapshot` was originally NOT NULL because every new membership
 * records the exact price the member paid. Imported (legacy SSMM) memberships,
 * however, have no known paid amount — the CSV doesn't include payment data —
 * so we now allow NULL to mean "historical price unknown" and the UI falls
 * back to showing the current type price for reference.
 *
 * Must run BEFORE 2026_04_30_080000_clear_imported_membership_price_snapshots
 * (which sets imported snapshots to NULL).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('memberships', function (Blueprint $table) {
            $table->unsignedInteger('price_cents_snapshot')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Reverting to NOT NULL would fail for any rows already nulled out by
        // the subsequent clear migration. Leave as nullable.
    }
};

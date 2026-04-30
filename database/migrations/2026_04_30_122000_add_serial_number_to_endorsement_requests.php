<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Capture serial numbers for both the action (receiver) and the barrel.
 *
 * A complete rifle endorsement may need both serials, while a component
 * endorsement might only need one (the action OR the barrel). The form
 * enforces "at least one"; both columns remain nullable in the database
 * so we never lose data if a member submits only one.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('endorsement_requests', function (Blueprint $table) {
            if (! Schema::hasColumn('endorsement_requests', 'action_serial_number')) {
                $table->string('action_serial_number', 80)->nullable()->after('component_type');
            }
            if (! Schema::hasColumn('endorsement_requests', 'barrel_serial_number')) {
                $table->string('barrel_serial_number', 80)->nullable()->after('action_serial_number');
            }
        });
    }

    public function down(): void
    {
        Schema::table('endorsement_requests', function (Blueprint $table) {
            $table->dropColumn(['action_serial_number', 'barrel_serial_number']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Captures the structured fields the endorsement letter actually needs to
 * be useful for a SAPS license application:
 *   - members.id_number   : the applicant's RSA ID (per-person, not per-request)
 *   - endorsement_requests.item_type      : 'rifle' | 'component'
 *   - endorsement_requests.make           : Make / brand (e.g. "Eagle Barrels")
 *   - endorsement_requests.calibre        : e.g. "6mm Dasher"
 *   - endorsement_requests.component_type : present when item_type=component
 *                                           (e.g. "Barrel", "Action", "Stock")
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            if (! Schema::hasColumn('members', 'id_number')) {
                $table->string('id_number', 32)->nullable()->after('date_of_birth');
            }
        });

        Schema::table('endorsement_requests', function (Blueprint $table) {
            if (! Schema::hasColumn('endorsement_requests', 'item_type')) {
                $table->string('item_type', 20)->default('rifle')->after('reason');
            }
            if (! Schema::hasColumn('endorsement_requests', 'make')) {
                $table->string('make', 120)->nullable()->after('firearm_type');
            }
            if (! Schema::hasColumn('endorsement_requests', 'calibre')) {
                $table->string('calibre', 60)->nullable()->after('make');
            }
            if (! Schema::hasColumn('endorsement_requests', 'component_type')) {
                $table->string('component_type', 60)->nullable()->after('calibre');
            }
        });
    }

    public function down(): void
    {
        Schema::table('endorsement_requests', function (Blueprint $table) {
            $table->dropColumn(['item_type', 'make', 'calibre', 'component_type']);
        });

        Schema::table('members', function (Blueprint $table) {
            $table->dropColumn('id_number');
        });
    }
};

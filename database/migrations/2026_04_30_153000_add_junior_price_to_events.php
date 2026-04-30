<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Junior shooters pay a reduced fee at most clubs. Allow a separate
 * junior price on each event; null means "use the member price".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            if (! Schema::hasColumn('events', 'junior_price_cents')) {
                $table->unsignedInteger('junior_price_cents')->nullable()->after('non_member_price_cents');
            }
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('junior_price_cents');
        });
    }
};

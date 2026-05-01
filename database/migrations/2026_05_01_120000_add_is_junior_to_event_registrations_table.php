<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Junior pricing already kicks in automatically for logged-in members whose
 * membership type or age says they're a junior. Guests don't have a member
 * record to detect from, so we let them tick "I am a junior" on the public
 * registration form. This flag also lets admins mark a registration as a
 * junior after the fact (e.g. a parent signed their kid up as themselves).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_registrations', function (Blueprint $table) {
            $table->boolean('is_junior')->default(false)->after('is_saprf_entry');
        });
    }

    public function down(): void
    {
        Schema::table('event_registrations', function (Blueprint $table) {
            $table->dropColumn('is_junior');
        });
    }
};

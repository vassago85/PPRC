<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * An optional "registrations open at" moment on events. Lets admins publish a
 * match ahead of time (so it appears in the calendar) but keep the entry form
 * suppressed until the window actually opens. The public site uses this to
 * distinguish "Not yet open" from "Closed", which previously looked identical.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->timestamp('registrations_open_at')
                ->nullable()
                ->after('registrations_close_at');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('registrations_open_at');
        });
    }
};

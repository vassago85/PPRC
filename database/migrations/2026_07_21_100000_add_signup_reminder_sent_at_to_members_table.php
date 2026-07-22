<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            // When we last nudged an incomplete signup to finish. Drives the
            // "nudge once, then archive after a grace period" cleanup.
            $table->timestamp('signup_reminder_sent_at')
                ->nullable()
                ->after('last_renewal_reminder_at');
        });
    }

    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropColumn('signup_reminder_sent_at');
        });
    }
};

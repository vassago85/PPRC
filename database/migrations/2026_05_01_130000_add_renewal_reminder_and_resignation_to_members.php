<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Two unrelated-but-touching-the-same-table additions:
 *
 *  - last_renewal_reminder_at: throttles the renewal reminder cron so we
 *    don't email the same person every day.
 *  - resigned_at + resignation_reason: persisted when a member self-cancels
 *    via the link in their renewal reminder so the membership secretary
 *    has a paper trail and we never accidentally re-mail them.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->timestamp('last_renewal_reminder_at')->nullable()->after('expiry_date');
            $table->timestamp('resigned_at')->nullable()->after('last_renewal_reminder_at');
            $table->text('resignation_reason')->nullable()->after('resigned_at');
        });
    }

    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropColumn(['last_renewal_reminder_at', 'resigned_at', 'resignation_reason']);
        });
    }
};

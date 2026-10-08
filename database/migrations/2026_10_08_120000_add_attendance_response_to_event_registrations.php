<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Track a shooter's response to the "are you still shooting?" email the admin
 * sends out a few days before each match. The email carries three signed-URL
 * buttons (still shooting / unsure / withdraw) that write the response here.
 *
 * `attendance_check_sent_at` mirrors `whatsapp_link_sent_at` so the admin can
 * re-run the send action to top up late signups without re-emailing everyone.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_registrations', function (Blueprint $table) {
            $table->string('attendance_response', 32)->nullable()->after('whatsapp_link_sent_at');
            $table->timestamp('attendance_responded_at')->nullable()->after('attendance_response');
            $table->timestamp('attendance_check_sent_at')->nullable()->after('attendance_responded_at');
        });
    }

    public function down(): void
    {
        Schema::table('event_registrations', function (Blueprint $table) {
            $table->dropColumn([
                'attendance_response',
                'attendance_responded_at',
                'attendance_check_sent_at',
            ]);
        });
    }
};

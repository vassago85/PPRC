<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Store a WhatsApp group link on each match so the admin can send it out to
 * entrants with one click, and stamp each entry when it has already received
 * the link so late signups can be topped up without emailing everyone again.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->string('whatsapp_group_url', 500)->nullable()->after('saprf_url');
        });

        Schema::table('event_registrations', function (Blueprint $table) {
            $table->timestamp('whatsapp_link_sent_at')->nullable()->after('proof_submitted_at');
        });
    }

    public function down(): void
    {
        Schema::table('event_registrations', function (Blueprint $table) {
            $table->dropColumn('whatsapp_link_sent_at');
        });

        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('whatsapp_group_url');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lets members upload proof of payment for a match entry from their portal.
 * The proof lands on the media disk; a committee member reviews it and then
 * confirms receipt with the existing "mark paid" action.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_registrations', function (Blueprint $table) {
            $table->string('payment_proof_path')->nullable()->after('marked_paid_by_user_id');
            $table->timestamp('proof_submitted_at')->nullable()->after('payment_proof_path');
        });
    }

    public function down(): void
    {
        Schema::table('event_registrations', function (Blueprint $table) {
            $table->dropColumn(['payment_proof_path', 'proof_submitted_at']);
        });
    }
};

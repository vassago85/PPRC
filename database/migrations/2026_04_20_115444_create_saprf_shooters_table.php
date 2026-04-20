<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Admin-maintained whitelist of SAPRF shooters. The MD (or any admin)
 * uploads this list periodically; when a user enters their SAPRF
 * membership number on their profile and it matches a row here, they
 * automatically qualify for SAPRF-tier pricing on SAPRF-hosted events.
 *
 * This avoids per-user verification flows entirely: verification lives
 * in the whitelist, not on the user record.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saprf_shooters', function (Blueprint $table) {
            $table->id();
            $table->string('membership_number')->unique();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('email')->nullable()->index();
            $table->date('verified_on')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('imported_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('imported_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saprf_shooters');
    }
};

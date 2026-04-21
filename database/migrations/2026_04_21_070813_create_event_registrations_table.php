<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Event registrations (entries). A member can enter a match; guests (non-members)
 * may also be recorded with just name + email + phone so the match director can
 * manage a full squad even during visitor/open matches. Event-level payment is
 * intentionally NOT handled here — event payments go through their own ledger
 * when that feature lands (separate from membership_payments).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_registrations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('event_id')
                ->constrained('events')
                ->cascadeOnDelete();

            $table->foreignId('member_id')
                ->nullable()
                ->constrained('members')
                ->nullOnDelete();

            $table->string('guest_name')->nullable();
            $table->string('guest_email')->nullable();
            $table->string('guest_phone', 32)->nullable();

            $table->unsignedSmallInteger('squad_number')->nullable();
            $table->unsignedSmallInteger('firing_order')->nullable();

            $table->string('status')->default('registered')->index();
            $table->boolean('attended')->default(false)->index();

            $table->text('notes')->nullable();

            $table->timestamp('registered_at')->nullable();
            $table->foreignId('checked_in_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('checked_in_at')->nullable();

            $table->timestamps();

            $table->unique(['event_id', 'member_id']);
            $table->index(['event_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_registrations');
    }
};

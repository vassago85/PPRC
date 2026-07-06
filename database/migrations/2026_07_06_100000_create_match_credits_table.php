<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A simple ledger of match-fee credits the club owes to shooters — typically
 * when someone paid but couldn't shoot, so their fee is held for a future
 * match instead of being refunded. Credits can belong to a member or a guest
 * (tracked by name/email). No expiry: a credit stays available until an admin
 * marks it used.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('match_credits', function (Blueprint $table) {
            $table->id();

            $table->foreignId('member_id')
                ->nullable()
                ->constrained('members')
                ->nullOnDelete();

            // Snapshot of who the credit is for — always populated so the
            // ledger stays readable even for guests or if a member is removed.
            $table->string('payee_name')->nullable();
            $table->string('payee_email')->nullable();

            $table->integer('amount_cents');
            $table->string('reason')->nullable();

            $table->foreignId('source_event_id')
                ->nullable()
                ->constrained('events')
                ->nullOnDelete();

            $table->string('status')->default('available')->index();

            $table->foreignId('used_event_id')
                ->nullable()
                ->constrained('events')
                ->nullOnDelete();
            $table->timestamp('used_at')->nullable();

            $table->foreignId('created_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('match_credits');
    }
};

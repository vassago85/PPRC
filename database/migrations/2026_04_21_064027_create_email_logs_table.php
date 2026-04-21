<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Single source of truth for every outbound email the app sends: contact-form
 * replies, member welcome/invite mails, future bulk communications, etc.
 *
 * Rows are written automatically by an event listener on
 * Illuminate\Mail\Events\MessageSent so we never have to remember to log
 * anything at the call site. Failures are written explicitly by sender
 * commands when Mail::send throws.
 *
 * We deliberately index on (to_email, mailable_class, sent_at) because the
 * send-welcome flow needs fast idempotency checks ("has this address already
 * received a welcome?").
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('to_email')->index();
            $table->string('to_name')->nullable();

            $table->string('from_email')->nullable();
            $table->string('from_name')->nullable();

            $table->string('subject')->nullable();
            $table->string('mailable_class')->nullable()->index();

            $table->string('status')->default('sent')->index();
            $table->text('error')->nullable();

            $table->jsonb('context')->nullable();

            $table->string('message_id')->nullable()->index();
            $table->timestamp('sent_at')->nullable()->index();

            $table->timestamps();

            $table->index(['to_email', 'mailable_class', 'sent_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_logs');
    }
};

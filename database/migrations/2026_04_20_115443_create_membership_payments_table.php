<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Payments for memberships ONLY. Event registration payments live in a
 * parallel `registration_payments` table in Phase 3 — we don't overload
 * a single payments table across domains because the reconciliation and
 * admin workflows are different.
 *
 * `meta` stores the raw Paystack payload (for paystack) or the operator's
 * free-form notes + uploaded-proof path (for manual_eft).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('membership_payments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('membership_id')
                ->constrained('memberships')
                ->cascadeOnDelete();

            $table->string('provider');
            $table->string('status')->default('pending')->index();

            $table->unsignedInteger('amount_cents');
            $table->string('currency', 3)->default('ZAR');

            $table->string('reference')->nullable()->unique();
            $table->string('paystack_reference')->nullable()->unique();

            $table->string('proof_path')->nullable();

            $table->jsonb('meta')->nullable();

            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->foreignId('confirmed_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('membership_payments');
    }
};

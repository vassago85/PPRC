<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `memberships` is one row per issued period (annual, renewal, etc.).
 * `price_cents_snapshot` is the locked-in price at the time of sale so
 * next year's annual price change doesn't mutate historic invoices.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('memberships', function (Blueprint $table) {
            $table->id();

            $table->foreignId('member_id')
                ->constrained('members')
                ->cascadeOnDelete();

            $table->foreignId('membership_type_id')
                ->constrained('membership_types')
                ->restrictOnDelete();

            $table->date('period_start');
            $table->date('period_end');

            $table->string('status')->default('pending_payment')->index();

            $table->unsignedInteger('price_cents_snapshot');
            $table->string('membership_type_slug_snapshot');
            $table->string('membership_type_name_snapshot');

            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->text('admin_notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['member_id', 'status']);
            $table->index(['period_start', 'period_end']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('memberships');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lightweight payment tracking for match entries. Entry fees are paid by EFT
 * into the club account; a committee member marks the entry as paid once the
 * deposit reflects. We only need a "paid" timestamp + who confirmed it — the
 * amount owed is still resolved dynamically via effectiveFeeCents().
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_registrations', function (Blueprint $table) {
            $table->timestamp('paid_at')->nullable()->after('attended');
            $table->foreignId('marked_paid_by_user_id')
                ->nullable()
                ->after('paid_at')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('event_registrations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('marked_paid_by_user_id');
            $table->dropColumn('paid_at');
        });
    }
};

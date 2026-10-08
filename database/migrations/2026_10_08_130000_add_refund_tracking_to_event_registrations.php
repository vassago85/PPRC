<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Track refunds issued against paid match entries. When an admin uses the
 * "Withdraw & refund" row action, the entry becomes Cancelled (which already
 * excludes it from EFT base calculations) and these columns capture the
 * refund so it shows up on the match director's cash-up slip as context —
 * "yes, this shooter paid and we gave them R450 back on 8 Oct".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_registrations', function (Blueprint $table) {
            $table->timestamp('refunded_at')->nullable()->after('attendance_check_sent_at');
            $table->integer('refunded_amount_cents')->nullable()->after('refunded_at');
            $table->string('refunded_method', 16)->nullable()->after('refunded_amount_cents');
            $table->text('refunded_note')->nullable()->after('refunded_method');

            $table->foreignId('refunded_by_user_id')
                ->nullable()
                ->after('refunded_note')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('event_registrations', function (Blueprint $table) {
            $table->dropForeign(['refunded_by_user_id']);
            $table->dropColumn([
                'refunded_at',
                'refunded_amount_cents',
                'refunded_method',
                'refunded_note',
                'refunded_by_user_id',
            ]);
        });
    }
};

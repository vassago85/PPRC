<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Richer bookkeeping on match expenses so the admin can categorise what the
 * money went on (trophies / medals / prize money / range fees) and link the
 * payee to a real user when the person being reimbursed is a club member —
 * the typical case being "I bought the trophies, pay me back off the top".
 *
 * `payee_name` stays as the human-readable string shown on the cash-up slip:
 * it's auto-synced to the user's name when a user is picked, and it still
 * takes free-text when the payee is an outside supplier with no account.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('match_expenses', function (Blueprint $table) {
            $table->string('category', 32)->nullable()->after('description');

            $table->foreignId('payee_user_id')
                ->nullable()
                ->after('payee_name')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('match_expenses', function (Blueprint $table) {
            $table->dropForeign(['payee_user_id']);
            $table->dropColumn(['category', 'payee_user_id']);
        });
    }
};

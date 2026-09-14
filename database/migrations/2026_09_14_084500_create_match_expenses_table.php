<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Free-form line items the club has to pay out of a match's takings — e.g.
 * the range fee owed to the venue, or petrol money for a stage crew. These
 * sit alongside the match director payout so the match report is a complete
 * "what does the club still have to pay" picture.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('match_expenses', function (Blueprint $table) {
            $table->id();

            $table->foreignId('event_id')
                ->constrained('events')
                ->cascadeOnDelete();

            $table->string('description', 200);
            $table->string('payee_name', 150)->nullable();
            $table->integer('amount_cents');
            $table->text('notes')->nullable();

            $table->foreignId('created_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('match_expenses');
    }
};

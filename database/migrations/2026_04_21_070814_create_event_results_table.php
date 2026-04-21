<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Event results. One row per shooter per event (typically also per event_registration,
 * but a result can exist without a registration for walk-ups backfilled after the
 * match). Results can be bulk-imported from CSV via the Filament ResultResource.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_results', function (Blueprint $table) {
            $table->id();

            $table->foreignId('event_id')
                ->constrained('events')
                ->cascadeOnDelete();

            $table->foreignId('event_registration_id')
                ->nullable()
                ->constrained('event_registrations')
                ->nullOnDelete();

            $table->foreignId('member_id')
                ->nullable()
                ->constrained('members')
                ->nullOnDelete();

            // Denormalised shooter display name so historical results keep
            // rendering even if we later delete a member record.
            $table->string('shooter_name');
            $table->string('division')->nullable();
            $table->string('class')->nullable();

            $table->unsignedSmallInteger('rank')->nullable()->index();
            $table->unsignedSmallInteger('score_hits')->nullable();
            $table->unsignedSmallInteger('score_possible')->nullable();
            $table->unsignedSmallInteger('score_points')->nullable();
            $table->decimal('score_percentage', 5, 2)->nullable();
            $table->unsignedMediumInteger('score_time_ms')->nullable();

            $table->boolean('dnf')->default(false);
            $table->boolean('dq')->default(false);

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['event_id', 'rank']);
            $table->index(['event_id', 'division', 'rank']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_results');
    }
};

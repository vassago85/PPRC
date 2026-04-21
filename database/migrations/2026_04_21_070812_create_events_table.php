<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Match events. Every event has ONE format (a PR22 match is separate from the
 * PRS match next door, even on the same day at the same venue) so we can run
 * discipline-specific leaderboards.
 *
 * Status lifecycle:
 *   draft       - match_director is building it, not public yet
 *   published   - visible on the public site, registrations may be open
 *   completed   - match is over; results are being uploaded / published
 *   cancelled   - abandoned; kept for historical reference
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();

            $table->foreignId('match_format_id')
                ->constrained('match_formats')
                ->restrictOnDelete();

            $table->string('slug')->unique();
            $table->string('title');
            $table->text('summary')->nullable();
            $table->longText('description')->nullable();

            $table->date('start_date')->index();
            $table->time('start_time')->nullable();
            $table->date('end_date')->nullable();

            $table->string('location_name')->nullable();
            $table->string('location_address')->nullable();
            $table->decimal('location_lat', 10, 7)->nullable();
            $table->decimal('location_lng', 10, 7)->nullable();

            $table->unsignedInteger('price_cents')->nullable();
            $table->unsignedSmallInteger('max_entries')->nullable();
            $table->unsignedSmallInteger('round_count')->nullable();

            $table->boolean('registrations_open')->default(false)->index();
            $table->timestamp('registrations_close_at')->nullable();

            $table->string('status')->default('draft')->index();

            $table->foreignId('match_director_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('created_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('published_at')->nullable()->index();
            $table->timestamp('results_published_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'start_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};

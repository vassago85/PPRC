<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Two related additions for richer match briefings:
 *
 *  - events: structured stage spec (count, shots-per-stage for the full vs
 *    club course, time limit, which stage is the tie-breaker). The two
 *    legacy round_count / club_round_count columns stay around for
 *    summaries; the new fields drive the per-stage info on the public page
 *    and let us derive accurate round totals if they're left blank.
 *
 *  - event_registrations: course flag ("full" or "club") so a combined
 *    match — where some shooters do the SAPRF provincial 60-round course
 *    and others do the PPRC 42-round club course — can squad both groups
 *    cleanly and show the right round count next to each name.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->unsignedSmallInteger('stage_count')->nullable()->after('club_round_count');
            $table->unsignedSmallInteger('shots_per_stage_full')->nullable()->after('stage_count');
            $table->unsignedSmallInteger('shots_per_stage_club')->nullable()->after('shots_per_stage_full');
            $table->unsignedSmallInteger('stage_time_seconds')->nullable()->after('shots_per_stage_club');
            $table->unsignedSmallInteger('tiebreaker_stage_number')->nullable()->after('stage_time_seconds');
        });

        Schema::table('event_registrations', function (Blueprint $table) {
            // 'full' (provincial / SAPRF course) or 'club' (shorter PPRC course).
            // Nullable so it stays optional on matches that only run one course.
            $table->string('course', 8)->nullable()->after('category');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn([
                'stage_count',
                'shots_per_stage_full',
                'shots_per_stage_club',
                'stage_time_seconds',
                'tiebreaker_stage_number',
            ]);
        });

        Schema::table('event_registrations', function (Blueprint $table) {
            $table->dropColumn('course');
        });
    }
};

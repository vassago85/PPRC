<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Track when the refund money actually left the club, separately from when the
 * refund was *recorded* (`refunded_at`). The two differ for EFT refunds, which
 * are batched into the weekly match-payment run and so stay as "owing" on the
 * cash-up slip for days after the shooter withdrew.
 *
 * Backfill rule: cash refunds have always come out of the match-day float the
 * same moment they were recorded, so for any legacy cash row we stamp paid at
 * the same time as recorded. EFT rows made before this column existed get
 * stamped paid too — the alternative is pretending every historical EFT refund
 * is suddenly "owing", which would spook the next match director to open the
 * slip. Only *new* EFT refunds, taken via the updated action, start out owing.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_registrations', function (Blueprint $table) {
            $table->timestamp('refund_paid_at')->nullable()->after('refunded_by_user_id');
        });

        // Treat every pre-existing refund as already paid out so the cash-up
        // slip never retroactively complains about owing money for a match
        // that is already closed.
        DB::statement('UPDATE event_registrations SET refund_paid_at = refunded_at WHERE refunded_at IS NOT NULL AND refund_paid_at IS NULL');
    }

    public function down(): void
    {
        Schema::table('event_registrations', function (Blueprint $table) {
            $table->dropColumn('refund_paid_at');
        });
    }
};

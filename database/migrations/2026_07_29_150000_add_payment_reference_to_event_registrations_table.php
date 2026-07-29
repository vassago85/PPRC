<?php

use App\Support\PaymentReferencePrefix;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Store the match entry's EFT reference instead of computing it on every read.
 *
 * It was derived from the row's id, which made it look stable but meant that
 * changing the format retroactively rewrote the reference on every entry that
 * already existed — including ones whose shooter had already been emailed the
 * old one, and was reading it back off the portal. A reference is a promise made
 * to somebody, so it has to be written down at the moment it is made.
 * membership_payments.reference has always worked this way; this brings match
 * entries in line.
 *
 * Every existing row is therefore backfilled with the LONG form
 * (PREFIX-M{match}-{entry}), because that is what those shooters were actually
 * given. New entries get the short PREFIX-M{entry} form, which survives a bank
 * stripping the separators. PaymentReferenceResolver already reads both.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_registrations', function (Blueprint $table) {
            $table->string('payment_reference')->nullable()->unique()->after('paid_at');
        });

        $this->backfill();
    }

    public function down(): void
    {
        Schema::table('event_registrations', function (Blueprint $table) {
            $table->dropUnique(['payment_reference']);
            $table->dropColumn('payment_reference');
        });
    }

    /**
     * Restore the reference each existing entry was quoted, so the admin table,
     * the portal and the shooter's inbox agree again.
     */
    protected function backfill(): void
    {
        $prefix = PaymentReferencePrefix::get();

        DB::table('event_registrations')
            ->select('id', 'event_id')
            ->whereNull('payment_reference')
            ->orderBy('id')
            ->chunkById(500, function ($registrations) use ($prefix) {
                foreach ($registrations as $registration) {
                    DB::table('event_registrations')
                        ->where('id', $registration->id)
                        ->update([
                            'payment_reference' => sprintf(
                                '%s-M%d-%d',
                                $prefix,
                                $registration->event_id,
                                $registration->id,
                            ),
                        ]);
                }
            });
    }
};

<?php

use App\Enums\MemberLifecycle;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Introduces the canonical member lifecycle alongside the old eight-value
 * `status` column, and backfills it.
 *
 * Additive on purpose. `status` keeps being written (the model mirrors
 * lifecycle back into it) so this is reversible and so nothing still reading
 * the old column breaks while the change is verified. Dropping `status` is a
 * separate, later migration.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->string('lifecycle')->default(MemberLifecycle::Pending->value)->index()->after('status');
            $table->timestamp('suspended_at')->nullable()->after('lifecycle');
            $table->timestamp('abandoned_at')->nullable()->after('suspended_at');
        });

        $this->backfill();
    }

    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropIndex(['lifecycle']);
            $table->dropColumn(['lifecycle', 'suspended_at', 'abandoned_at']);
        });
    }

    /**
     * Map the old eight statuses onto four, moving the two that were never
     * lifecycle positions onto their own columns.
     */
    protected function backfill(): void
    {
        $members = DB::table('members');
        $today = now()->toDateString();

        // Straight equivalents.
        (clone $members)->where('status', 'active')
            ->update(['lifecycle' => MemberLifecycle::Active->value]);

        (clone $members)->where('status', 'resigned')
            ->update(['lifecycle' => MemberLifecycle::Resigned->value]);

        // "Inactive" only ever meant expired a long time ago, which is now read
        // off expiry_date instead of stored.
        (clone $members)->whereIn('status', ['expired', 'inactive'])
            ->update(['lifecycle' => MemberLifecycle::Expired->value]);

        // "Unverified" is derived from the user's email_verified_at from here
        // on, so both of these are simply Pending.
        (clone $members)->whereIn('status', ['unverified', 'pending'])
            ->update(['lifecycle' => MemberLifecycle::Pending->value]);

        // An abandoned signup stays Pending and gains a timestamp, so it drops
        // out of the action inbox without becoming a different kind of member.
        // The nudge date is when we gave up on them; fall back to the last time
        // the row was touched.
        (clone $members)->where('status', 'abandoned')->update([
            'lifecycle' => MemberLifecycle::Pending->value,
            'abandoned_at' => DB::raw('COALESCE(signup_reminder_sent_at, updated_at)'),
        ]);

        // A suspension is laid on top of wherever the member actually is, so
        // recover that from their expiry date rather than losing it.
        (clone $members)->where('status', 'suspended')
            ->where(fn ($query) => $query->whereNull('expiry_date')->orWhere('expiry_date', '>=', $today))
            ->update(['lifecycle' => MemberLifecycle::Active->value]);

        (clone $members)->where('status', 'suspended')
            ->whereNotNull('expiry_date')
            ->where('expiry_date', '<', $today)
            ->update(['lifecycle' => MemberLifecycle::Expired->value]);

        // We have no record of when a suspension was applied; the last update
        // to the row is the closest honest answer.
        (clone $members)->where('status', 'suspended')
            ->update(['suspended_at' => DB::raw('updated_at')]);
    }
};

<?php

use App\Enums\MemberLifecycle;
use App\Enums\MembershipStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Track when a Pending member last moved forward in the onboarding pipeline.
 *
 * The admin Onboarding view needs "stuck for" as a column — days a person
 * has spent in the *current* stage. Without a per-stage timestamp there is
 * nowhere to derive that from, so the current dashboard shows "N members to
 * onboard" without any signal about who is close to giving up.
 *
 * Backfilled on migration from the timestamps we already keep:
 *
 *   Awaiting email     → users.created_at
 *   Choosing plan      → users.email_verified_at
 *   Awaiting payment   → the earliest membership.created_at
 *
 * Not backfilled for Active / Expired / Resigned — those members are not in
 * the pipeline and the column stays null for them.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->timestamp('stage_entered_at')->nullable()->after('signup_reminder_sent_at');
        });

        // Backfill for every Pending member so the "stuck for" column has a
        // useful starting point instead of "today".
        $this->backfillAwaitingEmail();
        $this->backfillChoosingPlan();
        $this->backfillAwaitingPayment();
    }

    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropColumn('stage_entered_at');
        });
    }

    /**
     * Awaiting email = pending + user.email_verified_at IS NULL.
     * Use users.created_at as the entry moment.
     */
    protected function backfillAwaitingEmail(): void
    {
        $rows = DB::table('members')
            ->join('users', 'users.id', '=', 'members.user_id')
            ->where('members.lifecycle', MemberLifecycle::Pending->value)
            ->whereNull('users.email_verified_at')
            ->whereNull('members.abandoned_at')
            ->whereNull('members.suspended_at')
            ->select('members.id', 'users.created_at')
            ->get();

        foreach ($rows as $row) {
            DB::table('members')
                ->where('id', $row->id)
                ->update(['stage_entered_at' => $row->created_at]);
        }
    }

    /**
     * Choosing plan = pending + verified + no membership row yet.
     * Use users.email_verified_at as the entry moment.
     */
    protected function backfillChoosingPlan(): void
    {
        $rows = DB::table('members')
            ->join('users', 'users.id', '=', 'members.user_id')
            ->leftJoin('memberships', 'memberships.member_id', '=', 'members.id')
            ->where('members.lifecycle', MemberLifecycle::Pending->value)
            ->whereNotNull('users.email_verified_at')
            ->whereNull('memberships.id')
            ->whereNull('members.abandoned_at')
            ->whereNull('members.suspended_at')
            ->select('members.id', 'users.email_verified_at')
            ->get();

        foreach ($rows as $row) {
            DB::table('members')
                ->where('id', $row->id)
                ->update(['stage_entered_at' => $row->email_verified_at]);
        }
    }

    /**
     * Awaiting payment = pending + verified + has a membership row.
     * Use the earliest membership.created_at as the entry moment.
     */
    protected function backfillAwaitingPayment(): void
    {
        $rows = DB::table('members')
            ->join('users', 'users.id', '=', 'members.user_id')
            ->join('memberships', 'memberships.member_id', '=', 'members.id')
            ->where('members.lifecycle', MemberLifecycle::Pending->value)
            ->whereNotNull('users.email_verified_at')
            ->whereNull('members.abandoned_at')
            ->whereNull('members.suspended_at')
            ->select('members.id', DB::raw('MIN(memberships.created_at) AS created_at'))
            ->groupBy('members.id')
            ->get();

        foreach ($rows as $row) {
            DB::table('members')
                ->where('id', $row->id)
                ->update(['stage_entered_at' => $row->created_at]);
        }
    }
};

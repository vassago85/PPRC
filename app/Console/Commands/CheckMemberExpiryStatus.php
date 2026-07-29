<?php

namespace App\Console\Commands;

use App\Enums\MemberLifecycle;
use App\Enums\MembershipStatus;
use App\Models\Member;
use App\Models\Membership;
use App\Services\Membership\MemberService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * WP SSMM parity: checkAndUpdateExpiryStatus applied to every member daily.
 *
 * Rules:
 *   - Null expiry_date → no change (life / honorary memberships).
 *   - Resigned → never touched automatically; leaving is terminal.
 *   - Pending  → not auto-expired; they have never been activated.
 *   - Past expiry_date → Expired.
 *   - Future expiry_date and currently Expired → back to Active.
 *
 * A suspended member is now aged like anybody else, because the suspension is a
 * flag over the lifecycle rather than a status that replaces it. That is the
 * point: lifting a suspension reveals the correct position instead of leaving an
 * admin to guess what it should have been.
 *
 * Additionally expires Membership rows whose period_end has passed while still
 * marked "active", then pulls each member's lifecycle and expiry_date up to the
 * best membership they still hold, keeping the two in sync.
 */
class CheckMemberExpiryStatus extends Command
{
    protected $signature = 'members:check-expiry {--dry-run : Preview changes without writing}';

    protected $description = 'Update member statuses based on membership expiry dates (SSMM parity)';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $today = Carbon::today();

        $stats = ['expired' => 0, 'reactivated' => 0, 'skipped' => 0];

        $expiredMemberships = $this->expireStaleMembershipRows($today, $dryRun);
        $resynced = $this->syncMembersToActiveMemberships($dryRun);

        $members = Member::query()
            ->whereNotNull('expiry_date')
            ->whereNotIn('lifecycle', [
                MemberLifecycle::Resigned->value,
                MemberLifecycle::Pending->value,
            ])
            ->cursor();

        foreach ($members as $member) {
            $expiry = Carbon::parse($member->expiry_date);

            $next = $this->resolveLifecycle($member->lifecycle, $expiry, $today);

            if ($next === null || $next === $member->lifecycle) {
                $stats['skipped']++;

                continue;
            }

            if ($dryRun) {
                $this->line("  {$member->membership_number} ({$member->fullName()}): {$member->lifecycle->label()} → {$next->label()}");
            } else {
                $member->update(['lifecycle' => $next]);
            }

            match ($next) {
                MemberLifecycle::Expired => $stats['expired']++,
                MemberLifecycle::Active => $stats['reactivated']++,
                default => $stats['skipped']++,
            };
        }

        $this->newLine();
        $prefix = $dryRun ? '[DRY RUN] ' : '';
        $this->table(['action', 'count'], [
            ["{$prefix}Membership rows expired", $expiredMemberships],
            ["{$prefix}Members resynced to active membership", $resynced],
            ["{$prefix}Members expired", $stats['expired']],
            ["{$prefix}Members reactivated", $stats['reactivated']],
            ['Skipped / unchanged', $stats['skipped']],
            ['Long lapsed (derived, not stored)', Member::query()->longLapsed()->count()],
        ]);

        return self::SUCCESS;
    }

    /**
     * Expire any Membership rows still marked "active" whose period_end is past.
     * This keeps Membership status in sync with reality so dashboard metrics
     * and currentMembership() queries always reflect accurate state.
     */
    protected function expireStaleMembershipRows(Carbon $today, bool $dryRun): int
    {
        $query = Membership::query()
            ->where('status', MembershipStatus::Active->value)
            ->whereNotNull('period_end')
            ->where('period_end', '<', $today->toDateString());

        $count = $query->count();

        if ($count > 0 && ! $dryRun) {
            $query->update(['status' => MembershipStatus::Expired->value]);
        }

        if ($count > 0) {
            $label = $dryRun ? '[DRY RUN] ' : '';
            $this->info("{$label}Expired {$count} stale membership row(s).");
        }

        return $count;
    }

    /**
     * Pull each member's own status and expiry_date up to the best membership
     * they still hold. Member status is derived from expiry_date, but that date
     * is only written at activation, so anything that set a membership active
     * without going through MemberService left the member behind — showing as
     * expired despite holding a valid membership. Running the sync here repairs
     * that drift instead of waiting for someone to re-save the membership.
     *
     * Runs after the stale rows above are expired, so only genuinely current
     * memberships are considered.
     */
    protected function syncMembersToActiveMemberships(bool $dryRun): int
    {
        $memberships = Membership::query()
            ->where('status', MembershipStatus::Active->value)
            ->whereNotNull('member_id')
            ->with('member')
            // Best membership last, so it wins for members holding more than
            // one — and a lifetime row (no period_end) beats any dated one.
            ->orderByRaw('period_end IS NULL, period_end')
            ->cursor();

        $memberService = app(MemberService::class);
        $resynced = 0;

        foreach ($memberships as $membership) {
            $updates = $memberService->pendingMemberSync($membership);

            if ($updates === []) {
                continue;
            }

            $resynced++;

            if (! $dryRun) {
                $membership->member->update($updates);

                continue;
            }

            $member = $membership->member;
            $changes = collect($updates)
                ->map(fn ($value, $key) => match ($key) {
                    'lifecycle' => "lifecycle {$member->lifecycle->label()} → {$value->label()}",
                    'abandoned_at' => 'no longer treated as an abandoned signup',
                    default => 'expiry '.($member->expiry_date?->toDateString() ?? 'none')
                        .' → '.($value?->toDateString() ?? 'none'),
                })
                ->implode(', ');

            $this->line("  {$member->membership_number} ({$member->fullName()}): {$changes}");
        }

        return $resynced;
    }

    /**
     * There are only two answers now. How long ago a membership lapsed used to
     * be a third stored status ("inactive"); it is read off expiry_date instead,
     * so this no longer has to decide it.
     */
    protected function resolveLifecycle(MemberLifecycle $current, Carbon $expiry, Carbon $today): ?MemberLifecycle
    {
        if ($expiry->isFuture() || $expiry->isSameDay($today)) {
            return $current === MemberLifecycle::Expired ? MemberLifecycle::Active : null;
        }

        return $current === MemberLifecycle::Expired ? null : MemberLifecycle::Expired;
    }
}

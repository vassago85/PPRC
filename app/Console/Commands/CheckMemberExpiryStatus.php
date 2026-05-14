<?php

namespace App\Console\Commands;

use App\Enums\MembershipStatus;
use App\Enums\MemberStatus;
use App\Models\Member;
use App\Models\Membership;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * WP SSMM parity: checkAndUpdateExpiryStatus applied to every member daily.
 *
 * Rules (matching the WordPress plugin behaviour):
 *   - Null expiry_date → no change (life / honorary memberships).
 *   - Suspended → never touched automatically.
 *   - Resigned  → never touched automatically.
 *   - Unverified / Pending → not auto-expired (hasn't been activated yet).
 *   - Past expiry_date → expired.
 *   - >6 months past expiry_date → inactive.
 *   - Future expiry_date + was expired/inactive → reactivate to active.
 *
 * Additionally expires Membership rows whose period_end has passed while
 * still marked as "active", keeping Membership and Member status in sync.
 */
class CheckMemberExpiryStatus extends Command
{
    protected $signature = 'members:check-expiry {--dry-run : Preview changes without writing}';

    protected $description = 'Update member statuses based on membership expiry dates (SSMM parity)';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $today = Carbon::today();
        $sixMonthsAgo = $today->copy()->subMonths(6);

        $stats = ['expired' => 0, 'inactive' => 0, 'reactivated' => 0, 'skipped' => 0];

        $expiredMemberships = $this->expireStaleMembershipRows($today, $dryRun);

        $members = Member::query()
            ->whereNotNull('expiry_date')
            ->whereNotIn('status', [
                MemberStatus::Suspended->value,
                MemberStatus::Resigned->value,
                MemberStatus::Unverified->value,
                MemberStatus::Pending->value,
            ])
            ->cursor();

        foreach ($members as $member) {
            $expiry = Carbon::parse($member->expiry_date);

            $newStatus = $this->resolveStatus($member->status, $expiry, $today, $sixMonthsAgo);

            if ($newStatus === null || $newStatus === $member->status) {
                $stats['skipped']++;

                continue;
            }

            if ($dryRun) {
                $this->line("  {$member->membership_number} ({$member->fullName()}): {$member->status->label()} → {$newStatus->label()}");
            } else {
                $member->update(['status' => $newStatus]);
            }

            match ($newStatus) {
                MemberStatus::Expired => $stats['expired']++,
                MemberStatus::Inactive => $stats['inactive']++,
                MemberStatus::Active => $stats['reactivated']++,
                default => $stats['skipped']++,
            };
        }

        $this->newLine();
        $prefix = $dryRun ? '[DRY RUN] ' : '';
        $this->table(['action', 'count'], [
            ["{$prefix}Membership rows expired", $expiredMemberships],
            ["{$prefix}Members expired", $stats['expired']],
            ["{$prefix}Members inactive (6mo+)", $stats['inactive']],
            ["{$prefix}Members reactivated", $stats['reactivated']],
            ['Skipped / unchanged', $stats['skipped']],
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

    protected function resolveStatus(
        MemberStatus $current,
        Carbon $expiry,
        Carbon $today,
        Carbon $sixMonthsAgo,
    ): ?MemberStatus {
        if ($expiry->isFuture() || $expiry->isSameDay($today)) {
            if (in_array($current, [MemberStatus::Expired, MemberStatus::Inactive], true)) {
                return MemberStatus::Active;
            }

            return null;
        }

        if ($expiry->lt($sixMonthsAgo)) {
            return $current === MemberStatus::Inactive ? null : MemberStatus::Inactive;
        }

        return $current === MemberStatus::Expired ? null : MemberStatus::Expired;
    }
}

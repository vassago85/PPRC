<?php

namespace App\Console\Commands;

use App\Enums\MemberStatus;
use App\Models\Member;
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
            ["{$prefix}Expired", $stats['expired']],
            ["{$prefix}Inactive (6mo+)", $stats['inactive']],
            ["{$prefix}Reactivated", $stats['reactivated']],
            ['Skipped / unchanged', $stats['skipped']],
        ]);

        return self::SUCCESS;
    }

    protected function resolveStatus(
        MemberStatus $current,
        Carbon $expiry,
        Carbon $today,
        Carbon $sixMonthsAgo,
    ): ?MemberStatus {
        if ($expiry->isFuture() || $expiry->isSameDay($today)) {
            // Expiry in the future: reactivate if previously expired/inactive
            if (in_array($current, [MemberStatus::Expired, MemberStatus::Inactive], true)) {
                return MemberStatus::Active;
            }

            return null;
        }

        // Past expiry
        if ($expiry->lt($sixMonthsAgo)) {
            return $current === MemberStatus::Inactive ? null : MemberStatus::Inactive;
        }

        return $current === MemberStatus::Expired ? null : MemberStatus::Expired;
    }
}

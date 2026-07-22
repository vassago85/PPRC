<?php

namespace App\Console\Commands;

use App\Services\Membership\StaleSignupProcessor;
use Illuminate\Console\Command;

/**
 * Nudge-then-archive for incomplete signups.
 *
 * Targets people who registered but never finished:
 *   - never confirmed their email (Unverified), or
 *   - verified but never started a membership application (Pending, no membership).
 *
 * First run nudges them once to finish; if they're still stuck after the grace
 * window they're moved to the "Abandoned signup" status (reversible). Anyone
 * who has paid or is awaiting the treasurer is never touched.
 *
 *   php artisan members:cleanup-stale-signups --dry-run
 *   php artisan members:cleanup-stale-signups --months=6 --grace-days=14 --sleep=2
 */
class CleanupStaleSignups extends Command
{
    protected $signature = 'members:cleanup-stale-signups
        {--months= : How many months of inactivity before a signup counts as stale (default: config).}
        {--grace-days= : Days to wait after nudging before archiving (default: config).}
        {--limit=0 : Max accounts to act on this run (0 = no limit).}
        {--sleep=0 : Seconds to sleep between nudge emails (dodges mail rate limits).}
        {--dry-run : Show what would happen without sending mail or changing anything.}';

    protected $description = 'Nudge incomplete signups to finish, then archive the ones that never do';

    public function handle(StaleSignupProcessor $processor): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $months = $this->option('months') !== null ? max(1, (int) $this->option('months')) : null;
        $graceDays = $this->option('grace-days') !== null ? max(0, (int) $this->option('grace-days')) : null;
        $limit = max(0, (int) $this->option('limit'));
        $sleep = max(0, (int) $this->option('sleep'));

        if ($dryRun) {
            $this->warn('DRY RUN — no emails will be sent and no statuses will change.');
        }

        $stats = $processor->process(
            dryRun: $dryRun,
            months: $months,
            graceDays: $graceDays,
            limit: $limit,
            sleepSeconds: $sleep,
            log: fn (string $line) => $this->line('  '.$line),
        );

        if ($stats['candidates'] === 0) {
            $this->info('No stale signups found. Nothing to do.');

            return self::SUCCESS;
        }

        $this->newLine();
        $this->table(['metric', 'count'], [
            ['Candidates', $stats['candidates']],
            [$dryRun ? 'Would nudge' : 'Nudged', $stats['nudged']],
            [$dryRun ? 'Would archive' : 'Archived', $stats['archived']],
            ['Waiting (recently nudged)', $stats['skipped']],
            ['Failed', $stats['failed']],
        ]);

        return self::SUCCESS;
    }
}

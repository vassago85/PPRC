<?php

namespace App\Console\Commands;

use App\Services\Membership\MembershipNumberAllocator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Run after a CSV / WP import to ensure the membership_number_sequences
 * row reflects the highest imported number, so subsequent allocations
 * do not collide with historical data.
 */
class SeedMembershipNumberSequence extends Command
{
    protected $signature = 'members:seed-sequence {--dry-run : Show what would be written}';

    protected $description = 'Recompute the membership number sequence counter from existing member data';

    public function handle(MembershipNumberAllocator $allocator): int
    {
        $prefix = (string) config('membership.number_prefix', '');
        $nextFromScan = $allocator->peekNextSequenceValue();

        $current = DB::table('membership_number_sequences')
            ->where('prefix', $prefix)
            ->value('last_sequence');

        $this->info("Prefix:            \"{$prefix}\"");
        $this->info("Current sequence:   ".($current !== null ? $current : '(no row)'));
        $this->info("Max from members:   ".($nextFromScan - 1));
        $this->info("New last_sequence:  ".($nextFromScan - 1));

        if ($current !== null && (int) $current >= $nextFromScan - 1) {
            $this->info('Sequence is already up to date — no change needed.');

            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            $this->warn('[DRY RUN] Would set last_sequence to '.($nextFromScan - 1));

            return self::SUCCESS;
        }

        DB::table('membership_number_sequences')->updateOrInsert(
            ['prefix' => $prefix],
            ['last_sequence' => $nextFromScan - 1, 'updated_at' => now()],
        );

        $this->info('Sequence updated successfully.');

        return self::SUCCESS;
    }
}

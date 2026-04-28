<?php

namespace App\Services\Membership;

use App\Models\Member;
use Illuminate\Support\Facades\DB;

class MembershipNumberAllocator
{
    /**
     * Assign the next sequential membership number to this member.
     *
     * Uses a DB row lock on `membership_number_sequences` for hard
     * concurrency guarantees across multiple PHP workers / queue jobs.
     *
     * Reads config('membership.number_prefix') and pads to
     * config('membership.number_pad_length').
     */
    public function assignNextTo(Member $member): void
    {
        if (filled($member->membership_number)) {
            return;
        }

        DB::transaction(function () use ($member): void {
            $member->refresh();

            if (filled($member->membership_number)) {
                return;
            }

            $prefix = (string) config('membership.number_prefix', '');

            $seq = DB::table('membership_number_sequences')
                ->where('prefix', $prefix)
                ->lockForUpdate()
                ->first();

            if (! $seq) {
                $bootstrapped = $this->bootstrapSequence($prefix);
                $seq = DB::table('membership_number_sequences')
                    ->where('prefix', $prefix)
                    ->lockForUpdate()
                    ->first();

                if (! $seq) {
                    $next = $bootstrapped;
                    DB::table('membership_number_sequences')->insert([
                        'prefix' => $prefix,
                        'last_sequence' => $next,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    $member->forceFill(['membership_number' => $this->format($prefix, $next)])->save();

                    return;
                }
            }

            $next = $seq->last_sequence + 1;

            DB::table('membership_number_sequences')
                ->where('prefix', $prefix)
                ->update(['last_sequence' => $next, 'updated_at' => now()]);

            $member->forceFill([
                'membership_number' => $this->format($prefix, $next),
            ])->save();
        });
    }

    /**
     * Peek without locking — for display / admin info only.
     */
    public function peekNextSequenceValue(): int
    {
        $prefix = (string) config('membership.number_prefix', '');

        $row = DB::table('membership_number_sequences')
            ->where('prefix', $prefix)
            ->first();

        if ($row) {
            return $row->last_sequence + 1;
        }

        return $this->scanMaxFromMembers($prefix);
    }

    /**
     * Bootstrap the sequence row from existing member data + legacy patterns.
     * Called once when the sequence table is empty for a given prefix.
     */
    protected function bootstrapSequence(string $prefix): int
    {
        $start = max(1, (int) config('membership.number_start', 1));
        $maxFromMembers = $this->scanMaxFromMembers($prefix);
        $next = max($start, $maxFromMembers);

        DB::table('membership_number_sequences')->updateOrInsert(
            ['prefix' => $prefix],
            ['last_sequence' => $next, 'created_at' => now(), 'updated_at' => now()],
        );

        return $next;
    }

    /**
     * Scan all existing membership_number values to find the highest sequence,
     * supporting both pure numeric and PREFIX-YYYY-#### legacy patterns.
     */
    protected function scanMaxFromMembers(string $prefix): int
    {
        $start = max(1, (int) config('membership.number_start', 1));

        $numbers = Member::query()
            ->withTrashed()
            ->whereNotNull('membership_number')
            ->pluck('membership_number');

        $max = 0;

        foreach ($numbers as $raw) {
            $num = $this->extractSequence((string) $raw, $prefix);
            if ($num !== null && $num > $max) {
                $max = $num;
            }
        }

        return max($start, $max + 1);
    }

    /**
     * Extract the numeric sequence from a membership number string.
     *
     * Handles:
     *   - Pure numeric: "42" → 42, "0042" → 42
     *   - Prefixed: "PPRC-42" → 42 (when prefix is "PPRC-")
     *   - Legacy: "PPRC-2025-0042" → 42 (PREFIX-YYYY-#### pattern)
     */
    public function extractSequence(string $raw, string $prefix = ''): ?int
    {
        $trimmed = trim($raw);
        if ($trimmed === '') {
            return null;
        }

        // Pure numeric
        if (ctype_digit($trimmed)) {
            return (int) $trimmed;
        }

        // Strip known prefix
        if ($prefix !== '' && str_starts_with($trimmed, $prefix)) {
            $rest = substr($trimmed, strlen($prefix));

            if (ctype_digit($rest)) {
                return (int) $rest;
            }

            // Legacy PREFIX-YYYY-#### pattern
            if (preg_match('/^\d{4}-(\d+)$/', $rest, $m)) {
                return (int) $m[1];
            }
        }

        // Try legacy pattern without prefix: ANYTHING-YYYY-####
        if (preg_match('/-(\d{4})-(\d+)$/', $trimmed, $m)) {
            return (int) $m[2];
        }

        return null;
    }

    public function format(string $prefix, int $sequence): string
    {
        $pad = config('membership.number_pad_length');

        $numeric = ($pad !== null && $pad > 0)
            ? str_pad((string) $sequence, $pad, '0', STR_PAD_LEFT)
            : (string) $sequence;

        return $prefix.$numeric;
    }
}

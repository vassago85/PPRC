<?php

namespace App\Services\Membership;

use App\Models\Member;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class MembershipNumberAllocator
{
    /**
     * Assign the next sequential PPRC membership number to this member.
     * Caller must ensure the member does not already have a number.
     *
     * Uses a cache lock so two concurrent activations cannot receive the same number.
     */
    public function assignNextTo(Member $member): void
    {
        if (filled($member->membership_number)) {
            return;
        }

        Cache::lock('membership_number_allocate', 30)->block(10, function () use ($member): void {
            DB::transaction(function () use ($member): void {
                $member->refresh();

                if (filled($member->membership_number)) {
                    return;
                }

                $next = $this->peekNextSequenceValue();

                $member->forceFill([
                    'membership_number' => $this->formatSequence($next),
                ])->save();
            });
        });
    }

    public function peekNextSequenceValue(): int
    {
        $start = max(1, (int) config('membership.number_start', 1));

        $maxUsed = Member::query()
            ->withTrashed()
            ->whereNotNull('membership_number')
            ->pluck('membership_number')
            ->map(fn (mixed $n) => $this->parseNumericSequence((string) $n))
            ->filter()
            ->max();

        return max($start, ($maxUsed ?? 0) + 1);
    }

    /**
     * Whole numeric strings only (e.g. "0042" => 42, "12A" => null).
     */
    public function parseNumericSequence(string $raw): ?int
    {
        $trimmed = trim($raw);

        if ($trimmed === '' || ! ctype_digit($trimmed)) {
            return null;
        }

        return (int) $trimmed;
    }

    public function formatSequence(int $value): string
    {
        $pad = config('membership.number_pad_length');

        if ($pad !== null && $pad > 0) {
            return str_pad((string) $value, $pad, '0', STR_PAD_LEFT);
        }

        return (string) $value;
    }
}

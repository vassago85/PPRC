<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * REVERTS the earlier 5-digit repad. The committee's historical SSMM numbers
 * are 4-digit zero-padded (PPRC-0150). Repadding to 5 digits silently changed
 * every existing member's number, which is unacceptable for an authoritative
 * record. This migration re-formats any PPRC-#### sequence back to 4 digits
 * (numbers that genuinely need >4 digits are kept as-is — str_pad never
 * truncates).
 */
return new class extends Migration
{
    public function up(): void
    {
        $prefix = 'PPRC-';
        $pad = 4;

        DB::table('members')
            ->whereNotNull('membership_number')
            ->where('membership_number', 'like', $prefix.'%')
            ->orderBy('id')
            ->each(function ($member) use ($prefix, $pad) {
                $rest = substr($member->membership_number, strlen($prefix));
                if (! ctype_digit($rest)) {
                    return;
                }
                $seq = (int) $rest;
                $newNumber = $prefix.str_pad((string) $seq, $pad, '0', STR_PAD_LEFT);

                if ($newNumber !== $member->membership_number) {
                    DB::table('members')
                        ->where('id', $member->id)
                        ->update(['membership_number' => $newNumber]);
                }
            });

        // Force the sequence row to re-bootstrap from the corrected member
        // table on the next allocation, so the next number is MAX(existing) + 1.
        if (DB::getSchemaBuilder()->hasTable('membership_number_sequences')) {
            DB::table('membership_number_sequences')->where('prefix', $prefix)->delete();
        }
    }

    public function down(): void
    {
        // No rollback — we never want to silently rewrite member numbers again.
    }
};

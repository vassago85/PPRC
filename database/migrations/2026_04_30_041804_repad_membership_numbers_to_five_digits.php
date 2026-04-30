<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $prefix = 'PPRC-';
        $newPad = 5;

        DB::table('members')
            ->whereNotNull('membership_number')
            ->where('membership_number', 'like', $prefix . '%')
            ->orderBy('id')
            ->each(function ($member) use ($prefix, $newPad) {
                $rest = substr($member->membership_number, strlen($prefix));
                if (! ctype_digit($rest)) {
                    return;
                }
                $seq = (int) $rest;
                $newNumber = $prefix . str_pad((string) $seq, $newPad, '0', STR_PAD_LEFT);

                if ($newNumber !== $member->membership_number) {
                    DB::table('members')
                        ->where('id', $member->id)
                        ->update(['membership_number' => $newNumber]);
                }
            });

        // Also repad pure-numeric membership numbers (from pre-prefix era)
        DB::table('members')
            ->whereNotNull('membership_number')
            ->where('membership_number', 'not like', $prefix . '%')
            ->orderBy('id')
            ->each(function ($member) use ($prefix, $newPad) {
                $raw = trim($member->membership_number);
                if (! ctype_digit($raw)) {
                    return;
                }
                $seq = (int) $raw;
                $newNumber = $prefix . str_pad((string) $seq, $newPad, '0', STR_PAD_LEFT);

                DB::table('members')
                    ->where('id', $member->id)
                    ->update(['membership_number' => $newNumber]);
            });
    }

    public function down(): void
    {
        // Revert to 4-digit padding
        $prefix = 'PPRC-';
        DB::table('members')
            ->whereNotNull('membership_number')
            ->where('membership_number', 'like', $prefix . '%')
            ->orderBy('id')
            ->each(function ($member) use ($prefix) {
                $rest = substr($member->membership_number, strlen($prefix));
                if (! ctype_digit($rest)) {
                    return;
                }
                $seq = (int) $rest;
                $newNumber = $prefix . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);

                DB::table('members')
                    ->where('id', $member->id)
                    ->update(['membership_number' => $newNumber]);
            });
    }
};

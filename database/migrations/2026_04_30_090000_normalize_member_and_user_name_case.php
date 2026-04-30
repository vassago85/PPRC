<?php

use App\Support\NameCase;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The SSMM CSV had inconsistent name casing — some rows are entirely
 * lowercase ("alex pienaar") or entirely uppercase. This pass normalises
 * malformed rows to Title Case while preserving deliberately mixed-case
 * names ("Van der Merwe", "MacDonald", "le Roux") via NameCase::normalize.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('members')
            ->select(['id', 'first_name', 'last_name', 'known_as'])
            ->orderBy('id')
            ->each(function ($member) {
                $update = [];

                foreach (['first_name', 'last_name', 'known_as'] as $field) {
                    $current = $member->{$field};
                    $normalized = NameCase::normalize($current);
                    if ($normalized !== $current) {
                        $update[$field] = $normalized;
                    }
                }

                if (! empty($update)) {
                    DB::table('members')->where('id', $member->id)->update($update);
                }
            });

        DB::table('users')
            ->select(['id', 'name'])
            ->orderBy('id')
            ->each(function ($user) {
                $normalized = NameCase::normalize($user->name);
                if ($normalized !== $user->name) {
                    DB::table('users')->where('id', $user->id)->update(['name' => $normalized]);
                }
            });
    }

    public function down(): void
    {
        // Cannot recover the original (malformed) casing. Leave normalised.
    }
};

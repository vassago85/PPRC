<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * PPRC's member-facing disciplines are now strictly "PRS" (centerfire) and
 * "PR22" (rimfire) — these are the only formats the club runs. The legacy
 * SSMM importer wrote "centrefire" / "rimfire" into shooting_disciplines,
 * and earlier admin lists offered NRL / F-Class / Benchrest / etc. Translate
 * everything in the DB to the new two-value vocabulary so the profile
 * checkboxes render existing members' choices correctly.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('members')
            ->whereNotNull('shooting_disciplines')
            ->orderBy('id')
            ->each(function ($row) {
                $raw = $row->shooting_disciplines;
                $list = is_string($raw) ? json_decode($raw, true) : $raw;
                if (! is_array($list)) {
                    return;
                }

                $mapped = [];
                foreach ($list as $value) {
                    $key = strtolower(trim((string) $value));
                    $mapped[] = match (true) {
                        $key === '' => null,
                        in_array($key, ['prs', 'prs centerfire', 'prs centrefire', 'centerfire', 'centrefire'], true) => 'PRS',
                        in_array($key, ['pr22', 'pr-22', 'rimfire', '.22'], true) => 'PR22',
                        default => null,
                    };
                }

                $clean = array_values(array_unique(array_filter($mapped)));

                DB::table('members')
                    ->where('id', $row->id)
                    ->update([
                        'shooting_disciplines' => $clean === [] ? null : json_encode($clean),
                    ]);
            });
    }

    public function down(): void
    {
        // No rollback — we don't keep the original mixed-vocabulary values.
    }
};

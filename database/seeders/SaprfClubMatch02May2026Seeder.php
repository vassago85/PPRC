<?php

namespace Database\Seeders;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\EventResult;
use App\Models\MatchFormat;
use App\Models\Member;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds the combined SAPRF Provincial + PPRC Club Match held on 2 May 2026.
 *
 * Idempotent: re-running deletes any existing results for this event slug
 * and re-inserts them, so we can safely fix data and re-run.
 *
 * Data conventions
 * ----------------
 * - Times below 60 are PractiScore "mm.ss" notation (e.g. 1.05 = 65s).
 * - 105.00 is the per-stage cap; treat as a real time, not a sentinel.
 * - PractiScore "Div" column mixes division (Open/Factory/...) and category
 *   (Ladies/Seniors). We split them into the dedicated columns so the
 *   public results filter can work on both axes independently.
 * - The "Class" column from PractiScore (SAPRFP60R / PPRCCM42R) is the
 *   course code and lives on the Event itself, not on each result row.
 */
class SaprfClubMatch02May2026Seeder extends Seeder
{
    private const EVENT_SLUG = 'saprf-provincial-pprc-club-match-02-may-2026';

    public function run(): void
    {
        $format = MatchFormat::where('slug', 'prs-centerfire')->first()
            ?? MatchFormat::orderBy('id')->first();

        $event = Event::updateOrCreate(
            ['slug' => self::EVENT_SLUG],
            [
                'match_format_id' => $format?->id,
                'title' => 'SAPRF Provincial and PPRC Club Match 02 May 2026',
                'summary' => 'Combined SAPRF Provincial (60 rounds) and PPRC Club Match (42 rounds), shot on 2 May 2026.',
                'start_date' => CarbonImmutable::create(2026, 5, 2),
                'end_date' => CarbonImmutable::create(2026, 5, 2),
                'location_name' => 'Marloo',
                'is_saprf_match' => true,
                'round_count' => 60,
                'club_round_count' => 42,
                'status' => EventStatus::Completed,
                'registrations_open' => false,
                'published_at' => CarbonImmutable::create(2026, 4, 15),
                'results_published_at' => CarbonImmutable::create(2026, 5, 3, 12, 0, 0),
            ]
        );

        DB::transaction(function () use ($event) {
            EventResult::where('event_id', $event->id)->delete();

            foreach ($this->rows() as $row) {
                [$division, $category] = $this->splitDivisionAndCategory($row['div']);

                EventResult::create([
                    'event_id' => $event->id,
                    'member_id' => $this->resolveMemberId($row['mem'], $row['name']),
                    'shooter_name' => $this->normalizeName($row['name']),
                    'division' => $division,
                    'category' => $category,
                    'rank' => $row['rank'],
                    'score_points' => (int) round($row['pts']),
                    'score_percentage' => $row['pct'],
                    'score_time_ms' => $this->parseTimeMs($row['time']),
                    'dnf' => false,
                    'dq' => false,
                ]);
            }
        });
    }

    /**
     * Source data, exactly as exported from DeadCenter, ranked 1..n.
     *
     * @return list<array{rank:int,name:string,mem:?string,div:string,time:float,pts:float,pct:float}>
     */
    private function rows(): array
    {
        return [
            ['rank' => 1,  'name' => 'Goosen, Leon',                'mem' => '3',          'div' => 'OPEN',                  'time' => 91.02,  'pts' => 52, 'pct' => 100.00],
            ['rank' => 2,  'name' => 'Cook, Donovan',               'mem' => null,         'div' => 'OPEN',                  'time' => 99.00,  'pts' => 52, 'pct' => 100.00],
            ['rank' => 3,  'name' => 'Nel, Johan',                  'mem' => '0046',       'div' => 'OPEN',                  'time' => 1.05,   'pts' => 50, 'pct' => 96.15],
            ['rank' => 4,  'name' => 'Ferreira, Kim-Leigh',         'mem' => null,         'div' => 'LADIES',                'time' => 105.00, 'pts' => 49, 'pct' => 94.23],
            ['rank' => 5,  'name' => 'Steyn, Marcel',               'mem' => null,         'div' => 'OPEN',                  'time' => 96.50,  'pts' => 48, 'pct' => 92.31],
            ['rank' => 6,  'name' => 'Ferreira, Russell',           'mem' => null,         'div' => 'FACTORY',               'time' => 102.61, 'pts' => 44, 'pct' => 84.62],
            ['rank' => 7,  'name' => 'Nel, Hendrik',                'mem' => null,         'div' => 'OPEN',                  'time' => 105.00, 'pts' => 42, 'pct' => 80.77],
            ['rank' => 8,  'name' => 'van der Merwe, Schalk',       'mem' => '088',        'div' => 'OPEN',                  'time' => 1.03,   'pts' => 40, 'pct' => 76.92],
            ['rank' => 9,  'name' => 'Leeson, Chris',               'mem' => null,         'div' => 'FACTORY',               'time' => 1.05,   'pts' => 40, 'pct' => 76.92],
            ['rank' => 10, 'name' => 'Pretorius, Chris',            'mem' => '0173',       'div' => 'OPEN',                  'time' => 1.03,   'pts' => 38, 'pct' => 73.08],
            ['rank' => 11, 'name' => 'Pio, Dirk',                   'mem' => '1',          'div' => 'OPEN',                  'time' => 102.70, 'pts' => 38, 'pct' => 73.08],
            ['rank' => 12, 'name' => 'Weideman, Jandre',            'mem' => '0137',       'div' => 'LIMITEDTACTICAL223308', 'time' => 1.05,   'pts' => 36, 'pct' => 69.23],
            ['rank' => 13, 'name' => 'Mey, Clive',                  'mem' => null,         'div' => 'FACTORY',               'time' => 1.05,   'pts' => 35, 'pct' => 67.31],
            ['rank' => 14, 'name' => 'Cilliers, Franco',            'mem' => '0156',       'div' => 'OPEN',                  'time' => 105.00, 'pts' => 35, 'pct' => 67.31],
            ['rank' => 15, 'name' => 'wyk, Francois Van',           'mem' => null,         'div' => 'OPEN',                  'time' => 105.00, 'pts' => 32, 'pct' => 61.54],
            ['rank' => 16, 'name' => 'Graham, Sean',                'mem' => null,         'div' => 'OPEN',                  'time' => 1.05,   'pts' => 31, 'pct' => 59.62],
            ['rank' => 17, 'name' => 'Graham, Trevor',              'mem' => '063',        'div' => 'SENIORS',               'time' => 1.05,   'pts' => 30, 'pct' => 57.69],
            ['rank' => 18, 'name' => 'Lategan, Andries',            'mem' => null,         'div' => 'SENIORS',               'time' => 105.00, 'pts' => 30, 'pct' => 57.69],
            ['rank' => 19, 'name' => 'Klopper, Henri',              'mem' => 'PPRC-0008',  'div' => 'OPEN',                  'time' => 105.00, 'pts' => 29, 'pct' => 55.77],
            ['rank' => 20, 'name' => 'van der Merwe, Stephan',      'mem' => 'PPRC-0147',  'div' => 'OPEN',                  'time' => 105.00, 'pts' => 29, 'pct' => 55.77],
            ['rank' => 21, 'name' => 'Mey, Aliza',                  'mem' => null,         'div' => 'LADIES',                'time' => 1.05,   'pts' => 27, 'pct' => 51.92],
            ['rank' => 22, 'name' => 'Van Tonder, Jaco',            'mem' => '113',        'div' => 'OPEN',                  'time' => 95.86,  'pts' => 26, 'pct' => 50.00],
            ['rank' => 23, 'name' => 'Ayob, Mohamed',               'mem' => null,         'div' => 'LIMITEDTACTICAL223308', 'time' => 1.05,   'pts' => 26, 'pct' => 50.00],
            ['rank' => 24, 'name' => 'Badenhorst, Jandre',          'mem' => null,         'div' => 'OPEN',                  'time' => 105.00, 'pts' => 24, 'pct' => 46.15],
            ['rank' => 25, 'name' => 'Swarts, Sean',                'mem' => null,         'div' => 'OPEN',                  'time' => 105.00, 'pts' => 24, 'pct' => 46.15],
            ['rank' => 26, 'name' => 'Du Preez, Danie',             'mem' => null,         'div' => 'SENIORS',               'time' => 105.00, 'pts' => 23, 'pct' => 44.23],
            ['rank' => 27, 'name' => 'Le roux, Justin',             'mem' => null,         'div' => 'OPEN',                  'time' => 98.33,  'pts' => 22, 'pct' => 42.31],
            ['rank' => 28, 'name' => 'du Plessis, Ruan',            'mem' => null,         'div' => 'OPEN',                  'time' => 105.00, 'pts' => 20, 'pct' => 38.46],
            ['rank' => 29, 'name' => 'Kruger, Danie',               'mem' => 'PPRC-0150',  'div' => 'OPEN',                  'time' => 105.00, 'pts' => 18, 'pct' => 34.62],
            ['rank' => 30, 'name' => 'Coetzer, Anton',              'mem' => null,         'div' => 'OPEN',                  'time' => 103.00, 'pts' => 17, 'pct' => 32.69],
            ['rank' => 31, 'name' => 'de Witt, Liné',               'mem' => '389',        'div' => 'LADIES',                'time' => 1.05,   'pts' => 16, 'pct' => 30.77],
            ['rank' => 32, 'name' => 'Niemand, Pieter',             'mem' => 'PPRC-0090',  'div' => 'LIMITEDTACTICAL223308', 'time' => 1.05,   'pts' => 16, 'pct' => 30.77],
            ['rank' => 33, 'name' => 'Charsley, Paul',              'mem' => '1701',       'div' => 'OPEN',                  'time' => 105.00, 'pts' => 16, 'pct' => 30.77],
            ['rank' => 34, 'name' => 'Labuschagne, perey',          'mem' => null,         'div' => 'OPEN',                  'time' => 105.00, 'pts' => 15, 'pct' => 28.85],
            ['rank' => 35, 'name' => 'Janse van Rensburg, Steven',  'mem' => 'PPRC-0134',  'div' => 'SENIORS',               'time' => 105.00, 'pts' => 14, 'pct' => 26.92],
            ['rank' => 36, 'name' => 'van der Westhuizen, Andre PJ','mem' => null,         'div' => 'OPEN',                  'time' => 105.00, 'pts' => 14, 'pct' => 26.92],
            ['rank' => 37, 'name' => 'Jatho, Rob',                  'mem' => 'PPRC-0141',  'div' => 'OPEN',                  'time' => 105.00, 'pts' => 13, 'pct' => 25.00],
            ['rank' => 38, 'name' => 'Glynn, Neville',              'mem' => null,         'div' => 'OPEN',                  'time' => 105.00, 'pts' => 12, 'pct' => 23.08],
            ['rank' => 39, 'name' => 'Andrews, Michael',            'mem' => null,         'div' => 'OPEN',                  'time' => 105.00, 'pts' => 10, 'pct' => 19.23],
            ['rank' => 40, 'name' => 'Janse van Rensburg, Adrian',  'mem' => 'PPRC-0135',  'div' => 'SENIORS',               'time' => 105.00, 'pts' => 10, 'pct' => 19.23],
            ['rank' => 41, 'name' => 'Wessels, Tiaan',              'mem' => 'PPRC-0116',  'div' => 'OPEN',                  'time' => 105.00, 'pts' =>  8, 'pct' => 15.38],
            ['rank' => 42, 'name' => 'Klopper, Tiaan',              'mem' => null,         'div' => 'LIMITEDTACTICAL223308', 'time' => 105.00, 'pts' =>  8, 'pct' => 15.38],
            ['rank' => 43, 'name' => 'de Kock, Francois',           'mem' => 'PPRC-0154',  'div' => 'OPEN',                  'time' => 105.00, 'pts' =>  8, 'pct' => 15.38],
            ['rank' => 44, 'name' => 'Arbee, Ismail',               'mem' => 'PPRC-0151',  'div' => 'OPEN',                  'time' => 105.00, 'pts' =>  5, 'pct' =>  9.62],
            ['rank' => 45, 'name' => 'van Staden, Leonard',         'mem' => null,         'div' => 'OPEN',                  'time' => 105.00, 'pts' =>  4, 'pct' =>  7.69],
            ['rank' => 46, 'name' => 'Gurovich, Juro',              'mem' => null,         'div' => 'LIMITEDTACTICAL223308', 'time' => 105.00, 'pts' =>  4, 'pct' =>  7.69],
            ['rank' => 47, 'name' => 'Smit, Gerhard',               'mem' => 'PPRC-0168',  'div' => 'OPEN',                  'time' => 105.00, 'pts' =>  0, 'pct' =>  0.00],
        ];
    }

    /** "Last, First" -> "First Last". Leaves single-token names alone. */
    private function normalizeName(string $name): string
    {
        if (! str_contains($name, ',')) {
            return $name;
        }
        [$last, $first] = array_map('trim', explode(',', $name, 2));

        return trim("{$first} {$last}");
    }

    /**
     * @return array{0: ?string, 1: ?string} [division, category]
     */
    private function splitDivisionAndCategory(string $raw): array
    {
        return match (strtoupper($raw)) {
            'OPEN'                  => ['Open',                          null],
            'FACTORY'               => ['Factory',                       null],
            'LIMITEDTACTICAL223308' => ['Limited Tactical (.223/.308)',  null],
            'CLUBOPEN'              => ['Open',                          null],
            'LADIES'                => ['Open',                          'Ladies'],
            'SENIORS'               => ['Open',                          'Seniors'],
            'JUNIORS'               => ['Open',                          'Juniors'],
            default                 => [$raw,                            null],
        };
    }

    /**
     * Times under 60 are PractiScore "mm.ss" (e.g. 1.05 == 65s); anything
     * else is plain seconds. Returns milliseconds because that's the column
     * unit on event_results.
     */
    private function parseTimeMs(float $value): int
    {
        if ($value < 60) {
            $minutes = (int) floor($value);
            $seconds = (int) round(($value - $minutes) * 100);

            return ($minutes * 60 + $seconds) * 1000;
        }

        return (int) round($value * 1000);
    }

    /**
     * Try to link a result back to a PPRC member. Membership numbers in the
     * source data come in three flavours:
     *   - Padded short form: "0046", "088", "063"          -> match by suffix
     *   - Full PPRC-#####:   "PPRC-0008", "PPRC-0147"      -> exact match
     *   - Junk:              "???", "NA", "N/a", ".", "7/8"-> ignore
     */
    private function resolveMemberId(?string $rawMem, string $shooterName): ?int
    {
        if ($rawMem !== null) {
            $mem = strtoupper(trim($rawMem));
            $junk = ['', '???', 'NA', 'N/A', '.', '7/8'];

            if (! in_array($mem, $junk, true)) {
                $mem = preg_replace('/^PPRC-?/', 'PPRC-', $mem) ?? $mem;

                $member = Member::where('membership_number', $mem)->first();
                if ($member) {
                    return $member->id;
                }

                if (! str_starts_with($mem, 'PPRC-') && ctype_digit(ltrim($mem, '0'))) {
                    $member = Member::where('membership_number', 'PPRC-'.str_pad(ltrim($mem, '0'), 4, '0', STR_PAD_LEFT))->first()
                        ?? Member::where('membership_number', 'like', '%'.ltrim($mem, '0'))->first();
                    if ($member) {
                        return $member->id;
                    }
                }
            }
        }

        $name = $this->normalizeName($shooterName);
        $parts = explode(' ', $name, 2);
        if (count($parts) !== 2) {
            return null;
        }
        [$first, $last] = $parts;

        $member = Member::whereRaw('lower(first_name) = ?', [strtolower($first)])
            ->whereRaw('lower(last_name) = ?', [strtolower($last)])
            ->first();

        return $member?->id;
    }
}

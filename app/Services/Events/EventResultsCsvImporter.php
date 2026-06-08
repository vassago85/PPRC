<?php

namespace App\Services\Events;

use App\Models\Event;
use App\Models\EventResult;
use App\Models\Member;
use Illuminate\Support\Facades\DB;

/**
 * Imports event results from a CSV file.
 *
 * Canonical headers (case-insensitive, extra cols ignored):
 *   rank, shooter_name, division, category, member_id, member_email,
 *   membership_number, hits, possible, points, percentage, time_ms,
 *   dnf, dq, notes
 *
 * Common export aliases are mapped automatically so operators don't have to
 * rename columns by hand:
 *   place -> rank, div -> division, class -> category,
 *   member number -> membership_number, impacts -> points,
 *   match % -> percentage, time (seconds) -> time_ms.
 *
 * Note: in impact scoring the "Impacts" column is the raw score (points the
 * shooter put on target), and "Match %" is that score normalised against the
 * top scorer of the day — so impacts map to score_points and the percentage
 * is stored separately.
 *
 * When there is no single shooter_name column, separate "first"/"last"
 * columns (as produced by the impact-scoring exports) are combined into one.
 * Percentage values may include a trailing "%" and time may be given in
 * whole seconds with decimals.
 *
 * Any of the member_* columns can be used to resolve a PPRC member and link
 * the result back to their profile. If no match is found the shooter_name
 * still carries the result forward so historical data is never dropped.
 */
class EventResultsCsvImporter
{
    /**
     * Map of lowercased CSV header variants to the canonical key the importer
     * reads. Headers not listed pass through unchanged (and are ignored if the
     * importer doesn't use them).
     *
     * @var array<string,string>
     */
    private const HEADER_ALIASES = [
        'place' => 'rank',
        'pos' => 'rank',
        'position' => 'rank',
        'div' => 'division',
        'class' => 'category',
        'cat' => 'category',
        'first' => 'first_name',
        'first name' => 'first_name',
        'firstname' => 'first_name',
        'last' => 'last_name',
        'last name' => 'last_name',
        'surname' => 'last_name',
        'lastname' => 'last_name',
        'name' => 'shooter_name',
        'shooter' => 'shooter_name',
        'member number' => 'membership_number',
        'member no' => 'membership_number',
        'membership no' => 'membership_number',
        'member #' => 'membership_number',
        'impacts' => 'points',
        'impact' => 'points',
        'match %' => 'percentage',
        'match%' => 'percentage',
        'percent' => 'percentage',
        '%' => 'percentage',
    ];
    /**
     * @return array{created:int, updated:int, errors:array<int,string>}
     */
    public function import(Event $event, string $absolutePath, bool $replaceExisting = false): array
    {
        if (! is_readable($absolutePath)) {
            return ['created' => 0, 'updated' => 0, 'errors' => ["File not readable: {$absolutePath}"]];
        }

        $handle = fopen($absolutePath, 'r');
        if ($handle === false) {
            return ['created' => 0, 'updated' => 0, 'errors' => ['Could not open CSV for reading.']];
        }

        $headerRow = fgetcsv($handle);
        if ($headerRow === false) {
            fclose($handle);
            return ['created' => 0, 'updated' => 0, 'errors' => ['CSV has no header row.']];
        }

        // Strip the UTF-8 BOM from the first header cell — exports from Excel
        // and a lot of timing software include it, which silently breaks
        // array_combine lookups on the first column. Same gotcha we hit on
        // the SSMM members import.
        if (isset($headerRow[0])) {
            $headerRow[0] = preg_replace('/^\\x{FEFF}/u', '', (string) $headerRow[0]);
        }

        $headers = array_map(function ($h) {
            $key = strtolower(trim((string) $h));

            return self::HEADER_ALIASES[$key] ?? $key;
        }, $headerRow);

        $created = 0;
        $updated = 0;
        $errors = [];
        $rowNum = 1;

        DB::beginTransaction();
        try {
            if ($replaceExisting) {
                EventResult::where('event_id', $event->id)->delete();
            }

            while (($row = fgetcsv($handle)) !== false) {
                $rowNum++;
                if (count($row) !== count($headers)) {
                    $errors[] = "Row {$rowNum}: column count mismatch.";
                    continue;
                }

                $data = array_combine($headers, array_map(fn ($v) => trim((string) $v), $row));
                if ($data === false) {
                    $errors[] = "Row {$rowNum}: could not combine row.";
                    continue;
                }

                $shooterName = self::resolveShooterName($data);
                if ($shooterName === '') {
                    $errors[] = "Row {$rowNum}: missing shooter name.";
                    continue;
                }

                $memberId = $this->resolveMemberId($data);

                $attrs = [
                    'shooter_name' => $shooterName,
                    'division' => ($data['division'] ?? '') ?: null,
                    'category' => ($data['category'] ?? '') ?: null,
                    'rank' => self::nullableInt($data['rank'] ?? null),
                    'score_hits' => self::nullableInt($data['hits'] ?? null),
                    'score_possible' => self::nullableInt($data['possible'] ?? null),
                    'score_points' => self::nullableInt($data['points'] ?? null),
                    'score_percentage' => self::nullableFloat($data['percentage'] ?? null),
                    'score_time_ms' => self::resolveTimeMs($data),
                    'dnf' => self::boolish($data['dnf'] ?? null),
                    'dq' => self::boolish($data['dq'] ?? null),
                    'notes' => ($data['notes'] ?? '') ?: null,
                    'member_id' => $memberId,
                ];

                // Use (event_id, member_id) or (event_id, shooter_name) as the
                // dedupe key depending on whether we found a member. This means
                // re-uploading the same CSV updates in place instead of doubling.
                $lookup = $memberId
                    ? ['event_id' => $event->id, 'member_id' => $memberId]
                    : ['event_id' => $event->id, 'shooter_name' => $shooterName, 'member_id' => null];

                $existing = EventResult::where($lookup)->first();
                if ($existing) {
                    $existing->update($attrs);
                    $updated++;
                } else {
                    EventResult::create(array_merge(['event_id' => $event->id], $attrs));
                    $created++;
                }
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            $errors[] = 'Import aborted: '.$e->getMessage();
        } finally {
            fclose($handle);
        }

        return compact('created', 'updated', 'errors');
    }

    /**
     * Build a single shooter name, supporting either a combined
     * "shooter_name" column or separate "first"/"last" columns.
     */
    private static function resolveShooterName(array $data): string
    {
        $name = trim((string) ($data['shooter_name'] ?? ''));
        if ($name !== '') {
            return $name;
        }

        $first = trim((string) ($data['first_name'] ?? ''));
        $last = trim((string) ($data['last_name'] ?? ''));

        return trim($first.' '.$last);
    }

    /**
     * Accept time as milliseconds (time_ms) or whole seconds with decimals
     * (time), as produced by the impact-scoring exports.
     */
    private static function resolveTimeMs(array $data): ?int
    {
        $ms = self::nullableInt($data['time_ms'] ?? null);
        if ($ms !== null) {
            return $ms;
        }

        $seconds = self::nullableFloat($data['time'] ?? null);
        if ($seconds === null) {
            return null;
        }

        return (int) round($seconds * 1000);
    }

    private function resolveMemberId(array $data): ?int
    {
        if (! empty($data['member_id']) && ctype_digit((string) $data['member_id'])) {
            return (int) $data['member_id'];
        }

        if (! empty($data['member_email'])) {
            $member = Member::whereHas('user', fn ($q) => $q->where('email', $data['member_email']))->first();
            if ($member) {
                return $member->id;
            }
        }

        if (! empty($data['membership_number'])) {
            $member = Member::where('membership_number', $data['membership_number'])->first();
            if ($member) {
                return $member->id;
            }
        }

        return null;
    }

    private static function nullableInt(?string $v): ?int
    {
        if ($v === null || $v === '') return null;
        if (! is_numeric($v)) return null;
        return (int) $v;
    }

    private static function nullableFloat(?string $v): ?float
    {
        if ($v === null) return null;
        // Strip a trailing percent sign, thousands separators and surrounding
        // whitespace so values like "89.06%" or "1,234.5" still parse.
        $v = trim(str_replace([',', '%'], '', $v));
        if ($v === '') return null;
        if (! is_numeric($v)) return null;
        return (float) $v;
    }

    private static function boolish(?string $v): bool
    {
        if ($v === null) return false;
        $lower = strtolower(trim($v));
        return in_array($lower, ['1', 'y', 'yes', 'true', 't'], true);
    }
}

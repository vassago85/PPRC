<?php

namespace App\Services\Events;

use App\Models\Event;
use App\Models\EventResult;
use App\Models\Member;
use Illuminate\Support\Facades\DB;

/**
 * Imports event results from a CSV file.
 *
 * Expected headers (case-insensitive, extra cols ignored):
 *   rank, shooter_name, division, class, member_id, member_email,
 *   hits, possible, points, percentage, time_ms, dnf, dq, notes
 *
 * Any of the member_* columns can be used to resolve a PPRC member and link
 * the result back to their profile. If no match is found the shooter_name
 * still carries the result forward so historical data is never dropped.
 */
class EventResultsCsvImporter
{
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

        $headers = array_map(fn ($h) => strtolower(trim((string) $h)), $headerRow);

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

                $shooterName = $data['shooter_name'] ?? '';
                if ($shooterName === '') {
                    $errors[] = "Row {$rowNum}: missing shooter_name.";
                    continue;
                }

                $memberId = $this->resolveMemberId($data);

                $attrs = [
                    'shooter_name' => $shooterName,
                    'division' => $data['division'] ?? null ?: null,
                    'class' => $data['class'] ?? null ?: null,
                    'rank' => self::nullableInt($data['rank'] ?? null),
                    'score_hits' => self::nullableInt($data['hits'] ?? null),
                    'score_possible' => self::nullableInt($data['possible'] ?? null),
                    'score_points' => self::nullableInt($data['points'] ?? null),
                    'score_percentage' => self::nullableFloat($data['percentage'] ?? null),
                    'score_time_ms' => self::nullableInt($data['time_ms'] ?? null),
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
        if ($v === null || $v === '') return null;
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

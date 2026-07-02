<?php

namespace App\Services\Events;

use App\Enums\MatchEntryAudience;
use App\Models\Event;
use App\Models\EventRegistration;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Builds a DeadCenter-friendly squad list for a match. DeadCenter sets up a
 * match from squads → shooters (name, bib, division, category), so we export
 * the confirmed entries as a CSV the match director can load when creating
 * the match in DeadCenter.
 */
class MatchEntryDeadCenterExporter
{
    /** @var array<int, string> */
    public const HEADERS = [
        'Squad', 'Bib', 'Name', 'Division', 'Category',
        'Email', 'Phone', 'Membership #', 'Has account',
    ];

    /**
     * Ordered CSV rows for the entries matching the given audience (defaults
     * to confirmed / paid, the set DeadCenter needs for scoring).
     *
     * @return array<int, array<int, string>>
     */
    public function rows(Event $event, MatchEntryAudience $audience = MatchEntryAudience::Confirmed): array
    {
        return $audience->filter($event)
            ->sortBy(fn (EventRegistration $r) => sprintf(
                '%06d-%06d-%s',
                $r->squad_number ?? 999999,
                $r->firing_order ?? 999999,
                $r->shooterName(),
            ))
            ->map(fn (EventRegistration $r) => [
                $r->squad_number ? (string) $r->squad_number : '',
                $r->firing_order ? (string) $r->firing_order : '',
                $r->shooterName(),
                $r->division ?? '',
                $r->category ?? '',
                $r->payerEmail() ?? '',
                $r->contactPhone() ?? '',
                $r->member?->membership_number ?? '',
                $r->member ? 'Yes' : 'No',
            ])
            ->values()
            ->all();
    }

    public function filename(Event $event): string
    {
        return 'entries-'.Str::slug($event->title ?: 'match').'-'.now()->format('Ymd-Hi').'.csv';
    }

    public function download(Event $event, MatchEntryAudience $audience = MatchEntryAudience::Confirmed): StreamedResponse
    {
        $rows = $this->rows($event, $audience);

        return response()->streamDownload(function () use ($rows): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, self::HEADERS);
            foreach ($rows as $row) {
                fputcsv($out, $row);
            }
            fclose($out);
        }, $this->filename($event), [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}

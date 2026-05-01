<?php

namespace App\Console\Commands;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\EventResult;
use App\Models\Member;
use Carbon\Carbon;
use DOMDocument;
use DOMXPath;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Pull a finished match's results page from PractiScore and import each
 * shooter as an EventResult on a chosen PPRC event.
 *
 * Usage:
 *   php artisan results:import-practiscore \
 *       --event=warrior-mini-grind \
 *       --url=https://practiscore.com/results/html/<uuid>?page=overall-combined \
 *       [--replace] [--publish] [--dry-run]
 *
 * The match-id can be passed directly with --match-id=<uuid> instead of the
 * full URL. Multiple shooter rows for the same event are de-duplicated by
 * matching either the linked member id (when we can resolve it from name)
 * or the shooter name + division string.
 */
class ImportPractiscoreResults extends Command
{
    protected $signature = 'results:import-practiscore
        {--event= : Event id or slug on this site (omit with --create to use the PractiScore name)}
        {--url= : Full PractiScore HTML results URL}
        {--match-id= : PractiScore match UUID (alternative to --url)}
        {--page=overall-combined : PractiScore page key (overall-combined, overall, etc.)}
        {--create : If --event is missing or the slug does not exist, create the event from the PractiScore page (title + date)}
        {--replace : Wipe existing EventResult rows for this event before import}
        {--publish : Mark the event as having published results when done}
        {--dry-run : Parse and report only — no DB writes}';

    protected $description = 'Import match results from a public PractiScore results page.';

    public function handle(): int
    {
        $url = $this->resolveUrl();
        if ($url === null) {
            $this->error('Pass --url=<full-url> or --match-id=<uuid>');

            return self::INVALID;
        }

        $eventRef = trim((string) $this->option('event'));
        $autoCreate = (bool) $this->option('create');

        if ($eventRef === '' && ! $autoCreate) {
            $this->error('Pass --event=<id-or-slug>, or use --create to auto-create from the PractiScore page.');

            return self::INVALID;
        }

        $this->info("Source: {$url}");

        $html = $this->fetchHtml($url);
        if ($html === null) {
            return self::FAILURE;
        }

        $event = $this->resolveOrCreateEvent($eventRef, $autoCreate, $html);
        if (! $event) {
            return self::FAILURE;
        }

        $this->info("Event:  {$event->title} (#{$event->id}, slug={$event->slug})");

        $rows = $this->parseRows($html);
        if (empty($rows)) {
            $this->error('No result rows found. PractiScore page format may have changed — re-run with --dry-run to inspect parser output.');

            return self::FAILURE;
        }

        $this->info('Parsed '.count($rows).' shooter rows.');

        if ($this->option('dry-run')) {
            $this->table(
                ['Rank', 'Shooter', 'Division', 'Class', 'Pct', 'Points', 'Hits', 'Possible'],
                array_map(static fn ($r) => [
                    $r['rank'] ?? '—',
                    $r['shooter_name'],
                    $r['division'] ?? '—',
                    $r['class'] ?? '—',
                    $r['score_percentage'] !== null ? number_format($r['score_percentage'], 2).'%' : '—',
                    $r['score_points'] ?? '—',
                    $r['score_hits'] ?? '—',
                    $r['score_possible'] ?? '—',
                ], $rows),
            );
            $this->warn('Dry run — no rows were written.');

            return self::SUCCESS;
        }

        $created = 0;
        $updated = 0;

        DB::transaction(function () use ($event, $rows, &$created, &$updated) {
            if ($this->option('replace')) {
                EventResult::where('event_id', $event->id)->delete();
            }

            foreach ($rows as $row) {
                $memNumber = $row['_mem_number'] ?? null;
                unset($row['_mem_number']);

                $memberId = $this->resolveMemberId($row['shooter_name'], $memNumber);

                $attrs = array_merge($row, ['member_id' => $memberId]);

                $lookup = $memberId
                    ? ['event_id' => $event->id, 'member_id' => $memberId]
                    : ['event_id' => $event->id, 'shooter_name' => $row['shooter_name'], 'member_id' => null];

                $existing = EventResult::where($lookup)->first();
                if ($existing) {
                    $existing->update($attrs);
                    $updated++;
                } else {
                    EventResult::create(array_merge(['event_id' => $event->id], $attrs));
                    $created++;
                }
            }

            if ($this->option('publish')) {
                $event->forceFill(['results_published_at' => now()])->save();
            }
        });

        $this->info("Created: {$created}");
        $this->info("Updated: {$updated}");
        if ($this->option('publish')) {
            $this->info('Event marked as results-published.');
        }

        return self::SUCCESS;
    }

    private function resolveOrCreateEvent(string $eventRef, bool $autoCreate, string $html): ?Event
    {
        if ($eventRef !== '') {
            $event = ctype_digit($eventRef)
                ? Event::find((int) $eventRef)
                : Event::where('slug', $eventRef)->first();

            if ($event) {
                return $event;
            }

            if (! $autoCreate) {
                $this->error("Event '{$eventRef}' not found. Re-run with --create to make a new one from the PractiScore page.");

                return null;
            }
        }

        $meta = $this->parseMatchMeta($html);
        $title = $meta['title'] ?: 'PractiScore Match '.now()->format('Y-m-d H:i');
        $startDate = $meta['date'] ?? Carbon::today();

        $slug = $eventRef !== '' && ! ctype_digit($eventRef)
            ? Str::slug($eventRef)
            : Str::slug($title);

        if ($existing = Event::where('slug', $slug)->first()) {
            return $existing;
        }

        if ($this->option('dry-run')) {
            $this->warn("Would create event: title='{$title}', slug='{$slug}', start_date={$startDate->toDateString()}");

            // Build an unsaved Event so the dry-run report still has something to render.
            return (new Event)->forceFill([
                'id' => 0,
                'title' => $title,
                'slug' => $slug,
                'start_date' => $startDate,
                'status' => EventStatus::Completed,
            ]);
        }

        $event = Event::create([
            'title' => $title,
            'slug' => $slug,
            'summary' => 'Imported from PractiScore.',
            'start_date' => $startDate,
            'end_date' => $startDate,
            'status' => EventStatus::Completed,
            'registrations_open' => false,
            'published_at' => now(),
            'results_published_at' => null,
        ]);

        $this->info("Created event #{$event->id}: {$event->title}");

        return $event;
    }

    /**
     * Pull the match name and date out of the PractiScore HTML.
     *
     * @return array{title: string, date: ?Carbon}
     */
    private function parseMatchMeta(string $html): array
    {
        libxml_use_internal_errors(true);
        $doc = new DOMDocument;
        $doc->loadHTML('<?xml encoding="UTF-8">'.$html, LIBXML_NOERROR | LIBXML_NOWARNING);
        $xp = new DOMXPath($doc);

        $title = '';
        $titleNode = $xp->query('//title')->item(0);
        if ($titleNode) {
            // PractiScore titles are usually "<Match Name> | PractiScore" — drop the suffix.
            $title = trim(preg_replace('/\s*\|\s*PractiScore.*$/i', '', $titleNode->textContent ?? ''));
        }

        if ($title === '') {
            $h1 = $xp->query('//h1')->item(0);
            if ($h1) {
                $title = trim($h1->textContent ?? '');
            }
        }

        $title = preg_replace('/\s+/u', ' ', $title);

        // Look for a date anywhere on the page in the most common formats. PractiScore
        // tends to render "Match Date: 2025-08-17" or "Aug 17, 2025" near the header.
        $date = null;
        $bodyText = $doc->textContent ?? '';
        if (preg_match('/(\d{4}-\d{2}-\d{2})/', $bodyText, $m)) {
            try {
                $date = Carbon::parse($m[1]);
            } catch (\Throwable) {
                $date = null;
            }
        }
        if ($date === null && preg_match('/\b((?:Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)[a-z]*\s+\d{1,2},?\s+\d{4})\b/u', $bodyText, $m)) {
            try {
                $date = Carbon::parse($m[1]);
            } catch (\Throwable) {
                $date = null;
            }
        }

        return ['title' => $title, 'date' => $date];
    }

    private function resolveUrl(): ?string
    {
        if ($url = $this->option('url')) {
            return (string) $url;
        }

        if ($id = $this->option('match-id')) {
            $page = (string) $this->option('page');

            return "https://practiscore.com/results/html/{$id}?page={$page}";
        }

        return null;
    }

    private function fetchHtml(string $url): ?string
    {
        $response = Http::withHeaders([
            // Real browser UA — PractiScore 403s on plain Guzzle/curl identifiers.
            'User-Agent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 '
                .'(KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language' => 'en-ZA,en;q=0.9',
        ])->timeout(30)->get($url);

        if (! $response->ok()) {
            $this->error("PractiScore responded {$response->status()} when fetching results.");

            return null;
        }

        return $response->body();
    }

    /**
     * @return array<int, array{shooter_name: string, division: ?string, class: ?string, rank: ?int, score_hits: ?int, score_possible: ?int, score_points: ?int, score_percentage: ?float, score_time_ms: ?int, dnf: bool, dq: bool, notes: ?string}>
     */
    private function parseRows(string $html): array
    {
        libxml_use_internal_errors(true);
        $doc = new DOMDocument;
        $doc->loadHTML('<?xml encoding="UTF-8">'.$html, LIBXML_NOERROR | LIBXML_NOWARNING);
        $xp = new DOMXPath($doc);

        // PractiScore renders the standings inside a <table>; find one whose
        // header row contains both a "Place" / "Rank" cell and a percentage.
        $tables = $xp->query('//table');
        $best = null;
        $bestHeaders = [];
        foreach ($tables as $table) {
            $headerCells = $xp->query('.//thead//th | .//tr[1]/th | .//tr[1]/td', $table);
            $headers = [];
            foreach ($headerCells as $h) {
                $headers[] = $this->normaliseHeader($h->textContent);
            }
            if ($headers === []) {
                continue;
            }

            $hasRank = (bool) array_intersect($headers, ['place', 'rank', '#', 'pos', 'position']);
            $hasName = (bool) array_intersect($headers, ['shooter', 'name', 'competitor', 'shooter_name']);
            if ($hasRank && $hasName) {
                $best = $table;
                $bestHeaders = $headers;
                break;
            }
        }

        if ($best === null) {
            return [];
        }

        $rows = [];
        $bodyRows = $xp->query('.//tbody/tr', $best);
        if ($bodyRows->length === 0) {
            // Some pages render rows directly inside <table> without <tbody>; skip the header row.
            $allRows = $xp->query('.//tr', $best);
            $bodyRows = [];
            foreach ($allRows as $i => $r) {
                if ($i === 0) {
                    continue;
                }
                $bodyRows[] = $r;
            }
        }

        foreach ($bodyRows as $tr) {
            $cells = $xp->query('.//td', $tr);
            if ($cells->length === 0) {
                continue;
            }

            $values = [];
            foreach ($cells as $c) {
                $values[] = trim(preg_replace('/\s+/u', ' ', $c->textContent ?? ''));
            }

            $row = $this->mapRow($bestHeaders, $values);
            if ($row !== null) {
                $rows[] = $row;
            }
        }

        return $rows;
    }

    private function normaliseHeader(string $raw): string
    {
        $h = strtolower(trim($raw));
        $h = preg_replace('/\s+/', ' ', $h);

        return match (true) {
            str_contains($h, 'shooter') || str_contains($h, 'name') || str_contains($h, 'competitor') => 'shooter',
            str_contains($h, 'place') || $h === 'pos' || $h === 'position' || $h === '#' || $h === 'rank' => 'rank',
            str_contains($h, 'div') => 'division',
            str_contains($h, 'class') => 'class',
            str_contains($h, '%') || str_contains($h, 'percent') => 'percentage',
            str_contains($h, 'point') || $h === 'pts' || str_contains($h, 'score') => 'points',
            str_contains($h, 'hit') => 'hits',
            str_contains($h, 'time') => 'time',
            str_contains($h, 'dq') => 'dq',
            str_contains($h, 'dnf') => 'dnf',
            str_contains($h, 'mem') || str_contains($h, 'member') => 'mem_number',
            default => $h,
        };
    }

    /**
     * @param array<int,string> $headers
     * @param array<int,string> $values
     */
    private function mapRow(array $headers, array $values): ?array
    {
        $cell = function (string $key) use ($headers, $values): ?string {
            $idx = array_search($key, $headers, true);
            if ($idx === false) {
                return null;
            }
            $v = $values[$idx] ?? null;

            return $v !== null && $v !== '' ? $v : null;
        };

        $name = $cell('shooter');
        if ($name === null) {
            return null;
        }

        // PractiScore's static HTML often prefixes the name cell with the
        // overall rank, e.g. "1-Olinchak, Matt" — pull the rank out and
        // hand back a clean "First Last" string for member matching.
        $rank = self::nullableInt($cell('rank'));
        if (preg_match('/^\s*(\d+)\s*-\s*(.+)$/', $name, $m)) {
            if ($rank === null) {
                $rank = (int) $m[1];
            }
            $name = trim($m[2]);
        }

        // "Last, First" → "First Last" so member lookup works against our DB.
        if (str_contains($name, ',')) {
            [$last, $first] = array_map('trim', explode(',', $name, 2));
            // Drop trailing edit-count badges PractiScore appends, e.g. "Few, Mark1".
            $first = preg_replace('/\d+$/', '', $first);
            $name = trim("{$first} {$last}");
        }

        $hits = $cell('hits');
        $possible = null;
        if ($hits !== null && str_contains($hits, '/')) {
            [$h, $p] = array_map('trim', explode('/', $hits, 2));
            $hits = $h;
            $possible = $p;
        }

        $pct = $cell('percentage');
        if ($pct !== null) {
            $pct = (float) trim(str_replace(['%', ','], ['', ''], $pct));
        }

        $time = $cell('time');
        $timeMs = null;
        if ($time !== null && preg_match('/^([0-9:.]+)$/', $time)) {
            $timeMs = $this->parseTimeToMs($time);
        }

        $dq = (bool) $cell('dq') && in_array(strtolower($cell('dq')), ['1', 'y', 'yes', 'true', 'dq'], true);
        $dnf = (bool) $cell('dnf') && in_array(strtolower($cell('dnf')), ['1', 'y', 'yes', 'true', 'dnf'], true);

        return [
            'shooter_name' => $name,
            'division' => $cell('division'),
            'class' => $cell('class'),
            'rank' => $rank,
            'score_hits' => self::nullableInt($hits),
            'score_possible' => self::nullableInt($possible),
            'score_points' => self::nullableInt($cell('points')),
            'score_percentage' => $pct,
            'score_time_ms' => $timeMs,
            'dnf' => $dnf,
            'dq' => $dq,
            'notes' => null,
            '_mem_number' => $cell('mem_number'),
        ];
    }

    private function parseTimeToMs(string $time): ?int
    {
        // Accept "12.345", "1:23.456", "01:23:45.678".
        $parts = explode(':', $time);
        $seconds = 0.0;
        foreach ($parts as $part) {
            $seconds = ($seconds * 60) + (float) $part;
        }

        return (int) round($seconds * 1000);
    }

    private function resolveMemberId(string $shooterName, ?string $memNumber = null): ?int
    {
        // PractiScore "Mem #" usually holds USPSA / IDPA numbers, but for our
        // local matches shooters often type their PPRC membership number here.
        if ($memNumber !== null && $memNumber !== '' && ! in_array(strtoupper($memNumber), ['NONE', 'PEN', 'N/A'], true)) {
            $member = Member::where('membership_number', $memNumber)->first();
            if ($member) {
                return $member->id;
            }
        }

        $shooter = trim(preg_replace('/\s+/', ' ', $shooterName));
        if ($shooter === '') {
            return null;
        }

        $parts = explode(' ', $shooter, 2);
        if (count($parts) === 2) {
            [$first, $last] = $parts;

            $member = Member::whereRaw('lower(first_name) = ?', [strtolower($first)])
                ->whereRaw('lower(last_name) = ?', [strtolower($last)])
                ->first();
            if ($member) {
                return $member->id;
            }

            $member = Member::whereRaw('lower(known_as) = ?', [strtolower($first)])
                ->whereRaw('lower(last_name) = ?', [strtolower($last)])
                ->first();
            if ($member) {
                return $member->id;
            }
        }

        return null;
    }

    private static function nullableInt(?string $v): ?int
    {
        if ($v === null) {
            return null;
        }
        $clean = preg_replace('/[^0-9-]/', '', $v);

        return $clean === '' ? null : (int) $clean;
    }
}

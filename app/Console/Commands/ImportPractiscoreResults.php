<?php

namespace App\Console\Commands;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\EventResult;
use App\Models\MatchFormat;
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
        {--file= : Path to a saved PractiScore HTML file (use this if PractiScore 403s the server)}
        {--page=overall-combined : PractiScore page key (overall-combined, overall, etc.)}
        {--create : If --event is missing or the slug does not exist, create the event from the PractiScore page (title + date)}
        {--replace : Wipe existing EventResult rows for this event before import}
        {--publish : Mark the event as having published results when done}
        {--dry-run : Parse and report only — no DB writes}
        {--debug : Print fetch diagnostics (status, headers, body snippet) and exit}';

    protected $description = 'Import match results from a public PractiScore results page.';

    public function handle(): int
    {
        $file = $this->option('file');
        $url = $this->resolveUrl();

        if ($file === null && $url === null) {
            $this->error('Pass --file=<path>, --url=<full-url>, or --match-id=<uuid>');

            return self::INVALID;
        }

        $eventRef = trim((string) $this->option('event'));
        $autoCreate = (bool) $this->option('create');

        if ($eventRef === '' && ! $autoCreate) {
            $this->error('Pass --event=<id-or-slug>, or use --create to auto-create from the source.');

            return self::INVALID;
        }

        if ($file !== null) {
            $this->info("Source: file://{$file}");
            if (! is_readable($file)) {
                $this->error("File not readable: {$file}");

                return self::FAILURE;
            }
            $html = (string) file_get_contents($file);
        } else {
            $this->info("Source: {$url}");
            $html = $this->fetchHtml($url);
            if ($html === null) {
                return self::FAILURE;
            }
        }

        if ($this->option('debug')) {
            $this->line('--- first 800 chars of body ---');
            $this->line(substr($html, 0, 800));
            $this->line('--- end snippet ---');
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
                ['Rank', 'Shooter', 'Division', 'Category', 'Pct', 'Points', 'Hits', 'Possible'],
                array_map(static fn ($r) => [
                    $r['rank'] ?? '—',
                    $r['shooter_name'],
                    $r['division'] ?? '—',
                    $r['category'] ?? '—',
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

        $matchFormat = $this->pickMatchFormat($title);

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
            'match_format_id' => $matchFormat?->id,
        ]);

        $this->info("Created event #{$event->id}: {$event->title}");

        return $event;
    }

    /**
     * Choose the match format for an auto-created event. Best-effort guess from
     * the title so .22 / rimfire matches land under PR22 and everything else
     * under the centerfire PRS bucket. Falls back to whichever active format
     * exists if both lookups fail (so we never throw away a match).
     */
    private function pickMatchFormat(string $title): ?MatchFormat
    {
        $needle = strtolower($title);
        $isRimfire = str_contains($needle, '.22')
            || str_contains($needle, 'rimfire')
            || str_contains($needle, 'pr22');

        $slug = $isRimfire ? 'pr22' : 'prs-centerfire';

        return MatchFormat::where('slug', $slug)->first()
            ?? MatchFormat::where('is_active', true)->orderBy('sort_order')->first()
            ?? MatchFormat::orderBy('id')->first();
    }

    /**
     * Pull the match name and date out of the PractiScore HTML.
     *
     * @return array{title: string, date: ?Carbon}
     */
    private function parseMatchMeta(string $html): array
    {
        // Use the wrapper page's <title> first since the og:title metadata
        // is the cleanest source of the match name.
        libxml_use_internal_errors(true);
        $doc = new DOMDocument;
        $doc->loadHTML('<?xml encoding="UTF-8">'.$html, LIBXML_NOERROR | LIBXML_NOWARNING);
        $xp = new DOMXPath($doc);

        $title = '';

        // PractiScore exposes the canonical match name in og:title — prefer it
        // over <title> because <title> sometimes has trailing whitespace/newlines.
        $og = $xp->query('//meta[@property="og:title"]/@content')->item(0);
        if ($og) {
            $title = trim($og->nodeValue ?? '');
        }

        if ($title === '') {
            $titleNode = $xp->query('//title')->item(0);
            if ($titleNode) {
                $raw = (string) ($titleNode->textContent ?? '');
                // Strip the trailing "| PractiScore" suffix and any surrounding whitespace,
                // including newlines (the wrapper page's <title> is multi-line).
                $title = trim(preg_replace('/\s*\|\s*PractiScore[\s\S]*$/i', '', $raw));
            }
        }

        if ($title === '') {
            $h1 = $xp->query('//h1')->item(0);
            if ($h1) {
                $title = trim($h1->textContent ?? '');
            }
        }

        $title = preg_replace('/\s+/u', ' ', $title);

        // Date can be inside the embedded data string (e.g. "Match Name : 2026-03-14"),
        // not in the wrapper page body, so search the unwrapped text too.
        $date = null;
        $bodyText = ($doc->textContent ?? '').' '.$this->unwrapEmbeddedResults($html);
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
        $debug = (bool) $this->option('debug');
        $candidates = $this->buildUrlCandidates($url);

        $headers = [
            // PractiScore's CDN sniffs these — match a real Chrome request.
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 '
                .'(KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36',
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,'
                .'image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
            'Accept-Language' => 'en-ZA,en;q=0.9',
            'Accept-Encoding' => 'gzip, deflate, br',
            'Cache-Control' => 'no-cache',
            'Pragma' => 'no-cache',
            'Sec-Ch-Ua' => '"Chromium";v="126", "Not.A/Brand";v="24", "Google Chrome";v="126"',
            'Sec-Ch-Ua-Mobile' => '?0',
            'Sec-Ch-Ua-Platform' => '"Windows"',
            'Sec-Fetch-Dest' => 'document',
            'Sec-Fetch-Mode' => 'navigate',
            'Sec-Fetch-Site' => 'none',
            'Sec-Fetch-User' => '?1',
            'Upgrade-Insecure-Requests' => '1',
            'DNT' => '1',
            'Connection' => 'keep-alive',
        ];

        $lastStatus = null;
        foreach ($candidates as $candidate) {
            try {
                $response = Http::withHeaders($headers)->timeout(30)->withOptions([
                    'allow_redirects' => true,
                    'verify' => true,
                ])->get($candidate);
            } catch (\Throwable $e) {
                if ($debug) {
                    $this->warn("  Try {$candidate} → exception: ".$e->getMessage());
                }
                continue;
            }

            $lastStatus = $response->status();

            if ($debug) {
                $this->line("  Try {$candidate} → HTTP {$lastStatus}");
                foreach ($response->headers() as $h => $v) {
                    $this->line('    '.$h.': '.implode(', ', (array) $v));
                }
            }

            if ($response->ok() && trim($response->body()) !== '') {
                if ($candidate !== $url) {
                    $this->info("Resolved via fallback URL: {$candidate}");
                }

                return $response->body();
            }
        }

        $this->error("PractiScore returned HTTP {$lastStatus} for every URL variant.");
        $this->warn('PractiScore (Cloudflare) likely blocks this server\'s IP. Workaround:');
        $this->warn('  1. Open the results page in your browser.');
        $this->warn('  2. View source / save the page as practiscore.html.');
        $this->warn('  3. Re-run the command with --file=/path/to/practiscore.html (you can scp it onto the server).');

        return null;
    }

    /**
     * @return array<int, string>
     */
    private function buildUrlCandidates(string $url): array
    {
        $candidates = [$url];

        // Pull the UUID out so we can also try the SPA "new" view, which is
        // sometimes served from a less-aggressive cache rule on PractiScore.
        if (preg_match('~/results/(?:html|new)/([0-9a-f-]{36})~i', $url, $m)) {
            $uuid = $m[1];
            $page = (string) $this->option('page') ?: 'overall-combined';

            $alts = [
                "https://practiscore.com/results/html/{$uuid}",
                "https://practiscore.com/results/html/{$uuid}?page={$page}",
                "https://practiscore.com/results/new/{$uuid}",
            ];

            foreach ($alts as $alt) {
                if (! in_array($alt, $candidates, true)) {
                    $candidates[] = $alt;
                }
            }
        }

        return $candidates;
    }

    /**
     * @return array<int, array{shooter_name: string, division: ?string, class: ?string, rank: ?int, score_hits: ?int, score_possible: ?int, score_points: ?int, score_percentage: ?float, score_time_ms: ?int, dnf: bool, dq: bool, notes: ?string}>
     */
    private function parseRows(string $html): array
    {
        $html = $this->unwrapEmbeddedResults($html);

        libxml_use_internal_errors(true);
        $doc = new DOMDocument;
        $doc->loadHTML('<?xml encoding="UTF-8">'.$html, LIBXML_NOERROR | LIBXML_NOWARNING);
        $xp = new DOMXPath($doc);

        // PractiScore renders standings as one or more <table>s. Each table can
        // have a banner row ("Match Results - Combined") above the real header
        // row, and SAPRF exports often skip <thead>/<tbody> entirely. Walk every
        // row, find the one that looks like the column header (contains a Place
        // and a Name cell), then treat every later row with data cells as a
        // shooter line. We collect rows from every table and concatenate so
        // multi-table exports (per-division) still aggregate.
        $rows = [];
        $tables = $xp->query('//table');

        foreach ($tables as $table) {
            $allRows = iterator_to_array($xp->query('.//tr', $table));
            $headers = [];
            $headerIndex = null;

            foreach ($allRows as $i => $tr) {
                $candidate = [];
                foreach ($xp->query('./th | ./td', $tr) as $cell) {
                    $candidate[] = $this->normaliseHeader($cell->textContent ?? '');
                }
                if ($candidate === []) {
                    continue;
                }

                $hasRank = (bool) array_intersect($candidate, ['rank']);
                $hasName = (bool) array_intersect($candidate, ['shooter']);
                if ($hasRank && $hasName) {
                    $headers = $candidate;
                    $headerIndex = $i;
                    break;
                }
            }

            if ($headerIndex === null) {
                continue;
            }

            for ($i = $headerIndex + 1; $i < count($allRows); $i++) {
                $tr = $allRows[$i];
                $cells = $xp->query('./td', $tr);
                if ($cells->length === 0) {
                    continue;
                }

                // Skip "division" banner rows that appear between sections —
                // they typically have a single <td colspan="…"> with bold text.
                if ($cells->length === 1) {
                    /** @var \DOMElement $only */
                    $only = $cells->item(0);
                    $colspan = (int) $only->getAttribute('colspan');
                    if ($colspan > 1) {
                        continue;
                    }
                }

                $values = [];
                foreach ($cells as $c) {
                    $values[] = trim(preg_replace('/\s+/u', ' ', $c->textContent ?? ''));
                }

                $row = $this->mapRow($headers, $values);
                if ($row !== null) {
                    $rows[] = $row;
                }
            }
        }

        return $rows;
    }

    private function normaliseHeader(string $raw): string
    {
        $h = strtolower(trim($raw));
        $h = preg_replace('/\s+/', ' ', $h);
        $h = rtrim($h, '.');

        return match (true) {
            str_contains($h, 'shooter') || str_contains($h, 'name') || str_contains($h, 'competitor') => 'shooter',
            str_contains($h, 'place') || $h === 'pos' || $h === 'position' || $h === '#' || $h === 'rank' => 'rank',
            str_contains($h, 'div') => 'division',
            str_contains($h, 'class') => 'class',
            str_contains($h, '%') || str_contains($h, 'percent') => 'percentage',
            str_contains($h, 'point') || str_contains($h, 'pts') || str_contains($h, 'score') => 'points',
            str_contains($h, 'hit') => 'hits',
            str_contains($h, 'time') => 'time',
            str_contains($h, 'dq') => 'dq',
            str_contains($h, 'dnf') => 'dnf',
            str_contains($h, 'category') || str_contains($h, 'cat') => 'category',
            // PractiScore SAPRF-style exports use a "No." column for the membership number.
            $h === 'no' || str_contains($h, 'mem') || str_contains($h, 'member') => 'mem_number',
            default => $h,
        };
    }

    /**
     * Some PractiScore "Save As" exports embed the entire results document as
     * a JS string assigned to `var data = "..."` — the table never makes it
     * into the static markup. Detect that, decode the JS string, and hand the
     * inner HTML back so the rest of the parser can treat it normally.
     */
    private function unwrapEmbeddedResults(string $html): string
    {
        if (! preg_match('/var\s+data\s*=\s*"((?:\\\\.|[^"\\\\])*)"\s*;/s', $html, $m)) {
            return $html;
        }

        $escaped = $m[1];
        $decoded = preg_replace_callback(
            '/\\\\(["\\\\\/])|\\\\n|\\\\r|\\\\t|\\\\u([0-9a-fA-F]{4})/',
            function ($match) {
                if (isset($match[1]) && $match[1] !== '') {
                    return $match[1];
                }
                if (str_starts_with($match[0], '\\u') && isset($match[2])) {
                    return mb_convert_encoding(pack('H*', $match[2]), 'UTF-8', 'UCS-2BE');
                }

                return match ($match[0]) {
                    '\\n' => "\n",
                    '\\r' => "\r",
                    '\\t' => "\t",
                    default => $match[0],
                };
            },
            $escaped
        );

        return is_string($decoded) && $decoded !== '' ? $decoded : $html;
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

        // PractiScore mixes shooter categories (Ladies/Seniors/Juniors) into
        // the same "Div" column as the rifle division (Open/Factory/etc).
        // Split them so the public results page can filter both axes
        // independently. The "Class" column from PractiScore is the
        // discipline code (SAPRFP60R, PPRCCM42R) which we already track on
        // the Event itself, so we discard it here.
        [$division, $category] = $this->splitDivisionAndCategory(
            $cell('division'),
            $cell('category')
        );
        $points = self::nullableFloat($cell('points'));

        return [
            'shooter_name' => $name,
            'division' => $division,
            'category' => $category,
            'rank' => $rank,
            'score_hits' => self::nullableInt($hits),
            'score_possible' => self::nullableInt($possible),
            'score_points' => $points !== null ? (int) round($points) : null,
            'score_percentage' => $pct,
            'score_time_ms' => $timeMs,
            'dnf' => $dnf,
            'dq' => $dq,
            'notes' => null,
            '_mem_number' => $cell('mem_number'),
        ];
    }

    /**
     * PractiScore SAPRF/PPRC exports stuff shooter category (LADIES, SENIORS,
     * JUNIORS) into the Division column right next to real divisions
     * (OPEN, FACTORY, LIMITEDTACTICAL...). Splitting them lets the public
     * results page filter by both axes independently.
     *
     * @return array{0: ?string, 1: ?string} [division, category]
     */
    private function splitDivisionAndCategory(?string $rawDivision, ?string $rawCategory): array
    {
        $categories = ['LADIES', 'SENIORS', 'JUNIORS', 'MILITARY', 'LAWMAN'];

        $division = $rawDivision !== null ? trim($rawDivision) : null;
        $category = $rawCategory !== null ? trim($rawCategory) : null;

        if ($division !== null && in_array(strtoupper($division), $categories, true) && $category === null) {
            return [null, ucfirst(strtolower($division))];
        }

        return [
            $division !== '' ? $division : null,
            $category !== null && $category !== '' ? $category : null,
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

    private static function nullableFloat(?string $v): ?float
    {
        if ($v === null) {
            return null;
        }
        $clean = preg_replace('/[^0-9.\-]/', '', $v);

        return $clean === '' || ! is_numeric($clean) ? null : (float) $clean;
    }
}

<?php

use App\Models\Event;
use App\Models\EventResult;
use App\Models\MatchFormat;
use App\Services\Events\EventResultsCsvImporter;

function makeImpactEvent(): Event
{
    $format = MatchFormat::create([
        'slug' => 'pr22',
        'name' => 'Precision Rifle .22',
        'short_name' => 'PR22',
        'is_active' => true,
        'sort_order' => 1,
    ]);

    return Event::create([
        'match_format_id' => $format->id,
        'slug' => '2-day-national-pr22',
        'title' => '2 Day National PR22',
        'start_date' => '2026-06-08',
        'status' => 'completed',
    ]);
}

function writeCsv(string $contents): string
{
    $path = tempnam(sys_get_temp_dir(), 'results_').'.csv';
    file_put_contents($path, $contents);

    return $path;
}

it('imports an impact-scoring export with split name and aliased headers', function () {
    $event = makeImpactEvent();

    // Exact header layout produced by the impact-scoring software.
    $csv = <<<CSV
    Place,Last,First,Class,Div,Category,Member Number,Time,Impacts,Match %
    1,Goncalves,Kevin,,Factory,Senior,1199,77.51,171,89.06%
    2,Slabbert,Gerhard,,Open,,1065,82.46,164,85.42%
    3,Shabangu,Dumisani,,Open,,,73.99,162,84.37%
    CSV;

    $report = app(EventResultsCsvImporter::class)->import($event, writeCsv($csv));

    expect($report['errors'])->toBe([]);
    expect($report['created'])->toBe(3);

    $first = EventResult::where('event_id', $event->id)->where('rank', 1)->first();

    expect($first)->not->toBeNull();
    expect($first->shooter_name)->toBe('Kevin Goncalves');
    expect($first->division)->toBe('Factory');
    expect($first->category)->toBe('Senior');
    expect($first->score_hits)->toBe(171);
    expect((float) $first->score_percentage)->toBe(89.06);
    expect($first->score_time_ms)->toBe(77510); // 77.51s -> ms
});

it('still imports the canonical shooter_name format', function () {
    $event = makeImpactEvent();

    $csv = <<<CSV
    rank,shooter_name,division,category,membership_number,hits,percentage,time_ms
    1,Jane Doe,Open,Ladies,1234,150,78.12,80000
    CSV;

    $report = app(EventResultsCsvImporter::class)->import($event, writeCsv($csv));

    expect($report['errors'])->toBe([]);
    expect($report['created'])->toBe(1);

    $row = EventResult::where('event_id', $event->id)->first();
    expect($row->shooter_name)->toBe('Jane Doe');
    expect($row->score_time_ms)->toBe(80000);
});

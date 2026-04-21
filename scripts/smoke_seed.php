<?php

/**
 * One-off data for local smoke tests (event + shop run). Run with SQLite DB.
 */
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

use App\Enums\EventStatus;
use App\Enums\ShopRunStatus;
use App\Models\Event;
use App\Models\MatchFormat;
use App\Models\ShopProduct;
use App\Models\ShopRun;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;

$mf = MatchFormat::query()->firstOrFail();
$u = User::query()->firstOrFail();

Event::query()->updateOrCreate(
    ['slug' => 'test-match'],
    [
        'match_format_id' => $mf->id,
        'title' => 'Test Match',
        'summary' => 'Smoke test',
        'start_date' => now()->addDays(7)->toDateString(),
        'start_time' => '07:00',
        'location_name' => 'Marloo',
        'location_address' => 'Bronkhorstspruit',
        'member_price_cents' => 45000,
        'non_member_price_cents' => 50000,
        'max_entries' => 600,
        'round_count' => 60,
        'registrations_open' => true,
        'status' => EventStatus::Published,
        'match_director_id' => $u->id,
        'published_at' => now(),
    ],
);

$run = ShopRun::query()->updateOrCreate(
    ['slug' => 'test-run'],
    [
        'title' => 'Test Run',
        'description' => 'Smoke',
        'status' => ShopRunStatus::Open,
        'preview_visible' => true,
        'orders_open_at' => now()->subDay(),
        'orders_close_at' => now()->addMonth(),
    ],
);

ShopProduct::query()->updateOrCreate(
    ['shop_run_id' => $run->id, 'slug' => 'tee'],
    [
        'name' => 'Club Tee',
        'description' => null,
        'price_cents' => 25000,
        'sort_order' => 1,
        'is_active' => true,
    ],
);

echo "smoke_seed ok\n";

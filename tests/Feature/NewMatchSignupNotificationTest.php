<?php

use App\Enums\EventRegistrationStatus;
use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\MatchFormat;
use App\Services\Admin\AdminDashboardService;
use Filament\Facades\Filament;

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

function makeSignupMatch(string $startDate, EventStatus $status = EventStatus::Published): Event
{
    $format = MatchFormat::firstOrCreate(
        ['slug' => 'prs-centerfire'],
        ['name' => 'PRS Centerfire', 'short_name' => 'PRS', 'is_active' => true],
    );

    return Event::create([
        'match_format_id' => $format->id,
        'title' => 'Match '.$startDate,
        'start_date' => $startDate,
        'status' => $status,
        'member_price_cents' => 15000,
        'non_member_price_cents' => 20000,
    ]);
}

function makeSignupEntry(Event $event, ?string $createdAt = null): EventRegistration
{
    $entry = EventRegistration::create([
        'event_id' => $event->id,
        'guest_name' => 'Shooter '.fake()->unique()->numberBetween(1, 100000),
        'status' => EventRegistrationStatus::Registered,
        'registered_at' => now(),
    ]);

    if ($createdAt) {
        $entry->forceFill(['created_at' => $createdAt])->saveQuietly();
    }

    return $entry;
}

it('counts recent signups for upcoming matches', function () {
    $upcoming = makeSignupMatch(now()->addWeek()->toDateString());

    makeSignupEntry($upcoming);                                  // today
    makeSignupEntry($upcoming, now()->subDays(3)->toDateString()); // within window

    expect(EventRegistration::query()->newSignups()->count())->toBe(2);
});

it('ignores signups older than the window', function () {
    $upcoming = makeSignupMatch(now()->addWeek()->toDateString());

    makeSignupEntry($upcoming, now()->subDays(30)->toDateString());

    expect(EventRegistration::query()->newSignups()->count())->toBe(0);
});

it('ignores signups for matches that already happened', function () {
    $past = makeSignupMatch(now()->subWeek()->toDateString());

    makeSignupEntry($past); // created now, but the match is in the past

    expect(EventRegistration::query()->newSignups()->count())->toBe(0);
});

it('surfaces new match entries on the dashboard action inbox', function () {
    $upcoming = makeSignupMatch(now()->addWeek()->toDateString());
    makeSignupEntry($upcoming);
    makeSignupEntry($upcoming);

    $items = collect(app(AdminDashboardService::class)->needsAttention());
    $card = $items->firstWhere('label', 'New match entries');

    expect($card)->not->toBeNull();
    expect($card['value'])->toBe(2);
});

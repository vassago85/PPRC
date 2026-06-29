<?php

use App\Enums\EventRegistrationStatus;
use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\MatchFormat;
use App\Services\Events\MatchEntryDeadCenterExporter;

function exportTestMatch(): Event
{
    $format = MatchFormat::firstOrCreate(
        ['slug' => 'prs-centerfire'],
        ['name' => 'PRS Centerfire', 'short_name' => 'PRS', 'is_active' => true],
    );

    return Event::create([
        'match_format_id' => $format->id,
        'title' => 'Legends 4 July',
        'start_date' => now()->addWeek()->toDateString(),
        'status' => EventStatus::Published,
        'member_price_cents' => 45000,
        'non_member_price_cents' => 50000,
    ]);
}

it('exports only confirmed entries, ordered by squad and firing order', function () {
    $event = exportTestMatch();

    // Confirmed (paid), squad 2 — a guest with no account
    EventRegistration::create([
        'event_id' => $event->id,
        'guest_name' => 'Bravo Shooter',
        'guest_email' => 'bravo@example.com',
        'guest_phone' => '082 555 1234',
        'division' => 'Open',
        'category' => 'Mens',
        'squad_number' => 2,
        'firing_order' => 1,
        'status' => EventRegistrationStatus::Registered,
        'registered_at' => now(),
        'paid_at' => now(),
    ]);

    // Confirmed (status), squad 1
    EventRegistration::create([
        'event_id' => $event->id,
        'guest_name' => 'Alpha Shooter',
        'division' => 'Limited',
        'category' => 'Senior',
        'squad_number' => 1,
        'firing_order' => 3,
        'status' => EventRegistrationStatus::Confirmed,
        'registered_at' => now(),
    ]);

    // Not confirmed — still awaiting payment, excluded
    EventRegistration::create([
        'event_id' => $event->id,
        'guest_name' => 'Owing Shooter',
        'status' => EventRegistrationStatus::Registered,
        'registered_at' => now(),
    ]);

    // Cancelled — excluded even if paid
    EventRegistration::create([
        'event_id' => $event->id,
        'guest_name' => 'Cancelled Shooter',
        'status' => EventRegistrationStatus::Cancelled,
        'registered_at' => now(),
        'paid_at' => now(),
    ]);

    $rows = app(MatchEntryDeadCenterExporter::class)->rows($event);

    expect($rows)->toHaveCount(2);
    // Squad 1 sorts before squad 2
    expect($rows[0])->toBe(['1', '3', 'Alpha Shooter', 'Limited', 'Senior', '', '', '', 'No']);
    // Guest contact details are exported for people without accounts
    expect($rows[1])->toBe(['2', '1', 'Bravo Shooter', 'Open', 'Mens', 'bravo@example.com', '082 555 1234', '', 'No']);
});

it('builds a slugged csv filename for the match', function () {
    $event = exportTestMatch();

    expect(app(MatchEntryDeadCenterExporter::class)->filename($event))
        ->toStartWith('entries-legends-4-july-')
        ->toEndWith('.csv');
});

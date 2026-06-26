<?php

use App\Enums\EventRegistrationStatus;
use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\MatchFormat;

function confirmTestMatch(): Event
{
    $format = MatchFormat::firstOrCreate(
        ['slug' => 'prs-centerfire'],
        ['name' => 'PRS Centerfire', 'short_name' => 'PRS', 'is_active' => true],
    );

    return Event::create([
        'match_format_id' => $format->id,
        'title' => 'Legends',
        'start_date' => now()->addWeek()->toDateString(),
        'status' => EventStatus::Published,
        'member_price_cents' => 45000,
        'non_member_price_cents' => 50000,
    ]);
}

it('treats a waived entry with confirmed status as confirmed', function () {
    $event = confirmTestMatch();

    $entry = EventRegistration::create([
        'event_id' => $event->id,
        'guest_name' => 'Henri Klopper',
        'fee_cents' => 0,
        'status' => EventRegistrationStatus::Confirmed,
        'registered_at' => now(),
    ]);

    expect($entry->paymentConfirmed())->toBeTrue();
});

it('treats a paid entry as confirmed', function () {
    $event = confirmTestMatch();

    $entry = EventRegistration::create([
        'event_id' => $event->id,
        'guest_name' => 'Paid Shooter',
        'status' => EventRegistrationStatus::Registered,
        'registered_at' => now(),
        'paid_at' => now(),
    ]);

    expect($entry->paymentConfirmed())->toBeTrue();
});

it('does not confirm a registered entry still awaiting payment', function () {
    $event = confirmTestMatch();

    $entry = EventRegistration::create([
        'event_id' => $event->id,
        'guest_name' => 'Owing Shooter',
        'status' => EventRegistrationStatus::Registered,
        'registered_at' => now(),
    ]);

    expect($entry->awaitingPayment())->toBeTrue();
    expect($entry->paymentConfirmed())->toBeFalse();
});

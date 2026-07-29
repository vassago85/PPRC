<?php

use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\MatchFormat;
use App\Support\PaymentReferencePrefix;
use Illuminate\Support\Facades\DB;

/**
 * A reference that has been emailed to somebody is a promise, so the one thing
 * these tests exist to prevent is a format change reaching back and rewriting
 * references that were already quoted. That happened once: shortening the format
 * silently renumbered every existing entry, so the admin table and the portal
 * disagreed with the shooter's inbox.
 */
function entryEvent(): Event
{
    $format = MatchFormat::firstOrCreate(
        ['slug' => 'prs-centerfire'],
        ['name' => 'PRS Centerfire', 'short_name' => 'PRS', 'is_active' => true],
    );

    return Event::create([
        'match_format_id' => $format->id,
        'title' => 'Club Match',
        'start_date' => now()->addWeek()->toDateString(),
        'member_price_cents' => 45000,
        'non_member_price_cents' => 55000,
    ]);
}

it('stamps a reference on a new entry so nothing has to recompute it later', function () {
    $entry = EventRegistration::create([
        'event_id' => entryEvent()->id,
        'guest_name' => 'Jaco Bosman',
        'guest_email' => 'jaco@example.com',
    ]);

    expect($entry->payment_reference)->toBe(PaymentReferencePrefix::get()."-M{$entry->id}")
        ->and($entry->paymentReference())->toBe($entry->payment_reference);
});

it('keeps quoting the reference an entry was given, not the one the format would produce today', function () {
    $entry = EventRegistration::create([
        'event_id' => entryEvent()->id,
        'guest_name' => 'Lee Thompson',
        'guest_email' => 'lee@example.com',
    ]);

    // Stand in for an entry emailed under the old long format.
    $legacy = PaymentReferencePrefix::get()."-M9-{$entry->id}";
    $entry->update(['payment_reference' => $legacy]);

    expect($entry->fresh()->paymentReference())->toBe($legacy);
});

it('does not renumber an entry when it is edited', function () {
    $entry = EventRegistration::create([
        'event_id' => entryEvent()->id,
        'guest_name' => 'Simon Duvenage',
        'guest_email' => 'simon@example.com',
    ]);

    $reference = $entry->paymentReference();

    $entry->update(['division' => 'Open', 'paid_at' => now()]);

    expect($entry->fresh()->paymentReference())->toBe($reference);
});

it('never hands out the same reference twice', function () {
    $event = entryEvent();

    $references = collect(range(1, 3))->map(fn (int $n) => EventRegistration::create([
        'event_id' => $event->id,
        'guest_name' => "Shooter {$n}",
        'guest_email' => "shooter{$n}@example.com",
    ])->paymentReference());

    expect($references->unique())->toHaveCount(3);
});

it('still produces a reference for a row that predates the column', function () {
    $entry = EventRegistration::create([
        'event_id' => entryEvent()->id,
        'guest_name' => 'Erich Van Der Merwe',
        'guest_email' => 'erich@example.com',
    ]);

    // Bypass the model so nothing can restamp it on the way through.
    DB::table('event_registrations')->where('id', $entry->id)->update(['payment_reference' => null]);

    expect($entry->fresh()->paymentReference())
        ->toBe(PaymentReferencePrefix::get()."-M{$entry->id}");
});

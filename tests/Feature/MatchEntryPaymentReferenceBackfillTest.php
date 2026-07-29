<?php

use App\Services\Payments\PaymentReferenceResolver;
use App\Support\PaymentReferencePrefix;
use Illuminate\Support\Facades\DB;

/**
 * The backfill is what gives every existing entry back the reference its shooter
 * was actually emailed, so it is worth proving rather than assuming.
 */
function backfillReferences(): void
{
    $migration = require database_path(
        'migrations/2026_07_29_150000_add_payment_reference_to_event_registrations_table.php',
    );

    (function () {
        $this->backfill();
    })->call($migration);
}

/** Insert an entry with no reference, the way rows looked before the column existed. */
function unreferencedEntry(int $id, int $eventId): void
{
    DB::table('event_registrations')->insert([
        'id' => $id,
        'event_id' => $eventId,
        'guest_name' => "Shooter {$id}",
        'guest_email' => "shooter{$id}@example.com",
        'status' => 'registered',
        'payment_reference' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

function referenceFor(int $id): ?string
{
    return DB::table('event_registrations')->where('id', $id)->value('payment_reference');
}

it('gives every existing entry back the long reference it was quoted', function () {
    $event = entryEvent();

    unreferencedEntry(71, $event->id);
    unreferencedEntry(77, $event->id);

    backfillReferences();

    $prefix = PaymentReferencePrefix::get();

    expect(referenceFor(71))->toBe("{$prefix}-M{$event->id}-71")
        ->and(referenceFor(77))->toBe("{$prefix}-M{$event->id}-77");
});

it('carries the match id so a reference stays traceable to its match', function () {
    $first = entryEvent();
    $second = entryEvent();

    unreferencedEntry(101, $first->id);
    unreferencedEntry(102, $second->id);

    backfillReferences();

    expect(referenceFor(101))->toContain("-M{$first->id}-")
        ->and(referenceFor(102))->toContain("-M{$second->id}-");
});

it('leaves an entry that already has a reference exactly as it is', function () {
    $event = entryEvent();

    unreferencedEntry(200, $event->id);
    DB::table('event_registrations')->where('id', 200)->update(['payment_reference' => 'PPRC-KEEPME']);

    backfillReferences();

    expect(referenceFor(200))->toBe('PPRC-KEEPME');
});

it('leaves nobody without a reference', function () {
    $event = entryEvent();

    foreach ([301, 302, 303] as $id) {
        unreferencedEntry($id, $event->id);
    }

    backfillReferences();

    expect(DB::table('event_registrations')->whereNull('payment_reference')->count())->toBe(0);
});

it('produces references the resolver can still read back', function () {
    $event = entryEvent();
    unreferencedEntry(103, $event->id);

    backfillReferences();

    $results = app(PaymentReferenceResolver::class)
        ->resolve('ABSA BANK '.referenceFor(103));

    expect($results)->not->toBeEmpty()
        ->and($results[0]->reference)->toBe(referenceFor(103));
});

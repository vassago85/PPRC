<?php

use App\Enums\EventRegistrationStatus;
use App\Enums\PaymentStatus;
use App\Services\Payments\PaymentMatch;
use App\Services\Payments\PaymentReferenceResolver;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    // The prefix is read through SiteSetting, which caches forever. Clear it so
    // these tests fall through to the config default of "PPRC" regardless of
    // what any earlier test in the run happened to store.
    Cache::forget('site_settings:payments.bank.reference_prefix');
});

function resolver(): PaymentReferenceResolver
{
    return app(PaymentReferenceResolver::class);
}

/*
|--------------------------------------------------------------------------
| Match entry references
|--------------------------------------------------------------------------
*/

it('reads a legacy reference hiding behind the bank name', function () {
    $event = refEvent(14, 'Match Fourteen');
    refEntry(103, $event, refMember('Jaco', 'Smit'));

    $results = resolver()->resolve('ABSA BANK PPRC-M14-103');

    expect($results[0]->kind)->toBe(PaymentMatch::MATCH_ENTRY)
        ->and($results[0]->id)->toBe(103)
        ->and($results[0]->confidence)->toBe(PaymentMatch::EXACT)
        ->and($results[0]->amountCents)->toBe(45000);
});

it('reads a reference the bank glued its own name onto', function () {
    $event = refEvent(14, 'Match Fourteen');
    refEntry(85, $event, refMember('Pieter', 'Nel'));

    $results = resolver()->resolve('INVESTECPBPPRC-M14-85');

    expect($results[0]->id)->toBe(85)
        ->and($results[0]->confidence)->toBe(PaymentMatch::EXACT);
});

it('reads a reference buried in the bank app narration', function () {
    $event = refEvent(13, 'Match Thirteen');
    refEntry(101, $event, refMember('Hannes', 'Botha'));

    $results = resolver()->resolve('FNB APP PAYMENT FROM PPRC-M13-101');

    expect($results[0]->id)->toBe(101)
        ->and($results[0]->confidence)->toBe(PaymentMatch::EXACT);
});

it('reads the current short reference', function () {
    $event = refEvent(14, 'Match Fourteen');
    $entry = refEntry(103, $event, refMember('Jaco', 'Smit'));

    expect($entry->paymentReference())->toBe('PPRC-M103');

    $results = resolver()->resolve('CAPITEC PPRC-M103');

    expect($results[0]->id)->toBe(103)
        ->and($results[0]->confidence)->toBe(PaymentMatch::EXACT);
});

it('resolves a reference whose separators the bank ate', function () {
    $event = refEvent(14, 'Match Fourteen');
    refEntry(89, $event, refMember('Wynand', 'Louw'));

    // "PPRCM1489" could be entry 1489, or entry 489 on match 1, or entry 89 on
    // match 14, or entry 9 on match 148. Only one of those is a real entry.
    $results = resolver()->resolve('PPRCM1489');

    expect($results[0]->id)->toBe(89)
        ->and($results[0]->confidence)->toBe(PaymentMatch::EXACT);
});

it('offers every reading when a reference without separators is genuinely ambiguous', function () {
    $shooter = refMember('Wynand', 'Louw');

    refEntry(89, refEvent(14, 'Match Fourteen'), $shooter);
    refEntry(489, refEvent(1, 'Match One'), $shooter);

    $results = collect(resolver()->resolve('PPRCM1489'))
        ->filter(fn (PaymentMatch $match) => $match->kind === PaymentMatch::MATCH_ENTRY);

    expect($results->map(fn (PaymentMatch $m) => $m->id)->sort()->values()->all())->toBe([89, 489])
        ->and($results->map(fn (PaymentMatch $m) => $m->confidence)->unique()->values()->all())
        ->toBe([PaymentMatch::LIKELY]);
});

it('flags an entry that is already settled rather than hiding it', function () {
    $event = refEvent(14, 'Match Fourteen');
    refEntry(103, $event, refMember('Jaco', 'Smit'), [
        'paid_at' => now()->subDay(),
        'status' => EventRegistrationStatus::Confirmed,
    ]);

    $results = resolver()->resolve('PPRC-M14-103');

    expect($results[0]->settled)->toBeTrue()
        ->and($results[0]->settledNote)->toContain('Already marked paid');
});

/*
|--------------------------------------------------------------------------
| Membership references
|--------------------------------------------------------------------------
*/

it('rebuilds a membership reference the bank flattened', function () {
    $member = refMember('Coenie', 'van Tonder');
    refMembershipPayment($member, 'PPRC-20260725-0001', PaymentStatus::Submitted);

    $results = collect(resolver()->resolve('AF BANK PPRC202607250001'));

    $payment = $results->first(fn (PaymentMatch $m) => $m->kind === PaymentMatch::MEMBERSHIP_PAYMENT);

    expect($payment?->reference)->toBe('PPRC-20260725-0001')
        ->and($payment?->confidence)->toBe(PaymentMatch::EXACT)
        ->and($payment?->settled)->toBeFalse();
});

/*
|--------------------------------------------------------------------------
| The reused reference — the whole reason this exists
|--------------------------------------------------------------------------
|
| A member pays a match entry quoting the membership reference they were given
| when they first joined. The reference resolves perfectly and tells you the
| wrong thing, so identifying the person has to be enough to surface what they
| actually owe.
|
*/

it('finds the unpaid match entry when a member pays it with their joining reference', function () {
    $member = refMember('Marius', 'Brummer');
    refMembershipPayment($member, 'PPRC-20260105-0006', PaymentStatus::Confirmed);

    refEntry(103, refEvent(14, 'Match Fourteen'), $member);

    $results = collect(resolver()->resolve('ABSA BANK PPRC-20260105-0006'));

    $entry = $results->first(fn (PaymentMatch $m) => $m->kind === PaymentMatch::MATCH_ENTRY);
    $membership = $results->first(fn (PaymentMatch $m) => $m->kind === PaymentMatch::MEMBERSHIP_PAYMENT);

    expect($entry?->id)->toBe(103)
        ->and($entry?->amountCents)->toBe(45000)
        ->and($entry?->confidence)->toBe(PaymentMatch::LIKELY)
        ->and($entry?->settled)->toBeFalse()
        ->and($entry?->reason)->toContain('still owes');

    // The membership payment it literally points at is shown too, marked as
    // nothing-to-do, so the admin can see why the reference looked resolved.
    expect($membership?->settled)->toBeTrue();
});

it('uses a membership number to find what that member owes', function () {
    $member = refMember('Jaco', 'Smit', ['membership_number' => 'PPRC-0181']);

    refEntry(103, refEvent(14, 'Match Fourteen'), $member);

    $results = collect(resolver()->resolve('PPRC-0181'));

    expect($results->first(fn (PaymentMatch $m) => $m->kind === PaymentMatch::MATCH_ENTRY)?->id)
        ->toBe(103);
});

/*
|--------------------------------------------------------------------------
| Nothing but a name
|--------------------------------------------------------------------------
*/

it('falls back to the payer name when the bank kept no reference', function () {
    $member = refMember('Ruan', 'du Plessis');
    refEntry(103, refEvent(14, 'Match Fourteen'), $member);

    $results = collect(resolver()->resolve('FNB APP PAYMENT FROM RUAN DU PLESSIS'));

    $entry = $results->first(fn (PaymentMatch $m) => $m->kind === PaymentMatch::MATCH_ENTRY);

    expect($entry?->id)->toBe(103)
        ->and($entry?->confidence)->toBe(PaymentMatch::POSSIBLE);
});

it('matches a guest entry on the surname the bank left behind', function () {
    refEntry(200, refEvent(14, 'Match Fourteen'), null, [
        'guest_name' => 'Marius Brummer',
        'guest_email' => 'marius@example.com',
    ]);

    $results = collect(resolver()->resolve('M BRUMMER'));

    expect($results->first()?->id)->toBe(200)
        ->and($results->first()?->who)->toBe('Marius Brummer')
        ->and($results->first()?->confidence)->toBe(PaymentMatch::POSSIBLE);
});

it('does not drag in names when the reference already resolved', function () {
    $event = refEvent(13, 'Match Thirteen');
    refEntry(95, $event, refMember('Jannie', 'Nel'));

    // A second Nel who owes nothing on this match must not be offered.
    refMember('Pieter', 'Nel');

    $results = resolver()->resolve('PPRC-M13-95 J NEL');

    expect($results)->toHaveCount(1)
        ->and($results[0]->id)->toBe(95)
        ->and($results[0]->confidence)->toBe(PaymentMatch::EXACT);
});

/*
|--------------------------------------------------------------------------
| Dead ends
|--------------------------------------------------------------------------
*/

it('returns nothing for a line with nothing to go on', function () {
    expect(resolver()->resolve('ABSA BANK CHARGES'))->toBe([]);
});

it('returns nothing for an empty line', function () {
    expect(resolver()->resolve('   '))->toBe([]);
});

it('returns nothing when the reference points at an entry that does not exist', function () {
    expect(resolver()->resolve('ABSA BANK PPRC-M99-4242'))->toBe([]);
});

<?php

use App\Enums\EventRegistrationStatus;
use App\Enums\MatchCreditStatus;
use App\Filament\Admin\Support\MatchCreditSettlement;
use App\Mail\MatchEntryPaymentConfirmedMail;
use App\Mail\MatchEntryPaymentMail;
use App\Models\EventRegistration;
use App\Models\MatchCredit;
use App\Services\Events\MatchDirectorReport;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

it('releases a paid entry as a credit and takes it off the match', function () {
    $member = transferShooter();
    $event = transferMatch('Rained Out', 15000);
    $entry = paidEntry($event, $member);

    $credit = transfers()->release($entry, 'Work trip');

    expect($entry->refresh()->status)->toBe(EventRegistrationStatus::Cancelled)
        ->and($credit->amount_cents)->toBe(15000)
        ->and($credit->status)->toBe(MatchCreditStatus::Available)
        ->and($credit->member_id)->toBe($member->id)
        ->and($credit->payee_name)->toBe($member->fullName())
        ->and($credit->source_event_id)->toBe($event->id)
        ->and($credit->source_registration_id)->toBe($entry->id)
        ->and($credit->reason)->toBe('Work trip');
});

it('refuses to credit an entry that was never paid', function () {
    $event = transferMatch('Unpaid', 15000);
    $entry = paidEntry($event, transferShooter(), ['paid_at' => null]);

    expect(fn () => transfers()->release($entry))->toThrow(ValidationException::class);
});

it('refuses to credit the same entry twice', function () {
    $entry = paidEntry(transferMatch('Once Only', 15000), transferShooter());

    transfers()->release($entry);

    expect(fn () => transfers()->release($entry->refresh()))->toThrow(ValidationException::class);
});

it('refuses to credit a free entry, since there is no money to carry', function () {
    $entry = paidEntry(transferMatch('Comped', 15000), transferShooter(), ['fee_cents' => 0]);

    expect(fn () => transfers()->release($entry))->toThrow(ValidationException::class);
});

it('transfers into a match of the same price and settles it in full', function () {
    $member = transferShooter();
    $from = transferMatch('Old Match', 15000);
    $to = transferMatch('New Match', 15000);
    $entry = paidEntry($from, $member);

    $moved = transfers()->transfer($entry, $to);

    expect($moved->event_id)->toBe($to->id)
        ->and($moved->member_id)->toBe($member->id)
        ->and($moved->creditAppliedCents())->toBe(15000)
        ->and($moved->outstandingCents())->toBe(0)
        ->and($moved->paid_at)->not->toBeNull()
        ->and($moved->status)->toBe(EventRegistrationStatus::Confirmed)
        ->and($moved->awaitingPayment())->toBeFalse();

    $credit = MatchCredit::sole();

    expect($credit->status)->toBe(MatchCreditStatus::Used)
        ->and($credit->used_event_id)->toBe($to->id)
        ->and($credit->used_registration_id)->toBe($moved->id)
        ->and($credit->used_at)->not->toBeNull();
});

it('leaves only the shortfall owing when the new match costs more', function () {
    $from = transferMatch('Cheap Match', 15000);
    $to = transferMatch('Dear Match', 25000);
    $entry = paidEntry($from, transferShooter());

    $moved = transfers()->transfer($entry, $to);

    expect($moved->creditAppliedCents())->toBe(15000)
        ->and($moved->effectiveFeeCents())->toBe(25000)
        ->and($moved->outstandingCents())->toBe(10000)
        ->and($moved->paid_at)->toBeNull()
        ->and($moved->awaitingPayment())->toBeTrue()
        ->and($moved->status)->toBe(EventRegistrationStatus::Registered);
});

it('keeps the balance as a fresh credit when the new match costs less', function () {
    $from = transferMatch('Dear Match', 25000);
    $to = transferMatch('Cheap Match', 15000);
    $entry = paidEntry($from, transferShooter());

    $moved = transfers()->transfer($entry, $to);

    expect($moved->outstandingCents())->toBe(0)
        ->and($moved->paid_at)->not->toBeNull();

    $leftover = MatchCredit::query()->available()->sole();

    expect($leftover->amount_cents)->toBe(10000)
        ->and($leftover->reason)->toContain('Cheap Match');
});

it('settles the entry they already have on the target match instead of adding a second', function () {
    $member = transferShooter();
    $from = transferMatch('Old Match', 15000);
    $to = transferMatch('New Match', 15000);

    $existing = EventRegistration::create([
        'event_id' => $to->id,
        'member_id' => $member->id,
        'status' => EventRegistrationStatus::Registered,
        'registered_at' => now(),
    ]);

    $moved = transfers()->transfer(paidEntry($from, $member), $to);

    expect($moved->id)->toBe($existing->id)
        ->and($to->registrations()->count())->toBe(1)
        ->and($existing->refresh()->paid_at)->not->toBeNull();
});

it('revives a withdrawal on the target match rather than tripping the unique index', function () {
    $member = transferShooter();
    $from = transferMatch('Old Match', 15000);
    $to = transferMatch('New Match', 15000);

    $withdrawn = EventRegistration::create([
        'event_id' => $to->id,
        'member_id' => $member->id,
        'status' => EventRegistrationStatus::Cancelled,
        'registered_at' => now()->subMonth(),
    ]);

    $moved = transfers()->transfer(paidEntry($from, $member), $to);

    expect($moved->id)->toBe($withdrawn->id)
        ->and($to->registrations()->count())->toBe(1)
        ->and($moved->status)->toBe(EventRegistrationStatus::Confirmed);
});

it('transfers a guest entry, carrying their name and email across', function () {
    // Guests are priced off the non-member fee, so the credit is raised at
    // R250 and the dearer target leaves R50 of it short.
    $from = transferMatch('Old Match', 15000);
    $to = transferMatch('New Match', 15000, ['non_member_price_cents' => 30000]);

    $entry = paidEntry($from, null, [
        'guest_name' => 'Jane Guest',
        'guest_email' => 'jane@example.com',
    ]);

    $moved = transfers()->transfer($entry, $to);

    expect($moved->member_id)->toBeNull()
        ->and($moved->guest_name)->toBe('Jane Guest')
        ->and($moved->guest_email)->toBe('jane@example.com')
        ->and($moved->creditAppliedCents())->toBe(25000)
        ->and($moved->outstandingCents())->toBe(5000);

    expect(MatchCredit::sole())
        ->amount_cents->toBe(25000)
        ->payee_name->toBe('Jane Guest')
        ->payee_email->toBe('jane@example.com')
        ->member_id->toBeNull();
});

it('will not transfer an entry into the match it is already on', function () {
    $event = transferMatch('Same Match', 15000);
    $entry = paidEntry($event, transferShooter());

    expect(fn () => transfers()->transfer($entry, $event))->toThrow(ValidationException::class);
});

it('takes the money off the match it left and adds it to the one it joined', function () {
    $member = transferShooter();
    $from = transferMatch('Old Match', 15000);
    $to = transferMatch('New Match', 15000);
    $entry = paidEntry($from, $member, ['attended' => false]);

    expect((new MatchDirectorReport($from))->summary()['gross_collected_cents'])->toBe(15000);

    transfers()->transfer($entry, $to);

    expect((new MatchDirectorReport($from->refresh()))->summary()['gross_collected_cents'])->toBe(0)
        ->and((new MatchDirectorReport($to->refresh()))->summary()['gross_collected_cents'])->toBe(15000);
});

it('reports only the shortfall as outstanding, and flags what a credit funded', function () {
    $from = transferMatch('Cheap Match', 15000);
    $to = transferMatch('Dear Match', 25000);

    transfers()->transfer(paidEntry($from, transferShooter()), $to);

    $summary = (new MatchDirectorReport($to->refresh()))->summary();

    expect($summary['outstanding_cents'])->toBe(10000)
        ->and($summary['credit_funded_cents'])->toBe(15000);
});

it('still pays the director for a head the club funded from a credit', function () {
    $member = transferShooter();
    $from = transferMatch('Old Match', 15000);
    $to = transferMatch('New Match', 15000);

    transfers()->transfer(paidEntry($from, $member), $to);

    $summary = (new MatchDirectorReport($to->refresh()))->summary();

    expect($summary['payout_base_cents'])->toBe(15000)
        ->and($summary['credit_funded_cents'])->toBe(15000);
});

it('finds the credits a shooter is holding for an unpaid entry', function () {
    $member = transferShooter();
    $from = transferMatch('Old Match', 15000);
    $to = transferMatch('New Match', 15000);

    transfers()->release(paidEntry($from, $member));

    $unpaid = EventRegistration::create([
        'event_id' => $to->id,
        'member_id' => $member->id,
        'status' => EventRegistrationStatus::Registered,
        'registered_at' => now(),
    ]);

    expect(transfers()->creditsFor($unpaid)->pluck('amount_cents')->all())->toBe([15000]);
});

it('does not offer one shooter another shooter\'s credit', function () {
    $from = transferMatch('Old Match', 15000);
    $to = transferMatch('New Match', 15000);

    transfers()->release(paidEntry($from, transferShooter()));

    $other = EventRegistration::create([
        'event_id' => $to->id,
        'member_id' => transferShooter()->id,
        'status' => EventRegistrationStatus::Registered,
        'registered_at' => now(),
    ]);

    expect(transfers()->creditsFor($other))->toBeEmpty();
});

it('refuses to spend a credit that is already used', function () {
    $member = transferShooter();
    $from = transferMatch('Old Match', 15000);
    $to = transferMatch('New Match', 15000);
    $later = transferMatch('Later Match', 15000);

    $credit = transfers()->release(paidEntry($from, $member));
    transfers()->applyToEvent($credit, $to);

    expect(fn () => transfers()->applyToEvent($credit->refresh(), $later))
        ->toThrow(ValidationException::class);
});

it('reinstating an applied credit takes it back off the entry it settled', function () {
    $member = transferShooter();
    $from = transferMatch('Old Match', 15000);
    $to = transferMatch('New Match', 15000);

    $moved = transfers()->transfer(paidEntry($from, $member), $to);
    $credit = MatchCredit::query()->where('used_registration_id', $moved->id)->sole();

    transfers()->unsettle($credit);

    expect($credit->refresh()->status)->toBe(MatchCreditStatus::Available)
        ->and($credit->used_registration_id)->toBeNull()
        ->and($credit->used_event_id)->toBeNull()
        ->and($moved->refresh()->creditAppliedCents())->toBe(0)
        ->and($moved->paid_at)->toBeNull()
        ->and($moved->status)->toBe(EventRegistrationStatus::Registered)
        ->and($moved->outstandingCents())->toBe(15000);
});

it('emails a confirmation when a credit covers the whole entry', function () {
    Mail::fake();

    $from = transferMatch('Old Match', 15000);
    $to = transferMatch('New Match', 15000);

    $moved = transfers()->transfer(paidEntry($from, transferShooter()), $to);

    $summary = MatchCreditSettlement::announce($moved);

    Mail::assertSent(MatchEntryPaymentConfirmedMail::class);
    Mail::assertNotSent(MatchEntryPaymentMail::class);
    expect($summary)->toContain('paid in full');
});

it('emails the shortfall when a credit only part-covers the entry', function () {
    Mail::fake();

    $from = transferMatch('Cheap Match', 15000);
    $to = transferMatch('Dear Match', 25000);

    $moved = transfers()->transfer(paidEntry($from, transferShooter()), $to);

    $summary = MatchCreditSettlement::announce($moved);

    Mail::assertSent(MatchEntryPaymentMail::class);
    expect($summary)->toContain('R 100.00 still to pay');
});

it('quotes only the outstanding amount in the payment email', function () {
    $from = transferMatch('Cheap Match', 15000);
    $to = transferMatch('Dear Match', 25000);

    $moved = transfers()->transfer(paidEntry($from, transferShooter()), $to);

    $rendered = (new MatchEntryPaymentMail($moved))->render();

    expect($rendered)->toContain('R 100.00')
        ->and($rendered)->toContain('covered by your match credit');
});

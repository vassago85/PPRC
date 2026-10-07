<?php

use App\Enums\EventRegistrationStatus;
use App\Enums\MatchPaymentMethod;
use App\Filament\Admin\Resources\Events\Pages\EditEvent;
use App\Filament\Admin\Resources\Events\RelationManagers\RegistrationsRelationManager;
use App\Mail\MatchEntryPaymentMail;
use App\Models\EventRegistration;
use App\Models\User;
use App\Services\Events\MatchEntryPaymentRequestService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    Mail::fake();

    $this->treasurer = User::factory()->create(['email_verified_at' => now()]);
    $this->treasurer->assignRole('treasurer');
    $this->actingAs($this->treasurer);
});

/** Build an unpaid entry (R150) for a registered-but-not-settled shooter. */
function cashEntry(): EventRegistration
{
    $event = transferMatch('Cash Intent Match', 15000);

    return EventRegistration::create([
        'event_id' => $event->id,
        'member_id' => transferShooter()->id,
        'status' => EventRegistrationStatus::Registered,
        'registered_at' => now(),
    ]);
}

it('flags an unpaid entry as paying cash on the day', function () {
    $entry = cashEntry();

    Livewire::test(RegistrationsRelationManager::class, [
        'ownerRecord' => $entry->event,
        'pageClass' => EditEvent::class,
    ])
        ->callTableAction('mark_cash_intent', $entry)
        ->assertHasNoTableActionErrors();

    $entry->refresh();

    expect($entry->payment_method)->toBe(MatchPaymentMethod::Cash)
        ->and($entry->paid_at)->toBeNull()
        ->and($entry->hasCashIntent())->toBeTrue()
        // Match director still sees them on the collect-on-the-day list.
        ->and($entry->awaitingPayment())->toBeTrue();
});

it('hides the cash intent action once it has already been flagged', function () {
    $entry = cashEntry();
    $entry->update(['payment_method' => MatchPaymentMethod::Cash->value]);

    $tab = Livewire::test(RegistrationsRelationManager::class, [
        'ownerRecord' => $entry->event,
        'pageClass' => EditEvent::class,
    ]);

    $tab->assertTableActionHidden('mark_cash_intent', $entry);
    $tab->assertTableActionVisible('clear_cash_intent', $entry);
});

it('clears the cash intent and puts the entry back in the EFT queue', function () {
    $entry = cashEntry();
    $entry->update(['payment_method' => MatchPaymentMethod::Cash->value]);

    Livewire::test(RegistrationsRelationManager::class, [
        'ownerRecord' => $entry->event,
        'pageClass' => EditEvent::class,
    ])
        ->callTableAction('clear_cash_intent', $entry)
        ->assertHasNoTableActionErrors();

    $entry->refresh();

    expect($entry->payment_method)->toBeNull()
        ->and($entry->hasCashIntent())->toBeFalse()
        ->and($entry->awaitingPayment())->toBeTrue();
});

it('preserves the cash method when the admin marks the entry paid', function () {
    $entry = cashEntry();
    $entry->update(['payment_method' => MatchPaymentMethod::Cash->value]);

    Livewire::test(RegistrationsRelationManager::class, [
        'ownerRecord' => $entry->event,
        'pageClass' => EditEvent::class,
    ])
        ->callTableAction('mark_paid', $entry)
        ->assertHasNoTableActionErrors();

    $entry->refresh();

    expect($entry->paid_at)->not->toBeNull()
        // Confirming payment on a cash-intent entry lands as "paid, by cash" —
        // the match director's report splits bank vs cash off this column.
        ->and($entry->payment_method)->toBe(MatchPaymentMethod::Cash)
        ->and($entry->status)->toBe(EventRegistrationStatus::Confirmed);
});

it('refuses to send an EFT reminder to a shooter paying cash', function () {
    $entry = cashEntry();
    $entry->update(['payment_method' => MatchPaymentMethod::Cash->value]);

    expect(fn () => app(MatchEntryPaymentRequestService::class)->send($entry))
        ->toThrow(ValidationException::class, 'cash on the day');

    Mail::assertNotQueued(MatchEntryPaymentMail::class);
    Mail::assertNotSent(MatchEntryPaymentMail::class);
});

it('silently skips cash-intent entries in a bulk EFT send', function () {
    $cashShooter = cashEntry();
    $cashShooter->update(['payment_method' => MatchPaymentMethod::Cash->value]);

    // A second entry on the same match that still expects an EFT.
    $eftShooter = EventRegistration::create([
        'event_id' => $cashShooter->event_id,
        'member_id' => transferShooter()->id,
        'status' => EventRegistrationStatus::Registered,
        'registered_at' => now(),
    ]);

    $result = app(MatchEntryPaymentRequestService::class)->sendBulk([$cashShooter, $eftShooter]);

    expect($result)->toMatchArray(['sent' => 1, 'skipped' => 1]);

    Mail::assertQueued(MatchEntryPaymentMail::class, 1);
});

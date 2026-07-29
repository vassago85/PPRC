<?php

use App\Enums\EventRegistrationStatus;
use App\Enums\MatchCreditStatus;
use App\Filament\Admin\Resources\Events\Pages\EditEvent;
use App\Filament\Admin\Resources\Events\RelationManagers\RegistrationsRelationManager;
use App\Filament\Admin\Resources\MatchCredits\Pages\ListMatchCredits;
use App\Models\EventRegistration;
use App\Models\MatchCredit;
use App\Models\User;
use App\Services\Events\MatchEntryTransferService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Mail;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    Mail::fake();

    $this->treasurer = User::factory()->create(['email_verified_at' => now()]);
    $this->treasurer->assignRole('treasurer');
    $this->actingAs($this->treasurer);
});

function entriesTab($event): Testable
{
    return Livewire::test(RegistrationsRelationManager::class, [
        'ownerRecord' => $event,
        'pageClass' => EditEvent::class,
    ]);
}

it('moves a paid shooter to another match from the entries tab', function () {
    $member = transferShooter();
    $from = transferMatch('Old Match', 15000);
    $to = transferMatch('New Match', 15000);
    $entry = paidEntry($from, $member);

    entriesTab($from)
        ->callTableAction('transfer', $entry, [
            'destination' => 'match',
            'event_id' => $to->id,
            'reason' => 'Away that weekend',
        ])
        ->assertHasNoTableActionErrors();

    expect($entry->refresh()->status)->toBe(EventRegistrationStatus::Cancelled);

    $moved = $to->registrations()->sole();

    expect($moved->member_id)->toBe($member->id)
        ->and($moved->paid_at)->not->toBeNull()
        ->and($moved->creditAppliedCents())->toBe(15000)
        ->and($moved->marked_paid_by_user_id)->toBe($this->treasurer->id);

    expect(MatchCredit::sole())
        ->status->toBe(MatchCreditStatus::Used)
        ->reason->toBe('Away that weekend')
        ->used_registration_id->toBe($moved->id)
        ->created_by_user_id->toBe($this->treasurer->id);
});

it('holds a paid entry as a credit for later from the entries tab', function () {
    $member = transferShooter();
    $event = transferMatch('Old Match', 15000);
    $entry = paidEntry($event, $member);

    entriesTab($event)
        ->callTableAction('transfer', $entry, ['destination' => 'credit'])
        ->assertHasNoTableActionErrors();

    expect($entry->refresh()->status)->toBe(EventRegistrationStatus::Cancelled);

    expect(MatchCredit::sole())
        ->status->toBe(MatchCreditStatus::Available)
        ->amount_cents->toBe(15000)
        ->member_id->toBe($member->id);
});

it('hides the transfer action on an entry that has not paid', function () {
    $event = transferMatch('Old Match', 15000);
    $entry = paidEntry($event, transferShooter(), ['paid_at' => null]);

    entriesTab($event)->assertTableActionHidden('transfer', $entry);
});

it('settles an unpaid entry from a credit the shooter is holding', function () {
    $member = transferShooter();
    $from = transferMatch('Old Match', 15000);
    $to = transferMatch('New Match', 15000);

    $credit = app(MatchEntryTransferService::class)->release(paidEntry($from, $member));

    $unpaid = EventRegistration::create([
        'event_id' => $to->id,
        'member_id' => $member->id,
        'status' => EventRegistrationStatus::Registered,
        'registered_at' => now(),
    ]);

    entriesTab($to)
        ->callTableAction('apply_credit', $unpaid, ['credit_id' => $credit->id])
        ->assertHasNoTableActionErrors();

    expect($unpaid->refresh()->paid_at)->not->toBeNull()
        ->and($unpaid->creditAppliedCents())->toBe(15000)
        ->and($unpaid->status)->toBe(EventRegistrationStatus::Confirmed);

    expect($credit->refresh()->status)->toBe(MatchCreditStatus::Used);
});

it('offers no credit button to a shooter who is not holding one', function () {
    $event = transferMatch('New Match', 15000);

    $unpaid = EventRegistration::create([
        'event_id' => $event->id,
        'member_id' => transferShooter()->id,
        'status' => EventRegistrationStatus::Registered,
        'registered_at' => now(),
    ]);

    entriesTab($event)->assertTableActionHidden('apply_credit', $unpaid);
});

it('enters a shooter and settles them straight from the credits ledger', function () {
    $member = transferShooter();
    $from = transferMatch('Old Match', 15000);
    $to = transferMatch('New Match', 15000);

    $credit = app(MatchEntryTransferService::class)->release(paidEntry($from, $member));

    Livewire::test(ListMatchCredits::class)
        ->callTableAction('use_on_match', $credit, ['event_id' => $to->id])
        ->assertHasNoTableActionErrors();

    $entry = $to->registrations()->sole();

    expect($entry->member_id)->toBe($member->id)
        ->and($entry->paid_at)->not->toBeNull()
        ->and($entry->creditAppliedCents())->toBe(15000);

    expect($credit->refresh())
        ->status->toBe(MatchCreditStatus::Used)
        ->used_event_id->toBe($to->id)
        ->used_registration_id->toBe($entry->id);
});

it('unwinds the entry when an applied credit is reinstated from the ledger', function () {
    $member = transferShooter();
    $from = transferMatch('Old Match', 15000);
    $to = transferMatch('New Match', 15000);

    $moved = app(MatchEntryTransferService::class)
        ->transfer(paidEntry($from, $member), $to);

    $credit = MatchCredit::query()->where('used_registration_id', $moved->id)->sole();

    Livewire::test(ListMatchCredits::class)
        ->set('activeTab', 'used')
        ->callTableAction('mark_available', $credit)
        ->assertHasNoTableActionErrors();

    expect($credit->refresh()->status)->toBe(MatchCreditStatus::Available)
        ->and($moved->refresh()->paid_at)->toBeNull()
        ->and($moved->creditAppliedCents())->toBe(0);
});

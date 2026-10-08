<?php

use App\Enums\EventRegistrationStatus;
use App\Filament\Admin\Resources\Events\Pages\EditEvent;
use App\Filament\Admin\Resources\Events\RelationManagers\RegistrationsRelationManager;
use App\Models\EventRegistration;
use App\Models\Member;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Facades\Filament;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    $this->admin = User::factory()->create(['email_verified_at' => now()]);
    $this->admin->assignRole('treasurer');
    $this->actingAs($this->admin);
});

/** Build a Registered-not-yet-paid entry for a real member, like Dirk's row. */
function unconfirmedEntryFor(Member $member, int $priceCents = 45000): EventRegistration
{
    return EventRegistration::create([
        'event_id' => transferMatch('MD Match', $priceCents)->id,
        'member_id' => $member->id,
        'status' => EventRegistrationStatus::Registered,
        'registered_at' => now()->subDays(5),
    ]);
}

it('is not a match director until an admin assigns one', function () {
    $entry = unconfirmedEntryFor(transferShooter());

    expect($entry->isMatchDirector())->toBeFalse()
        ->and($entry->event->match_director_id)->toBeNull();
});

it('sets as MD by waiving the fee, confirming the entry, and naming the user on the event', function () {
    $member = transferShooter();
    $entry = unconfirmedEntryFor($member, priceCents: 45000);

    Livewire::test(RegistrationsRelationManager::class, [
        'ownerRecord' => $entry->event,
        'pageClass' => EditEvent::class,
    ])
        ->callTableAction('make_match_director', $entry)
        ->assertHasNoTableActionErrors();

    $entry->refresh();
    $event = $entry->event->fresh();

    expect($entry->fee_cents)->toBe(0)
        ->and($entry->status)->toBe(EventRegistrationStatus::Confirmed)
        ->and($entry->isMatchDirector())->toBeTrue()
        ->and($entry->paymentConfirmed())->toBeTrue()
        ->and($entry->isWaived())->toBeTrue()
        ->and($event->match_director_id)->toBe($member->user_id)
        ->and($event->match_director_name)->toBe($member->fullName());
});

it('hides Set as Match Director on the entry that is already the MD', function () {
    $member = transferShooter();
    $entry = unconfirmedEntryFor($member);
    $entry->event->update([
        'match_director_id' => $member->user_id,
        'match_director_name' => $member->fullName(),
    ]);
    $entry->update(['fee_cents' => 0, 'status' => EventRegistrationStatus::Confirmed]);

    Livewire::test(RegistrationsRelationManager::class, [
        'ownerRecord' => $entry->event,
        'pageClass' => EditEvent::class,
    ])->assertTableActionHidden('make_match_director', $entry);
});

it('hides Set as Match Director on guest entries because they have no user_id', function () {
    $event = transferMatch('Guest MD', 45000);
    $guest = EventRegistration::create([
        'event_id' => $event->id,
        'guest_name' => 'Walk-in Jane',
        'guest_email' => 'jane@example.test',
        'status' => EventRegistrationStatus::Registered,
        'registered_at' => now()->subDay(),
    ]);

    Livewire::test(RegistrationsRelationManager::class, [
        'ownerRecord' => $event,
        'pageClass' => EditEvent::class,
    ])->assertTableActionHidden('make_match_director', $guest);
});

it('moves the MD title cleanly without stripping the previous MD\'s waiver', function () {
    // Start with Dirk as MD on the match.
    $dirk = transferShooter();
    $firstEntry = unconfirmedEntryFor($dirk);
    $event = $firstEntry->event;
    $event->update([
        'match_director_id' => $dirk->user_id,
        'match_director_name' => $dirk->fullName(),
    ]);
    $firstEntry->update(['fee_cents' => 0, 'status' => EventRegistrationStatus::Confirmed]);

    // A late request comes in to hand MD duty to Johan.
    $johan = transferShooter();
    $johansEntry = EventRegistration::create([
        'event_id' => $event->id,
        'member_id' => $johan->id,
        'status' => EventRegistrationStatus::Registered,
        'registered_at' => now()->subDay(),
    ]);

    Livewire::test(RegistrationsRelationManager::class, [
        'ownerRecord' => $event->fresh(),
        'pageClass' => EditEvent::class,
    ])
        ->callTableAction('make_match_director', $johansEntry)
        ->assertHasNoTableActionErrors();

    $firstEntry->refresh();
    $johansEntry->refresh();

    // Johan picks up the title + fee waiver.
    expect($johansEntry->isMatchDirector())->toBeTrue()
        ->and($johansEntry->fee_cents)->toBe(0)
        ->and($johansEntry->status)->toBe(EventRegistrationStatus::Confirmed);

    // Dirk keeps his existing waiver + confirmation (not re-charged), just
    // no longer the MD.
    expect($firstEntry->isMatchDirector())->toBeFalse()
        ->and($firstEntry->fee_cents)->toBe(0)
        ->and($firstEntry->status)->toBe(EventRegistrationStatus::Confirmed);
});

it('renders an MD badge next to the MD\'s name on the public shooter list', function () {
    $dirk = transferShooter();
    $entry = unconfirmedEntryFor($dirk);
    $event = $entry->event;
    $event->update([
        'match_director_id' => $dirk->user_id,
        'match_director_name' => $dirk->fullName(),
    ]);
    $entry->update(['fee_cents' => 0, 'status' => EventRegistrationStatus::Confirmed]);

    $this->get(route('matches.show', ['event' => $event->slug]))
        ->assertOk()
        ->assertSee($dirk->fullName())
        ->assertSee('MD')
        ->assertSee('Match Director', escape: false);
});

it('does not render an MD badge for non-MD entries', function () {
    $event = transferMatch('No MD', 45000);
    $member = transferShooter();
    EventRegistration::create([
        'event_id' => $event->id,
        'member_id' => $member->id,
        'fee_cents' => 45000,
        'status' => EventRegistrationStatus::Confirmed,
        'paid_at' => now()->subDay(),
        'registered_at' => now()->subDays(2),
    ]);

    $response = $this->get(route('matches.show', ['event' => $event->slug]))
        ->assertOk()
        ->assertSee($member->fullName());

    // The MD badge markup carries this title attribute — its absence confirms
    // the badge isn't being rendered for an unassigned match.
    expect($response->getContent())->not->toContain('title="Match Director"');
});

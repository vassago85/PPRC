<?php

use App\Enums\EventRegistrationStatus;
use App\Enums\EventStatus;
use App\Filament\Admin\Actions\EnterMatchAction;
use App\Filament\Admin\Resources\Events\Pages\EditEvent;
use App\Filament\Admin\Resources\Events\Pages\ListEvents;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\MatchFormat;
use App\Models\Member;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Facades\Filament;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

/**
 * Matches require a division by default, so that is the shape most of these
 * tests exercise. Division options are pinned rather than left to the SAPRF
 * config so the assertions do not move when that list does.
 */
function enterMatchEvent(array $overrides = []): Event
{
    $format = MatchFormat::firstOrCreate(
        ['slug' => 'prs-centerfire'],
        ['name' => 'PRS Centerfire', 'short_name' => 'PRS', 'is_active' => true],
    );

    return Event::create(array_merge([
        'match_format_id' => $format->id,
        'title' => 'Club Shoot',
        'start_date' => now()->addWeek()->toDateString(),
        'status' => EventStatus::Published,
        'published_at' => now(),
        'registrations_open' => true,
        'member_price_cents' => 15000,
        'non_member_price_cents' => 25000,
        'registration_division_options' => ['Production', 'Open'],
    ], $overrides));
}

/**
 * A committee member as they actually exist: an admin login with a committee
 * role, linked to a shooting member record.
 */
function excoShooter(string $role = 'chairperson'): User
{
    $user = User::factory()->create(['email_verified_at' => now()]);
    $user->assignRole($role);
    Member::factory()->create(['user_id' => $user->id, 'status' => 'active']);

    return $user->refresh();
}

it('lets a committee member enter a match from the list, free of charge', function () {
    $user = excoShooter();
    $this->actingAs($user);

    $event = enterMatchEvent();

    Livewire::test(ListEvents::class)
        ->callTableAction('enter_match', $event, ['division' => 'Production'])
        ->assertHasNoTableActionErrors();

    $entry = EventRegistration::query()
        ->where('event_id', $event->id)
        ->where('member_id', $user->member->id)
        ->first();

    expect($entry)->not->toBeNull()
        ->and($entry->status)->toBe(EventRegistrationStatus::Registered)
        ->and($entry->division)->toBe('Production')
        ->and($entry->fee_cents)->toBe(0)
        ->and($entry->registered_at)->not->toBeNull();
});

it('lets a committee member enter from the match page header', function () {
    $user = excoShooter('match_director');
    $this->actingAs($user);

    $event = enterMatchEvent();

    Livewire::test(EditEvent::class, ['record' => $event->getRouteKey()])
        ->callAction('enter_match', ['division' => 'Open'])
        ->assertHasNoActionErrors();

    expect(EventRegistration::where('event_id', $event->id)->sole()->division)->toBe('Open');
});

it('asks for nothing when the match requires nothing', function () {
    $user = excoShooter();
    $this->actingAs($user);

    $event = enterMatchEvent(['registration_require_division' => false]);

    Livewire::test(ListEvents::class)
        ->callTableAction('enter_match', $event)
        ->assertHasNoTableActionErrors();

    expect(EventRegistration::where('event_id', $event->id)->count())->toBe(1);
});

it('carries the division from their last entry into the form', function () {
    $user = excoShooter();
    $this->actingAs($user);

    $previous = enterMatchEvent(['title' => 'Last Month']);
    EventRegistration::create([
        'event_id' => $previous->id,
        'member_id' => $user->member->id,
        'division' => 'Open',
        'status' => EventRegistrationStatus::Registered,
        'registered_at' => now()->subMonth(),
    ]);

    Livewire::test(ListEvents::class)
        ->mountTableAction('enter_match', enterMatchEvent(['title' => 'This Month']))
        ->assertTableActionDataSet(['division' => 'Open']);
});

it('ignores a carried-over division the match does not offer', function () {
    $user = excoShooter();
    $this->actingAs($user);

    $previous = enterMatchEvent(['title' => 'Last Month']);
    EventRegistration::create([
        'event_id' => $previous->id,
        'member_id' => $user->member->id,
        'division' => 'Open',
        'status' => EventRegistrationStatus::Registered,
        'registered_at' => now()->subMonth(),
    ]);

    Livewire::test(ListEvents::class)
        ->mountTableAction('enter_match', enterMatchEvent([
            'title' => 'Production Only',
            'registration_division_options' => ['Production'],
        ]))
        ->assertTableActionDataSet(['division' => null]);
});

it('charges an admin who does not get free committee entry', function () {
    // `developer` holds every permission but is deliberately excluded from the
    // free-entry roles, so it proves the waiver is not just "anyone in admin".
    $user = excoShooter('developer');
    $this->actingAs($user);

    $event = enterMatchEvent();

    Livewire::test(ListEvents::class)
        ->callTableAction('enter_match', $event, ['division' => 'Production'])
        ->assertHasNoTableActionErrors();

    $entry = EventRegistration::where('event_id', $event->id)->sole();

    expect($entry->fee_cents)->toBeNull()
        ->and($entry->effectiveFeeCents())->toBe(15000);
});

it('will not enter the same person twice', function () {
    $user = excoShooter();
    $this->actingAs($user);

    $event = enterMatchEvent();
    EventRegistration::create([
        'event_id' => $event->id,
        'member_id' => $user->member->id,
        'status' => EventRegistrationStatus::Registered,
        'registered_at' => now(),
    ]);

    Livewire::test(ListEvents::class)
        ->callTableAction('enter_match', $event, ['division' => 'Production'])
        ->assertHasNoTableActionErrors();

    expect(EventRegistration::where('event_id', $event->id)->count())->toBe(1);
});

it('revives a withdrawn entry rather than tripping the unique index', function () {
    $user = excoShooter();
    $this->actingAs($user);

    $event = enterMatchEvent();
    $withdrawn = EventRegistration::create([
        'event_id' => $event->id,
        'member_id' => $user->member->id,
        'status' => EventRegistrationStatus::Cancelled,
        'registered_at' => now()->subDay(),
    ]);

    Livewire::test(ListEvents::class)
        ->callTableAction('enter_match', $event, ['division' => 'Production'])
        ->assertHasNoTableActionErrors();

    expect(EventRegistration::where('event_id', $event->id)->count())->toBe(1)
        ->and($withdrawn->refresh()->status)->toBe(EventRegistrationStatus::Registered)
        ->and($withdrawn->fee_cents)->toBe(0);
});

it('hides the button from an admin with no member profile', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $user->assignRole('secretary');
    $this->actingAs($user);

    expect(EnterMatchAction::isAvailableFor(enterMatchEvent()))->toBeFalse();
});

it('hides the button once the match is over', function () {
    $this->actingAs(excoShooter());

    expect(EnterMatchAction::isAvailableFor(enterMatchEvent([
        'start_date' => now()->subWeek()->toDateString(),
        'status' => EventStatus::Completed,
    ])))->toBeFalse();
});

it('still lets an admin enter a match whose registrations have closed', function () {
    $user = excoShooter();
    $this->actingAs($user);

    $event = enterMatchEvent(['registrations_open' => false]);

    expect(EnterMatchAction::isAvailableFor($event))->toBeTrue();

    Livewire::test(ListEvents::class)
        ->callTableAction('enter_match', $event, ['division' => 'Production'])
        ->assertHasNoTableActionErrors();

    expect(EventRegistration::where('event_id', $event->id)->count())->toBe(1);
});

<?php

use App\Enums\EventRegistrationStatus;
use App\Enums\EventStatus;
use App\Filament\Admin\Resources\Events\Pages\EditEvent;
use App\Filament\Admin\Resources\Events\RelationManagers\RegistrationsRelationManager;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\MatchFormat;
use App\Models\Member;
use App\Models\User;
use App\Mail\MatchEntryPaymentConfirmedMail;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

it('renders the entries relation manager with mixed entries', function () {
    $admin = User::factory()->create(['email_verified_at' => now()]);
    $admin->assignRole('match_director');
    $this->actingAs($admin);

    $format = MatchFormat::create([
        'slug' => 'prs-centerfire',
        'name' => 'PRS Centerfire',
        'short_name' => 'PRS',
        'is_active' => true,
    ]);

    $event = Event::create([
        'match_format_id' => $format->id,
        'title' => 'Centerfire Club Match',
        'start_date' => now()->addWeek()->toDateString(),
        'status' => EventStatus::Published,
        'member_price_cents' => 15000,
        'non_member_price_cents' => 20000,
    ]);

    $memberUser = User::factory()->create(['email' => 'm@example.com']);
    $member = Member::factory()->create(['user_id' => $memberUser->id]);

    EventRegistration::create([
        'event_id' => $event->id,
        'member_id' => $member->id,
        'status' => EventRegistrationStatus::Registered,
        'registered_at' => now(),
    ]);

    EventRegistration::create([
        'event_id' => $event->id,
        'guest_name' => 'Jane Guest',
        'guest_email' => 'guest@example.com',
        'status' => EventRegistrationStatus::Registered,
        'registered_at' => now(),
    ]);

    EventRegistration::create([
        'event_id' => $event->id,
        'guest_name' => 'Saprf Shooter',
        'is_saprf_entry' => true,
        'status' => EventRegistrationStatus::Registered,
        'registered_at' => now(),
    ]);

    Livewire::test(RegistrationsRelationManager::class, [
        'ownerRecord' => $event,
        'pageClass' => EditEvent::class,
    ])->assertOk();
});

it('marks an entry as paid and confirms it', function () {
    Mail::fake();
    $admin = User::factory()->create(['email_verified_at' => now()]);
    $admin->assignRole('treasurer');
    $this->actingAs($admin);

    $format = MatchFormat::create([
        'slug' => 'prs-centerfire',
        'name' => 'PRS Centerfire',
        'short_name' => 'PRS',
        'is_active' => true,
    ]);

    $event = Event::create([
        'match_format_id' => $format->id,
        'title' => 'Centerfire Club Match',
        'start_date' => now()->addWeek()->toDateString(),
        'status' => EventStatus::Published,
        'member_price_cents' => 15000,
        'non_member_price_cents' => 20000,
    ]);

    $entry = EventRegistration::create([
        'event_id' => $event->id,
        'guest_name' => 'Jane Guest',
        'guest_email' => 'guest@example.com',
        'status' => EventRegistrationStatus::Registered,
        'registered_at' => now(),
    ]);

    expect($entry->awaitingPayment())->toBeTrue();
    expect($entry->isPaid())->toBeFalse();

    Livewire::test(RegistrationsRelationManager::class, [
        'ownerRecord' => $event,
        'pageClass' => EditEvent::class,
    ])->callTableAction('mark_paid', $entry)->assertHasNoTableActionErrors();

    $entry->refresh();

    expect($entry->paid_at)->not->toBeNull();
    expect($entry->marked_paid_by_user_id)->toBe($admin->id);
    expect($entry->status)->toBe(EventRegistrationStatus::Confirmed);
    expect($entry->awaitingPayment())->toBeFalse();
    expect($entry->isPaid())->toBeTrue();

    Mail::assertSent(MatchEntryPaymentConfirmedMail::class, 1);
});

it('treats waived and SAPRF entries as not awaiting payment', function () {
    $format = MatchFormat::create([
        'slug' => 'prs-centerfire',
        'name' => 'PRS Centerfire',
        'short_name' => 'PRS',
        'is_active' => true,
    ]);

    $event = Event::create([
        'match_format_id' => $format->id,
        'title' => 'Centerfire Club Match',
        'start_date' => now()->addWeek()->toDateString(),
        'status' => EventStatus::Published,
        'member_price_cents' => 15000,
        'non_member_price_cents' => 20000,
    ]);

    $saprf = EventRegistration::create([
        'event_id' => $event->id,
        'guest_name' => 'Saprf Shooter',
        'is_saprf_entry' => true,
        'status' => EventRegistrationStatus::Registered,
        'registered_at' => now(),
    ]);

    $waived = EventRegistration::create([
        'event_id' => $event->id,
        'guest_name' => 'Free Entry',
        'fee_cents' => 0,
        'status' => EventRegistrationStatus::Registered,
        'registered_at' => now(),
    ]);

    expect($saprf->awaitingPayment())->toBeFalse();
    expect($saprf->isPaid())->toBeTrue();
    expect($waived->awaitingPayment())->toBeFalse();
    expect($waived->isPaid())->toBeTrue();
});

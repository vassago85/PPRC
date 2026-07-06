<?php

use App\Enums\EventRegistrationStatus;
use App\Enums\EventStatus;
use App\Filament\Admin\Resources\Events\Pages\MatchReport;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\MatchFormat;
use App\Models\SiteSetting;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Facades\Filament;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

function matchReportEvent(): Event
{
    $format = MatchFormat::firstOrCreate(
        ['slug' => 'prs-centerfire'],
        ['name' => 'PRS Centerfire', 'short_name' => 'PRS', 'is_active' => true],
    );

    return Event::create([
        'match_format_id' => $format->id,
        'title' => 'Report Page Match',
        'start_date' => now()->subDay()->toDateString(),
        'status' => EventStatus::Completed,
        'member_price_cents' => 20000,
        'non_member_price_cents' => 25000,
    ]);
}

it('renders the match report page', function () {
    $admin = User::factory()->create(['email_verified_at' => now()]);
    $admin->assignRole('match_director');
    $this->actingAs($admin);

    $event = matchReportEvent();
    EventRegistration::create([
        'event_id' => $event->id,
        'guest_name' => 'Jane Guest',
        'guest_email' => 'jane@example.com',
        'paid_at' => now(),
        'attended' => true,
        'status' => EventRegistrationStatus::Confirmed,
        'registered_at' => now(),
    ]);

    Livewire::test(MatchReport::class, ['record' => $event->slug])->assertOk();
});

it('toggles paid and attended from the report', function () {
    $admin = User::factory()->create(['email_verified_at' => now()]);
    $admin->assignRole('match_director');
    $this->actingAs($admin);

    $event = matchReportEvent();
    $entry = EventRegistration::create([
        'event_id' => $event->id,
        'guest_name' => 'Owing Shooter',
        'guest_email' => 'owe@example.com',
        'status' => EventRegistrationStatus::Registered,
        'registered_at' => now(),
    ]);

    $component = Livewire::test(MatchReport::class, ['record' => $event->slug]);

    $component->call('togglePaid', $entry->id);
    expect($entry->refresh()->paid_at)->not->toBeNull();

    $component->call('toggleAttended', $entry->id);
    expect($entry->refresh()->attended)->toBeTrue();

    // Toggling again clears them.
    $component->call('togglePaid', $entry->id);
    expect($entry->refresh()->paid_at)->toBeNull();
});

it('records the payment method when paying via EFT or cash', function () {
    $admin = User::factory()->create(['email_verified_at' => now()]);
    $admin->assignRole('match_director');
    $this->actingAs($admin);

    $event = matchReportEvent();
    $entry = EventRegistration::create([
        'event_id' => $event->id,
        'guest_name' => 'Cash Walkin',
        'guest_email' => 'cashwalkin@example.com',
        'status' => EventRegistrationStatus::Registered,
        'registered_at' => now(),
    ]);

    $component = Livewire::test(MatchReport::class, ['record' => $event->slug]);

    $component->call('payVia', $entry->id, 'cash');
    $entry->refresh();
    expect($entry->paid_at)->not->toBeNull();
    expect($entry->payment_method?->value)->toBe('cash');

    // Switch to EFT
    $component->call('payVia', $entry->id, 'eft');
    expect($entry->refresh()->payment_method?->value)->toBe('eft');

    // Clicking the active method again clears payment
    $component->call('payVia', $entry->id, 'eft');
    $entry->refresh();
    expect($entry->paid_at)->toBeNull();
    expect($entry->payment_method)->toBeNull();
});

it('adds a walk-in shooter paid in cash', function () {
    $admin = User::factory()->create(['email_verified_at' => now()]);
    $admin->assignRole('match_director');
    $this->actingAs($admin);

    $event = matchReportEvent();

    Livewire::test(MatchReport::class, ['record' => $event->slug])
        ->callAction('add_walkin', [
            'guest_name' => 'Junior Walkin',
            'is_junior' => true,
            'pay' => 'cash',
        ]);

    $entry = $event->registrations()->where('guest_name', 'Junior Walkin')->first();

    expect($entry)->not->toBeNull();
    expect($entry->attended)->toBeTrue();
    expect($entry->paid_at)->not->toBeNull();
    expect($entry->payment_method?->value)->toBe('cash');
});

it('saves the levy default to site settings', function () {
    $admin = User::factory()->create(['email_verified_at' => now()]);
    $admin->assignRole('match_director');
    $this->actingAs($admin);

    $event = matchReportEvent();

    Livewire::test(MatchReport::class, ['record' => $event->slug])
        ->set('levyRands', 75)
        ->call('saveLevyDefault');

    expect((int) SiteSetting::get(MatchReport::LEVY_SETTING_KEY))->toBe(7500);
});

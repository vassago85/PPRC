<?php

use App\Enums\EventStatus;
use App\Livewire\Site\EventRegister;
use App\Mail\MatchEntryPaymentMail;
use App\Models\Event;
use App\Models\MatchFormat;
use App\Models\Member;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

function makeOpenMatch(): Event
{
    $format = MatchFormat::firstOrCreate(
        ['slug' => 'prs-centerfire'],
        ['name' => 'PRS Centerfire', 'short_name' => 'PRS', 'is_active' => true],
    );

    return Event::create([
        'match_format_id' => $format->id,
        'title' => 'Centerfire Club Match',
        'start_date' => now()->addWeek()->toDateString(),
        'status' => EventStatus::Published,
        'registrations_open' => true,
        'registration_require_division' => false,
        'registration_require_category' => false,
        'member_price_cents' => 15000,
        'non_member_price_cents' => 20000,
    ]);
}

it('emails banking details and reference immediately when a member registers', function () {
    Mail::fake();
    $event = makeOpenMatch();

    $user = User::factory()->create(['email_verified_at' => now()]);
    Member::factory()->create(['user_id' => $user->id]);

    Livewire::actingAs($user)
        ->test(EventRegister::class, ['event' => $event])
        ->call('registerMember')
        ->assertHasNoErrors();

    Mail::assertSent(MatchEntryPaymentMail::class, 1);
});

it('does not email SAPRF member entries on registration', function () {
    Mail::fake();
    $event = makeOpenMatch();
    $event->update(['is_saprf_match' => true]);

    $user = User::factory()->create(['email_verified_at' => now()]);
    Member::factory()->create(['user_id' => $user->id]);

    Livewire::actingAs($user)
        ->test(EventRegister::class, ['event' => $event])
        ->set('viaSaprf', true)
        ->call('registerMember')
        ->assertHasNoErrors();

    Mail::assertNotSent(MatchEntryPaymentMail::class);
});

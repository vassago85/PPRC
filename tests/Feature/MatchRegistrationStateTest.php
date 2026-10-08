<?php

use App\Enums\EventStatus;
use App\Enums\RegistrationState;
use App\Livewire\Site\EventRegister;
use App\Models\EventRegistration;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

/*
 * The single source of truth for whether a match is accepting entries lives on
 * Event::registrationState(). The old code tangled "closed" together with
 * "full" and "not yet open", and the public site had no reliable way to show
 * the right copy. These tests pin the precedence rules and prove that a
 * closed/full/not-yet-open match cannot accept entries by any path — form,
 * tampered POST, nothing.
 */

/* -------------------------------------------------------------------------
 * State precedence
 * ------------------------------------------------------------------------- */

it('classifies a published, open, in-future, under-cap match as Open', function () {
    $event = transferMatch('Live Match', 45000, [
        'registrations_open' => true,
        'registrations_close_at' => now()->addWeek(),
        'max_entries' => 10,
    ]);

    expect($event->fresh()->registrationState())->toBe(RegistrationState::Open)
        ->and($event->isRegistrationOpen())->toBeTrue();
});

it('classifies registrations_open=false as Closed, not Full, even with capacity', function () {
    $event = transferMatch('Switched Off', 45000, [
        'registrations_open' => false,
        'max_entries' => 10,
    ]);

    expect($event->fresh()->registrationState())->toBe(RegistrationState::Closed);
});

it('classifies a past close date as Closed', function () {
    $event = transferMatch('Past Deadline', 45000, [
        'registrations_open' => true,
        'registrations_close_at' => now()->subDay(),
    ]);

    expect($event->fresh()->registrationState())->toBe(RegistrationState::Closed);
});

it('classifies a future open date as NotYetOpen', function () {
    $event = transferMatch('Coming Soon', 45000, [
        'registrations_open' => true,
        'registrations_open_at' => now()->addDays(3),
    ]);

    expect($event->fresh()->registrationState())->toBe(RegistrationState::NotYetOpen);
});

it('classifies at-or-over max_entries as Full', function () {
    $event = transferMatch('Capped Match', 45000, [
        'registrations_open' => true,
        'max_entries' => 1,
    ]);

    paidEntry($event, transferShooter(), ['fee_cents' => 45000]);

    expect($event->fresh()->registrationState())->toBe(RegistrationState::Full);
});

it('classifies a completed or cancelled match as Finished', function () {
    $completed = transferMatch('Done', 45000, ['status' => EventStatus::Completed]);
    $cancelled = transferMatch('Called off', 45000, ['status' => EventStatus::Cancelled]);

    expect($completed->fresh()->registrationState())->toBe(RegistrationState::Finished)
        ->and($cancelled->fresh()->registrationState())->toBe(RegistrationState::Finished);
});

it('does not reopen a Closed match just because entries are below capacity', function () {
    // This is the 10 Oct scenario — organisers close entries so they can plan,
    // the match is only half full, and the system must keep it closed.
    $event = transferMatch('10 Oct Scenario', 45000, [
        'registrations_open' => false,
        'max_entries' => 20,
    ]);

    paidEntry($event, transferShooter(), ['fee_cents' => 45000]);
    paidEntry($event, transferShooter(), ['fee_cents' => 45000]);

    expect($event->fresh()->registrationState())->toBe(RegistrationState::Closed);
});

/* -------------------------------------------------------------------------
 * No entry path accepts a closed match
 * ------------------------------------------------------------------------- */

it('refuses sendGuestPin on a closed match and emails nothing', function () {
    Mail::fake();

    $event = transferMatch('Closed To Guests', 45000, ['registrations_open' => false]);

    Livewire::test(EventRegister::class, ['event' => $event])
        ->set('guestName', 'Walkup Walter')
        ->set('guestEmail', 'walter@example.test')
        ->call('sendGuestPin')
        ->assertHasErrors(['guestEmail']);

    Mail::assertNothingSent();
    expect(EventRegistration::count())->toBe(0);
});

it('refuses registerMemberFor on a closed match', function () {
    $event = transferMatch('Closed To Members', 45000, ['registrations_open' => false]);
    $shooter = transferShooter();
    $this->actingAs($shooter->user);

    Livewire::test(EventRegister::class, ['event' => $event])
        ->call('registerMemberFor', $shooter->id)
        ->assertHasErrors(['register']);

    expect(EventRegistration::count())->toBe(0);
});

/* -------------------------------------------------------------------------
 * Public blade panels + CTAs
 * ------------------------------------------------------------------------- */

it('renders the Closed panel on the entry component, with the closing date and no entry CTA', function () {
    $event = transferMatch('Closed Panel', 45000, [
        'registrations_open' => true,
        'registrations_close_at' => now()->subDay()->setTime(18, 0),
    ]);

    $html = Livewire::test(EventRegister::class, ['event' => $event])->html();

    expect($html)->toContain('Registration closed')
        ->and($html)->toContain('finalise squad lists and match planning')
        ->and($html)->toContain(url('/contact'))
        // No invitation to enter: no "Enter match", no "Email me a code".
        ->and($html)->not->toContain('Email me a code')
        ->and($html)->not->toContain('Register me');
});

it('renders a distinct NotYetOpen panel with the opening date', function () {
    $event = transferMatch('Future', 45000, [
        'registrations_open' => true,
        'registrations_open_at' => now()->addDays(5)->setTime(9, 0),
    ]);

    $html = Livewire::test(EventRegister::class, ['event' => $event])->html();

    expect($html)->toContain('Not yet open')
        ->and($html)->toContain('Registration opens')
        ->and($html)->not->toContain('Registration closed')
        ->and($html)->not->toContain('Email me a code');
});

it('renders a Full panel instead of Closed when capacity is the reason', function () {
    $event = transferMatch('Capped Panel', 45000, [
        'registrations_open' => true,
        'max_entries' => 1,
    ]);
    paidEntry($event, transferShooter(), ['fee_cents' => 45000]);

    $html = Livewire::test(EventRegister::class, ['event' => $event->fresh()])->html();

    expect($html)->toContain('Match full')
        ->and($html)->not->toContain('Registration closed')
        ->and($html)->not->toContain('Email me a code');
});

it('shows the Enter match CTA on the match page summary when the match is open', function () {
    $event = transferMatch('Open Match', 45000, [
        'registrations_open' => true,
        'registrations_close_at' => now()->addWeek(),
    ]);

    $response = $this->get(route('matches.show', ['event' => $event->slug]))->assertOk();

    $response->assertSee('Registration open', false)
        ->assertSee('Enter match', false)
        ->assertSee('#enter', false);
});

it('shows Registration closed and no Enter match CTA on a closed match page', function () {
    $event = transferMatch('Closed Match Page', 45000, [
        'registrations_open' => false,
        'registrations_close_at' => now()->subDay(),
    ]);

    $response = $this->get(route('matches.show', ['event' => $event->slug]))->assertOk();

    $response->assertSee('Registration closed', false)
        ->assertDontSee('>Enter match<', false);
});

/* -------------------------------------------------------------------------
 * Sign-in return route
 * ------------------------------------------------------------------------- */

it('matches.sign-in parks the match URL as url.intended and redirects to login', function () {
    $event = transferMatch('Sign In Match', 45000, ['registrations_open' => true]);
    $target = route('matches.show', ['event' => $event->slug]).'#enter';

    $this->get(route('matches.sign-in', ['event' => $event->slug]))
        ->assertRedirect(route('login'));

    expect(session('url.intended'))->toBe($target);
});

it('matches.sign-in sends an already-logged-in member straight back to the match', function () {
    $event = transferMatch('Already In', 45000, ['registrations_open' => true]);
    $user = User::factory()->create(['email_verified_at' => now()]);
    $this->actingAs($user);

    $this->get(route('matches.sign-in', ['event' => $event->slug]))
        ->assertRedirect(route('matches.show', ['event' => $event->slug]).'#enter');
});

/* -------------------------------------------------------------------------
 * Listing badges
 * ------------------------------------------------------------------------- */

it('tags each upcoming match card with a Registration state badge', function () {
    transferMatch('Open Listing', 45000, [
        'registrations_open' => true,
        'registrations_close_at' => now()->addWeek(),
    ]);
    transferMatch('Closed Listing', 45000, [
        'registrations_open' => false,
    ]);

    $response = $this->get(route('matches'))->assertOk();

    // "Open" and "Closed" are both the short labels used on the badge.
    $response->assertSeeInOrder(['Open Listing', 'Open'], false);
    $response->assertSee('Closed Listing', false);
    $response->assertSee('Closed', false);
});

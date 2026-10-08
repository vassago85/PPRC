<?php

use App\Livewire\Site\EventRegister;
use App\Models\User;
use Livewire\Livewire;

/*
 * Non-members used to half-sign up for memberships thinking they needed one
 * to shoot a match. These tests pin the UX guardrails that stop that funnel:
 *
 *   • the /register page tells them up front they don't need an account to
 *     shoot a match, and offers a one-click divert to the match listing;
 *   • the match-entry page spells out "no membership required" for anyone
 *     who isn't signed in, and no longer leads with a "Join the club" CTA.
 */

it('shows a divert at the top of /register that steers match-only visitors away', function () {
    $response = $this->get('/register')->assertOk();

    // The purpose picker is the first thing we expect.
    $response->assertSee('Why are you here?', false);
    $response->assertSee('I just want to shoot a match', false);
    $response->assertSee('Join PPRC as a member', false);

    // And the match-only branch has an obvious escape hatch to /matches.
    $response->assertSee('You don\'t need an account to shoot a match', false);
    $response->assertSee(url('/matches'), false);

    // Default headline no longer silently implies "this is how you shoot a match".
    $response->assertSee('Create your member account.', false);
});

it('tells logged-out visitors on the match page that membership is not required', function () {
    $event = transferMatch('No Membership Match', 45000);

    // Static blade text (not {{ ... }}) is served raw — pass escape=false so
    // the apostrophe matches instead of looking for &#039;.
    Livewire::test(EventRegister::class, ['event' => $event])
        ->assertSee('You don\'t need a PPRC membership to shoot this match.', false)
        ->assertSee('Guests &amp; visitors', false)
        ->assertSee('No membership required. Enter below.', false);
});

it('does not lead non-members on the match page with a Join the club CTA', function () {
    $event = transferMatch('Guest-First Match', 45000);

    $html = Livewire::test(EventRegister::class, ['event' => $event])->html();

    // The old funnel: a brand-coloured "Join the club" link right where someone
    // trying to shoot would click. The new copy must not resurrect it.
    expect($html)->not->toContain('>Join the club<');
    expect($html)->toContain('Don\'t start a membership just to shoot this match');
});

it('does not show the non-member banner once a member is signed in', function () {
    $event = transferMatch('Signed-In Match', 45000);
    $user = User::factory()->create(['email_verified_at' => now()]);

    $this->actingAs($user);

    Livewire::test(EventRegister::class, ['event' => $event])
        ->assertDontSee('You don\'t need a PPRC membership to shoot this match.', false)
        ->assertDontSee('No membership required. Enter below.', false);
});

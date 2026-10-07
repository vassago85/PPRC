<?php

use App\Enums\EventRegistrationStatus;
use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\MatchFormat;
use App\Models\Member;
use App\Models\User;
use App\Services\Members\MemberMerger;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

function matchForMerge(string $title = 'Merge Match'): Event
{
    $format = MatchFormat::firstOrCreate(
        ['slug' => 'prs-centerfire'],
        ['name' => 'PRS Centerfire', 'short_name' => 'PRS', 'is_active' => true],
    );

    return Event::create([
        'match_format_id' => $format->id,
        'title' => $title,
        'start_date' => now()->addWeek()->toDateString(),
        'status' => EventStatus::Published,
        'member_price_cents' => 45000,
        'non_member_price_cents' => 50000,
    ]);
}

it('reparents memberships and match entries from loser to survivor', function () {
    $survivor = Member::factory()->active()->create();
    $loser = Member::factory()->create();

    $event1 = matchForMerge('Event One');
    $event2 = matchForMerge('Event Two');

    // Loser has entries in both events.
    EventRegistration::create([
        'event_id' => $event1->id,
        'member_id' => $loser->id,
        'status' => EventRegistrationStatus::Registered,
        'registered_at' => now(),
    ]);
    EventRegistration::create([
        'event_id' => $event2->id,
        'member_id' => $loser->id,
        'status' => EventRegistrationStatus::Registered,
        'registered_at' => now(),
    ]);

    $stats = app(MemberMerger::class)->merge($survivor, $loser);

    expect($stats['event_registrations'])->toBe(2);
    expect(EventRegistration::where('member_id', $survivor->id)->count())->toBe(2);
    expect(EventRegistration::where('member_id', $loser->id)->count())->toBe(0);
    expect(Member::find($loser->id))->toBeNull(); // soft-deleted
    expect(Member::withTrashed()->find($loser->id)?->trashed())->toBeTrue();
});

it('dedups event entries where both members entered the same match', function () {
    $survivor = Member::factory()->active()->create();
    $loser = Member::factory()->create();

    $event = matchForMerge('Shared Event');

    // Both members already have an entry in the same event.
    $survivorEntry = EventRegistration::create([
        'event_id' => $event->id,
        'member_id' => $survivor->id,
        'status' => EventRegistrationStatus::Confirmed,
        'registered_at' => now()->subDay(),
        'paid_at' => now()->subDay(),
    ]);
    EventRegistration::create([
        'event_id' => $event->id,
        'member_id' => $loser->id,
        'status' => EventRegistrationStatus::Registered,
        'registered_at' => now(),
    ]);

    app(MemberMerger::class)->merge($survivor, $loser);

    // Survivor's entry wins; loser's duplicate is deleted.
    $entries = EventRegistration::where('event_id', $event->id)->get();
    expect($entries)->toHaveCount(1);
    expect($entries->first()->id)->toBe($survivorEntry->id);
    expect($entries->first()->member_id)->toBe($survivor->id);
});

it('reparents linked sub-members to the survivor', function () {
    $survivor = Member::factory()->active()->create();
    $loser = Member::factory()->create();

    $junior = Member::factory()->create([
        'linked_adult_member_id' => $loser->id,
    ]);

    $stats = app(MemberMerger::class)->merge($survivor, $loser);

    expect($stats['sub_members'])->toBe(1);
    expect($junior->fresh()->linked_adult_member_id)->toBe($survivor->id);
});

it('attaches loser email as alias when both members have users', function () {
    $survivor = Member::factory()->active()->create();
    $loser = Member::factory()->create();
    $loserEmail = $loser->user->email;
    $loserUserId = $loser->user_id;

    app(MemberMerger::class)->merge($survivor, $loser);

    $survivorUser = $survivor->fresh()->user;
    expect($survivorUser->emailAliases()->count())->toBe(1);
    expect($survivorUser->emailAliases()->first()->email)->toBe($loserEmail);
    expect($survivorUser->must_pick_primary_email_at)->not->toBeNull();

    // The loser user stays as a scrubbed shell so the member soft-delete
    // tombstone survives (members.user_id cascadeOnDelete would hard-delete
    // the member row). Its email is unreachable and password is cleared, so
    // nobody can sign in with it.
    $loserUser = User::find($loserUserId);
    expect($loserUser)->not->toBeNull();
    expect($loserUser->email)->toContain('@deleted.pretoriaprc.local');
    // Password is replaced with a random hash — nothing can bcrypt-verify
    // against it, so the account is effectively locked.
    expect(Hash::check('secret-pass', $loserUser->password))->toBeFalse();
    expect(Hash::check('password', $loserUser->password))->toBeFalse();
});

it('skips the alias when the admin opts out', function () {
    $survivor = Member::factory()->active()->create();
    $loser = Member::factory()->create();

    app(MemberMerger::class)->merge($survivor, $loser, keepLoserEmailAsAlias: false);

    expect($survivor->fresh()->user->emailAliases()->count())->toBe(0);
    expect($survivor->fresh()->user->must_pick_primary_email_at)->toBeNull();
});

it('refuses to merge a member into itself', function () {
    $member = Member::factory()->create();

    expect(fn () => app(MemberMerger::class)->merge($member, $member))
        ->toThrow(ValidationException::class);
});

it('allows login with an alias email after a merge', function () {
    $survivor = Member::factory()->active()->create();
    $loser = Member::factory()->create();

    // Give the survivor a known password so we can log in.
    $survivor->user->forceFill(['password' => Hash::make('secret-pass')])->save();

    $loserEmail = $loser->user->email;
    app(MemberMerger::class)->merge($survivor, $loser);

    $response = $this->post('/login', [
        'email' => $loserEmail,
        'password' => 'secret-pass',
    ]);

    $response->assertRedirect();
    expect(auth()->id())->toBe($survivor->user_id);
});

it('still allows login with the survivor primary email after a merge', function () {
    $survivor = Member::factory()->active()->create();
    $loser = Member::factory()->create();

    $survivor->user->forceFill(['password' => Hash::make('secret-pass')])->save();
    $survivorEmail = $survivor->user->email;

    app(MemberMerger::class)->merge($survivor, $loser);

    $this->post('/login', [
        'email' => $survivorEmail,
        'password' => 'secret-pass',
    ])->assertRedirect();

    expect(auth()->id())->toBe($survivor->user_id);
});

it('resolves the right user for a password reset request using an alias', function () {
    $survivor = Member::factory()->active()->create();
    $loser = Member::factory()->create();
    $loserEmail = $loser->user->email;

    app(MemberMerger::class)->merge($survivor, $loser);

    // The broker uses the same user provider as login, so credentials-only
    // lookup by alias should resolve back to the survivor's user.
    $resolved = app('auth.password')->broker()->getUser(['email' => $loserEmail]);

    expect($resolved)->not->toBeNull();
    expect($resolved->getAuthIdentifier())->toBe($survivor->user_id);
});

it('promotes a chosen alias to primary and clears the flag', function () {
    $survivor = Member::factory()->active()->create();
    $loser = Member::factory()->create();

    $survivor->user->forceFill(['password' => Hash::make('secret-pass')])->save();

    $survivorEmail = $survivor->user->email;
    $loserEmail = $loser->user->email;

    app(MemberMerger::class)->merge($survivor, $loser);

    $this->actingAs($survivor->fresh()->user)
        ->put(route('portal.account.primary-email.update'), [
            'primary_email' => $loserEmail,
        ])
        ->assertRedirect(route('portal.dashboard'));

    $survivorUser = $survivor->fresh()->user;
    expect($survivorUser->email)->toBe($loserEmail);
    expect($survivorUser->must_pick_primary_email_at)->toBeNull();
    // The previous primary becomes the alias.
    expect($survivorUser->emailAliases()->pluck('email')->all())->toBe([$survivorEmail]);
});

it('shows the picker banner only while the flag is set', function () {
    $survivor = Member::factory()->active()->create();
    $loser = Member::factory()->create();

    $survivor->user->forceFill(['password' => Hash::make('secret-pass')])->save();

    app(MemberMerger::class)->merge($survivor, $loser);

    $this->actingAs($survivor->fresh()->user);

    // Before picking: banner is up on the dashboard.
    $this->get(route('portal.dashboard'))
        ->assertSee('Pick primary email');

    // After picking (keeping the current primary): banner is gone.
    $this->put(route('portal.account.primary-email.update'), [
        'primary_email' => $survivor->user->email,
    ])->assertRedirect();

    $this->get(route('portal.dashboard'))
        ->assertDontSee('Pick primary email');
});

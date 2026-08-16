<?php

use App\Enums\EventRegistrationStatus;
use App\Enums\MembershipStatus;
use App\Livewire\Portal\Membership as PortalMembership;
use App\Livewire\Portal\MyRegistrations;
use App\Livewire\Site\EventRegister;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\Member;
use App\Models\Membership;
use App\Models\User;
use App\Services\Membership\SubMemberRegistrar;
use Database\Seeders\MembershipTypesSeeder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(MembershipTypesSeeder::class);
});

function familyParent(?Carbon $periodEnd = null): Member
{
    $parent = refMember('Family', 'Parent');

    Membership::factory()->create([
        'member_id' => $parent->id,
        'status' => MembershipStatus::Active,
        'period_start' => now()->startOfYear(),
        'period_end' => $periodEnd ?? now()->endOfYear(),
    ]);

    return $parent->refresh();
}

function addJuniorFor(Member $parent, array $overrides = []): Member
{
    return app(SubMemberRegistrar::class)->registerJunior($parent, array_merge([
        'first_name' => 'Kiddo',
        'last_name' => 'Parent',
        'date_of_birth' => now()->subYears(12)->toDateString(),
    ], $overrides));
}

function openMatch(int $memberPriceCents = 20000, int $nonMemberPriceCents = 30000, ?int $juniorPriceCents = 10000): Event
{
    // Uses the same shape as tests/Pest.php refEvent(), but skips the fixed id
    // so we can seed multiple matches in one test without a PK collision.
    $format = App\Models\MatchFormat::firstOrCreate(
        ['slug' => 'prs-centerfire'],
        ['name' => 'PRS Centerfire', 'short_name' => 'PRS', 'is_active' => true],
    );

    return Event::create([
        'match_format_id' => $format->id,
        'title' => 'Family Match',
        'start_date' => now()->addWeek()->toDateString(),
        'status' => App\Enums\EventStatus::Published,
        'published_at' => now(),
        'registrations_open' => true,
        'registration_require_division' => false,
        'registration_require_category' => false,
        'member_price_cents' => $memberPriceCents,
        'non_member_price_cents' => $nonMemberPriceCents,
        'junior_price_cents' => $juniorPriceCents,
    ]);
}

it('routes a managed junior\'s payment email to the linked adult, not the placeholder inbox', function () {
    $parent = familyParent();
    $junior = addJuniorFor($parent);

    $event = openMatch();

    $registration = EventRegistration::create([
        'event_id' => $event->id,
        'member_id' => $junior->id,
        'status' => EventRegistrationStatus::Registered,
        'registered_at' => now(),
    ]);

    expect($junior->user->email)->toEndWith('@members.pretoriaprc.co.za')
        ->and($registration->payerEmail())->toBe($parent->user->email)
        ->and($registration->owesPayment())->toBeTrue();
});

it('leaves a junior with a real email as their own payer', function () {
    $parent = familyParent();
    $junior = addJuniorFor($parent, ['email' => 'my-kid@example.com']);

    $event = openMatch();
    $registration = EventRegistration::create([
        'event_id' => $event->id,
        'member_id' => $junior->id,
        'status' => EventRegistrationStatus::Registered,
        'registered_at' => now(),
    ]);

    expect($registration->payerEmail())->toBe('my-kid@example.com');
});

it('registers a linked junior in a match via the household picker', function () {
    $parent = familyParent();
    $junior = addJuniorFor($parent);
    $event = openMatch();

    Livewire::actingAs($parent->user)
        ->test(EventRegister::class, ['event' => $event])
        ->call('registerMemberFor', $junior->id)
        ->assertHasNoErrors();

    $entry = EventRegistration::where('event_id', $event->id)->where('member_id', $junior->id)->first();
    expect($entry)->not->toBeNull()
        ->and($entry->status)->toBe(EventRegistrationStatus::Registered)
        // Auto-flagged as a junior so the junior tier price applies.
        ->and($entry->is_junior)->toBeTrue();
});

it('refuses to register an unrelated member for a match', function () {
    $parent = familyParent();
    $stranger = refMember('Not', 'Yours');
    $event = openMatch();

    Livewire::actingAs($parent->user)
        ->test(EventRegister::class, ['event' => $event])
        ->call('registerMemberFor', $stranger->id)
        ->assertHasErrors('register');

    expect(EventRegistration::where('event_id', $event->id)->where('member_id', $stranger->id)->exists())->toBeFalse();
});

it('shows the household\'s registrations to the parent, not to a stranger', function () {
    $parent = familyParent();
    $junior = addJuniorFor($parent);
    $stranger = refMember('Random', 'Person');
    $event = openMatch();

    $junior_entry = EventRegistration::create([
        'event_id' => $event->id,
        'member_id' => $junior->id,
        'status' => EventRegistrationStatus::Registered,
        'registered_at' => now(),
    ]);

    Livewire::actingAs($parent->user)
        ->test(MyRegistrations::class)
        ->assertSee($event->title);

    Livewire::actingAs($stranger->user)
        ->test(MyRegistrations::class)
        ->assertDontSee($event->title);
});

it('stops a stranger from uploading proof for someone else\'s entry', function () {
    $parent = familyParent();
    $junior = addJuniorFor($parent);
    $stranger = refMember('Not', 'Family');
    $event = openMatch();

    $entry = EventRegistration::create([
        'event_id' => $event->id,
        'member_id' => $junior->id,
        'status' => EventRegistrationStatus::Registered,
        'registered_at' => now(),
    ]);

    expect(fn () => Livewire::actingAs($stranger->user)
        ->test(MyRegistrations::class)
        ->set("proofUploads.$entry->id", \Illuminate\Http\Testing\File::create('proof.pdf'))
        ->call('uploadProof', $entry->id))->toThrow(ModelNotFoundException::class);
});

it('lets a parent add a junior from the portal Membership screen', function () {
    $parent = familyParent();

    Livewire::actingAs($parent->user)
        ->test(PortalMembership::class)
        ->call('openFamilyForm', 'junior')
        ->set('familyFirstName', 'Jamie')
        ->set('familyLastName', 'Parent')
        ->set('familyDob', now()->subYears(10)->toDateString())
        ->call('addFamily')
        ->assertHasNoErrors();

    expect($parent->fresh()->subMembers()->count())->toBe(1);
});

it('lets a parent add a spouse from the portal Membership screen and it lands in pending payment', function () {
    $parent = familyParent();

    Livewire::actingAs($parent->user)
        ->test(PortalMembership::class)
        ->call('openFamilyForm', 'spouse')
        ->set('familyFirstName', 'Alex')
        ->set('familyLastName', 'Parent')
        ->call('addFamily')
        ->assertHasNoErrors();

    $spouse = $parent->fresh()->subMembers()->first();
    expect($spouse)->not->toBeNull()
        ->and($spouse->currentMembership()->status)->toBe(MembershipStatus::PendingPayment);
});

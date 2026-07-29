<?php

use App\Enums\EventRegistrationStatus;
use App\Enums\EventStatus;
use App\Enums\MembershipStatus;
use App\Enums\PaymentStatus;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\MatchFormat;
use App\Models\Member;
use App\Models\Membership;
use App\Models\MembershipPayment;
use App\Models\User;
use App\Services\Events\MatchEntryTransferService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Unit');

/*
|--------------------------------------------------------------------------
| Match fixtures
|--------------------------------------------------------------------------
|
| Shared by the match-credit and transfer suites, which each need a priced
| match, a shooter and an entry that has already been paid for. They live here
| rather than in one of the test files so neither suite depends on the order
| Pest happens to load them in.
|
*/

function transferMatch(string $title, int $memberPriceCents, array $overrides = []): Event
{
    $format = MatchFormat::firstOrCreate(
        ['slug' => 'prs-centerfire'],
        ['name' => 'PRS Centerfire', 'short_name' => 'PRS', 'is_active' => true],
    );

    return Event::create(array_merge([
        'match_format_id' => $format->id,
        'title' => $title,
        'start_date' => now()->addWeek()->toDateString(),
        'status' => EventStatus::Published,
        'published_at' => now(),
        'registrations_open' => true,
        'member_price_cents' => $memberPriceCents,
        'non_member_price_cents' => $memberPriceCents + 10000,
    ], $overrides));
}

/**
 * An active shooting member with a login, and no committee role — committee
 * roles get free entry, which would leave nothing to transfer.
 */
function transferShooter(): Member
{
    $user = User::factory()->create(['email_verified_at' => now()]);

    return Member::factory()->create(['user_id' => $user->id, 'status' => 'active']);
}

function paidEntry(Event $event, ?Member $member = null, array $overrides = []): EventRegistration
{
    return EventRegistration::create(array_merge([
        'event_id' => $event->id,
        'member_id' => $member?->id,
        'status' => EventRegistrationStatus::Confirmed,
        'registered_at' => now()->subWeek(),
        'paid_at' => now()->subWeek(),
    ], $overrides));
}

function transfers(): MatchEntryTransferService
{
    return app(MatchEntryTransferService::class);
}

/*
|--------------------------------------------------------------------------
| Payment reference fixtures
|--------------------------------------------------------------------------
|
| Both of the club's reference families encode database ids, so anything
| testing reconciliation has to pin those ids down — that's the only way to
| exercise the readings of a reference whose separators the bank stripped.
| Shared by the resolver suite and the Find payment page suite.
|
*/

function refEvent(int $id, string $title, int $memberPriceCents = 45000): Event
{
    $format = MatchFormat::firstOrCreate(
        ['slug' => 'prs-centerfire'],
        ['name' => 'PRS Centerfire', 'short_name' => 'PRS', 'is_active' => true],
    );

    $event = new Event;
    $event->forceFill([
        'id' => $id,
        'match_format_id' => $format->id,
        'title' => $title,
        'start_date' => now()->addWeek()->toDateString(),
        'status' => EventStatus::Published,
        'published_at' => now(),
        'registrations_open' => true,
        'member_price_cents' => $memberPriceCents,
        'non_member_price_cents' => $memberPriceCents + 10000,
    ])->save();

    return $event->refresh();
}

function refEntry(int $id, Event $event, ?Member $member = null, array $overrides = []): EventRegistration
{
    $entry = new EventRegistration;
    $entry->forceFill(array_merge([
        'id' => $id,
        'event_id' => $event->id,
        'member_id' => $member?->id,
        'status' => EventRegistrationStatus::Registered,
        'registered_at' => now()->subDay(),
    ], $overrides))->save();

    return $entry->refresh();
}

function refMember(string $first, string $last, array $overrides = []): Member
{
    $user = User::factory()->create(['email_verified_at' => now()]);

    return Member::factory()->create(array_merge([
        'user_id' => $user->id,
        'status' => 'active',
        'first_name' => $first,
        'last_name' => $last,
    ], $overrides));
}

function refMembershipPayment(Member $member, string $reference, PaymentStatus $status, int $amountCents = 60000): MembershipPayment
{
    // Keep the membership consistent with the payment, otherwise "confirm and
    // activate" is testing against a state the app would never produce.
    $awaitingMoney = in_array($status, [PaymentStatus::Pending, PaymentStatus::Submitted], true);

    $membership = Membership::factory()->create([
        'member_id' => $member->id,
        'status' => $awaitingMoney ? MembershipStatus::PendingPayment : MembershipStatus::Active,
    ]);

    return MembershipPayment::create([
        'membership_id' => $membership->id,
        'provider' => 'manual_eft',
        'status' => $status,
        'amount_cents' => $amountCents,
        'currency' => 'ZAR',
        'reference' => $reference,
    ]);
}

<?php

use App\Enums\EventRegistrationStatus;
use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\MatchFormat;
use App\Models\Member;
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

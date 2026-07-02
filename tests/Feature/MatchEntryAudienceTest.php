<?php

use App\Enums\EventRegistrationStatus;
use App\Enums\EventStatus;
use App\Enums\MatchEntryAudience;
use App\Mail\MatchEntrantMessageMail;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\MatchFormat;
use App\Models\Member;
use App\Services\Events\MatchEntrantBroadcastService;
use Illuminate\Support\Facades\Mail;

function audienceMatch(): Event
{
    $format = MatchFormat::firstOrCreate(
        ['slug' => 'prs-centerfire'],
        ['name' => 'PRS Centerfire', 'short_name' => 'PRS', 'is_active' => true],
    );

    return Event::create([
        'match_format_id' => $format->id,
        'title' => 'Audience Match',
        'slug' => 'audience-match',
        'start_date' => now()->addWeek()->toDateString(),
        'status' => EventStatus::Published,
        'member_price_cents' => 45000,
        'non_member_price_cents' => 50000,
    ]);
}

/**
 * @return array{event: Event, paidGuest: EventRegistration, owingGuest: EventRegistration, member: EventRegistration}
 */
function audienceEntries(): array
{
    $event = audienceMatch();

    // Guest, paid → confirmed, not awaiting, not unpaid.
    $paidGuest = EventRegistration::create([
        'event_id' => $event->id,
        'guest_name' => 'Paid Guest',
        'guest_email' => 'paid@example.com',
        'status' => EventRegistrationStatus::Registered,
        'registered_at' => now(),
        'paid_at' => now(),
    ]);

    // Guest, owes → awaiting + unpaid, not confirmed.
    $owingGuest = EventRegistration::create([
        'event_id' => $event->id,
        'guest_name' => 'Owing Guest',
        'guest_email' => 'owing@example.com',
        'status' => EventRegistrationStatus::Registered,
        'registered_at' => now(),
    ]);

    // Member entry (has account), paid → confirmed, not a guest.
    $member = Member::factory()->create();
    $memberEntry = EventRegistration::create([
        'event_id' => $event->id,
        'member_id' => $member->id,
        'status' => EventRegistrationStatus::Registered,
        'registered_at' => now(),
        'paid_at' => now(),
    ]);

    // Cancelled → excluded from every audience.
    EventRegistration::create([
        'event_id' => $event->id,
        'guest_name' => 'Cancelled Guest',
        'guest_email' => 'cancelled@example.com',
        'status' => EventRegistrationStatus::Cancelled,
        'registered_at' => now(),
        'paid_at' => now(),
    ]);

    return [
        'event' => $event,
        'paidGuest' => $paidGuest,
        'owingGuest' => $owingGuest,
        'member' => $memberEntry,
    ];
}

it('excludes cancelled entries from every audience', function () {
    $data = audienceEntries();

    foreach (MatchEntryAudience::cases() as $audience) {
        expect($audience->filter($data['event'])->pluck('guest_name'))
            ->not->toContain('Cancelled Guest');
    }
});

it('scopes the All audience to non-cancelled entries', function () {
    $data = audienceEntries();

    expect(MatchEntryAudience::All->filter($data['event']))->toHaveCount(3);
});

it('scopes the Confirmed audience to paid or waived entries', function () {
    $data = audienceEntries();

    $ids = MatchEntryAudience::Confirmed->filter($data['event'])->pluck('id');

    expect($ids)->toContain($data['paidGuest']->id, $data['member']->id)
        ->not->toContain($data['owingGuest']->id);
});

it('scopes the Awaiting audience to entries that still owe', function () {
    $data = audienceEntries();

    $ids = MatchEntryAudience::Awaiting->filter($data['event'])->pluck('id');

    expect($ids)->toContain($data['owingGuest']->id)
        ->not->toContain($data['paidGuest']->id, $data['member']->id);
});

it('scopes the Unpaid audience to entries not marked paid', function () {
    $data = audienceEntries();

    $ids = MatchEntryAudience::Unpaid->filter($data['event'])->pluck('id');

    expect($ids)->toContain($data['owingGuest']->id)
        ->not->toContain($data['paidGuest']->id, $data['member']->id);
});

it('scopes the Guests audience to entries without an account', function () {
    $data = audienceEntries();

    $ids = MatchEntryAudience::Guests->filter($data['event'])->pluck('id');

    expect($ids)->toContain($data['paidGuest']->id, $data['owingGuest']->id)
        ->not->toContain($data['member']->id);
});

it('emails only the chosen audience and reports skips', function () {
    Mail::fake();
    $data = audienceEntries();

    // Add a guest with no email so it is skipped even though it matches.
    EventRegistration::create([
        'event_id' => $data['event']->id,
        'guest_name' => 'No Email Guest',
        'status' => EventRegistrationStatus::Registered,
        'registered_at' => now(),
    ]);

    $result = app(MatchEntrantBroadcastService::class)->send(
        $data['event'],
        MatchEntryAudience::Awaiting,
        'Please pay your match fee',
        "Hi there,\nYour entry is confirmed once payment reflects.",
    );

    // Owing guest (has email) queued; no-email guest skipped.
    expect($result['sent'])->toBe(1);
    expect($result['skipped'])->toBe(1);

    // Bulk sends are queued (staggered) rather than sent immediately.
    Mail::assertQueued(MatchEntrantMessageMail::class, 1);
    Mail::assertQueued(MatchEntrantMessageMail::class, fn (MatchEntrantMessageMail $mail) => $mail->hasTo('owing@example.com')
        && $mail->subjectLine === 'Please pay your match fee');
});

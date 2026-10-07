<?php

use App\Enums\EventRegistrationStatus;
use App\Enums\EventStatus;
use App\Enums\MatchEntryAudience;
use App\Mail\MatchEntrantMessageMail;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\MatchFormat;
use App\Services\Events\MatchEntrantBroadcastService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

function whatsappMatch(?string $url = 'https://chat.whatsapp.com/ABC123xyz'): Event
{
    $format = MatchFormat::firstOrCreate(
        ['slug' => 'prs-centerfire'],
        ['name' => 'PRS Centerfire', 'short_name' => 'PRS', 'is_active' => true],
    );

    return Event::create([
        'match_format_id' => $format->id,
        'title' => 'WhatsApp Match',
        'slug' => 'whatsapp-match',
        'start_date' => now()->addWeek()->toDateString(),
        'status' => EventStatus::Published,
        'member_price_cents' => 45000,
        'non_member_price_cents' => 50000,
        'whatsapp_group_url' => $url,
    ]);
}

/**
 * Three paid guests on the match, plus a cancelled guest and a guest with no
 * email. All with distinct addresses so Mail::assertQueued can target each.
 *
 * @return array{event: Event, alice: EventRegistration, bob: EventRegistration, carol: EventRegistration, cancelled: EventRegistration, noEmail: EventRegistration}
 */
function whatsappEntries(?string $url = 'https://chat.whatsapp.com/ABC123xyz'): array
{
    $event = whatsappMatch($url);

    $alice = EventRegistration::create([
        'event_id' => $event->id,
        'guest_name' => 'Alice Guest',
        'guest_email' => 'alice@example.com',
        'status' => EventRegistrationStatus::Registered,
        'registered_at' => now(),
        'paid_at' => now(),
    ]);

    $bob = EventRegistration::create([
        'event_id' => $event->id,
        'guest_name' => 'Bob Guest',
        'guest_email' => 'bob@example.com',
        'status' => EventRegistrationStatus::Registered,
        'registered_at' => now(),
        'paid_at' => now(),
    ]);

    $carol = EventRegistration::create([
        'event_id' => $event->id,
        'guest_name' => 'Carol Guest',
        'guest_email' => 'carol@example.com',
        'status' => EventRegistrationStatus::Registered,
        'registered_at' => now(),
        'paid_at' => now(),
    ]);

    $cancelled = EventRegistration::create([
        'event_id' => $event->id,
        'guest_name' => 'Cancelled Guest',
        'guest_email' => 'cancelled@example.com',
        'status' => EventRegistrationStatus::Cancelled,
        'registered_at' => now(),
        'paid_at' => now(),
    ]);

    $noEmail = EventRegistration::create([
        'event_id' => $event->id,
        'guest_name' => 'No Email Guest',
        'status' => EventRegistrationStatus::Registered,
        'registered_at' => now(),
        'paid_at' => now(),
    ]);

    return [
        'event' => $event,
        'alice' => $alice,
        'bob' => $bob,
        'carol' => $carol,
        'cancelled' => $cancelled,
        'noEmail' => $noEmail,
    ];
}

it('queues the WhatsApp link mail and stamps each entry', function () {
    Mail::fake();
    $data = whatsappEntries();

    $result = app(MatchEntrantBroadcastService::class)->sendWhatsAppLink(
        $data['event'],
        MatchEntryAudience::Confirmed,
        skipAlreadySent: true,
    );

    // Three paid guests have emails; the no-email guest is skipped; the
    // cancelled guest is filtered out by the audience itself.
    expect($result)->toMatchArray([
        'sent' => 3,
        'skipped' => 1,
        'already' => 0,
    ]);

    Mail::assertQueued(MatchEntrantMessageMail::class, 3);
    Mail::assertQueued(
        MatchEntrantMessageMail::class,
        fn (MatchEntrantMessageMail $mail) => $mail->hasTo('alice@example.com')
            && $mail->whatsappUrl === 'https://chat.whatsapp.com/ABC123xyz'
            && str_contains($mail->subjectLine, 'WhatsApp Match'),
    );
    Mail::assertNotQueued(
        MatchEntrantMessageMail::class,
        fn (MatchEntrantMessageMail $mail) => $mail->hasTo('cancelled@example.com'),
    );

    expect($data['alice']->fresh()->whatsapp_link_sent_at)->not->toBeNull();
    expect($data['bob']->fresh()->whatsapp_link_sent_at)->not->toBeNull();
    expect($data['carol']->fresh()->whatsapp_link_sent_at)->not->toBeNull();
    // Entries that got nothing stay unstamped so they can be caught by a
    // follow-up send once they have an email on file.
    expect($data['noEmail']->fresh()->whatsapp_link_sent_at)->toBeNull();
    expect($data['cancelled']->fresh()->whatsapp_link_sent_at)->toBeNull();
});

it('appends an optional note to the body', function () {
    Mail::fake();
    $data = whatsappEntries();

    app(MatchEntrantBroadcastService::class)->sendWhatsAppLink(
        $data['event'],
        MatchEntryAudience::Confirmed,
        skipAlreadySent: true,
        note: 'Match brief at 07:30 sharp.',
    );

    Mail::assertQueued(
        MatchEntrantMessageMail::class,
        fn (MatchEntrantMessageMail $mail) => $mail->hasTo('alice@example.com')
            && str_contains($mail->body, 'Match brief at 07:30 sharp.'),
    );
});

it('only tops up new entries when skip-already-sent is on', function () {
    Mail::fake();
    $data = whatsappEntries();

    // First send: everyone with an email gets the link.
    app(MatchEntrantBroadcastService::class)->sendWhatsAppLink(
        $data['event'],
        MatchEntryAudience::Confirmed,
        skipAlreadySent: true,
    );

    Mail::fake(); // reset the assertion log before the top-up

    // A late signup joins the same audience.
    $dave = EventRegistration::create([
        'event_id' => $data['event']->id,
        'guest_name' => 'Dave Latecomer',
        'guest_email' => 'dave@example.com',
        'status' => EventRegistrationStatus::Registered,
        'registered_at' => now(),
        'paid_at' => now(),
    ]);

    $result = app(MatchEntrantBroadcastService::class)->sendWhatsAppLink(
        $data['event'],
        MatchEntryAudience::Confirmed,
        skipAlreadySent: true,
    );

    expect($result)->toMatchArray([
        'sent' => 1,
        'skipped' => 1,  // no-email guest still skipped
        'already' => 3,
    ]);

    Mail::assertQueued(MatchEntrantMessageMail::class, 1);
    Mail::assertQueued(
        MatchEntrantMessageMail::class,
        fn (MatchEntrantMessageMail $mail) => $mail->hasTo('dave@example.com'),
    );
    Mail::assertNotQueued(
        MatchEntrantMessageMail::class,
        fn (MatchEntrantMessageMail $mail) => $mail->hasTo('alice@example.com'),
    );

    expect($dave->fresh()->whatsapp_link_sent_at)->not->toBeNull();
});

it('re-sends to everyone when skip-already-sent is off', function () {
    Mail::fake();
    $data = whatsappEntries();

    app(MatchEntrantBroadcastService::class)->sendWhatsAppLink(
        $data['event'],
        MatchEntryAudience::Confirmed,
        skipAlreadySent: true,
    );

    Mail::fake();

    $result = app(MatchEntrantBroadcastService::class)->sendWhatsAppLink(
        $data['event'],
        MatchEntryAudience::Confirmed,
        skipAlreadySent: false,
    );

    expect($result)->toMatchArray([
        'sent' => 3,
        'skipped' => 1,
        'already' => 0,
    ]);

    Mail::assertQueued(MatchEntrantMessageMail::class, 3);
});

it('raises a validation error when the match has no link', function () {
    $event = whatsappMatch(null);

    expect(fn () => app(MatchEntrantBroadcastService::class)->sendWhatsAppLink(
        $event,
        MatchEntryAudience::Confirmed,
    ))->toThrow(ValidationException::class);
});

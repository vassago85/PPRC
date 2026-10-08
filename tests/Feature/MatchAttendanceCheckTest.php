<?php

use App\Enums\AttendanceResponse;
use App\Enums\EventRegistrationStatus;
use App\Enums\EventStatus;
use App\Enums\MatchEntryAudience;
use App\Mail\MatchAttendanceCheckMail;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\MatchFormat;
use App\Services\Events\MatchEntrantBroadcastService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

function attendanceMatch(): Event
{
    $format = MatchFormat::firstOrCreate(
        ['slug' => 'prs-centerfire'],
        ['name' => 'PRS Centerfire', 'short_name' => 'PRS', 'is_active' => true],
    );

    return Event::create([
        'match_format_id' => $format->id,
        'title' => 'Attendance Check Match',
        'slug' => 'attendance-check-match',
        'start_date' => now()->addWeek()->toDateString(),
        'status' => EventStatus::Published,
        'member_price_cents' => 45000,
        'non_member_price_cents' => 50000,
    ]);
}

/**
 * Builds an owing guest + a SAPRF guest + an email-less guest on the same
 * match so the service's audience + skip logic can be exercised in one go.
 *
 * @return array{event: Event, owing: EventRegistration, saprf: EventRegistration, noEmail: EventRegistration}
 */
function attendanceEntries(): array
{
    $event = attendanceMatch();

    $owing = EventRegistration::create([
        'event_id' => $event->id,
        'guest_name' => 'Owing Guest',
        'guest_email' => 'owing@example.com',
        'status' => EventRegistrationStatus::Registered,
        'registered_at' => now(),
    ]);

    $saprf = EventRegistration::create([
        'event_id' => $event->id,
        'guest_name' => 'SAPRF Guest',
        'guest_email' => 'saprf@example.com',
        'is_saprf_entry' => true,
        'status' => EventRegistrationStatus::Registered,
        'registered_at' => now(),
    ]);

    $noEmail = EventRegistration::create([
        'event_id' => $event->id,
        'guest_name' => 'Mystery Guest',
        'status' => EventRegistrationStatus::Registered,
        'registered_at' => now(),
    ]);

    return [
        'event' => $event,
        'owing' => $owing,
        'saprf' => $saprf,
        'noEmail' => $noEmail,
    ];
}

it('queues the attendance check and stamps each entry it mails', function () {
    Mail::fake();
    $data = attendanceEntries();

    $result = app(MatchEntrantBroadcastService::class)->sendAttendanceCheck(
        $data['event'],
        MatchEntryAudience::Awaiting,
    );

    // Owing guest (has email) is mailed; the no-email guest matches
    // Awaiting too but gets skipped silently; SAPRF is excluded by the
    // Awaiting audience entirely (never awaiting payment).
    expect($result)->toMatchArray([
        'sent' => 1,
        'skipped' => 1,
        'already' => 0,
    ]);

    Mail::assertQueued(MatchAttendanceCheckMail::class, 1);
    Mail::assertQueued(
        MatchAttendanceCheckMail::class,
        fn (MatchAttendanceCheckMail $mail) => $mail->hasTo('owing@example.com'),
    );

    expect($data['owing']->fresh()->attendance_check_sent_at)->not->toBeNull();
    expect($data['saprf']->fresh()->attendance_check_sent_at)->toBeNull();
});

it('tops up new entries when skip-already-sent is on', function () {
    Mail::fake();
    $data = attendanceEntries();

    // First send: the owing guest gets the check.
    app(MatchEntrantBroadcastService::class)->sendAttendanceCheck(
        $data['event'],
        MatchEntryAudience::Awaiting,
    );

    Mail::fake(); // reset the assertion log

    // A new late signup joins the Awaiting audience.
    $late = EventRegistration::create([
        'event_id' => $data['event']->id,
        'guest_name' => 'Late Signup',
        'guest_email' => 'late@example.com',
        'status' => EventRegistrationStatus::Registered,
        'registered_at' => now(),
    ]);

    $result = app(MatchEntrantBroadcastService::class)->sendAttendanceCheck(
        $data['event'],
        MatchEntryAudience::Awaiting,
        skipAlreadySent: true,
    );

    expect($result)->toMatchArray([
        'sent' => 1,
        'skipped' => 1,  // the no-email guest is still skipped on every pass
        'already' => 1,
    ]);

    Mail::assertQueued(MatchAttendanceCheckMail::class, 1);
    Mail::assertQueued(
        MatchAttendanceCheckMail::class,
        fn (MatchAttendanceCheckMail $mail) => $mail->hasTo('late@example.com'),
    );
    Mail::assertNotQueued(
        MatchAttendanceCheckMail::class,
        fn (MatchAttendanceCheckMail $mail) => $mail->hasTo('owing@example.com'),
    );

    expect($late->fresh()->attendance_check_sent_at)->not->toBeNull();
});

it('skips the attendance check for SAPRF shooters even on broader audiences', function () {
    Mail::fake();
    $data = attendanceEntries();

    // "Unpaid" audience was recently tightened to exclude SAPRF / waived —
    // this test is the belt-and-braces check that no attendance check ever
    // reaches a shooter who doesn't owe anything.
    app(MatchEntrantBroadcastService::class)->sendAttendanceCheck(
        $data['event'],
        MatchEntryAudience::Unpaid,
    );

    Mail::assertNotQueued(
        MatchAttendanceCheckMail::class,
        fn (MatchAttendanceCheckMail $mail) => $mail->hasTo('saprf@example.com'),
    );

    expect($data['saprf']->fresh()->attendance_check_sent_at)->toBeNull();
});

it('records a still-shooting response from the signed URL without cancelling the entry', function () {
    $data = attendanceEntries();

    $url = URL::temporarySignedRoute(
        'matches.attendance.record',
        now()->addDays(60),
        ['registration' => $data['owing']->id, 'response' => AttendanceResponse::StillShooting->value],
    );

    $this->get($url)->assertOk();

    $data['owing']->refresh();

    expect($data['owing']->attendance_response)->toBe(AttendanceResponse::StillShooting)
        ->and($data['owing']->attendance_responded_at)->not->toBeNull()
        ->and($data['owing']->status)->toBe(EventRegistrationStatus::Registered);
});

it('cancels the entry when the shooter clicks the withdraw button', function () {
    $data = attendanceEntries();

    $url = URL::temporarySignedRoute(
        'matches.attendance.record',
        now()->addDays(60),
        ['registration' => $data['owing']->id, 'response' => AttendanceResponse::Withdrawn->value],
    );

    $this->get($url)->assertOk();

    $data['owing']->refresh();

    expect($data['owing']->attendance_response)->toBe(AttendanceResponse::Withdrawn)
        ->and($data['owing']->status)->toBe(EventRegistrationStatus::Cancelled)
        ->and($data['owing']->attendance_responded_at)->not->toBeNull();
});

it('rejects a tampered attendance URL', function () {
    $data = attendanceEntries();

    $url = URL::temporarySignedRoute(
        'matches.attendance.record',
        now()->addDays(60),
        ['registration' => $data['owing']->id, 'response' => AttendanceResponse::StillShooting->value],
    );

    // Swap the response segment (it's a path parameter, not a query arg) from
    // "still shooting" to "withdrawn" — the signature was bound to the
    // original value so this must fail.
    $tampered = str_replace(
        '/'.AttendanceResponse::StillShooting->value.'?',
        '/'.AttendanceResponse::Withdrawn->value.'?',
        $url,
    );

    $this->get($tampered)->assertForbidden();

    expect($data['owing']->fresh()->attendance_response)->toBeNull();
});

it('shows an expired page when the match has already run', function () {
    $data = attendanceEntries();
    $data['event']->update(['start_date' => now()->subWeek()->toDateString()]);

    $url = URL::temporarySignedRoute(
        'matches.attendance.record',
        now()->addDays(60),
        ['registration' => $data['owing']->id, 'response' => AttendanceResponse::Withdrawn->value],
    );

    $this->get($url)->assertStatus(410);

    // The entry is not cancelled retroactively from a past-match click.
    expect($data['owing']->fresh()->status)->toBe(EventRegistrationStatus::Registered);
});

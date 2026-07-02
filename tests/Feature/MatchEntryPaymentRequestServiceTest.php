<?php

use App\Enums\EventRegistrationStatus;
use App\Enums\EventStatus;
use App\Mail\MatchEntryPaymentMail;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\MatchFormat;
use App\Models\Member;
use App\Models\User;
use App\Services\Events\MatchEntryPaymentRequestService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->service = app(MatchEntryPaymentRequestService::class);
});

function makeMatch(): Event
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
        'member_price_cents' => 15000,
        'non_member_price_cents' => 20000,
    ]);
}

it('emails a member entry their banking details and reference', function () {
    Mail::fake();

    $user = User::factory()->create(['email' => 'shooter@example.com']);
    $member = Member::factory()->create(['user_id' => $user->id]);
    $event = makeMatch();

    $registration = EventRegistration::create([
        'event_id' => $event->id,
        'member_id' => $member->id,
        'fee_cents' => 15000,
        'status' => EventRegistrationStatus::Registered,
        'registered_at' => now(),
    ]);

    $this->service->send($registration);

    Mail::assertSent(MatchEntryPaymentMail::class, function (MatchEntryPaymentMail $mail) use ($user, $registration) {
        return $mail->hasTo($user->email)
            && $mail->registration->id === $registration->id;
    });
});

it('emails a guest entry at their guest email', function () {
    Mail::fake();

    $event = makeMatch();

    $registration = EventRegistration::create([
        'event_id' => $event->id,
        'member_id' => null,
        'guest_name' => 'Jane Guest',
        'guest_email' => 'guest@example.com',
        'fee_cents' => 20000,
        'status' => EventRegistrationStatus::Registered,
        'registered_at' => now(),
    ]);

    $this->service->send($registration);

    Mail::assertSent(MatchEntryPaymentMail::class, fn (MatchEntryPaymentMail $mail) => $mail->hasTo('guest@example.com'));
});

it('rejects entries that owe nothing (waived / free)', function () {
    Mail::fake();

    $event = makeMatch();

    $registration = EventRegistration::create([
        'event_id' => $event->id,
        'member_id' => null,
        'guest_name' => 'Comped Official',
        'guest_email' => 'official@example.com',
        'fee_cents' => 0,
        'status' => EventRegistrationStatus::Registered,
        'registered_at' => now(),
    ]);

    expect(fn () => $this->service->send($registration))->toThrow(ValidationException::class);
    Mail::assertNothingSent();
});

it('rejects SAPRF entries (paid externally)', function () {
    Mail::fake();

    $event = makeMatch();

    $registration = EventRegistration::create([
        'event_id' => $event->id,
        'member_id' => null,
        'guest_name' => 'Saprf Shooter',
        'guest_email' => 'saprf@example.com',
        'is_saprf_entry' => true,
        'status' => EventRegistrationStatus::Registered,
        'registered_at' => now(),
    ]);

    expect(fn () => $this->service->send($registration))->toThrow(ValidationException::class);
    Mail::assertNothingSent();
});

it('rejects entries with no email on file', function () {
    Mail::fake();

    $event = makeMatch();

    $registration = EventRegistration::create([
        'event_id' => $event->id,
        'member_id' => null,
        'guest_name' => 'No Email',
        'guest_email' => null,
        'fee_cents' => 15000,
        'status' => EventRegistrationStatus::Registered,
        'registered_at' => now(),
    ]);

    expect(fn () => $this->service->send($registration))->toThrow(ValidationException::class);
    Mail::assertNothingSent();
});

it('skips invalid entries in bulk and reports counts', function () {
    Mail::fake();

    $event = makeMatch();

    $payable = EventRegistration::create([
        'event_id' => $event->id,
        'guest_name' => 'Payer',
        'guest_email' => 'payer@example.com',
        'fee_cents' => 15000,
        'status' => EventRegistrationStatus::Registered,
        'registered_at' => now(),
    ]);

    $waived = EventRegistration::create([
        'event_id' => $event->id,
        'guest_name' => 'Waived',
        'guest_email' => 'waived@example.com',
        'fee_cents' => 0,
        'status' => EventRegistrationStatus::Registered,
        'registered_at' => now(),
    ]);

    $result = $this->service->sendBulk(collect([$payable, $waived]));

    expect($result['sent'])->toBe(1)
        ->and($result['skipped'])->toBe(1);

    // Bulk sends are queued with staggered delays, not sent immediately.
    Mail::assertQueued(MatchEntryPaymentMail::class, 1);
});

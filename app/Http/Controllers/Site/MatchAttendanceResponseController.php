<?php

namespace App\Http\Controllers\Site;

use App\Enums\AttendanceResponse;
use App\Enums\EventRegistrationStatus;
use App\Http\Controllers\Controller;
use App\Models\EventRegistration;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\URL;

/**
 * Records a shooter's answer to the "are you still shooting?" email.
 *
 * The route is guarded by Laravel's `signed` middleware — the signature in
 * every URL covers both the registration id and the chosen response, so one
 * shooter forwarding their email to a friend doesn't let the friend withdraw
 * them, and the three response buttons in a single email can't be swapped by
 * tampering with the URL.
 */
class MatchAttendanceResponseController extends Controller
{
    public function record(Request $request, EventRegistration $registration, string $response): Response
    {
        $responseEnum = AttendanceResponse::tryFrom($response);

        if ($responseEnum === null) {
            abort(404);
        }

        $registration->loadMissing('event');

        // Guard against trying to respond after the match has already been
        // and gone — stops stale emails bouncing shooters into weird states
        // weeks after the fact.
        if ($registration->event?->start_date?->isPast()) {
            return response()->view('site.attendance-response-expired', [
                'event' => $registration->event,
            ], 410);
        }

        $attributes = [
            'attendance_response' => $responseEnum->value,
            'attendance_responded_at' => now(),
        ];

        // "Withdraw" also cancels the entry. The other two intents are
        // informational only — the match director sees them in the entries
        // table but the shooter stays on the list until they pay / show up.
        if ($responseEnum === AttendanceResponse::Withdrawn) {
            $attributes['status'] = EventRegistrationStatus::Cancelled->value;
        }

        $registration->update($attributes);

        // "Change your mind" links for the two intents the shooter did NOT
        // pick, so one click can flip them to any of the three states. We
        // intentionally regenerate the signed URLs here (fresh signatures)
        // rather than threading them through the email, so a stale link
        // can't bypass expiry via the thanks page.
        $alternates = collect(AttendanceResponse::cases())
            ->reject(fn (AttendanceResponse $r) => $r === $responseEnum)
            ->map(fn (AttendanceResponse $r) => [
                'response' => $r,
                'url' => URL::temporarySignedRoute(
                    'matches.attendance.record',
                    now()->addDays(60),
                    ['registration' => $registration->id, 'response' => $r->value],
                ),
            ])
            ->values();

        return response()->view('site.attendance-response-recorded', [
            'event' => $registration->event,
            'registration' => $registration,
            'response' => $responseEnum,
            'alternates' => $alternates,
        ]);
    }
}

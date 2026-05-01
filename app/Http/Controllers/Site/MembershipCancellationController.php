<?php

namespace App\Http\Controllers\Site;

use App\Enums\MemberStatus;
use App\Http\Controllers\Controller;
use App\Mail\MembershipCancelledMail;
use App\Models\Member;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

/**
 * Self-service membership cancellation. Reached from the renewal reminder
 * email via a signed temporary URL — no login required, since lapsed members
 * may not even remember their password and we don't want friction for them
 * to opt out.
 *
 * Flow:
 *  1. GET  /membership/cancel/{member}?signature=…  → confirmation page
 *  2. POST /membership/cancel/{member}?signature=…  → set status=Resigned,
 *     stamp resigned_at, optionally save a reason, send confirmation mail
 *     to the member and the membership secretary, redirect to thank-you.
 */
class MembershipCancellationController extends Controller
{
    public function confirm(Request $request, Member $member): View
    {
        abort_unless($request->hasValidSignature(), 403, 'This cancellation link is invalid or has expired.');

        return view('site.membership-cancel-confirm', [
            'member' => $member,
            'alreadyResigned' => $member->status === MemberStatus::Resigned || $member->resigned_at !== null,
        ]);
    }

    public function apply(Request $request, Member $member): View|RedirectResponse
    {
        abort_unless($request->hasValidSignature(), 403, 'This cancellation link is invalid or has expired.');

        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($member->status === MemberStatus::Resigned || $member->resigned_at !== null) {
            return view('site.membership-cancel-done', ['member' => $member, 'alreadyResigned' => true]);
        }

        $member->forceFill([
            'status' => MemberStatus::Resigned,
            'resigned_at' => now(),
            'resignation_reason' => $data['reason'] ?? null,
        ])->save();

        // Notify the resigning member (so they have proof) and the secretary
        // (so they can update SAPRF / paper records). Failures don't block
        // the response — the cancellation still happened.
        try {
            $email = $member->user?->email;
            if ($email) {
                Mail::to($email, $member->fullName())
                    ->cc('membership@pretoriaprc.co.za')
                    ->send(new MembershipCancelledMail($member));
            } else {
                Mail::to('membership@pretoriaprc.co.za')
                    ->send(new MembershipCancelledMail($member));
            }
        } catch (\Throwable) {
            // Already cancelled in DB; mail failure is non-fatal here.
        }

        return view('site.membership-cancel-done', ['member' => $member, 'alreadyResigned' => false]);
    }
}

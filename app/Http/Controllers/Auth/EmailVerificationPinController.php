<?php

namespace App\Http\Controllers\Auth;

use App\Services\Auth\EmailVerificationPinService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;

class EmailVerificationPinController
{
    public function show(Request $request, EmailVerificationPinService $pins): View|RedirectResponse
    {
        $user = $request->user();
        abort_unless($user, 403);

        if ($user->hasVerifiedEmail()) {
            return redirect()->intended(config('fortify.home', '/portal'));
        }

        if ($user->created_via_import) {
            return redirect()->intended(config('fortify.home', '/portal'));
        }

        if (! $pins->hasUsablePin($user)) {
            $executed = RateLimiter::attempt(
                'email-verification-auto-pin:'.$user->id,
                1,
                fn () => $pins->issueAndSend($user),
                120,
            );

            if (! $executed) {
                session()->flash('status', 'verification-pin-wait');
            }
        }

        return view('auth.verify-email');
    }

    public function verify(Request $request, EmailVerificationPinService $pins): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user, 403);

        if ($user->hasVerifiedEmail()) {
            return redirect()->intended(config('fortify.home', '/portal'));
        }

        if ($user->created_via_import) {
            return redirect()->intended(config('fortify.home', '/portal'));
        }

        $validated = $request->validate([
            'pin' => ['required', 'string', 'regex:/^\d{4,10}$/'],
        ]);

        $lockKey = 'email-verification-pin-lock:'.$user->id;
        if (Cache::has($lockKey)) {
            return back()->withErrors([
                'pin' => 'Too many incorrect attempts. Please wait before trying again.',
            ])->onlyInput('pin');
        }

        if (! $pins->verifyPin($user, $validated['pin'])) {
            $failKey = 'email-verification-pin-fails:'.$user->id;
            $fails = (int) Cache::get($failKey, 0) + 1;
            $maxFails = max(3, (int) config('auth_verification.pin_max_attempts', 8));
            $lockout = max(60, (int) config('auth_verification.pin_lockout_seconds', 900));
            Cache::put($failKey, $fails, now()->addSeconds($lockout));

            if ($fails >= $maxFails) {
                Cache::forget($failKey);
                Cache::put($lockKey, true, now()->addSeconds($lockout));
            }

            return back()->withErrors(['pin' => 'That code is incorrect or has expired.'])->onlyInput('pin');
        }

        Cache::forget('email-verification-pin-fails:'.$user->id);
        Cache::forget($lockKey);

        return redirect()->intended(config('fortify.home', '/portal'))
            ->with('status', 'email-verified');
    }

    public function resend(Request $request, EmailVerificationPinService $pins): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user, 403);

        if ($user->hasVerifiedEmail()) {
            return redirect()->intended(config('fortify.home', '/portal'));
        }

        if ($user->created_via_import) {
            return redirect()->intended(config('fortify.home', '/portal'));
        }

        $executed = RateLimiter::attempt(
            'email-verification-resend:'.$user->id,
            5,
            fn () => $pins->issueAndSend($user),
            3600,
        );

        if (! $executed) {
            return back()->withErrors(['pin' => 'Please wait before requesting another code.']);
        }

        return back()->with('status', 'verification-pin-sent');
    }
}

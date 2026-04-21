<?php

namespace App\Services\Auth;

use App\Mail\EmailVerificationPinMail;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class EmailVerificationPinService
{
    public function hasUsablePin(User $user): bool
    {
        if ($user->email_verification_pin_hash === null) {
            return false;
        }

        if ($user->email_verification_pin_expires_at === null) {
            return false;
        }

        return $user->email_verification_pin_expires_at->isFuture();
    }

    /**
     * Generate a numeric PIN, store a hash + expiry, and email it (plain PIN once).
     */
    public function issueAndSend(User $user): string
    {
        $length = max(4, min(10, (int) config('auth_verification.pin_length', 6)));
        $min = 10 ** ($length - 1);
        $max = (10 ** $length) - 1;
        $pin = (string) random_int($min, $max);

        $minutes = max(5, (int) config('auth_verification.pin_expires_minutes', 60));

        $user->forceFill([
            'email_verification_pin_hash' => Hash::make($pin),
            'email_verification_pin_expires_at' => now()->addMinutes($minutes),
        ])->save();

        Mail::to($user)->queue(new EmailVerificationPinMail($user, $pin, $minutes));

        return $pin;
    }

    public function clearPin(User $user): void
    {
        $user->forceFill([
            'email_verification_pin_hash' => null,
            'email_verification_pin_expires_at' => null,
        ])->save();
    }

    public function verifyPin(User $user, string $pin): bool
    {
        if ($user->hasVerifiedEmail()) {
            return true;
        }

        if (! $this->hasUsablePin($user)) {
            return false;
        }

        $pin = trim($pin);

        if ($pin === '' || ! ctype_digit($pin)) {
            return false;
        }

        if (! Hash::check($pin, (string) $user->email_verification_pin_hash)) {
            return false;
        }

        $user->markEmailAsVerified();

        return true;
    }
}

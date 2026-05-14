<?php

namespace App\Actions\Fortify;

use App\Models\User;
use App\Services\Membership\MemberService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Validate and create a newly registered user + member profile.
     *
     * @param  array<string, string>  $input
     *
     * @throws ValidationException
     */
    public function create(array $input): User
    {
        // Honeypot: if the hidden "website" field is filled, it's a bot.
        if (filled($input['website'] ?? null)) {
            throw ValidationException::withMessages([
                'email' => ['Registration failed. Please try again.'],
            ]);
        }

        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique(User::class),
            ],
            'password' => $this->passwordRules(),
        ])->validate();

        // Cloudflare Turnstile verification
        $this->verifyTurnstile($input['cf-turnstile-response'] ?? null);

        $user = User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => Hash::make($input['password']),
        ]);

        app(MemberService::class)->register($user);

        return $user;
    }

    protected function verifyTurnstile(?string $token): void
    {
        $secret = config('services.turnstile.secret_key');

        if (blank($secret)) {
            return;
        }

        if (blank($token)) {
            throw ValidationException::withMessages([
                'cf-turnstile-response' => ['Please complete the security check.'],
            ]);
        }

        $response = Http::asForm()->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
            'secret' => $secret,
            'response' => $token,
            'remoteip' => request()->ip(),
        ]);

        if (! $response->json('success')) {
            throw ValidationException::withMessages([
                'cf-turnstile-response' => ['Security verification failed. Please try again.'],
            ]);
        }
    }
}

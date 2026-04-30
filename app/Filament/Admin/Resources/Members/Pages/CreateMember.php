<?php

namespace App\Filament\Admin\Resources\Members\Pages;

use App\Filament\Admin\Resources\Members\MemberResource;
use App\Mail\MemberWelcomeInvite;
use App\Models\EmailLog;
use App\Models\User;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CreateMember extends CreateRecord
{
    protected static string $resource = MemberResource::class;

    /**
     * Captured from the form before they're stripped from the Member payload,
     * so afterCreate() can decide whether to fire the welcome email.
     */
    protected bool $shouldSendWelcome = false;

    protected ?string $createdEmail = null;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $email = strtolower(trim((string) ($data['email'] ?? '')));
        if ($email === '') {
            throw ValidationException::withMessages([
                'data.email' => 'Email is required.',
            ]);
        }

        $this->shouldSendWelcome = (bool) ($data['send_welcome_email'] ?? true);
        $this->createdEmail = $email;

        // Resolve / create the linked user. If the email already exists we
        // attach the existing account (no password reset, no overwrite) —
        // this lets a committee admin add a Member profile to a user record
        // that was created via the public registration flow first.
        $existing = User::where('email', $email)->first();

        if ($existing) {
            // Guard: refuse to attach to a user who already has a Member
            // profile, so we never accidentally orphan / duplicate one.
            if ($existing->member()->exists()) {
                throw ValidationException::withMessages([
                    'data.email' => 'A member already exists for that email.',
                ]);
            }

            $data['user_id'] = $existing->id;

            // The member's first/last name lives on Member; mirror it onto
            // the user's display name so emails and the portal greeting
            // pick it up.
            $existing->forceFill([
                'name' => self::nameFromMember($data) ?: $existing->name,
            ])->save();
        } else {
            $user = User::create([
                'name' => self::nameFromMember($data) ?: $email,
                'email' => $email,
                'password' => Hash::make(Str::random(48)),
                'created_via_import' => false,
            ]);

            $data['user_id'] = $user->id;
        }

        unset($data['email'], $data['send_welcome_email']);

        return $data;
    }

    protected function afterCreate(): void
    {
        if (! $this->shouldSendWelcome) {
            return;
        }

        /** @var \App\Models\Member $member */
        $member = $this->record;
        $user = $member->user;
        if (! $user) {
            return;
        }

        try {
            $token = Password::broker()->createToken($user);
            $setupUrl = url(route('password.reset', [
                'token' => $token,
                'email' => $user->email,
            ], absolute: false));

            Mail::to($user->email, $user->name)->send(new MemberWelcomeInvite(
                user: $user,
                setupUrl: $setupUrl,
                firstName: $member->first_name ?: null,
            ));

            Notification::make()->success()
                ->title('Welcome email sent')
                ->body($user->email)
                ->send();
        } catch (\Throwable $e) {
            EmailLog::create([
                'user_id' => $user->id,
                'to_email' => $user->email,
                'to_name' => $user->name,
                'subject' => 'Welcome to Pretoria Precision Rifle Club — claim your account',
                'mailable_class' => MemberWelcomeInvite::class,
                'status' => EmailLog::STATUS_FAILED,
                'error' => $e->getMessage(),
                'context' => ['source' => 'filament-admin-create'],
            ]);

            Notification::make()->warning()
                ->title('Member created, but welcome email failed')
                ->body($e->getMessage().' — you can resend from the row action.')
                ->persistent()
                ->send();
        }
    }

    protected static function nameFromMember(array $data): ?string
    {
        $full = trim(($data['first_name'] ?? '').' '.($data['last_name'] ?? ''));

        return $full !== '' ? $full : null;
    }

    protected function getRedirectUrl(): string
    {
        /** @var Model $record */
        $record = $this->record;

        return static::getResource()::getUrl('edit', ['record' => $record]);
    }
}

<?php

namespace App\Livewire\Site;

use App\Enums\EventRegistrationStatus;
use App\Mail\EventGuestRegistrationPinMail;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\Member;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Livewire\Component;

class EventRegister extends Component
{
    public Event $event;

    public string $guestName = '';

    public string $guestEmail = '';

    public string $guestPhone = '';

    public string $pin = '';

    public string $division = '';

    public string $category = '';

    /** guest | pin | done */
    public string $guestStep = 'guest';

    public ?string $toast = null;

    public function mount(Event $event): void
    {
        $this->event = $event;
    }

    public function getMemberProperty(): ?Member
    {
        return auth()->user()?->member;
    }

    public function getAlreadyRegisteredProperty(): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        $member = $user->member;
        if ($member && EventRegistration::query()
            ->where('event_id', $this->event->id)
            ->where('member_id', $member->id)
            ->exists()) {
            return true;
        }

        $email = strtolower(trim((string) $user->email));
        if ($email !== '' && EventRegistration::query()
            ->where('event_id', $this->event->id)
            ->whereRaw('LOWER(guest_email) = ?', [$email])
            ->exists()) {
            return true;
        }

        return false;
    }

    public function sendGuestPin(): void
    {
        $this->validate(array_merge([
            'guestName' => ['required', 'string', 'max:150'],
            'guestEmail' => ['required', 'email', 'max:150'],
            'guestPhone' => ['nullable', 'string', 'max:32'],
        ], $this->registrationFieldRules()));

        $email = strtolower(trim($this->guestEmail));

        if (EventRegistration::query()
            ->where('event_id', $this->event->id)
            ->whereRaw('LOWER(guest_email) = ?', [$email])
            ->exists()) {
            $this->addError('guestEmail', 'This email is already entered for this match.');

            return;
        }

        $rateKey = 'evt-pin:'.$this->event->id.':'.$email;
        if (Cache::get($rateKey.'.cooldown')) {
            $this->addError('guestEmail', 'Please wait a minute before requesting another code.');

            return;
        }

        $pin = str_pad((string) random_int(0, 999_999), 6, '0', STR_PAD_LEFT);
        $hash = password_hash($pin, PASSWORD_DEFAULT);

        Cache::put($this->pinCacheKey($email), $hash, now()->addMinutes(15));
        Cache::put($rateKey.'.cooldown', 1, now()->addMinute());

        Mail::to($this->guestEmail)->send(new EventGuestRegistrationPinMail($this->event, $pin));

        $this->guestStep = 'pin';
        $this->pin = '';
        $this->toast = 'We emailed you a 6-digit code. Enter it below to confirm.';
    }

    public function confirmGuestPin(): void
    {
        $this->validate([
            'pin' => ['required', 'digits:6'],
        ]);

        $email = strtolower(trim($this->guestEmail));
        $stored = Cache::get($this->pinCacheKey($email));

        if (! is_string($stored) || ! password_verify($this->pin, $stored)) {
            $this->addError('pin', 'That code is incorrect or has expired.');

            return;
        }

        Cache::put($this->verifiedCacheKey($email), 1, now()->addMinutes(20));
        Cache::forget($this->pinCacheKey($email));

        $this->registerGuest();
    }

    public function registerGuest(): void
    {
        $this->validate(array_merge([
            'guestName' => ['required', 'string', 'max:150'],
            'guestEmail' => ['required', 'email', 'max:150'],
            'guestPhone' => ['nullable', 'string', 'max:32'],
        ], $this->registrationFieldRules()));

        if (! $this->event->isRegistrationOpen()) {
            $this->addError('guestEmail', 'Registrations are not open for this match.');

            return;
        }

        $email = strtolower(trim($this->guestEmail));

        if (! Cache::get($this->verifiedCacheKey($email))) {
            $this->addError('guestEmail', 'Verify your email with the code we sent before registering.');
            $this->guestStep = 'pin';

            return;
        }

        try {
            EventRegistration::create([
                'event_id' => $this->event->id,
                'member_id' => null,
                'guest_name' => $this->guestName,
                'guest_email' => $email,
                'guest_phone' => $this->guestPhone ?: null,
                'division' => $this->normalizedDivision(),
                'category' => $this->normalizedCategory(),
                'status' => EventRegistrationStatus::Registered,
                'registered_at' => now(),
            ]);
        } catch (QueryException) {
            $this->addError('guestEmail', 'This email is already registered for this match.');

            return;
        }

        Cache::forget($this->verifiedCacheKey($email));

        $this->guestStep = 'done';
        $this->toast = 'You are registered. We will see you at the range.';
    }

    public function registerMember(): void
    {
        $user = auth()->user();
        abort_unless($user && $user->hasVerifiedEmail(), 403);

        $member = $user->member;
        abort_unless($member instanceof Member, 403);

        if (! $this->event->isRegistrationOpen()) {
            $this->addError('register', 'Registrations are not open for this match.');

            return;
        }

        if (EventRegistration::query()
            ->where('event_id', $this->event->id)
            ->where('member_id', $member->id)
            ->exists()) {
            $this->addError('register', 'You are already registered for this match.');

            return;
        }

        $this->validate($this->registrationFieldRules());

        EventRegistration::create([
            'event_id' => $this->event->id,
            'member_id' => $member->id,
            'guest_name' => null,
            'guest_email' => null,
            'guest_phone' => null,
            'division' => $this->normalizedDivision(),
            'category' => $this->normalizedCategory(),
            'status' => EventRegistrationStatus::Registered,
            'registered_at' => now(),
        ]);

        $this->toast = 'You are registered for this match.';
    }

    private function pinCacheKey(string $emailLower): string
    {
        return 'evt-reg-pin:'.$this->event->id.':'.$emailLower;
    }

    private function verifiedCacheKey(string $emailLower): string
    {
        return 'evt-reg-verified:'.$this->event->id.':'.$emailLower;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function registrationFieldRules(): array
    {
        $rules = [];
        $divisions = $this->event->registrationDivisionChoices();
        $categories = $this->event->registrationCategoryChoices();

        if ($this->event->collectsDivisionAtRegistration()) {
            $rules['division'] = $divisions === []
                ? ['required', 'string', 'max:80']
                : ['required', 'string', 'max:80', Rule::in($divisions)];
        } else {
            $rules['division'] = ['nullable', 'string', 'max:80'];
        }

        if ($this->event->collectsCategoryAtRegistration()) {
            $rules['category'] = $categories === []
                ? ['required', 'string', 'max:80']
                : ['required', 'string', 'max:80', Rule::in($categories)];
        } else {
            $rules['category'] = ['nullable', 'string', 'max:80'];
        }

        return $rules;
    }

    private function normalizedDivision(): ?string
    {
        if (! $this->event->collectsDivisionAtRegistration()) {
            return null;
        }

        return $this->division === '' ? null : $this->division;
    }

    private function normalizedCategory(): ?string
    {
        if (! $this->event->collectsCategoryAtRegistration()) {
            return null;
        }

        return $this->category === '' ? null : $this->category;
    }

    public function render()
    {
        return view('livewire.site.event-register');
    }
}

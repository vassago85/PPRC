<?php

namespace App\Livewire\Portal;

use App\Models\Member;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.portal.layout')]
#[Title('Edit profile')]
class ProfileEdit extends Component
{
    use WithFileUploads;

    public string $first_name = '';

    public string $last_name = '';

    public string $known_as = '';

    public string $phone_country_code = '+27';

    public string $phone_number = '';

    public string $address_line1 = '';

    public string $address_line2 = '';

    public string $city = '';

    public string $province = '';

    public string $postal_code = '';

    public string $country = 'South Africa';

    public string $date_of_birth = '';

    /** @var array<int, string> */
    public array $shooting_disciplines = [];

    public $photo = null;

    public const DISCIPLINES = [
        'PRS Centerfire',
        'PR22',
        'F-Class',
        'Benchrest',
        'Long Range',
        'Other',
    ];

    public function mount(): void
    {
        $member = auth()->user()?->member;
        abort_unless($member, 403);

        $this->first_name = (string) $member->first_name;
        $this->last_name = (string) $member->last_name;
        $this->known_as = (string) ($member->known_as ?? '');
        $this->phone_country_code = (string) ($member->phone_country_code ?: '+27');
        $this->phone_number = (string) ($member->phone_number ?? '');
        $this->address_line1 = (string) ($member->address_line1 ?? '');
        $this->address_line2 = (string) ($member->address_line2 ?? '');
        $this->city = (string) ($member->city ?? '');
        $this->province = (string) ($member->province ?? '');
        $this->postal_code = (string) ($member->postal_code ?? '');
        $this->country = (string) ($member->country ?: 'South Africa');
        $this->date_of_birth = $member->date_of_birth?->format('Y-m-d') ?? '';
        $this->shooting_disciplines = $member->shooting_disciplines ?? [];
    }

    public function save(): void
    {
        $validated = $this->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'known_as' => ['nullable', 'string', 'max:100'],
            'phone_country_code' => ['required', 'string', 'max:8'],
            'phone_number' => ['required', 'string', 'max:32'],
            'address_line1' => ['required', 'string', 'max:255'],
            'address_line2' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:120'],
            'province' => ['required', 'string', 'max:120'],
            'postal_code' => ['required', 'string', 'max:32'],
            'country' => ['required', 'string', 'max:120'],
            'date_of_birth' => ['required', 'date', 'before:today'],
            'shooting_disciplines' => ['nullable', 'array'],
            'shooting_disciplines.*' => ['string', 'in:'.implode(',', self::DISCIPLINES)],
            'photo' => ['nullable', 'image', 'max:4096'],
        ]);

        /** @var Member $member */
        $member = auth()->user()->member;
        abort_unless($member, 403);

        $member->update([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'known_as' => $validated['known_as'] ?? null,
            'phone_country_code' => $validated['phone_country_code'],
            'phone_number' => $validated['phone_number'],
            'address_line1' => $validated['address_line1'],
            'address_line2' => $validated['address_line2'] ?? null,
            'city' => $validated['city'],
            'province' => $validated['province'],
            'postal_code' => $validated['postal_code'],
            'country' => $validated['country'],
            'date_of_birth' => $validated['date_of_birth'],
            'shooting_disciplines' => $validated['shooting_disciplines'] ?? [],
        ]);

        if ($this->photo) {
            $path = $this->photo->storeAs(
                'members/photos',
                $member->id.'.'.$this->photo->getClientOriginalExtension(),
                's3',
            );
            $member->update(['profile_photo_path' => $path]);
            $this->photo = null;
        }

        session()->flash('flash', 'Profile updated.');
    }

    public function render(): mixed
    {
        return view('livewire.portal.profile-edit');
    }
}

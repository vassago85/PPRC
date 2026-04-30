<?php

namespace App\Livewire\Portal;

use App\Enums\EndorsementStatus;
use App\Models\EndorsementRequest;
use App\Models\Member;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.portal.layout')]
#[Title('Documents')]
class Documents extends Component
{
    public string $reason = 'Sport shooting';

    public string $itemType = 'rifle';

    public string $firearmType = 'Bolt action';

    public string $componentType = '';

    public string $make = '';

    public string $calibre = '';

    public string $idNumber = '';

    public string $firearmDetails = '';

    public function mount(): void
    {
        abort_unless(auth()->check(), 403);

        // Pre-fill the ID number field from the member record, if known —
        // members typically only need to supply it once and then it's stored
        // for any future endorsement requests.
        $this->idNumber = (string) ($this->member?->id_number ?? '');
    }

    #[Computed]
    public function member(): ?Member
    {
        return auth()->user()?->member;
    }

    #[Computed]
    public function endorsements()
    {
        $member = $this->member;
        if (! $member) {
            return collect();
        }

        return EndorsementRequest::where('member_id', $member->id)
            ->orderByDesc('created_at')
            ->get();
    }

    #[Computed]
    public function hasPendingEndorsement(): bool
    {
        $member = $this->member;
        if (! $member) {
            return false;
        }

        return EndorsementRequest::where('member_id', $member->id)
            ->where('status', EndorsementStatus::Pending->value)
            ->exists();
    }

    #[Computed]
    public function hasActiveMembership(): bool
    {
        return $this->member?->hasActiveMembership() ?? false;
    }

    public function requestEndorsement(): void
    {
        $member = $this->member;
        abort_unless($member, 403);

        if (! $member->hasActiveMembership()) {
            session()->flash('flash_error', 'You must have an active membership to request an endorsement.');
            return;
        }

        if ($this->hasPendingEndorsement) {
            session()->flash('flash_error', 'You already have a pending endorsement request.');
            return;
        }

        $this->validate([
            'reason' => ['required', 'string', 'max:255'],
            'itemType' => ['required', 'in:rifle,component'],
            'firearmType' => ['nullable', 'string', 'max:120'],
            'componentType' => ['nullable', 'string', 'max:60', 'required_if:itemType,component'],
            'make' => ['required', 'string', 'max:120'],
            'calibre' => ['required', 'string', 'max:60'],
            'firearmDetails' => ['nullable', 'string', 'max:1000'],
            'idNumber' => ['required', 'string', 'max:32'],
        ]);

        if ($member->id_number !== $this->idNumber) {
            $member->update(['id_number' => $this->idNumber]);
        }

        $isComponent = $this->itemType === 'component';

        EndorsementRequest::create([
            'member_id' => $member->id,
            'reason' => $this->reason,
            'item_type' => $this->itemType,
            'firearm_type' => $isComponent ? null : ($this->firearmType ?: null),
            'component_type' => $isComponent ? ($this->componentType ?: null) : null,
            'make' => $this->make ?: null,
            'calibre' => $this->calibre ?: null,
            'firearm_details' => $this->firearmDetails ?: null,
            'status' => EndorsementStatus::Pending,
        ]);

        $this->reset('itemType', 'firearmType', 'componentType', 'make', 'calibre', 'firearmDetails');
        $this->reason = 'Sport shooting';
        $this->itemType = 'rifle';
        $this->firearmType = 'Bolt action';
        unset($this->endorsements, $this->hasPendingEndorsement);
        session()->flash('flash', 'Endorsement request submitted. The committee will review it shortly.');
    }

    public function render(): mixed
    {
        return view('livewire.portal.documents');
    }
}

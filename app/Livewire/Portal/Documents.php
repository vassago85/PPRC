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
    public string $reason = '';

    public string $firearmType = '';

    public string $firearmDetails = '';

    public string $motivation = '';

    public function mount(): void
    {
        abort_unless(auth()->check(), 403);
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
            'firearmType' => ['required', 'string', 'max:120'],
            'firearmDetails' => ['nullable', 'string', 'max:1000'],
            'motivation' => ['required', 'string', 'max:2000'],
        ]);

        EndorsementRequest::create([
            'member_id' => $member->id,
            'reason' => $this->reason,
            'firearm_type' => $this->firearmType,
            'firearm_details' => $this->firearmDetails ?: null,
            'motivation' => $this->motivation,
            'status' => EndorsementStatus::Pending,
        ]);

        $this->reset('reason', 'firearmType', 'firearmDetails', 'motivation');
        unset($this->endorsements, $this->hasPendingEndorsement);
        session()->flash('flash', 'Endorsement request submitted. The committee will review it shortly.');
    }

    public function render(): mixed
    {
        return view('livewire.portal.documents');
    }
}

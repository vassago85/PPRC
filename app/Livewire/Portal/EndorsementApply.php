<?php

namespace App\Livewire\Portal;

use App\Enums\EndorsementStatus;
use App\Models\EndorsementRequest;
use App\Models\Member;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Dedicated endorsement application page.
 *
 * Lives on its own URL (portal/documents/endorsement/apply) so that the
 * questionnaire has space to breathe instead of being stacked underneath the
 * other documents/membership cards. The Documents page links here.
 */
#[Layout('components.portal.layout')]
#[Title('Apply for endorsement')]
class EndorsementApply extends Component
{
    public string $reason = 'Sport shooting';

    public string $itemType = 'rifle';

    public string $firearmType = 'Bolt action';

    public string $componentType = '';

    public string $make = '';

    public string $calibre = '';

    public string $actionSerialNumber = '';

    public string $barrelSerialNumber = '';

    public string $idNumber = '';

    public string $firearmDetails = '';

    public function mount(): void
    {
        abort_unless(auth()->check(), 403);

        $this->idNumber = (string) ($this->member?->id_number ?? '');
    }

    #[Computed]
    public function member(): ?Member
    {
        return auth()->user()?->member;
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

    public function requestEndorsement()
    {
        $member = $this->member;
        abort_unless($member, 403);

        if (! $member->hasActiveMembership()) {
            session()->flash('flash_error', 'You must have an active membership to request an endorsement.');

            return null;
        }

        if ($this->hasPendingEndorsement) {
            session()->flash('flash_error', 'You already have a pending endorsement request.');

            return null;
        }

        $this->validate(
            rules: [
                'reason' => ['required', 'string', 'max:255'],
                'itemType' => ['required', 'in:rifle,component'],
                'firearmType' => ['nullable', 'string', 'max:120'],
                'componentType' => ['nullable', 'string', 'max:60', 'required_if:itemType,component'],
                'make' => ['required', 'string', 'max:120'],
                'calibre' => ['required', 'string', 'max:60'],
                // At least one serial is required; both can be supplied.
                'actionSerialNumber' => ['nullable', 'string', 'max:80', 'required_without:barrelSerialNumber'],
                'barrelSerialNumber' => ['nullable', 'string', 'max:80', 'required_without:actionSerialNumber'],
                'firearmDetails' => ['nullable', 'string', 'max:1000'],
                'idNumber' => ['required', 'string', 'max:32'],
            ],
            messages: [
                'actionSerialNumber.required_without' => 'Provide the action serial number, the barrel serial number, or both.',
                'barrelSerialNumber.required_without' => 'Provide the action serial number, the barrel serial number, or both.',
            ],
            attributes: [
                'actionSerialNumber' => 'action serial number',
                'barrelSerialNumber' => 'barrel serial number',
            ],
        );

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
            'action_serial_number' => $this->actionSerialNumber ?: null,
            'barrel_serial_number' => $this->barrelSerialNumber ?: null,
            'firearm_details' => $this->firearmDetails ?: null,
            'status' => EndorsementStatus::Pending,
        ]);

        session()->flash('flash', 'Endorsement request submitted. The committee will review it shortly.');

        return $this->redirect(route('portal.documents'), navigate: true);
    }

    public function render(): mixed
    {
        return view('livewire.portal.endorsement-apply');
    }
}

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
 * Lightweight documents landing page.
 *
 * The endorsement application form lives on its own page
 * ({@see EndorsementApply}) so this view only summarises what the member
 * can do here and links out to the dedicated apply flow.
 */
#[Layout('components.portal.layout')]
#[Title('Documents')]
class Documents extends Component
{
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

    public function render(): mixed
    {
        return view('livewire.portal.documents');
    }
}

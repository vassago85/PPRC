<?php

namespace App\Livewire\Portal;

use App\Enums\MembershipStatus;
use App\Models\Event;
use App\Models\EventResult;
use App\Models\Member;
use App\Models\Membership;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.portal.layout')]
#[Title('Dashboard')]
class Dashboard extends Component
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
    public function membership(): ?Membership
    {
        return $this->member?->currentMembership();
    }

    #[Computed]
    public function needsRenewal(): bool
    {
        $m = $this->membership;

        if (! $m) {
            return true;
        }

        if ($m->status === MembershipStatus::Expired || $m->status === MembershipStatus::Cancelled) {
            return true;
        }

        if ($m->period_end && $m->period_end->diffInDays(now(), absolute: false) > -30) {
            return true;
        }

        return false;
    }

    #[Computed]
    public function upcomingMatches(): Collection
    {
        return Event::upcoming()
            ->with('matchFormat')
            ->limit(5)
            ->get();
    }

    #[Computed]
    public function recentResults(): Collection
    {
        $member = $this->member;

        if (! $member) {
            return collect();
        }

        return EventResult::where('member_id', $member->id)
            ->with(['event' => fn ($q) => $q->withTrashed()])
            ->latest('id')
            ->limit(5)
            ->get();
    }

    public function render(): mixed
    {
        return view('livewire.portal.dashboard');
    }
}

<?php

namespace App\Livewire\Portal;

use App\Models\EventRegistration;
use App\Models\Member;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.portal.layout')]
#[Title('My Registrations')]
class MyRegistrations extends Component
{
    public function mount(): void
    {
        abort_unless(auth()->user()?->member, 403);
    }

    #[Computed]
    public function member(): Member
    {
        return auth()->user()->member;
    }

    #[Computed]
    public function registrations(): Collection
    {
        return EventRegistration::query()
            ->where('member_id', $this->member->id)
            ->with(['event' => fn ($q) => $q->with('matchFormat')])
            ->whereHas('event')
            ->get()
            ->sortByDesc(fn (EventRegistration $r) => $r->event->start_date);
    }

    #[Computed]
    public function upcoming(): Collection
    {
        return $this->registrations->filter(
            fn (EventRegistration $r) => $r->event->start_date->gte(today())
        )->values();
    }

    #[Computed]
    public function past(): Collection
    {
        return $this->registrations->filter(
            fn (EventRegistration $r) => $r->event->start_date->lt(today())
        )->values();
    }

    public function render(): mixed
    {
        return view('livewire.portal.my-registrations');
    }
}

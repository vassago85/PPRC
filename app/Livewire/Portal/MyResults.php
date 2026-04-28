<?php

namespace App\Livewire\Portal;

use App\Models\EventResult;
use App\Models\Member;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.portal.layout')]
#[Title('My Results')]
class MyResults extends Component
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
    public function results(): Collection
    {
        return EventResult::query()
            ->where('member_id', $this->member->id)
            ->with(['event' => fn ($q) => $q->with('matchFormat')])
            ->whereHas('event')
            ->orderByDesc(
                \App\Models\Event::select('start_date')
                    ->whereColumn('events.id', 'event_results.event_id')
                    ->limit(1)
            )
            ->get();
    }

    public function render(): mixed
    {
        return view('livewire.portal.my-results');
    }
}

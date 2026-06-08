<?php

namespace App\Livewire\Portal;

use App\Enums\EventRegistrationStatus;
use App\Models\EventRegistration;
use App\Models\Member;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.portal.layout')]
#[Title('My Registrations')]
class MyRegistrations extends Component
{
    use WithFileUploads;

    /** @var array<int, mixed> Proof-of-payment uploads keyed by registration id. */
    public array $proofUploads = [];

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

    /**
     * Upcoming entries that still owe an entry fee — what the member needs to
     * pay and upload proof for. Entries marked paid drop off automatically.
     */
    #[Computed]
    public function payable(): Collection
    {
        return $this->upcoming->filter(
            fn (EventRegistration $r) => $r->status !== EventRegistrationStatus::Cancelled
                && $r->awaitingPayment()
        )->values();
    }

    public function withdraw(int $registrationId): void
    {
        $reg = EventRegistration::query()
            ->where('member_id', $this->member->id)
            ->whereHas('event', fn ($q) => $q->where('start_date', '>=', today()))
            ->findOrFail($registrationId);

        if ($reg->status === EventRegistrationStatus::Cancelled) {
            session()->flash('flash_error', 'This registration is already cancelled.');
            return;
        }

        $reg->update(['status' => EventRegistrationStatus::Cancelled]);

        unset($this->registrations, $this->upcoming, $this->past, $this->payable);
        session()->flash('flash', 'Withdrawn from ' . $reg->event->title . '.');
    }

    public function uploadProof(int $registrationId): void
    {
        $this->validate([
            "proofUploads.$registrationId" => ['required', 'file', 'max:8192', 'mimes:pdf,jpg,jpeg,png'],
        ], [], [
            "proofUploads.$registrationId" => 'proof of payment',
        ]);

        $reg = EventRegistration::query()
            ->where('member_id', $this->member->id)
            ->findOrFail($registrationId);

        $path = $this->proofUploads[$registrationId]->store('events/proofs', \App\Support\MediaDisk::name());

        $reg->update([
            'payment_proof_path' => $path,
            'proof_submitted_at' => now(),
        ]);

        unset($this->proofUploads[$registrationId]);
        unset($this->registrations, $this->upcoming, $this->past, $this->payable);
        session()->flash('flash', 'Proof of payment uploaded for ' . $reg->event->title . '. The committee will confirm shortly.');
    }

    public function render(): mixed
    {
        return view('livewire.portal.my-registrations');
    }
}

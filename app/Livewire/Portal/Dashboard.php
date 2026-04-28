<?php

namespace App\Livewire\Portal;

use App\Enums\MembershipStatus;
use App\Enums\PaymentProvider;
use App\Enums\PaymentStatus;
use App\Models\Event;
use App\Models\EventResult;
use App\Models\Member;
use App\Models\Membership;
use App\Models\MembershipPayment;
use App\Models\MembershipType;
use App\Services\Membership\MembershipTypeService;
use App\Services\Membership\PaymentReferenceGenerator;
use App\Services\Membership\RenewalService;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.portal.layout')]
#[Title('Dashboard')]
class Dashboard extends Component
{
    use WithFileUploads;

    public ?int $renewIntoTypeId = null;

    public $proofUpload = null;

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
    public function pendingPayment(): ?MembershipPayment
    {
        $m = $this->membership;
        if (! $m || $m->status !== MembershipStatus::PendingPayment) {
            return null;
        }

        return $m->payments->firstWhere('status', PaymentStatus::Pending);
    }

    #[Computed]
    public function needsRenewal(): bool
    {
        $m = $this->membership;

        if (! $m) {
            return true;
        }

        if (in_array($m->status, [MembershipStatus::Expired, MembershipStatus::Cancelled])) {
            return true;
        }

        if ($m->period_end && $m->period_end->diffInDays(now(), absolute: false) > -30) {
            return true;
        }

        return false;
    }

    #[Computed]
    public function types(): Collection
    {
        return app(MembershipTypeService::class)->activeForRegistration();
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

    public function renew(RenewalService $renewal): void
    {
        $this->validate(['renewIntoTypeId' => ['required', 'exists:membership_types,id']]);

        $member = $this->member;
        abort_unless($member, 403);

        if ($this->membership?->status === MembershipStatus::PendingPayment) {
            session()->flash('flash_error', 'You already have a pending membership — pay for it first.');
            return;
        }

        $type = MembershipType::findOrFail($this->renewIntoTypeId);
        $renewal->renew($member, $type);

        $this->renewIntoTypeId = null;
        unset($this->membership, $this->pendingPayment, $this->needsRenewal);
        session()->flash('flash', 'Membership requested — see your payment details below.');
    }

    public function uploadProof(int $paymentId): void
    {
        $this->validate(['proofUpload' => ['required', 'file', 'max:8192']]);

        $payment = MembershipPayment::whereHas(
            'membership',
            fn ($q) => $q->where('member_id', $this->member?->id),
        )->findOrFail($paymentId);

        $path = $this->proofUpload->store('memberships/proofs', 's3');

        $payment->update([
            'proof_path' => $path,
            'status' => PaymentStatus::Submitted,
            'submitted_at' => now(),
        ]);

        $payment->membership->update(['status' => MembershipStatus::PendingApproval]);

        $this->proofUpload = null;
        unset($this->membership, $this->pendingPayment);
        session()->flash('flash', 'Proof uploaded — the committee will verify shortly.');
    }

    public function render(): mixed
    {
        return view('livewire.portal.dashboard');
    }
}

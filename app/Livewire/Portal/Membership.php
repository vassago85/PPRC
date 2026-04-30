<?php

namespace App\Livewire\Portal;

use App\Enums\MembershipStatus;
use App\Enums\PaymentProvider;
use App\Enums\PaymentStatus;
use App\Models\Member;
use App\Models\Membership as MembershipModel;
use App\Models\MembershipPayment;
use App\Models\MembershipType;
use App\Services\Membership\MembershipIssuer;
use App\Services\Membership\MembershipTypeService;
use App\Services\Membership\PaymentReferenceGenerator;
use App\Services\Membership\RenewalService;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.portal.layout')]
#[Title('My Membership')]
class Membership extends Component
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
    public function current(): ?MembershipModel
    {
        return $this->member()?->currentMembership();
    }

    #[Computed]
    public function types()
    {
        return app(MembershipTypeService::class)->activeForRegistration();
    }

    #[Computed]
    public function subMembers()
    {
        return $this->member()
            ?->subMembers()
            ?->with(['memberships' => fn ($q) => $q->latest('period_end')->limit(1)])
            ->get() ?? collect();
    }

    #[Computed]
    public function history()
    {
        $member = $this->member();

        return $member
            ? $member->memberships()->with('payments')->orderByDesc('period_end')->get()
            : collect();
    }

    #[Computed]
    public function clubBadges()
    {
        $member = $this->member();

        return $member
            ? $member->clubBadges()->orderBy('club_badges.sort_order')->get()
            : collect();
    }

    public function renew(RenewalService $renewal): void
    {
        $this->validate(['renewIntoTypeId' => ['required', 'exists:membership_types,id']]);

        $member = $this->member();
        abort_unless($member, 403);

        if (in_array($this->current()?->status, [MembershipStatus::PendingPayment, MembershipStatus::PendingApproval], true)) {
            session()->flash('flash', 'You already have a pending membership. Complete the current process first.');
            return;
        }

        $type = MembershipType::findOrFail($this->renewIntoTypeId);
        $renewal->renew($member, $type);

        $this->renewIntoTypeId = null;
        session()->flash('flash', 'Membership requested. Please pay to activate.');
    }

    public function startEftPayment(int $membershipId): void
    {
        $membership = MembershipModel::where('member_id', $this->member()?->id)->findOrFail($membershipId);

        MembershipPayment::firstOrCreate(
            [
                'membership_id' => $membership->id,
                'provider' => PaymentProvider::ManualEft->value,
                'status' => PaymentStatus::Pending->value,
            ],
            [
                'amount_cents' => $membership->price_cents_snapshot,
                'currency' => 'ZAR',
                'reference' => app(PaymentReferenceGenerator::class)->generate(),
            ],
        );

        session()->flash('flash', 'EFT reference generated. Please pay and then upload proof.');
    }

    public function uploadProof(int $paymentId): void
    {
        $this->validate(['proofUpload' => ['required', 'file', 'max:8192']]);

        $payment = MembershipPayment::whereHas('membership', fn ($q) => $q->where('member_id', $this->member()?->id))
            ->findOrFail($paymentId);

        $path = $this->proofUpload->store('memberships/proofs', \App\Support\MediaDisk::name());

        $payment->update([
            'proof_path' => $path,
            'status' => PaymentStatus::Submitted,
            'submitted_at' => now(),
        ]);

        $payment->membership->update(['status' => MembershipStatus::PendingApproval]);

        $this->proofUpload = null;
        session()->flash('flash', 'Proof of payment uploaded. The committee will verify shortly.');
    }

    public function render(): mixed
    {
        return view('livewire.portal.membership');
    }
}

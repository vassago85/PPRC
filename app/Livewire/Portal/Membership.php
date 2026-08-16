<?php

namespace App\Livewire\Portal;

use App\Enums\MembershipStatus;
use App\Enums\PaymentProvider;
use App\Enums\PaymentStatus;
use App\Enums\RenewalSource;
use App\Models\Member;
use App\Models\Membership as MembershipModel;
use App\Models\MembershipPayment;
use App\Models\MembershipType;
use App\Services\Membership\MembershipIssuer;
use App\Services\Membership\MembershipTypeService;
use App\Services\Membership\PaymentReferenceGenerator;
use App\Services\Membership\RenewalService;
use App\Services\Membership\SubMemberRegistrar;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.portal.layout')]
#[Title('My Membership')]
class Membership extends Component
{
    use WithFileUploads;

    public ?int $renewIntoTypeId = null;

    public $proofUpload = null;

    #[Url(as: 'via', keep: false)]
    public ?string $via = null;

    /** null | 'junior' | 'spouse' — controls which inline family form is open. */
    public ?string $familyForm = null;

    public string $familyFirstName = '';

    public string $familyLastName = '';

    public string $familyDob = '';

    public string $familyEmail = '';

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

    /**
     * Every linked family member (junior + spouse) with their latest membership
     * eager-loaded. Used to render the Family section: name, type, status,
     * plus the pending EFT payment for a spouse.
     */
    #[Computed]
    public function family()
    {
        $member = $this->member();
        if (! $member) {
            return collect();
        }

        return $member->subMembers()
            ->with(['memberships' => fn ($q) => $q
                ->latest('period_end')
                ->with(['payments' => fn ($p) => $p->orderBy('id', 'desc')]),
            ])
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();
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

    /**
     * The parent can add family only when they hold an active membership and
     * are not themselves a junior. Life and pensioner members qualify.
     */
    #[Computed]
    public function canAddFamily(): bool
    {
        $member = $this->member();
        if (! $member) {
            return false;
        }

        return $member->hasActiveMembership() && ! $member->isJunior();
    }

    #[Computed]
    public function juniorCount(): int
    {
        return $this->family->filter(function ($sub) {
            $ms = $sub->memberships->first();

            return $ms && str_contains(strtolower((string) $ms->membership_type_slug_snapshot), 'junior');
        })->count();
    }

    #[Computed]
    public function spouseCount(): int
    {
        return $this->family->filter(function ($sub) {
            $ms = $sub->memberships->first();

            return $ms && $ms->membership_type_slug_snapshot === 'spouse';
        })->count();
    }

    #[Computed]
    public function canAddJunior(): bool
    {
        return $this->canAddFamily() && $this->juniorCount() < 4;
    }

    #[Computed]
    public function canAddSpouse(): bool
    {
        return $this->canAddFamily() && $this->spouseCount() < 1;
    }

    public function openFamilyForm(string $type): void
    {
        if (! in_array($type, ['junior', 'spouse'], true)) {
            return;
        }

        $this->resetFamilyForm();
        $this->familyForm = $type;
        $this->familyLastName = (string) ($this->member()?->last_name ?? '');
    }

    public function cancelFamilyForm(): void
    {
        $this->resetFamilyForm();
    }

    protected function resetFamilyForm(): void
    {
        $this->familyForm = null;
        $this->familyFirstName = '';
        $this->familyLastName = '';
        $this->familyDob = '';
        $this->familyEmail = '';
        $this->resetErrorBag(['familyFirstName', 'familyLastName', 'familyDob', 'familyEmail']);
    }

    public function addFamily(SubMemberRegistrar $registrar): void
    {
        $type = $this->familyForm;
        abort_unless(in_array($type, ['junior', 'spouse'], true), 422);

        $parent = $this->member();
        abort_unless($parent, 403);

        if (! $this->canAddFamily()) {
            session()->flash('flash_error', 'You need an active membership before you can add family members.');

            return;
        }

        if ($type === 'junior' && ! $this->canAddJunior()) {
            session()->flash('flash_error', 'You have reached the maximum of 4 juniors on your account.');

            return;
        }

        if ($type === 'spouse' && ! $this->canAddSpouse()) {
            session()->flash('flash_error', 'You already have a spouse linked to your account.');

            return;
        }

        $rules = [
            'familyFirstName' => ['required', 'string', 'max:80'],
            'familyLastName' => ['required', 'string', 'max:80'],
            'familyEmail' => ['nullable', 'email', 'max:150'],
        ];

        if ($type === 'junior') {
            $rules['familyDob'] = [
                'required', 'date',
                'before:' . now()->subYears(0)->toDateString(),
                'after:' . now()->subYears(25)->toDateString(),
            ];
        } else {
            $rules['familyDob'] = ['nullable', 'date'];
        }

        $data = $this->validate($rules, [], [
            'familyFirstName' => 'first name',
            'familyLastName' => 'last name',
            'familyDob' => 'date of birth',
            'familyEmail' => 'email',
        ]);

        try {
            $payload = [
                'first_name' => trim($data['familyFirstName']),
                'last_name' => trim($data['familyLastName']),
                'date_of_birth' => $data['familyDob'] !== '' ? $data['familyDob'] : null,
                'email' => trim($data['familyEmail']) !== '' ? trim($data['familyEmail']) : null,
            ];

            $type === 'junior'
                ? $registrar->registerJunior($parent, $payload)
                : $registrar->registerSpouse($parent, $payload);
        } catch (ValidationException $e) {
            // Surface the registrar's rule errors (age, cap, duplicate email)
            // on the closest field so the message lands where the user is looking.
            foreach ($e->errors() as $field => $messages) {
                $target = match ($field) {
                    'email' => 'familyEmail',
                    'membership_type_id', 'membership_type', 'linked_adult_member_id' => 'familyFirstName',
                    default => 'familyFirstName',
                };
                foreach ((array) $messages as $m) {
                    $this->addError($target, $m);
                }
            }

            return;
        }

        unset($this->family, $this->subMembers, $this->juniorCount, $this->spouseCount, $this->canAddJunior, $this->canAddSpouse);

        $flash = $type === 'junior'
            ? 'Added! Their junior membership is free while yours is active. Enter them in a match from any match page.'
            : 'Added! Pay the spouse membership fee below to activate their account.';

        session()->flash('flash', $flash);
        $this->resetFamilyForm();
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
        $source = $this->via === 'reminder' ? RenewalSource::Reminder : RenewalSource::MemberInitiated;
        $renewal->renew($member, $type, source: $source);

        $this->renewIntoTypeId = null;
        $this->via = null;
        unset($this->current);
        session()->flash('flash', 'Membership requested — your banking details and reference are below.');
    }

    public function startEftPayment(int $membershipId): void
    {
        $membership = MembershipModel::whereIn('member_id', $this->householdMemberIds())->findOrFail($membershipId);

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
        $this->validate(['proofUpload' => ['required', 'file', 'max:8192', 'mimes:pdf,jpg,jpeg,png']]);

        // Widened from "my membership" to "any household membership" so a
        // parent can pay a spouse's fee from their own portal. The scope is
        // the member's own id plus every linked sub-member's id — nothing
        // outside that set is ever loadable, so a tampered id 404s.
        $payment = MembershipPayment::whereHas(
            'membership',
            fn ($q) => $q->whereIn('member_id', $this->householdMemberIds())
        )->findOrFail($paymentId);

        $path = $this->proofUpload->store('memberships/proofs', \App\Support\ProofDisk::name());

        $payment->update([
            'proof_path' => $path,
            'status' => PaymentStatus::Submitted,
            'submitted_at' => now(),
        ]);

        $payment->membership->update(['status' => MembershipStatus::PendingApproval]);

        $this->proofUpload = null;
        unset($this->family);
        session()->flash('flash', 'Proof of payment uploaded. The committee will verify shortly.');
    }

    /**
     * IDs of the members the logged-in adult may pay/upload proof for —
     * themselves plus their linked sub-members. Used by every action that
     * accepts a client-controlled membership or payment id.
     *
     * @return array<int, int>
     */
    protected function householdMemberIds(): array
    {
        $member = $this->member();
        if (! $member) {
            return [];
        }

        return $member->householdMembers()->pluck('id')->all();
    }

    public function render(): mixed
    {
        return view('livewire.portal.membership');
    }
}

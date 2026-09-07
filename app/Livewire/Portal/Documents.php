<?php

namespace App\Livewire\Portal;

use App\Enums\EndorsementStatus;
use App\Enums\InvoiceType;
use App\Invoices\InvoiceFactory;
use App\Invoices\InvoiceUrl;
use App\Models\EndorsementRequest;
use App\Models\EventRegistration;
use App\Models\Member;
use App\Models\MembershipPayment;
use App\Models\ShopOrder;
use Illuminate\Support\Collection;
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

    /**
     * Household membership payments, match entries, and this account's shop
     * orders that can produce an invoice — newest first.
     *
     * @return Collection<int, array{type: InvoiceType, id: int, label: string, number: string, amount: string, date: string, url: string}>
     */
    #[Computed]
    public function invoices(): Collection
    {
        $factory = app(InvoiceFactory::class);
        $rows = collect();
        $member = $this->member;
        $householdIds = $member?->householdMembers()->pluck('id') ?? collect();

        if ($householdIds->isNotEmpty()) {
            MembershipPayment::query()
                ->whereHas('membership', fn ($q) => $q->whereIn('member_id', $householdIds))
                ->with(['membership.member', 'membership.membershipType'])
                ->orderByDesc('id')
                ->get()
                ->filter(fn (MembershipPayment $payment) => $factory->canGenerate($payment))
                ->each(function (MembershipPayment $payment) use ($factory, $rows) {
                    $invoice = $factory->make($payment);
                    $rows->push([
                        'type' => InvoiceType::Membership,
                        'id' => $payment->id,
                        'label' => $payment->purposeLabel().' — '.($payment->membership?->membership_type_name_snapshot ?? 'Membership'),
                        'number' => $invoice->number,
                        'amount' => $invoice->formatted($invoice->totalCents),
                        'date' => $invoice->issuedAt->format('d M Y'),
                        'sort' => $invoice->issuedAt->timestamp,
                        'url' => InvoiceUrl::portal(InvoiceType::Membership, $payment->id),
                    ]);
                });

            EventRegistration::query()
                ->whereIn('member_id', $householdIds)
                ->with(['event', 'member'])
                ->orderByDesc('id')
                ->get()
                ->filter(fn (EventRegistration $registration) => $factory->canGenerate($registration))
                ->each(function (EventRegistration $registration) use ($factory, $rows) {
                    $invoice = $factory->make($registration);
                    $rows->push([
                        'type' => InvoiceType::Match,
                        'id' => $registration->id,
                        'label' => $registration->event?->title ?? 'Match entry',
                        'number' => $invoice->number,
                        'amount' => $invoice->formatted($invoice->totalCents),
                        'date' => $invoice->issuedAt->format('d M Y'),
                        'sort' => $invoice->issuedAt->timestamp,
                        'url' => InvoiceUrl::portal(InvoiceType::Match, $registration->id),
                    ]);
                });
        }

        $user = auth()->user();
        if ($user) {
            ShopOrder::query()
                ->where('user_id', $user->id)
                ->with(['lines.product', 'run'])
                ->orderByDesc('id')
                ->get()
                ->filter(fn (ShopOrder $order) => $factory->canGenerate($order))
                ->each(function (ShopOrder $order) use ($factory, $rows) {
                    $invoice = $factory->make($order);
                    $rows->push([
                        'type' => InvoiceType::Shop,
                        'id' => $order->id,
                        'label' => $order->run?->title ?? 'Shop order',
                        'number' => $invoice->number,
                        'amount' => $invoice->formatted($invoice->totalCents),
                        'date' => $invoice->issuedAt->format('d M Y'),
                        'sort' => $invoice->issuedAt->timestamp,
                        'url' => InvoiceUrl::portal(InvoiceType::Shop, $order->id),
                    ]);
                });
        }

        return $rows->sortByDesc('sort')->values();
    }

    public function render(): mixed
    {
        return view('livewire.portal.documents');
    }
}

<?php

namespace App\Invoices;

use App\Enums\InvoiceType;
use App\Enums\PaymentProvider;
use App\Enums\PaymentStatus;
use App\Enums\ShopOrderStatus;
use App\Models\EventRegistration;
use App\Models\Member;
use App\Models\MembershipPayment;
use App\Models\ShopOrder;

class InvoiceFactory
{
    public function find(InvoiceType $type, int $id): MembershipPayment|EventRegistration|ShopOrder
    {
        $source = match ($type) {
            InvoiceType::Membership => MembershipPayment::query()
                ->with(['membership.member.user', 'membership.member.linkedAdult.user', 'membership.membershipType'])
                ->find($id),
            InvoiceType::Match => EventRegistration::query()
                ->with(['event', 'member.user', 'member.linkedAdult.user'])
                ->find($id),
            InvoiceType::Shop => ShopOrder::query()
                ->with(['lines.product', 'user.member', 'run'])
                ->find($id),
        };

        abort_unless($source !== null, 404);

        return $source;
    }

    public function canGenerate(MembershipPayment|EventRegistration|ShopOrder $source): bool
    {
        return match (true) {
            $source instanceof MembershipPayment => $this->canGenerateMembership($source),
            $source instanceof EventRegistration => $this->canGenerateMatch($source),
            $source instanceof ShopOrder => $this->canGenerateShop($source),
        };
    }

    public function make(MembershipPayment|EventRegistration|ShopOrder $source): InvoiceDocument
    {
        abort_unless($this->canGenerate($source), 404);

        return match (true) {
            $source instanceof MembershipPayment => $this->fromMembership($source),
            $source instanceof EventRegistration => $this->fromMatch($source),
            $source instanceof ShopOrder => $this->fromShop($source),
        };
    }

    public function typeOf(MembershipPayment|EventRegistration|ShopOrder $source): InvoiceType
    {
        return match (true) {
            $source instanceof MembershipPayment => InvoiceType::Membership,
            $source instanceof EventRegistration => InvoiceType::Match,
            $source instanceof ShopOrder => InvoiceType::Shop,
        };
    }

    private function canGenerateMembership(MembershipPayment $payment): bool
    {
        if ((int) $payment->amount_cents <= 0) {
            return false;
        }

        return in_array($payment->status, [
            PaymentStatus::Pending,
            PaymentStatus::Submitted,
            PaymentStatus::Confirmed,
        ], true);
    }

    private function canGenerateMatch(EventRegistration $registration): bool
    {
        if ($registration->is_saprf_entry || $registration->isWaived()) {
            return false;
        }

        if ((int) ($registration->effectiveFeeCents() ?? 0) <= 0) {
            return false;
        }

        return $registration->outstandingCents() > 0 || $registration->paid_at !== null;
    }

    private function canGenerateShop(ShopOrder $order): bool
    {
        if ((int) $order->total_cents <= 0) {
            return false;
        }

        return ! in_array($order->status, [
            ShopOrderStatus::Draft,
            ShopOrderStatus::Cancelled,
        ], true);
    }

    private function fromMembership(MembershipPayment $payment): InvoiceDocument
    {
        $membership = $payment->membership;
        $member = $payment->payerMember();
        $typeName = $membership?->membership_type_name_snapshot
            ?? $membership?->membershipType?->name
            ?? 'Membership';
        $periodStart = $membership?->period_start?->format('j M Y');
        $periodEnd = $membership?->period_end?->format('j M Y') ?? 'Life';
        $period = $periodStart ? "{$periodStart} – {$periodEnd}" : $typeName;

        $lines = [
            new InvoiceLine(
                description: $payment->purposeLabel().' — '.$typeName.' ('.$period.')',
                quantity: 1,
                unitCents: (int) $payment->amount_cents,
                lineCents: (int) $payment->amount_cents,
            ),
        ];

        $paid = $payment->status === PaymentStatus::Confirmed;
        [$billToName, $billToEmail] = $this->billTo($member);

        return new InvoiceDocument(
            type: InvoiceType::Membership,
            sourceId: (int) $payment->id,
            number: filled($payment->reference) ? (string) $payment->reference : 'MEM-'.$payment->id,
            issuedAt: $payment->confirmed_at ?? $payment->created_at ?? now(),
            payerName: $payment->payerName(),
            billToName: $billToName,
            billToEmail: $billToEmail,
            membershipNumber: $member?->membership_number,
            lines: $lines,
            subtotalCents: (int) $payment->amount_cents,
            totalCents: (int) $payment->amount_cents,
            currency: $payment->currency ?: 'ZAR',
            paid: $paid,
            paidAt: $payment->confirmed_at,
            paymentMethod: $this->providerLabel($payment->provider),
            statusLabel: $paid ? 'Paid' : 'Amount due',
        );
    }

    private function fromMatch(EventRegistration $registration): InvoiceDocument
    {
        $event = $registration->event;
        $title = $event?->title ?? 'Match entry';
        $date = $event?->start_date?->format('j F Y');
        $description = $date ? "{$title} — {$date}" : $title;
        $shooter = $registration->shooterName();

        if ($registration->member && $registration->member->linkedAdult
            && $registration->member->linkedAdult->id !== $registration->member->id) {
            $description .= ' (shooter: '.$shooter.')';
        }

        $fee = (int) ($registration->effectiveFeeCents() ?? 0);
        $credit = $registration->creditAppliedCents();
        $total = $registration->outstandingCents();

        $lines = [
            new InvoiceLine(
                description: $description,
                quantity: 1,
                unitCents: $fee,
                lineCents: $fee,
            ),
        ];

        if ($credit > 0) {
            $lines[] = new InvoiceLine(
                description: 'Match credit applied',
                quantity: 1,
                unitCents: -$credit,
                lineCents: -$credit,
            );
        }

        $paid = $registration->paid_at !== null;
        $member = $registration->member;
        [$billToName, $billToEmail] = $member
            ? $this->billTo($member)
            : [trim((string) $registration->guest_name) ?: 'Guest', $registration->guest_email];

        return new InvoiceDocument(
            type: InvoiceType::Match,
            sourceId: (int) $registration->id,
            number: $registration->paymentReference(),
            issuedAt: $registration->paid_at ?? $registration->registered_at ?? $registration->created_at ?? now(),
            payerName: $shooter,
            billToName: $billToName,
            billToEmail: filled($billToEmail) ? $billToEmail : null,
            membershipNumber: $member?->membership_number,
            lines: $lines,
            subtotalCents: $fee,
            totalCents: $total,
            currency: 'ZAR',
            paid: $paid,
            paidAt: $registration->paid_at,
            paymentMethod: $registration->payment_method?->label() ?? 'EFT',
            statusLabel: $paid ? 'Paid' : 'Amount due',
        );
    }

    private function fromShop(ShopOrder $order): InvoiceDocument
    {
        $lines = [];

        foreach ($order->lines as $line) {
            $lines[] = new InvoiceLine(
                description: $line->product?->name ?? 'Shop item',
                quantity: max(1, (int) $line->quantity),
                unitCents: (int) $line->unit_price_cents,
                lineCents: (int) $line->line_total_cents,
            );
        }

        if ((int) $order->shipping_cents > 0) {
            $lines[] = new InvoiceLine(
                description: 'Shipping',
                quantity: 1,
                unitCents: (int) $order->shipping_cents,
                lineCents: (int) $order->shipping_cents,
            );
        }

        $paid = in_array($order->status, [ShopOrderStatus::Paid, ShopOrderStatus::Fulfilled], true);
        $number = $order->eft_reference
            ?: $order->paystack_reference
            ?: 'SHOP-'.$order->id;

        return new InvoiceDocument(
            type: InvoiceType::Shop,
            sourceId: (int) $order->id,
            number: (string) $number,
            issuedAt: $order->submitted_at ?? $order->created_at ?? now(),
            payerName: $order->ship_to_name ?: ($order->user?->name ?? 'Customer'),
            billToName: $order->ship_to_name ?: ($order->user?->name ?? 'Customer'),
            billToEmail: $order->user?->email,
            membershipNumber: $order->user?->member?->membership_number,
            lines: $lines,
            subtotalCents: (int) $order->subtotal_cents,
            totalCents: (int) $order->total_cents,
            currency: $order->currency ?: 'ZAR',
            paid: $paid,
            paidAt: $paid ? ($order->submitted_at ?? $order->updated_at) : null,
            paymentMethod: $this->providerLabel($order->payment_provider),
            statusLabel: $paid ? 'Paid' : 'Amount due',
        );
    }

    /**
     * @return array{0: ?string, 1: ?string}
     */
    private function billTo(?Member $member): array
    {
        if (! $member) {
            return [null, null];
        }

        $adult = $member->linkedAdult;
        $billTo = ($adult && $adult->id !== $member->id) ? $adult : $member;

        return [
            $billTo->fullName(),
            $this->reachableEmail($billTo->user?->email),
        ];
    }

    private function reachableEmail(?string $email): ?string
    {
        if (! is_string($email) || $email === '') {
            return null;
        }

        if (str_ends_with(strtolower($email), '@members.pretoriaprc.co.za')) {
            return null;
        }

        return $email;
    }

    private function providerLabel(?PaymentProvider $provider): ?string
    {
        return match ($provider) {
            PaymentProvider::ManualEft => 'EFT',
            PaymentProvider::Paystack => 'Paystack',
            default => null,
        };
    }
}

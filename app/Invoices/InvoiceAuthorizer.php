<?php

namespace App\Invoices;

use App\Enums\InvoiceType;
use App\Models\EventRegistration;
use App\Models\MembershipPayment;
use App\Models\ShopOrder;
use App\Models\User;

class InvoiceAuthorizer
{
    public function canView(
        ?User $user,
        InvoiceType $type,
        MembershipPayment|EventRegistration|ShopOrder $source,
    ): bool {
        if (! $user) {
            return false;
        }

        return match ($type) {
            InvoiceType::Membership => $this->canViewMembership($user, $source instanceof MembershipPayment ? $source : null),
            InvoiceType::Match => $this->canViewMatch($user, $source instanceof EventRegistration ? $source : null),
            InvoiceType::Shop => $this->canViewShop($user, $source instanceof ShopOrder ? $source : null),
        };
    }

    private function canViewMembership(User $user, ?MembershipPayment $payment): bool
    {
        if (! $payment) {
            return false;
        }

        if ($user->can('payments.view')) {
            return true;
        }

        $payer = $payment->payerMember();
        $actor = $user->member;

        return $payer !== null && $actor !== null && $actor->canActFor($payer);
    }

    private function canViewMatch(User $user, ?EventRegistration $registration): bool
    {
        if (! $registration) {
            return false;
        }

        if ($user->can('payments.view')) {
            return true;
        }

        $member = $registration->member;
        $actor = $user->member;

        return $member !== null && $actor !== null && $actor->canActFor($member);
    }

    private function canViewShop(User $user, ?ShopOrder $order): bool
    {
        if (! $order) {
            return false;
        }

        if ($user->can('shop.orders.view') || $user->can('shop.orders.manage')) {
            return true;
        }

        return $order->user_id !== null && (int) $order->user_id === (int) $user->id;
    }
}

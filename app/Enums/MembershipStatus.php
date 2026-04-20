<?php

namespace App\Enums;

enum MembershipStatus: string
{
    case PendingPayment = 'pending_payment';
    case PendingApproval = 'pending_approval';
    case Active = 'active';
    case Expired = 'expired';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::PendingPayment => 'Pending payment',
            self::PendingApproval => 'Pending approval',
            self::Active => 'Active',
            self::Expired => 'Expired',
            self::Cancelled => 'Cancelled',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PendingPayment => 'warning',
            self::PendingApproval => 'info',
            self::Active => 'success',
            self::Expired => 'gray',
            self::Cancelled => 'danger',
        };
    }

    public function isCurrent(): bool
    {
        return $this === self::Active;
    }
}

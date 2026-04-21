<?php

namespace App\Enums;

enum ShopOrderStatus: string
{
    case Draft = 'draft';
    case PendingPayment = 'pending_payment';
    case Paid = 'paid';
    case Cancelled = 'cancelled';
    case Fulfilled = 'fulfilled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::PendingPayment => 'Pending payment',
            self::Paid => 'Paid',
            self::Cancelled => 'Cancelled',
            self::Fulfilled => 'Fulfilled',
        };
    }
}

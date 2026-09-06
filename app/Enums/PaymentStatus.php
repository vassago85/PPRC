<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Pending = 'pending';
    case Submitted = 'submitted';
    case Confirmed = 'confirmed';
    case Failed = 'failed';
    case Refunded = 'refunded';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        // "No proof uploaded" is the accurate name for the pending bucket —
        // "Pending" was ambiguous with an admin's own to-do queue and made
        // the treasurer chase the wrong records.
        return match ($this) {
            self::Pending => 'No proof uploaded',
            self::Submitted => 'Awaiting review',
            self::Confirmed => 'Confirmed',
            self::Failed => 'Failed',
            self::Refunded => 'Refunded',
            self::Cancelled => 'Cancelled',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'gray',
            self::Submitted => 'warning',
            self::Confirmed => 'success',
            self::Failed => 'danger',
            self::Refunded => 'info',
            self::Cancelled => 'danger',
        };
    }
}

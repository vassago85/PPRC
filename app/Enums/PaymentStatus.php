<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Pending = 'pending';
    case Submitted = 'submitted';
    case Confirmed = 'confirmed';
    case Failed = 'failed';
    case Refunded = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Submitted => 'Awaiting verification',
            self::Confirmed => 'Confirmed',
            self::Failed => 'Failed',
            self::Refunded => 'Refunded',
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
        };
    }
}

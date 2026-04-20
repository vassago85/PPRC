<?php

namespace App\Enums;

enum MemberStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Suspended = 'suspended';
    case Expired = 'expired';
    case Resigned = 'resigned';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending approval',
            self::Active => 'Active',
            self::Suspended => 'Suspended',
            self::Expired => 'Expired',
            self::Resigned => 'Resigned',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Active => 'success',
            self::Suspended => 'danger',
            self::Expired => 'gray',
            self::Resigned => 'gray',
        };
    }
}

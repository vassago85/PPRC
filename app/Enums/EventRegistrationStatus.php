<?php

namespace App\Enums;

enum EventRegistrationStatus: string
{
    case Registered = 'registered';
    case Waitlisted = 'waitlisted';
    case Confirmed = 'confirmed';
    case Cancelled = 'cancelled';
    case NoShow = 'no_show';

    public function label(): string
    {
        return match ($this) {
            self::Registered => 'Registered',
            self::Waitlisted => 'Waitlisted',
            self::Confirmed => 'Confirmed',
            self::Cancelled => 'Cancelled',
            self::NoShow => 'No-show',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Registered => 'info',
            self::Waitlisted => 'warning',
            self::Confirmed => 'success',
            self::Cancelled => 'gray',
            self::NoShow => 'danger',
        };
    }
}

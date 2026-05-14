<?php

namespace App\Enums;

enum RenewalSource: string
{
    case Reminder = 'reminder';
    case MemberInitiated = 'self';
    case Admin = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::Reminder => 'Via reminder',
            self::MemberInitiated => 'Self-initiated',
            self::Admin => 'Admin',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Reminder => 'info',
            self::MemberInitiated => 'gray',
            self::Admin => 'purple',
        };
    }
}

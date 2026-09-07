<?php

namespace App\Enums;

enum InvoiceType: string
{
    case Membership = 'membership';
    case Match = 'match';
    case Shop = 'shop';

    public function label(): string
    {
        return match ($this) {
            self::Membership => 'Membership',
            self::Match => 'Match entry',
            self::Shop => 'Shop order',
        };
    }
}

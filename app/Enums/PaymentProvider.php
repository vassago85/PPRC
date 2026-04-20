<?php

namespace App\Enums;

enum PaymentProvider: string
{
    case Paystack = 'paystack';
    case ManualEft = 'manual_eft';

    public function label(): string
    {
        return match ($this) {
            self::Paystack => 'Paystack',
            self::ManualEft => 'Manual EFT',
        };
    }

    public function requiresProof(): bool
    {
        return $this === self::ManualEft;
    }
}

<?php

namespace App\Enums;

enum MatchPaymentMethod: string
{
    case Eft = 'eft';
    case Cash = 'cash';

    public function label(): string
    {
        return match ($this) {
            self::Eft => 'EFT',
            self::Cash => 'Cash',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $c) => [$c->value => $c->label()])
            ->all();
    }
}

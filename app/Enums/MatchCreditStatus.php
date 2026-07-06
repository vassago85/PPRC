<?php

namespace App\Enums;

enum MatchCreditStatus: string
{
    case Available = 'available';
    case Used = 'used';

    public function label(): string
    {
        return match ($this) {
            self::Available => 'Available',
            self::Used => 'Used',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Available => 'success',
            self::Used => 'gray',
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

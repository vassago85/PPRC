<?php

namespace App\Enums;

use App\Models\MatchExpense;

/**
 * Buckets a {@see MatchExpense} can fall into. The categories were
 * chosen to match how the match director actually thinks about costs when
 * reading the cash-up slip — "I bought the trophies", "we owe the range fee",
 * "prize money went out". `Other` is the catch-all when none of the specific
 * labels fit, which is what the legacy expenses migrate to.
 */
enum MatchExpenseCategory: string
{
    case Trophies = 'trophies';
    case Medals = 'medals';
    case PrizeMoney = 'prize_money';
    case RangeFees = 'range_fees';
    case Supplies = 'supplies';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Trophies => 'Trophies',
            self::Medals => 'Medals',
            self::PrizeMoney => 'Prize money',
            self::RangeFees => 'Range fees',
            self::Supplies => 'Supplies',
            self::Other => 'Other',
        };
    }

    /**
     * Tailwind-style colour hint for the badge rendered on the cash-up slip.
     * Keep these aligned with the palette already used elsewhere on the page.
     */
    public function color(): string
    {
        return match ($this) {
            self::Trophies => 'amber',
            self::Medals => 'yellow',
            self::PrizeMoney => 'emerald',
            self::RangeFees => 'sky',
            self::Supplies => 'violet',
            self::Other => 'gray',
        };
    }

    /** Convenience for a <select> that includes a blank "— Choose —" row. */
    public static function options(): array
    {
        $out = [];

        foreach (self::cases() as $case) {
            $out[$case->value] = $case->label();
        }

        return $out;
    }
}

<?php

namespace App\Enums;

/**
 * Ported from the legacy SSMM PPRC plugin: age rules applied to a membership type.
 *
 * - Under:   member must be younger than `value` at join date.
 * - AtLeast: member must be at least `value` years old at join date.
 * - Between: member age must be within `[min_age, max_age]` on join date.
 */
enum AgeRequirementType: string
{
    case Under = 'under';
    case AtLeast = 'at_least';
    case Between = 'between';

    public function label(): string
    {
        return match ($this) {
            self::Under => 'Under X years',
            self::AtLeast => 'At least X years',
            self::Between => 'Between min and max',
        };
    }
}

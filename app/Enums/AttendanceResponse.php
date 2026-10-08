<?php

namespace App\Enums;

/**
 * How a shooter answered the "are you still shooting?" email the admin sends
 * out before a match. `Withdrawn` also cancels the entry; the other two only
 * record intent so the match director can plan squads.
 */
enum AttendanceResponse: string
{
    case StillShooting = 'still_shooting';
    case Unsure = 'unsure';
    case Withdrawn = 'withdrawn';

    public function label(): string
    {
        return match ($this) {
            self::StillShooting => 'Still shooting',
            self::Unsure => 'Unsure',
            self::Withdrawn => 'Withdrawn',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::StillShooting => 'success',
            self::Unsure => 'warning',
            self::Withdrawn => 'danger',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::StillShooting => 'heroicon-o-hand-thumb-up',
            self::Unsure => 'heroicon-o-question-mark-circle',
            self::Withdrawn => 'heroicon-o-x-circle',
        };
    }
}

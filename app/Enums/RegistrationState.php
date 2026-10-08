<?php

namespace App\Enums;

use App\Models\Event;

/**
 * Single source of truth for whether a match is accepting entries right now.
 *
 * Historically {@see Event::isRegistrationOpen()} returned a bare
 * boolean, which collapsed four very different "no" reasons (switch off, past
 * the close date, over capacity, not yet opened) into one undifferentiated
 * state. The public site has to tell them apart to show the right copy — a
 * closed match must never read like "Not yet open" and vice versa.
 *
 * `Finished` is a terminal state for completed or cancelled matches; the UI
 * usually hides registration altogether in that case.
 */
enum RegistrationState: string
{
    case Open = 'open';
    case Closed = 'closed';
    case Full = 'full';
    case NotYetOpen = 'not_yet_open';
    case Finished = 'finished';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Registration open',
            self::Closed => 'Registration closed',
            self::Full => 'Match full',
            self::NotYetOpen => 'Not yet open',
            self::Finished => 'Match finished',
        };
    }

    /** Short badge-friendly form for crowded card headers. */
    public function shortLabel(): string
    {
        return match ($this) {
            self::Open => 'Open',
            self::Closed => 'Closed',
            self::Full => 'Full',
            self::NotYetOpen => 'Opens soon',
            self::Finished => 'Finished',
        };
    }

    /**
     * Tailwind colour family hint — the actual class strings live in the
     * badge components so dark/light palettes stay consistent across the site.
     */
    public function color(): string
    {
        return match ($this) {
            self::Open => 'emerald',
            self::Closed => 'slate',
            self::Full => 'amber',
            self::NotYetOpen => 'sky',
            self::Finished => 'slate',
        };
    }

    public function isAcceptingEntries(): bool
    {
        return $this === self::Open;
    }
}

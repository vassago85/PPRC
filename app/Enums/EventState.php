<?php

namespace App\Enums;

use App\Models\Event;

/**
 * Display-only state of a match, derived from Event fields.
 *
 * The stored EventStatus enum (Draft / Published / Completed / Cancelled) is
 * a business-rule value the rest of the app is built around and must not
 * change. This enum is the vocabulary the admin *reads*:
 *
 *   Draft            — never published
 *   EntriesOpen      — Published, registrations still accepted
 *   EntriesClosed    — Published, upcoming, but no more entries
 *   Shot             — the match has been run, results not out yet
 *   ResultsPublished — results are live
 *   Cancelled        — called off
 *
 * Deriving it in one place stops each screen inventing its own labels for
 * the same combinations of Event fields (which is how "Completed" ended up
 * next to a button called "Complete").
 */
enum EventState: string
{
    case Draft = 'draft';
    case EntriesOpen = 'entries_open';
    case EntriesClosed = 'entries_closed';
    case Shot = 'shot';
    case ResultsPublished = 'results_published';
    case Cancelled = 'cancelled';

    public static function for(Event $event): self
    {
        $status = $event->status instanceof EventStatus
            ? $event->status
            : EventStatus::tryFrom((string) $event->status);

        if ($status === EventStatus::Cancelled) {
            return self::Cancelled;
        }

        if ($event->results_published_at !== null) {
            return self::ResultsPublished;
        }

        if ($status === EventStatus::Draft) {
            return self::Draft;
        }

        // Anything that has been run (past the start date, or explicitly
        // marked Completed) with no results yet is "Shot".
        if ($status === EventStatus::Completed
            || ($event->start_date !== null && $event->start_date->isPast())) {
            return self::Shot;
        }

        return $event->isRegistrationOpen() ? self::EntriesOpen : self::EntriesClosed;
    }

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::EntriesOpen => 'Entries open',
            self::EntriesClosed => 'Entries closed',
            self::Shot => 'Shot',
            self::ResultsPublished => 'Results published',
            self::Cancelled => 'Cancelled',
        };
    }

    /** Pill variant for `x-ui.pill`. */
    public function variant(): string
    {
        return match ($this) {
            self::Draft => 'muted',
            self::EntriesOpen => 'ok',
            self::EntriesClosed => 'info',
            self::Shot => 'warn',
            self::ResultsPublished => 'ok',
            self::Cancelled => 'muted',
        };
    }
}

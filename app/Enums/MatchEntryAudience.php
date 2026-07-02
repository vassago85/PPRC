<?php

namespace App\Enums;

use App\Models\Event;
use App\Models\EventRegistration;
use Illuminate\Support\Collection;

/**
 * A shared audience filter for a match's entries, used by both the entrant
 * export and the "email entrants" action so the two always agree on who is
 * "paid", "awaiting", etc. Cancelled entries are excluded from every bucket.
 */
enum MatchEntryAudience: string
{
    case All = 'all';
    case Confirmed = 'confirmed';
    case Awaiting = 'awaiting';
    case Unpaid = 'unpaid';
    case Guests = 'guests';

    public function label(): string
    {
        return match ($this) {
            self::All => 'All entries',
            self::Confirmed => 'Confirmed / paid',
            self::Awaiting => 'Awaiting payment',
            self::Unpaid => 'Not yet marked paid',
            self::Guests => 'Guests (no account)',
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

    public function includes(EventRegistration $registration): bool
    {
        return match ($this) {
            self::All => true,
            self::Confirmed => $registration->paymentConfirmed(),
            self::Awaiting => $registration->awaitingPayment(),
            self::Unpaid => $registration->paid_at === null,
            self::Guests => $registration->member_id === null,
        };
    }

    /**
     * Non-cancelled entries for the event that match this audience.
     *
     * @return Collection<int, EventRegistration>
     */
    public function filter(Event $event): Collection
    {
        return $event->registrations()
            ->with(['member.user.roles'])
            ->get()
            ->reject(fn (EventRegistration $r) => $r->status === EventRegistrationStatus::Cancelled)
            ->filter(fn (EventRegistration $r) => $this->includes($r))
            ->values();
    }
}

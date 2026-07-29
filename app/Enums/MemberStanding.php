<?php

namespace App\Enums;

/**
 * The status a human reads, derived entirely from MemberLifecycle plus the
 * facts already on the record. Nothing here is stored.
 *
 * The point of deriving it is that it can be richer than the stored lifecycle
 * without being another thing that drifts. "Pending" alone never told an admin
 * what they were waiting for; these three do:
 *
 *   AwaitingEmail   — registered, never confirmed their email address
 *   AwaitingChoice  — confirmed, never picked a membership type
 *   AwaitingPayment — picked a type, the money or the approval is outstanding
 *
 * Resolved by Member::standing().
 */
enum MemberStanding: string
{
    case AwaitingEmail = 'awaiting_email';

    case AwaitingChoice = 'awaiting_choice';

    case AwaitingPayment = 'awaiting_payment';

    case Abandoned = 'abandoned';

    case Active = 'active';

    case Suspended = 'suspended';

    case Expired = 'expired';

    case LongLapsed = 'long_lapsed';

    case Resigned = 'resigned';

    public function label(): string
    {
        return match ($this) {
            self::AwaitingEmail => 'Awaiting email confirmation',
            self::AwaitingChoice => 'Awaiting membership choice',
            self::AwaitingPayment => 'Awaiting payment',
            self::Abandoned => 'Abandoned signup',
            self::Active => 'Active',
            self::Suspended => 'Suspended',
            self::Expired => 'Expired',
            self::LongLapsed => 'Long lapsed',
            self::Resigned => 'Resigned',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::AwaitingEmail => 'info',
            self::AwaitingChoice => 'warning',
            self::AwaitingPayment => 'warning',
            self::Abandoned => 'gray',
            self::Active => 'success',
            self::Suspended => 'danger',
            self::Expired => 'danger',
            self::LongLapsed => 'gray',
            self::Resigned => 'gray',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::AwaitingEmail => 'heroicon-o-envelope',
            self::AwaitingChoice => 'heroicon-o-question-mark-circle',
            self::AwaitingPayment => 'heroicon-o-banknotes',
            self::Abandoned => 'heroicon-o-archive-box-x-mark',
            self::Active => 'heroicon-o-check-badge',
            self::Suspended => 'heroicon-o-no-symbol',
            self::Expired => 'heroicon-o-exclamation-triangle',
            self::LongLapsed => 'heroicon-o-moon',
            self::Resigned => 'heroicon-o-archive-box',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::AwaitingEmail => 'Registered but has not clicked the link in their verification email.',
            self::AwaitingChoice => 'Email confirmed, but they have not picked a membership type yet.',
            self::AwaitingPayment => 'Application started; the payment or the committee approval is outstanding.',
            self::Abandoned => 'Never finished signing up and stopped responding. Returns to the queue if they come back.',
            self::Active => 'Paid up and entitled to member rates.',
            self::Suspended => 'Suspended by the committee. The underlying membership is untouched.',
            self::Expired => 'The paid period has ended and the renewal is still worth chasing.',
            self::LongLapsed => 'Expired long enough ago that they are no longer counted as a lapsed renewal.',
            self::Resigned => 'Left the club.',
        };
    }

    /**
     * Belongs in the onboarding inbox. An abandoned signup deliberately does
     * not — that is the whole reason the flag exists.
     */
    public function needsAction(): bool
    {
        return in_array($this, [self::AwaitingEmail, self::AwaitingChoice, self::AwaitingPayment], true);
    }
}

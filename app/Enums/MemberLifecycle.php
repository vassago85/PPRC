<?php

namespace App\Enums;

/**
 * Where a member sits in the club's lifecycle. This is the single source of
 * truth, and it is deliberately small.
 *
 *   Pending ──► Active ──► Expired ──► (Active again on renewal)
 *      │           │           │
 *      └───────────┴───────────┴──► Resigned  (terminal)
 *
 * Everything the old eight-value enum expressed is still visible, but the
 * things that were never really lifecycle positions have moved to where they
 * belong:
 *
 *   - Suspended  → `members.suspended_at`. A suspension is something laid on
 *                  top of a member; it does not replace where they are in the
 *                  lifecycle. Lifting it reveals the correct state again
 *                  instead of leaving an admin to guess what to set.
 *   - Abandoned  → `members.abandoned_at`. A terminal sub-state of Pending, so
 *                  a stale signup drops out of the action inbox without
 *                  pretending to be a different kind of member.
 *   - Unverified → derived from the user's `email_verified_at`, which was
 *                  always the real answer.
 *   - Inactive   → derived from how long ago `expiry_date` passed.
 *
 * See MemberStanding for the richer status a human actually reads.
 */
enum MemberLifecycle: string
{
    case Pending = 'pending';

    case Active = 'active';

    case Expired = 'expired';

    case Resigned = 'resigned';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Active => 'Active',
            self::Expired => 'Expired',
            self::Resigned => 'Resigned',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Active => 'success',
            self::Expired => 'danger',
            self::Resigned => 'gray',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Pending => 'heroicon-o-clock',
            self::Active => 'heroicon-o-check-badge',
            self::Expired => 'heroicon-o-exclamation-triangle',
            self::Resigned => 'heroicon-o-archive-box',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Pending => 'Signed up, but not yet a paid-up member.',
            self::Active => 'Paid up and entitled to member rates.',
            self::Expired => 'Was a member; the paid period has ended.',
            self::Resigned => 'Left the club.',
        };
    }

    /** Sits in someone's inbox until it is dealt with. */
    public function needsAction(): bool
    {
        return $this === self::Pending;
    }

    /**
     * Translate one of the old eight status strings into the columns that
     * replace it. Used by the CSV importers, which still receive legacy
     * vocabulary from the WordPress exports.
     *
     * @return array{lifecycle: string, suspended_at: ?string, abandoned_at: ?string}
     */
    public static function mapLegacy(?string $legacy, ?string $expiryDate = null): array
    {
        $legacy = strtolower(trim((string) $legacy));

        $lapsed = $expiryDate !== null && $expiryDate !== '' && strtotime($expiryDate) < strtotime('today');

        $lifecycle = match ($legacy) {
            'active' => self::Active,
            'expired', 'inactive' => self::Expired,
            'resigned' => self::Resigned,
            // A suspension says nothing about where they are in the lifecycle,
            // so recover that from the expiry date instead of losing it.
            'suspended' => $lapsed ? self::Expired : self::Active,
            default => self::Pending,
        };

        return [
            'lifecycle' => $lifecycle->value,
            'suspended_at' => $legacy === 'suspended' ? now()->toDateTimeString() : null,
            'abandoned_at' => $legacy === 'abandoned' ? now()->toDateTimeString() : null,
        ];
    }

    /** @return array<string, string> value => label, for form selects */
    public static function options(): array
    {
        return array_reduce(
            self::cases(),
            fn (array $carry, self $case) => $carry + [$case->value => $case->label()],
            [],
        );
    }
}

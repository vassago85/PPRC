<?php

namespace App\Enums;

/**
 * Lifecycle of a member profile (separate from User email verification):
 *
 *   Unverified → Pending → Active ─→ Expired ─→ Inactive
 *        │         ↑ │                  │            │
 *        │         └─┘                  │            │
 *        └──────────┴──► Abandoned  (stale signup that was never completed;
 *                        re-engaging moves them back to Pending)
 *
 *   Suspended  (manual, never auto-touched)
 *   Resigned   (terminal)
 *
 * WP SSMM parity: unverified = email not yet confirmed on the member record
 * (distinct from User.email_verified_at which gates Fortify login).
 */
enum MemberStatus: string
{
    case Unverified = 'unverified';
    case Pending = 'pending';
    case Active = 'active';
    case Suspended = 'suspended';
    case Expired = 'expired';
    case Inactive = 'inactive';
    case Resigned = 'resigned';
    case Abandoned = 'abandoned';

    public function label(): string
    {
        return match ($this) {
            self::Unverified => 'Unverified',
            self::Pending => 'Pending approval',
            self::Active => 'Active',
            self::Suspended => 'Suspended',
            self::Expired => 'Expired',
            self::Inactive => 'Inactive',
            self::Resigned => 'Resigned',
            self::Abandoned => 'Abandoned signup',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Unverified => 'info',
            self::Pending => 'warning',
            self::Active => 'success',
            self::Suspended => 'danger',
            self::Expired => 'gray',
            self::Inactive => 'gray',
            self::Resigned => 'gray',
            self::Abandoned => 'gray',
        };
    }
}

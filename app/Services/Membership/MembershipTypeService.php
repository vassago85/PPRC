<?php

namespace App\Services\Membership;

use App\Models\MembershipType;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class MembershipTypeService
{
    /**
     * Types shown on public registration / portal renewal.
     * Mirrors the WP SSMM getForRegistration(): active, show_on_registration,
     * excludes sub-memberships (juniors cannot self-register).
     *
     * @return Collection<int, MembershipType>
     */
    public function activeForRegistration(): Collection
    {
        return MembershipType::query()
            ->where('is_active', true)
            ->where('show_on_registration', true)
            ->where('is_sub_membership', false)
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * Compute the period end date for a membership type starting on $start.
     *
     * WP SSMM rule: duration_months == 0 means no expiry (e.g. honorary life member).
     * Otherwise end = start + N months - 1 day (so a 12-month membership
     * starting 1 Jan ends 31 Dec, not 1 Jan next year).
     */
    public function calculateExpiryDate(MembershipType $type, Carbon $start): ?Carbon
    {
        if ($type->duration_months <= 0) {
            return null;
        }

        return $start->copy()->addMonths($type->duration_months)->subDay();
    }
}

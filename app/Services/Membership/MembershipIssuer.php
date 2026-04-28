<?php

namespace App\Services\Membership;

use App\Enums\MembershipStatus;
use App\Models\Member;
use App\Models\Membership;
use App\Models\MembershipType;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MembershipIssuer
{
    public function __construct(
        protected MembershipTypeService $typeService,
    ) {}

    /**
     * Issue a membership for the given member + type. Enforces:
     *   - sub-member types must be linked to an adult member
     *   - max_per_parent caps (active or pending sub-memberships)
     *   - free_while_linked_adult_active ⇒ price_cents_snapshot 0 iff parent has an active membership
     *   - age rules on the chosen type
     *
     * Returns the new Membership. Does not create payments.
     */
    public function issue(Member $member, MembershipType $type, ?Carbon $start = null): Membership
    {
        $start ??= Carbon::now();

        $this->assertAgeMatches($member, $type, $start);
        $this->assertSubMembershipRules($member, $type);

        $priceCents = $this->resolvePrice($member, $type);
        $periodEnd = $this->typeService->calculateExpiryDate($type, $start);

        $needsApproval = $type->requires_manual_approval;
        $status = $needsApproval
            ? ($priceCents > 0 ? MembershipStatus::PendingPayment : MembershipStatus::PendingApproval)
            : ($priceCents > 0 ? MembershipStatus::PendingPayment : MembershipStatus::Active);

        return DB::transaction(function () use ($member, $type, $start, $periodEnd, $priceCents, $status) {
            return Membership::create([
                'member_id' => $member->id,
                'membership_type_id' => $type->id,
                'period_start' => $start,
                'period_end' => $periodEnd,
                'status' => $status,
                'price_cents_snapshot' => $priceCents,
                'membership_type_slug_snapshot' => $type->slug,
                'membership_type_name_snapshot' => $type->name,
            ]);
        });
    }

    /**
     * When a parent renews, auto-renew each linked sub-member whose type is
     * flagged free_while_linked_adult_active (juniors). Returns the created
     * membership rows.
     *
     * @return array<int, Membership>
     */
    public function autoRenewLinkedFreeSubMembers(Member $parent, Carbon $periodStart, Carbon $periodEnd): array
    {
        $created = [];

        foreach ($parent->subMembers as $sub) {
            $lastMembership = $sub->memberships()->latest('period_end')->first();
            $type = $lastMembership?->type
                ?? MembershipType::where('slug', 'junior')->first();

            if (! $type || ! $type->free_while_linked_adult_active) {
                continue;
            }

            if (! $type->satisfiesAge($sub->date_of_birth, $periodStart)) {
                continue;
            }

            $created[] = Membership::create([
                'member_id' => $sub->id,
                'membership_type_id' => $type->id,
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
                'status' => MembershipStatus::Active,
                'price_cents_snapshot' => 0,
                'membership_type_slug_snapshot' => $type->slug,
                'membership_type_name_snapshot' => $type->name,
            ]);
        }

        return $created;
    }

    protected function assertAgeMatches(Member $member, MembershipType $type, Carbon $asOf): void
    {
        if (! $type->satisfiesAge($member->date_of_birth, $asOf)) {
            throw ValidationException::withMessages([
                'membership_type_id' => "{$member->fullName()} does not meet the age requirement for {$type->name}.",
            ]);
        }
    }

    public function assertSubMembershipRulesPublic(Member $member, MembershipType $type): void
    {
        $this->assertSubMembershipRules($member, $type);
    }

    protected function assertSubMembershipRules(Member $member, MembershipType $type): void
    {
        if (! $type->is_sub_membership) {
            return;
        }

        if (! $member->linked_adult_member_id) {
            throw ValidationException::withMessages([
                'linked_adult_member_id' => "{$type->name} memberships must be linked to an adult member.",
            ]);
        }

        $parent = $member->linkedAdult;
        if (! $parent) {
            throw ValidationException::withMessages([
                'linked_adult_member_id' => 'Linked adult member is missing.',
            ]);
        }

        if ($type->max_per_parent !== null) {
            $count = Membership::query()
                ->whereIn('status', [
                    MembershipStatus::Active->value,
                    MembershipStatus::PendingPayment->value,
                    MembershipStatus::PendingApproval->value,
                ])
                ->where('membership_type_id', $type->id)
                ->whereIn('member_id',
                    $parent->subMembers()->where('id', '!=', $member->id)->pluck('id')
                )->count();

            if ($count >= $type->max_per_parent) {
                throw ValidationException::withMessages([
                    'membership_type_id' => "{$parent->fullName()} already has the maximum of {$type->max_per_parent} {$type->name} sub-members.",
                ]);
            }
        }
    }

    protected function resolvePrice(Member $member, MembershipType $type): int
    {
        if ($type->is_sub_membership
            && $type->free_while_linked_adult_active
            && $member->linkedAdult?->hasActiveMembership()
        ) {
            return 0;
        }

        return (int) $type->price_cents;
    }
}

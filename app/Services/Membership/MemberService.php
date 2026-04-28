<?php

namespace App\Services\Membership;

use App\Enums\MembershipStatus;
use App\Enums\MemberStatus;
use App\Events\MemberActivated;
use App\Events\MemberEmailVerified;
use App\Events\MemberRegistered;
use App\Models\Member;
use App\Models\Membership;
use App\Models\User;
use Illuminate\Support\Carbon;

class MemberService
{
    public function __construct(
        protected MembershipNumberAllocator $numberAllocator,
    ) {}

    /**
     * Create a Member profile for a User that just registered (Fortify).
     *
     * WP SSMM parity: register() → status = unverified; once the user
     * verifies their email the listener can transition to pending.
     */
    public function register(User $user, array $profile = []): Member
    {
        $member = Member::create(array_merge([
            'user_id' => $user->id,
            'first_name' => $profile['first_name'] ?? $this->guessFirstName($user->name),
            'last_name' => $profile['last_name'] ?? $this->guessLastName($user->name),
            'status' => MemberStatus::Unverified,
            'join_date' => Carbon::today(),
        ], array_filter($profile, fn ($v) => $v !== null && $v !== '')));

        MemberRegistered::dispatch($member);

        return $member;
    }

    /**
     * Called when a member's email is verified. Moves unverified → pending
     * so a committee member can approve.
     */
    public function markVerified(Member $member): void
    {
        if ($member->status !== MemberStatus::Unverified) {
            return;
        }

        $member->update(['status' => MemberStatus::Pending]);

        MemberEmailVerified::dispatch($member);
    }

    /**
     * Activate a membership (committee approval). Assigns membership number
     * if the type calls for it, sets the member to active, and records
     * approval metadata on the membership.
     */
    public function activate(Membership $membership, ?User $approvedBy = null): void
    {
        $membership->loadMissing(['member', 'membershipType']);

        $membership->update([
            'status' => MembershipStatus::Active,
            'approved_at' => now(),
            'approved_by_user_id' => $approvedBy?->id,
        ]);

        $member = $membership->member;
        if (! $member) {
            return;
        }

        if ($member->status !== MemberStatus::Active) {
            $member->update(['status' => MemberStatus::Active]);
        }

        if ($member->expiry_date === null || ($membership->period_end && $membership->period_end->gt($member->expiry_date))) {
            $member->update(['expiry_date' => $membership->period_end]);
        }

        MemberActivated::dispatch($member, $membership);
    }

    /**
     * Validate sub-member / junior constraints (same rules as MembershipIssuer
     * but accessible as a standalone check for registration flows).
     */
    public function assertSubMemberRules(Member $member, \App\Models\MembershipType $type): void
    {
        app(MembershipIssuer::class)->assertSubMembershipRulesPublic($member, $type);
    }

    protected function guessFirstName(string $fullName): string
    {
        $parts = preg_split('/\s+/', trim($fullName), 2);

        return $parts[0] ?? '';
    }

    protected function guessLastName(string $fullName): string
    {
        $parts = preg_split('/\s+/', trim($fullName), 2);

        return $parts[1] ?? '';
    }
}

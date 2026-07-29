<?php

namespace App\Services\Membership;

use App\Enums\MembershipStatus;
use App\Enums\MemberStatus;
use App\Enums\PaymentStatus;
use App\Events\MemberActivated;
use App\Events\MemberEmailVerified;
use App\Events\MemberRegistered;
use App\Mail\MembershipApprovedMail;
use App\Models\Member;
use App\Models\Membership;
use App\Models\MembershipType;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;

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
     * so a committee member can approve. Also revives an Abandoned signup
     * (someone who ignored the finish-your-signup nudge and later came back
     * to verify) back into the pending queue.
     */
    public function markVerified(Member $member): void
    {
        if (! in_array($member->status, [MemberStatus::Unverified, MemberStatus::Abandoned], true)) {
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
        $membership->loadMissing(['member', 'membershipType', 'payments']);

        if ($membership->status === MembershipStatus::Active) {
            return;
        }

        $membership->update([
            'status' => MembershipStatus::Active,
            'approved_at' => now(),
            'approved_by_user_id' => $approvedBy?->id,
        ]);

        $membership->payments()
            ->whereIn('status', [PaymentStatus::Pending->value, PaymentStatus::Submitted->value])
            ->update([
                'status' => PaymentStatus::Confirmed->value,
                'confirmed_at' => now(),
                'confirmed_by_user_id' => $approvedBy?->id,
            ]);

        $member = $membership->member;
        if (! $member) {
            return;
        }

        $this->syncMemberToActiveMembership($membership);

        $this->cancelSupersededMemberships($member, $membership);

        MemberActivated::dispatch($member, $membership);

        $member->loadMissing('user');
        if ($member->user?->email) {
            Mail::to($member->user)->queue(new MembershipApprovedMail($member, $membership));
        }
    }

    /**
     * Bring the parent Member's own status and expiry date in line with an
     * active membership.
     *
     * Runs from the Membership model whenever an active row is saved, so an
     * admin who flips the status straight to Active on the edit form gets the
     * same result as clicking Approve. Without it the member keeps showing as
     * expired, and members:check-expiry reverts them again overnight because
     * their expiry_date was never moved forward.
     */
    public function syncMemberToActiveMembership(Membership $membership): void
    {
        $updates = $this->pendingMemberSync($membership);

        if ($updates === []) {
            return;
        }

        $membership->member->update($updates);
    }

    /**
     * The changes syncMemberToActiveMembership() would apply, without applying
     * them, so members:check-expiry can report a dry run honestly.
     *
     * @return array<string, mixed>
     */
    public function pendingMemberSync(Membership $membership): array
    {
        if ($membership->status !== MembershipStatus::Active) {
            return [];
        }

        $member = $membership->member;

        if (! $member) {
            return [];
        }

        $updates = [];

        // Suspended and resigned are deliberate administrative states; only a
        // human lifts those, exactly as members:check-expiry treats them.
        if (! in_array($member->status, [
            MemberStatus::Active,
            MemberStatus::Suspended,
            MemberStatus::Resigned,
        ], true)) {
            $updates['status'] = MemberStatus::Active;
        }

        $periodEnd = $membership->period_end;

        // A null period_end means a membership that never expires, so any date
        // inherited from an older membership has to be cleared rather than left
        // behind for the nightly expiry job to trip over.
        $expiryMovesForward = $member->expiry_date === null
            || $periodEnd === null
            || $periodEnd->gt($member->expiry_date);

        if ($expiryMovesForward && $member->expiry_date?->toDateString() !== $periodEnd?->toDateString()) {
            $updates['expiry_date'] = $periodEnd;
        }

        return $updates;
    }

    /**
     * Cancel any other pending/pending-payment memberships for the same member
     * that are now superseded by the newly activated one.
     */
    protected function cancelSupersededMemberships(Member $member, Membership $activeMembership): void
    {
        $superseded = $member->memberships()
            ->where('id', '!=', $activeMembership->id)
            ->whereIn('status', [
                MembershipStatus::PendingPayment->value,
                MembershipStatus::PendingApproval->value,
            ])
            ->get();

        foreach ($superseded as $old) {
            $old->update(['status' => MembershipStatus::Cancelled]);

            $old->payments()
                ->where('status', PaymentStatus::Pending->value)
                ->update(['status' => PaymentStatus::Cancelled->value]);
        }
    }

    /**
     * Validate sub-member / junior constraints (same rules as MembershipIssuer
     * but accessible as a standalone check for registration flows).
     */
    public function assertSubMemberRules(Member $member, MembershipType $type): void
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

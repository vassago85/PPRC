<?php

namespace App\Services\Membership;

use App\Enums\MemberLifecycle;
use App\Enums\MembershipStatus;
use App\Models\Member;
use App\Models\MembershipType;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Registers a junior (or other sub-member) linked to an adult member, so the
 * child ends up with the same shape as everyone else: their own account, a real
 * Membership row, a type, a period and an expiry.
 *
 * Before this, a sub-member added from the parent's panel was written as a bare
 * "active" row with no user account (which the schema forbids) and no membership
 * at all, so their profile looked nothing like a normal member and the age-out
 * job had nothing to expire when they turned 18.
 */
class SubMemberRegistrar
{
    /** Synthesized placeholder addresses live under this domain so they're obvious and never clash with a real inbox. */
    protected const PLACEHOLDER_EMAIL_DOMAIN = 'members.pretoriaprc.co.za';

    public function __construct(
        protected MembershipIssuer $issuer,
    ) {}

    /**
     * @param  array{first_name:string,last_name:string,date_of_birth:mixed,known_as?:?string,email?:?string}  $data
     */
    public function registerJunior(Member $parent, array $data): Member
    {
        $type = MembershipType::where('slug', 'junior')->first();

        if (! $type) {
            throw ValidationException::withMessages([
                'membership_type' => 'The Junior membership type is missing. Seed the membership types first.',
            ]);
        }

        return DB::transaction(function () use ($parent, $data, $type) {
            $fullName = trim(($data['first_name'] ?? '').' '.($data['last_name'] ?? ''));

            $junior = new Member;
            $junior->forceFill([
                'user_id' => $this->resolveUser($data, $fullName)->id,
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'known_as' => $data['known_as'] ?? null,
                'date_of_birth' => $data['date_of_birth'],
                'linked_adult_member_id' => $parent->id,
                // Issuing the membership below drives the final lifecycle; a free
                // junior with an active parent lands on Active via the sync hook.
                'lifecycle' => MemberLifecycle::Pending,
            ])->save();

            [$start, $periodEnd] = $this->period($parent, $type);

            $membership = $this->issuer->issue($junior, $type, $start, null, $periodEnd);

            // The Junior type is flagged for manual approval, but an admin adding
            // a junior from the parent's record IS that approval — and juniors are
            // free, so there is nothing to wait for. Activate straight away; the
            // membership's saved hook then pulls the member's own lifecycle and
            // expiry into line. Guarded on a zero balance so this can never skip a
            // payment for a paid sub-membership type.
            if ($membership->status !== MembershipStatus::Active
                && (int) $membership->price_cents_snapshot === 0) {
                $membership->update(['status' => MembershipStatus::Active]);
            }

            return $junior->refresh();
        });
    }

    /**
     * A junior's term mirrors the parent's so they renew together — except a
     * lifetime parent, who never renews and so can't hand down an endless term.
     * There the junior runs on the Junior type's own annual clock, which the
     * nightly expiry check and renewal reminders then look after.
     *
     * @return array{0: Carbon, 1: ?Carbon} [start, periodEnd] — a null end means "use the type's own duration".
     */
    protected function period(Member $parent, MembershipType $type): array
    {
        $parentMembership = $parent->currentMembership();

        if ($parentMembership && $parentMembership->period_end !== null) {
            return [
                $parentMembership->period_start
                    ? Carbon::parse($parentMembership->period_start)
                    : Carbon::now(),
                Carbon::parse($parentMembership->period_end),
            ];
        }

        return [Carbon::now(), null];
    }

    /**
     * A junior usually has no inbox of their own. If a contact email is given we
     * attach or create a real account for it; otherwise we provision a managed
     * placeholder account they cannot log into, so the parent looks after them.
     */
    protected function resolveUser(array $data, string $fullName): User
    {
        $email = strtolower(trim((string) ($data['email'] ?? '')));

        if ($email !== '') {
            $existing = User::where('email', $email)->first();

            if ($existing) {
                if ($existing->member()->exists()) {
                    throw ValidationException::withMessages([
                        'email' => 'A member already exists for that email.',
                    ]);
                }

                return $existing;
            }

            return User::create([
                'name' => $fullName ?: $email,
                'email' => $email,
                'password' => Hash::make(Str::random(48)),
                'created_via_import' => false,
            ]);
        }

        // Managed placeholder: a unique, unreachable address and a random
        // password. created_via_import suppresses the verification email that
        // would otherwise bounce off a mailbox that does not exist.
        return User::create([
            'name' => $fullName !== '' ? $fullName : 'Junior member',
            'email' => 'junior-'.Str::uuid().'@'.self::PLACEHOLDER_EMAIL_DOMAIN,
            'password' => Hash::make(Str::random(48)),
            'created_via_import' => true,
        ]);
    }
}

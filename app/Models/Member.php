<?php

namespace App\Models;

use App\Enums\MemberLifecycle;
use App\Enums\MembershipStatus;
use App\Enums\MemberStanding;
use App\Enums\MemberStatus;
use App\Support\NameCase;
use Database\Factories\MemberFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Member extends Model
{
    /** @use HasFactory<MemberFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'known_as',
        'membership_number',
        'phone_country_code',
        'phone_number',
        'address_line1',
        'address_line2',
        'city',
        'province',
        'postal_code',
        'country',
        'date_of_birth',
        'id_number',
        'shooting_disciplines',
        'profile_photo_path',
        'status',
        'lifecycle',
        'suspended_at',
        'abandoned_at',
        'join_date',
        'expiry_date',
        'last_renewal_reminder_at',
        'signup_reminder_sent_at',
        'resigned_at',
        'resignation_reason',
        'linked_adult_member_id',
        'saprf_membership_number',
        'saprf_verified_at',
        'saprf_notes',
        'notes',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'join_date' => 'date',
        'expiry_date' => 'date',
        'last_renewal_reminder_at' => 'datetime',
        'signup_reminder_sent_at' => 'datetime',
        'resigned_at' => 'datetime',
        'saprf_verified_at' => 'datetime',
        'suspended_at' => 'datetime',
        'abandoned_at' => 'datetime',
        'shooting_disciplines' => 'array',
        'status' => MemberStatus::class,
        'lifecycle' => MemberLifecycle::class,
    ];

    /**
     * Keep the legacy eight-value `status` column in step with the canonical
     * lifecycle.
     *
     * `lifecycle` is the only thing written by application code now. This
     * mirror exists so the old column stays truthful while the change is being
     * verified — anything still reading `status`, including a rollback, gets the
     * right answer without a second source of truth to keep in sync by hand.
     * It goes away with the column.
     */
    protected static function booted(): void
    {
        static::saving(function (self $member) {
            $member->status = $member->legacyStatus();
        });
    }

    /**
     * Title-case mutators. Lower/upper-only input is normalised on save;
     * intentionally mixed-case names ("Van der Merwe") are preserved.
     */
    protected function firstName(): Attribute
    {
        return Attribute::set(fn ($value) => NameCase::normalize($value));
    }

    protected function lastName(): Attribute
    {
        return Attribute::set(fn ($value) => NameCase::normalize($value));
    }

    protected function knownAs(): Attribute
    {
        return Attribute::set(fn ($value) => NameCase::normalize($value));
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function linkedAdult(): BelongsTo
    {
        return $this->belongsTo(self::class, 'linked_adult_member_id');
    }

    public function subMembers(): HasMany
    {
        return $this->hasMany(self::class, 'linked_adult_member_id');
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(Membership::class);
    }

    public function clubBadges(): BelongsToMany
    {
        return $this->belongsToMany(ClubBadge::class, 'club_badge_member')
            ->withPivot(['awarded_at', 'notes'])
            ->withTimestamps();
    }

    public function currentMembership(): ?Membership
    {
        return $this->memberships()
            ->with('payments')
            ->whereIn('status', ['active', 'pending_payment', 'pending_approval'])
            ->where(function ($q) {
                // Exclude "active" memberships whose period has already ended (stale rows
                // not yet caught by the daily check-expiry command). Pending rows are
                // always shown regardless of period_end.
                $q->where('status', '!=', 'active')
                    ->orWhereNull('period_end')
                    ->orWhere('period_end', '>=', now()->toDateString());
            })
            ->orderByRaw("CASE status WHEN 'active' THEN 0 WHEN 'pending_approval' THEN 1 WHEN 'pending_payment' THEN 2 ELSE 3 END")
            ->orderByRaw('period_end IS NOT NULL, period_end DESC')
            ->first();
    }

    /**
     * Most recent payment row across all of this member's memberships.
     * Surfaces in the admin Members list so a treasurer can see at a
     * glance what reference (and what state) the member is sitting on.
     */
    public function latestPayment(): ?MembershipPayment
    {
        return MembershipPayment::query()
            ->whereIn('membership_id', $this->memberships()->select('id'))
            ->orderByDesc('created_at')
            ->first();
    }

    // -----------------------------------------------------------------
    // Lifecycle
    // -----------------------------------------------------------------

    /**
     * The richer status a human reads, worked out from the lifecycle plus what
     * is already on the record. Nothing here is stored, so it cannot drift.
     */
    public function standing(): MemberStanding
    {
        if ($this->lifecycle === MemberLifecycle::Resigned) {
            return MemberStanding::Resigned;
        }

        // A suspension is laid over the lifecycle rather than replacing it, so
        // it wins the display without the underlying position being lost.
        if ($this->isSuspended()) {
            return MemberStanding::Suspended;
        }

        return match ($this->lifecycle) {
            MemberLifecycle::Pending => $this->pendingStanding(),
            MemberLifecycle::Active => MemberStanding::Active,
            MemberLifecycle::Expired => $this->isLongLapsed()
                ? MemberStanding::LongLapsed
                : MemberStanding::Expired,
            default => MemberStanding::Resigned,
        };
    }

    /** What exactly a Pending member is waiting on. */
    protected function pendingStanding(): MemberStanding
    {
        if ($this->isAbandoned()) {
            return MemberStanding::Abandoned;
        }

        if (! $this->hasVerifiedEmail()) {
            return MemberStanding::AwaitingEmail;
        }

        return $this->hasStartedApplication()
            ? MemberStanding::AwaitingPayment
            : MemberStanding::AwaitingChoice;
    }

    /**
     * Whether they ever picked a membership type. Prefers an eager-loaded
     * relation or a withExists() aggregate so rendering a list of members does
     * not fire a query per row.
     */
    public function hasStartedApplication(): bool
    {
        $counted = $this->getAttribute('memberships_exists');

        if ($counted !== null) {
            return (bool) $counted;
        }

        if ($this->relationLoaded('memberships')) {
            return $this->memberships->isNotEmpty();
        }

        return $this->memberships()->exists();
    }

    /**
     * Entitled to member rates and member-only features. A suspension revokes
     * that without disturbing the lifecycle underneath it.
     */
    public function isActiveMember(): bool
    {
        return $this->lifecycle === MemberLifecycle::Active && ! $this->isSuspended();
    }

    public function isSuspended(): bool
    {
        return $this->suspended_at !== null;
    }

    public function isAbandoned(): bool
    {
        return $this->abandoned_at !== null;
    }

    /**
     * Replaces the old stored "unverified" status. A member with no user
     * account has no email to confirm, so there is nothing to wait for.
     */
    public function hasVerifiedEmail(): bool
    {
        return $this->user === null || $this->user->email_verified_at !== null;
    }

    /**
     * Expired long enough ago that chasing the renewal is no longer realistic.
     * Replaces the old stored "inactive" status.
     */
    public function isLongLapsed(): bool
    {
        if ($this->lifecycle !== MemberLifecycle::Expired || $this->expiry_date === null) {
            return false;
        }

        return $this->expiry_date->lt(now()->subMonths((int) config('membership.long_lapsed_months', 6)));
    }

    /**
     * The old eight-value status this member would have had. Only used to keep
     * the deprecated column truthful; see booted().
     */
    public function legacyStatus(): MemberStatus
    {
        $lifecycle = $this->lifecycle instanceof MemberLifecycle
            ? $this->lifecycle
            : MemberLifecycle::tryFrom((string) $this->lifecycle) ?? MemberLifecycle::Pending;

        if ($lifecycle === MemberLifecycle::Resigned) {
            return MemberStatus::Resigned;
        }

        if ($this->isSuspended()) {
            return MemberStatus::Suspended;
        }

        return match ($lifecycle) {
            MemberLifecycle::Pending => match (true) {
                $this->isAbandoned() => MemberStatus::Abandoned,
                ! $this->hasVerifiedEmail() => MemberStatus::Unverified,
                default => MemberStatus::Pending,
            },
            MemberLifecycle::Active => MemberStatus::Active,
            MemberLifecycle::Expired => $this->isLongLapsed()
                ? MemberStatus::Inactive
                : MemberStatus::Expired,
            default => MemberStatus::Resigned,
        };
    }

    // -----------------------------------------------------------------
    // Lifecycle buckets
    //
    // One scope per bucket, used by every list, badge, dashboard card and
    // scheduled command. Before this, each screen wrote its own where()
    // clauses and "lapsed" meant three different things depending on where you
    // read it, which is why the counts never agreed.
    // -----------------------------------------------------------------

    /** Paid-up members in good standing. Suspensions are counted separately. */
    public function scopeActive(Builder $query): Builder
    {
        return $query
            ->where('lifecycle', MemberLifecycle::Active->value)
            ->whereNull('suspended_at');
    }

    public function scopeSuspended(Builder $query): Builder
    {
        return $query->whereNotNull('suspended_at');
    }

    /**
     * The real onboarding inbox: signed up, not yet paid up, and not given up
     * on. This is the count that used to be inflated by stale signups.
     */
    public function scopePending(Builder $query): Builder
    {
        return $query
            ->where('lifecycle', MemberLifecycle::Pending->value)
            ->whereNull('abandoned_at')
            ->whereNull('suspended_at');
    }

    /** Pending, and we are waiting on them to confirm their email address. */
    public function scopeAwaitingEmail(Builder $query): Builder
    {
        return $query->pending()->whereHas('user', fn (Builder $q) => $q->whereNull('email_verified_at'));
    }

    /** Pending, email confirmed, but they never picked a membership type. */
    public function scopeAwaitingChoice(Builder $query): Builder
    {
        return $query->pending()
            ->whereDoesntHave('memberships')
            ->where(fn (Builder $q) => $q
                ->whereDoesntHave('user')
                ->orWhereHas('user', fn (Builder $u) => $u->whereNotNull('email_verified_at')));
    }

    /** Pending with an application in flight: the money or the approval is due. */
    public function scopeAwaitingPayment(Builder $query): Builder
    {
        return $query->pending()->whereHas('memberships');
    }

    /** Stale signups we have stopped chasing. Deliberately out of the inbox. */
    public function scopeAbandoned(Builder $query): Builder
    {
        return $query->whereNotNull('abandoned_at')->whereNull('suspended_at');
    }

    /**
     * Raw lifecycle position, suspended or not.
     *
     * The scopes above answer "whose list does this member belong on", so they
     * exclude suspensions the way the displayed standing does. These two answer
     * "where is this member in the lifecycle", which a suspension does not
     * change.
     */
    public function scopeExpired(Builder $query): Builder
    {
        return $query->where('lifecycle', MemberLifecycle::Expired->value);
    }

    public function scopeResigned(Builder $query): Builder
    {
        return $query->where('lifecycle', MemberLifecycle::Resigned->value);
    }

    /**
     * Active, expiring soon, and nobody has started the renewal yet — so it is
     * still worth a nudge.
     */
    public function scopeRenewalDue(Builder $query, ?int $withinDays = null): Builder
    {
        $days = $withinDays ?? (int) config('membership.renewal_due_days', 30);

        return $query->active()
            ->whereNotNull('expiry_date')
            ->whereBetween('expiry_date', [now()->toDateString(), now()->addDays($days)->toDateString()])
            ->whereDoesntHave('memberships', fn (Builder $q) => $q->needsAction());
    }

    /** Recently expired with no renewal started: the winnable-back list. */
    public function scopeRecentlyLapsed(Builder $query, ?int $withinDays = null): Builder
    {
        $days = $withinDays ?? (int) config('membership.recently_lapsed_days', 60);

        return $query->expired()
            ->whereNull('suspended_at')
            ->whereNotNull('expiry_date')
            ->where('expiry_date', '>=', now()->subDays($days)->toDateString())
            ->whereDoesntHave('memberships', fn (Builder $q) => $q->needsAction());
    }

    /** Expired so long ago they are no longer part of the renewal effort. */
    public function scopeLongLapsed(Builder $query, ?int $months = null): Builder
    {
        $months = $months ?? (int) config('membership.long_lapsed_months', 6);

        return $query->expired()
            ->whereNotNull('expiry_date')
            ->where('expiry_date', '<', now()->subMonths($months)->toDateString());
    }

    /**
     * Registered but never confirmed their email, and it's been a while.
     * These accounts have a User + Member but no verified email and no
     * membership application — pure abandoned signups.
     */
    public function scopeStaleUnverifiedSignups(Builder $query, \DateTimeInterface $before): Builder
    {
        return $query->awaitingEmail()->where('created_at', '<', $before);
    }

    /**
     * Verified their email but never started a membership application (no
     * membership row at all), and it's been a while. This is the largest
     * slice of the "Members to onboard" queue.
     */
    public function scopeStaleUnstartedSignups(Builder $query, \DateTimeInterface $before): Builder
    {
        return $query->awaitingChoice()->where('created_at', '<', $before);
    }

    /**
     * Picked a membership type but never paid for it. Deliberately separate
     * from the two above because these people did engage — the club just never
     * saw the money — so they are chased on a shorter, gentler clock.
     */
    public function scopeStaleUnpaidSignups(Builder $query, \DateTimeInterface $before): Builder
    {
        return $query->awaitingPayment()->where('created_at', '<', $before);
    }

    public function hasActiveMembership(): bool
    {
        return $this->memberships()
            ->where('status', MembershipStatus::Active->value)
            ->where(fn ($q) => $q
                ->whereNull('period_end')
                ->orWhere('period_end', '>=', now()->toDateString())
            )
            ->exists();
    }

    public function ageOnDate(\DateTimeInterface $on): ?int
    {
        return $this->date_of_birth
            ? $this->date_of_birth->diffInYears($on)
            : null;
    }

    /**
     * Junior status drives match-fee tiers (and any other youth-specific
     * pricing). Detected primarily from the active membership's type slug —
     * any slug containing "junior" qualifies. Falls back to age (under 18)
     * if a date of birth is on file but no membership type is set.
     */
    public function isJunior(): bool
    {
        $current = $this->currentMembership();
        $slug = $current?->membership_type_slug_snapshot
            ?? $current?->membershipType?->slug
            ?? null;

        if ($slug !== null && str_contains(strtolower((string) $slug), 'junior')) {
            return true;
        }

        $age = $this->ageOnDate(now());

        return $age !== null && $age < 18;
    }

    public function fullName(): string
    {
        return trim("{$this->first_name} {$this->last_name}") ?: ($this->user?->name ?? '—');
    }

    /**
     * Membership number normalised for consistent display.
     *
     * The stored value is authoritative and left untouched, but historical
     * imports produced inconsistent zero-padding (e.g. "PPRC-00181" sitting
     * next to "PPRC-0100"). When the value matches the standard
     * "{prefix}{digits}" shape we re-pad the numeric part to the configured
     * width so the column always reads consistently. Non-standard legacy
     * values (e.g. "PPRC-2019-0032", "LEGACY") are returned exactly as stored,
     * and members without a number yet return null.
     */
    public function formattedMembershipNumber(): ?string
    {
        $raw = trim((string) $this->membership_number);

        if ($raw === '') {
            return null;
        }

        $prefix = (string) config('membership.number_prefix', '');
        $pad = (int) config('membership.number_pad_length', 0);

        $hasPrefix = $prefix !== '' && str_starts_with($raw, $prefix);
        $body = $hasPrefix ? substr($raw, strlen($prefix)) : $raw;

        // Only normalise clean numeric bodies; anything else is left as-is.
        if ($body === '' || ! ctype_digit($body)) {
            return $raw;
        }

        $numeric = $pad > 0
            ? str_pad((string) (int) $body, $pad, '0', STR_PAD_LEFT)
            : (string) (int) $body;

        return ($hasPrefix ? $prefix : '').$numeric;
    }

    /**
     * Name as it appears on the South African ID — full first and surname
     * in upper case (e.g. "PAUL CHARSLEY"). Used on official documents
     * such as endorsement letters where the name must match the ID document.
     */
    public function idDocumentName(): string
    {
        $name = trim("{$this->first_name} {$this->last_name}");

        if ($name === '') {
            $name = (string) ($this->user?->name ?? '');
        }

        return mb_strtoupper(trim($name));
    }

    public function isSaprfVerified(): bool
    {
        if (! $this->saprf_membership_number) {
            return false;
        }

        if ($this->saprf_verified_at) {
            return true;
        }

        return SaprfShooter::query()
            ->where('membership_number', $this->saprf_membership_number)
            ->exists();
    }
}

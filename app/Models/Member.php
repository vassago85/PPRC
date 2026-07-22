<?php

namespace App\Models;

use App\Enums\MembershipStatus;
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
        'shooting_disciplines' => 'array',
        'status' => MemberStatus::class,
    ];

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

    /**
     * Registered but never confirmed their email, and it's been a while.
     * These accounts have a User + Member but no verified email and no
     * membership application — pure abandoned signups.
     */
    public function scopeStaleUnverifiedSignups(Builder $query, \DateTimeInterface $before): Builder
    {
        return $query
            ->where('status', MemberStatus::Unverified->value)
            ->where('created_at', '<', $before);
    }

    /**
     * Verified their email but never started a membership application (no
     * membership row at all), and it's been a while. This is the largest
     * slice of the "Members to onboard" queue.
     */
    public function scopeStaleUnstartedSignups(Builder $query, \DateTimeInterface $before): Builder
    {
        return $query
            ->where('status', MemberStatus::Pending->value)
            ->whereDoesntHave('memberships')
            ->where('created_at', '<', $before);
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

<?php

namespace App\Models;

use App\Enums\MembershipStatus;
use App\Enums\MemberStatus;
use App\Support\NameCase;
use Database\Factories\MemberFactory;
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
            ->orderByRaw("CASE status WHEN 'active' THEN 0 WHEN 'pending_approval' THEN 1 WHEN 'pending_payment' THEN 2 ELSE 3 END")
            ->orderByRaw('period_end IS NOT NULL, period_end DESC')
            ->first();
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

    public function fullName(): string
    {
        return trim("{$this->first_name} {$this->last_name}") ?: ($this->user?->name ?? '—');
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

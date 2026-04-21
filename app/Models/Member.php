<?php

namespace App\Models;

use App\Enums\MemberStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Member extends Model
{
    /** @use HasFactory<\Database\Factories\MemberFactory> */
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

    public function currentMembership(): ?Membership
    {
        return $this->memberships()
            ->whereIn('status', ['active', 'pending_payment', 'pending_approval'])
            ->orderByDesc('period_end')
            ->first();
    }

    public function hasActiveMembership(): bool
    {
        return $this->memberships()
            ->where('status', \App\Enums\MembershipStatus::Active->value)
            ->where('period_end', '>=', now()->toDateString())
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

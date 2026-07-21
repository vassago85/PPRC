<?php

namespace App\Models;

use App\Enums\MembershipStatus;
use App\Enums\RenewalSource;
use App\Services\Membership\MembershipNumberAssignment;
use Database\Factories\MembershipFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Membership extends Model
{
    /** @use HasFactory<MembershipFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'member_id',
        'membership_type_id',
        'period_start',
        'period_end',
        'status',
        'renewal_source',
        'price_cents_snapshot',
        'membership_type_slug_snapshot',
        'membership_type_name_snapshot',
        'approved_at',
        'approved_by_user_id',
        'admin_notes',
        'certificate_token',
        'certificate_issued_at',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'approved_at' => 'datetime',
        'status' => MembershipStatus::class,
        'renewal_source' => RenewalSource::class,
        'price_cents_snapshot' => 'integer',
        'certificate_issued_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saved(function (Membership $membership): void {
            if ($membership->status !== MembershipStatus::Active) {
                return;
            }

            app(MembershipNumberAssignment::class)->syncForActiveMembership($membership);

            if (empty($membership->certificate_token)) {
                $membership->forceFill([
                    'certificate_token' => Str::lower(Str::random(40)),
                    'certificate_issued_at' => $membership->certificate_issued_at ?? now(),
                ])->saveQuietly();
            }
        });
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    /**
     * Same as member(), but includes soft-deleted members so payments and
     * other admin views can still identify who a removed member was.
     */
    public function memberWithTrashed(): BelongsTo
    {
        return $this->belongsTo(Member::class)->withTrashed();
    }

    public function membershipType(): BelongsTo
    {
        return $this->belongsTo(MembershipType::class);
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(MembershipType::class, 'membership_type_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(MembershipPayment::class);
    }

    /**
     * The most recent payment row regardless of status. Used to surface
     * the EFT reference an admin needs when reconciling against bank
     * statements — pending or submitted reference is what the member
     * was actually told to use.
     */
    public function latestPayment(): ?MembershipPayment
    {
        return $this->payments()
            ->orderByDesc('created_at')
            ->first();
    }

    public function confirmedPayment(): ?MembershipPayment
    {
        return $this->payments()->where('status', 'confirmed')->latest('confirmed_at')->first();
    }

    public function isPaid(): bool
    {
        return $this->confirmedPayment() !== null;
    }

    /**
     * Whether this membership is a renewal rather than a first-time join.
     * Renewals created through RenewalService carry a renewal_source; as a
     * fallback we treat it as a renewal when the member already held an
     * earlier membership that was active or expired (i.e. they've been a
     * paid-up member before), ignoring abandoned/never-activated signups.
     */
    public function isRenewal(): bool
    {
        if ($this->renewal_source !== null) {
            return true;
        }

        if ($this->member_id === null) {
            return false;
        }

        return static::query()
            ->where('member_id', $this->member_id)
            ->where('id', '!=', $this->id)
            ->whereIn('status', [
                MembershipStatus::Active->value,
                MembershipStatus::Expired->value,
            ])
            ->where('created_at', '<', $this->created_at ?? now())
            ->exists();
    }

    /**
     * Life memberships never expire. Historically these are stored either with
     * a null period_end or with a far-future placeholder date (the Life Member
     * type runs 1200 months, landing roughly 100 years out — e.g. "29 Apr
     * 2126"). Either way we treat them as lifetime for display, without
     * altering the stored date.
     */
    public function isLifetime(): bool
    {
        if ($this->period_end === null) {
            return true;
        }

        $slug = strtolower((string) ($this->membership_type_slug_snapshot ?? ''));
        $name = strtolower((string) ($this->membership_type_name_snapshot ?? ''));

        if (str_contains($slug, 'life') || str_contains($name, 'life')) {
            return true;
        }

        // A period end decades away is a "never expires" placeholder, not a
        // real renewal date.
        return $this->period_end->greaterThan(now()->addYears(50));
    }

    /**
     * A cancelled membership that has been replaced by a newer, non-cancelled
     * membership for the same member covering an overlapping period. Surfaced
     * in the admin list so an old "cancelled" row sitting next to its active
     * twin (a re-signup) reads as intentional history rather than a
     * duplicate-data bug.
     */
    public function isSuperseded(): bool
    {
        if ($this->status !== MembershipStatus::Cancelled || $this->member_id === null) {
            return false;
        }

        $start = $this->period_start?->toDateString();
        $end = $this->period_end?->toDateString();

        return static::query()
            ->where('member_id', $this->member_id)
            ->where('id', '!=', $this->id)
            ->where('status', '!=', MembershipStatus::Cancelled->value)
            // Overlaps this membership's period (null ends are treated as open).
            ->where(fn ($q) => $end === null ? $q : $q
                ->whereNull('period_start')
                ->orWhere('period_start', '<=', $end))
            ->where(fn ($q) => $start === null ? $q : $q
                ->whereNull('period_end')
                ->orWhere('period_end', '>=', $start))
            ->exists();
    }
}

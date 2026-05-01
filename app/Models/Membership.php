<?php

namespace App\Models;

use App\Enums\MembershipStatus;
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
}

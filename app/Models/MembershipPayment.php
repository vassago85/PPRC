<?php

namespace App\Models;

use App\Enums\PaymentProvider;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class MembershipPayment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'membership_id',
        'provider',
        'status',
        'amount_cents',
        'currency',
        'reference',
        'paystack_reference',
        'proof_path',
        'meta',
        'submitted_at',
        'confirmed_at',
        'confirmed_by_user_id',
    ];

    protected $casts = [
        'provider' => PaymentProvider::class,
        'status' => PaymentStatus::class,
        'amount_cents' => 'integer',
        'meta' => AsArrayObject::class,
        'submitted_at' => 'datetime',
        'confirmed_at' => 'datetime',
    ];

    public function membership(): BelongsTo
    {
        return $this->belongsTo(Membership::class);
    }

    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by_user_id');
    }

    /**
     * The member this payment is for, including soft-deleted members so a
     * removed member's payment still resolves rather than showing blank.
     */
    public function payerMember(): ?Member
    {
        return $this->membership?->member
            ?? $this->membership?->memberWithTrashed
            ?? ($this->membership?->member_id
                ? Member::withTrashed()->find($this->membership->member_id)
                : null);
    }

    /**
     * Best available display name for the payer. Falls back through the
     * linked account name/email so a payment is never unidentifiable in
     * the admin list, even when the member record is missing or nameless.
     */
    public function payerName(): string
    {
        $member = $this->payerMember();

        if ($member) {
            $name = $member->fullName();

            if ($name !== '—') {
                return $member->trashed() ? "{$name} (removed)" : $name;
            }

            if ($accountName = $member->user?->name) {
                return $member->trashed() ? "{$accountName} (removed)" : $accountName;
            }

            if ($email = $member->user?->email) {
                return $email;
            }
        }

        return '—';
    }

    /**
     * Secondary line for the payer column: membership type, plus the linked
     * email when we have it, to help reconcile blank/removed members.
     */
    public function payerSubtitle(): ?string
    {
        $type = $this->membership?->membershipType?->name
            ?? $this->membership?->membership_type_name_snapshot;

        $email = $this->payerMember()?->user?->email;

        return collect([$type, $email])->filter()->implode(' · ') ?: null;
    }
}

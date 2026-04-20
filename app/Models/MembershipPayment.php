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
}

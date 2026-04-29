<?php

namespace App\Models;

use App\Enums\EndorsementStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class EndorsementRequest extends Model
{
    protected $fillable = [
        'member_id',
        'reason',
        'firearm_type',
        'firearm_details',
        'motivation',
        'status',
        'token',
        'reviewed_by_user_id',
        'reviewed_at',
        'admin_notes',
    ];

    protected $casts = [
        'status' => EndorsementStatus::class,
        'reviewed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $req) {
            if (empty($req->token) && $req->status === EndorsementStatus::Approved) {
                $req->token = Str::lower(Str::random(48));
            }
        });

        static::updating(function (self $req) {
            if ($req->isDirty('status') && $req->status === EndorsementStatus::Approved && empty($req->token)) {
                $req->token = Str::lower(Str::random(48));
            }
        });
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }
}

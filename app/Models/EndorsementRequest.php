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
        'item_type',
        'firearm_type',
        'make',
        'calibre',
        'component_type',
        'action_serial_number',
        'barrel_serial_number',
        'firearm_details',
        'motivation',
        'status',
        'token',
        'reviewed_by_user_id',
        'reviewed_at',
        'admin_notes',
    ];

    /**
     * Human-readable description of what's being endorsed, used in the
     * letter, verify page, and admin tables. Composes whatever fields
     * are populated, gracefully handling legacy rows that only filled
     * `firearm_type`/`firearm_details`.
     */
    public function describeItem(): string
    {
        $parts = [];

        if ($this->item_type === 'component' && $this->component_type) {
            $parts[] = $this->component_type;
        } elseif ($this->firearm_type) {
            $parts[] = $this->firearm_type;
        }

        $body = trim(($this->make ? $this->make.' ' : '').($this->calibre ?? ''));

        if ($body !== '') {
            $parts[] = $body;
        } elseif ($this->firearm_details) {
            $parts[] = $this->firearm_details;
        }

        return implode(' — ', array_filter($parts));
    }

    public function isComponent(): bool
    {
        return $this->item_type === 'component';
    }

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

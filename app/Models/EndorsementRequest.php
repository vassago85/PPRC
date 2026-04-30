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

    /**
     * Loose detection of rimfire vs centerfire from the calibre string.
     * Anything that looks like a .22 LR / .22 WMR / .17 HMR is treated
     * as rimfire; everything else falls through to centerfire.
     */
    public function isRimfire(): bool
    {
        $calibre = mb_strtolower((string) $this->calibre);

        if ($calibre === '') {
            return false;
        }

        $rimfirePatterns = [
            '22 lr', '22lr', '.22lr', '.22 lr',
            '22 long rifle', '22 short', '22 wmr', '22 mag',
            '17 hmr', '17hmr', '.17 hmr', '.17hmr',
            '17 mach 2', '17 mach2',
            'rimfire',
        ];

        foreach ($rimfirePatterns as $needle) {
            if (str_contains($calibre, $needle)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Distance range used in the letter body. Precision Rifle disciplines
     * play out at very different distances depending on whether the rifle
     * is rimfire (NRL22 / club rimfire) or centerfire (PRS-style stages).
     */
    public function distanceRange(): string
    {
        return $this->isRimfire() ? '50m and 300m' : '300m and 900m';
    }

    public function disciplineLabel(): string
    {
        return $this->isRimfire() ? 'Rimfire Precision Rifle Shooting' : 'Precision Rifle Shooting';
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

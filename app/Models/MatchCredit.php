<?php

namespace App\Models;

use App\Enums\MatchCreditStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MatchCredit extends Model
{
    protected $fillable = [
        'member_id',
        'payee_name',
        'payee_email',
        'amount_cents',
        'reason',
        'source_event_id',
        'source_registration_id',
        'status',
        'used_event_id',
        'used_at',
        'created_by_user_id',
        'notes',
    ];

    protected $casts = [
        'amount_cents' => 'integer',
        'status' => MatchCreditStatus::class,
        'used_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        // Keep the payee snapshot populated from the linked member so the
        // ledger is always readable, even if the member is later removed.
        static::saving(function (MatchCredit $credit) {
            if ($credit->member_id && blank($credit->payee_name)) {
                $member = $credit->relationLoaded('member') ? $credit->member : Member::find($credit->member_id);
                if ($member) {
                    $credit->payee_name = $member->fullName();
                    $credit->payee_email ??= $member->user?->email;
                }
            }
        });
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function sourceEvent(): BelongsTo
    {
        return $this->belongsTo(Event::class, 'source_event_id');
    }

    public function sourceRegistration(): BelongsTo
    {
        return $this->belongsTo(EventRegistration::class, 'source_registration_id');
    }

    public function usedEvent(): BelongsTo
    {
        return $this->belongsTo(Event::class, 'used_event_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('status', MatchCreditStatus::Available->value);
    }

    public function payeeName(): string
    {
        if (filled($this->payee_name)) {
            return (string) $this->payee_name;
        }

        if ($this->member) {
            return $this->member->fullName();
        }

        return filled($this->payee_email) ? (string) $this->payee_email : 'Unknown';
    }

    public function isAvailable(): bool
    {
        return $this->status === MatchCreditStatus::Available;
    }
}

<?php

namespace App\Models;

use App\Enums\EventRegistrationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventRegistration extends Model
{
    protected $fillable = [
        'event_id',
        'member_id',
        'guest_name',
        'guest_email',
        'guest_phone',
        'squad_number',
        'firing_order',
        'fee_cents',
        'status',
        'attended',
        'notes',
        'registered_at',
        'checked_in_by_user_id',
        'checked_in_at',
    ];

    protected $casts = [
        'attended' => 'boolean',
        'registered_at' => 'datetime',
        'checked_in_at' => 'datetime',
        'status' => EventRegistrationStatus::class,
        'squad_number' => 'integer',
        'firing_order' => 'integer',
        'fee_cents' => 'integer',
    ];

    /**
     * Auto-waive the entry fee for committee / ExCo members on creation.
     * Done in a model observer (rather than in one specific form) so every
     * registration path — Filament, future public portal, CSV import —
     * applies the same rule without opt-in.
     *
     * Precedence:
     *   1. If fee_cents was set explicitly (incl. 0), respect it.
     *   2. Guests (no member_id) pay full event price — fee stays null.
     *   3. Otherwise, if the linked member's user holds a free-entry role,
     *      set fee_cents = 0. Leave null for regular members so the event's
     *      current price_cents is used at invoice time.
     */
    protected static function booted(): void
    {
        static::creating(function (EventRegistration $reg) {
            if ($reg->fee_cents !== null) {
                return;
            }
            if (! $reg->member_id) {
                return;
            }

            $member = $reg->relationLoaded('member')
                ? $reg->member
                : Member::find($reg->member_id);

            if ($member?->user?->hasFreeEventEntry()) {
                $reg->fee_cents = 0;
            }
        });
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function checkedInBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_in_by_user_id');
    }

    public function shooterName(): string
    {
        if ($this->member) {
            return $this->member->fullName();
        }

        return trim((string) $this->guest_name) ?: 'Guest';
    }

    /**
     * What this entry actually costs: the explicit override if set,
     * otherwise the event's current price_cents.
     */
    public function effectiveFeeCents(): ?int
    {
        if ($this->fee_cents !== null) {
            return $this->fee_cents;
        }

        return $this->event?->price_cents;
    }

    /**
     * True when the entry is waived (fee explicitly set to 0 against a priced
     * event). Used to render a "Waived" badge in the UI.
     */
    public function isWaived(): bool
    {
        if ($this->fee_cents !== 0) {
            return false;
        }

        return ($this->event?->price_cents ?? 0) > 0;
    }
}

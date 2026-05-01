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
        'division',
        'category',
        'course',
        'squad_number',
        'firing_order',
        'fee_cents',
        'is_saprf_entry',
        'is_junior',
        'status',
        'attended',
        'notes',
        'registered_at',
        'checked_in_by_user_id',
        'checked_in_at',
    ];

    protected $casts = [
        'attended' => 'boolean',
        'is_saprf_entry' => 'boolean',
        'is_junior' => 'boolean',
        'registered_at' => 'datetime',
        'checked_in_at' => 'datetime',
        'status' => EventRegistrationStatus::class,
        'squad_number' => 'integer',
        'firing_order' => 'integer',
        'fee_cents' => 'integer',
    ];

    /**
     * Auto-resolve the entry fee on creation.
     *
     * Done in a model observer (rather than in one specific form) so every
     * registration path — Filament, future public portal, CSV import —
     * applies the same rule without opt-in.
     *
     * Precedence:
     *   1. If fee_cents was set explicitly (incl. 0), respect it.
     *   2. SAPRF entries are paid externally via the SAPRF site; we don't
     *      charge them, so fee_cents is left null (see effectiveFeeCents).
     *   3. ExCo / committee members -> fee_cents = 0 (free entry).
     *   4. Otherwise leave null; effectiveFeeCents() resolves member vs
     *      non-member pricing against the event at read time so changes to
     *      event pricing propagate to existing entries.
     */
    protected static function booted(): void
    {
        static::creating(function (EventRegistration $reg) {
            if ($reg->fee_cents !== null) {
                return;
            }
            if ($reg->is_saprf_entry) {
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
     * What PPRC actually charges this entry.
     *
     * Precedence:
     *   1. SAPRF entries: paid externally, PPRC charges nothing -> 0.
     *   2. Explicit fee_cents override (incl. 0 for waivers).
     *   3. Tiered event pricing based on member-vs-non-member status.
     *
     * Returns null when the event has no price configured at all.
     */
    public function effectiveFeeCents(): ?int
    {
        if ($this->is_saprf_entry) {
            return 0;
        }

        if ($this->fee_cents !== null) {
            return $this->fee_cents;
        }

        $member = $this->relationLoaded('member') ? $this->member : $this->member;

        return $this->event?->effectivePriceCentsFor($member, (bool) $this->is_junior);
    }

    /**
     * True when PPRC isn't charging this entry but the event normally has a fee.
     * Covers ExCo waivers and SAPRF-paid entries — both render a badge in the UI.
     */
    public function isWaived(): bool
    {
        if ($this->effectiveFeeCents() !== 0) {
            return false;
        }

        $eventHasPrice = ($this->event?->memberPriceCents() ?? 0) > 0
            || ($this->event?->nonMemberPriceCents() ?? 0) > 0;

        return $eventHasPrice;
    }

    public function waiverReason(): ?string
    {
        if (! $this->isWaived()) {
            return null;
        }

        if ($this->is_saprf_entry) {
            return 'Paid via SAPRF';
        }

        return 'Waived';
    }
}

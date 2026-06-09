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
        'paid_at',
        'marked_paid_by_user_id',
        'payment_proof_path',
        'proof_submitted_at',
    ];

    protected $casts = [
        'attended' => 'boolean',
        'is_saprf_entry' => 'boolean',
        'is_junior' => 'boolean',
        'registered_at' => 'datetime',
        'checked_in_at' => 'datetime',
        'paid_at' => 'datetime',
        'proof_submitted_at' => 'datetime',
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

    public function markedPaidBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'marked_paid_by_user_id');
    }

    /**
     * Whether this entry still owes a fee and has not yet been marked paid —
     * the set a treasurer needs to chase / approve. Entries that owe nothing
     * (waived / SAPRF / free) are never "awaiting payment".
     */
    public function awaitingPayment(): bool
    {
        if ($this->paid_at !== null) {
            return false;
        }

        if ($this->is_saprf_entry || $this->isWaived()) {
            return false;
        }

        return (int) ($this->effectiveFeeCents() ?? 0) > 0;
    }

    /**
     * True once the fee has been confirmed received, or when nothing is owed.
     */
    public function isPaid(): bool
    {
        return $this->paid_at !== null || ! $this->awaitingPayment();
    }

    /**
     * Member has uploaded proof of payment that a committee member still needs
     * to verify (proof on file but not yet confirmed paid).
     */
    public function hasUnverifiedProof(): bool
    {
        return $this->payment_proof_path !== null && $this->paid_at === null;
    }

    /**
     * Whether this entry counts as settled for the public list — either a
     * committee member confirmed the fee, or the shooter is an ExCo member
     * who enters for free (auto-confirmed, nothing to pay).
     */
    public function paymentConfirmed(): bool
    {
        if ($this->paid_at !== null) {
            return true;
        }

        return (bool) $this->member?->user?->hasFreeEventEntry();
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

    /**
     * Email address to send a payment request to — the linked member's portal
     * account email, or the guest email captured at registration.
     */
    public function payerEmail(): ?string
    {
        if ($this->member) {
            $email = $this->member->user?->email;

            return filled($email) ? $email : null;
        }

        return filled($this->guest_email) ? $this->guest_email : null;
    }

    /**
     * First name to greet in the payment email.
     */
    public function payerFirstName(): string
    {
        if ($this->member && filled($this->member->first_name)) {
            return (string) $this->member->first_name;
        }

        $name = trim((string) $this->guest_name);

        return $name === '' ? 'there' : explode(' ', $name)[0];
    }

    /**
     * Whether a payment email makes sense for this entry — i.e. it actually
     * owes PPRC money and we have somewhere to send it.
     */
    public function owesPayment(): bool
    {
        if ($this->is_saprf_entry || $this->isWaived()) {
            return false;
        }

        return (int) ($this->effectiveFeeCents() ?? 0) > 0
            && filled($this->payerEmail());
    }

    /**
     * Stable, human-traceable EFT reference for this match entry, e.g.
     * "PPRC-M5-123" (M = match, then event id + entry id). Deterministic so
     * re-sending the email always quotes the same reference for reconciliation.
     */
    public function paymentReference(): string
    {
        $prefix = trim((string) \App\Models\SiteSetting::get('payments.bank.reference_prefix', ''));
        if ($prefix === '') {
            $prefix = (string) config('membership.payment_ref_prefix', 'PPRC') ?: 'PPRC';
        }
        $prefix = strtoupper(trim((string) preg_replace('/[^A-Za-z0-9-]+/', '', $prefix), '-')) ?: 'PPRC';

        return sprintf('%s-M%d-%d', $prefix, $this->event_id, $this->id);
    }
}

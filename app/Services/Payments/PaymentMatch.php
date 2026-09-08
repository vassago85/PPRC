<?php

namespace App\Services\Payments;

/**
 * One thing a bank deposit could be paying for, together with an honest
 * account of why we think so.
 *
 * Confidence is never used to act on the admin's behalf — it only decides the
 * order candidates are shown in, and how loudly the screen hedges.
 */
final class PaymentMatch
{
    public const MATCH_ENTRY = 'match_entry';

    public const MEMBERSHIP_PAYMENT = 'membership_payment';

    public const SHOP_ORDER = 'shop_order';

    /**
     * A reference we can *identify* but not act on — a match entry that was
     * deleted, a membership payment that was removed, or a reference that only
     * survives in the email log. Surfaced so the treasurer knows whose deposit
     * they are looking at even when the underlying record is gone, but never
     * settle-able because there is nothing left to settle against.
     */
    public const IDENTIFICATION = 'identification';

    /** The reference resolved to exactly one real record. */
    public const EXACT = 'exact';

    /** Several readings of the reference are real, or the payer is certain but the item isn't. */
    public const LIKELY = 'likely';

    /** Reached by name, or by a reference we had to guess the shape of. */
    public const POSSIBLE = 'possible';

    /**
     * An identification-only hit: the reference belongs to somebody, but the
     * record it belongs to is no longer available to settle against.
     */
    public const INFO = 'info';

    public function __construct(
        public readonly string $kind,
        public readonly int $id,
        public readonly string $confidence,
        public readonly string $reason,
        public readonly string $who,
        public readonly string $what,
        public readonly int $amountCents,
        public readonly bool $settled,
        public readonly ?string $reference = null,
        public readonly ?string $settledNote = null,
        public readonly ?string $url = null,
        public readonly ?string $email = null,
        public readonly ?int $memberId = null,
    ) {}

    public function key(): string
    {
        return $this->kind.':'.$this->id;
    }

    public function isExact(): bool
    {
        return $this->confidence === self::EXACT;
    }

    /** Higher sorts first. */
    public function weight(): int
    {
        return match ($this->confidence) {
            self::EXACT => 3,
            self::LIKELY => 2,
            self::POSSIBLE => 1,
            self::INFO => 0,
            default => 1,
        };
    }

    /**
     * Whether this hit is informational only — surfaced so the admin knows
     * whose reference it is, but not actionable because there is no live
     * record left to settle against.
     */
    public function isIdentification(): bool
    {
        return $this->kind === self::IDENTIFICATION;
    }

    public function withConfidence(string $confidence, ?string $reason = null): self
    {
        return new self(
            kind: $this->kind,
            id: $this->id,
            confidence: $confidence,
            reason: $reason ?? $this->reason,
            who: $this->who,
            what: $this->what,
            amountCents: $this->amountCents,
            settled: $this->settled,
            reference: $this->reference,
            settledNote: $this->settledNote,
            url: $this->url,
            email: $this->email,
            memberId: $this->memberId,
        );
    }

    public function withUrl(?string $url): self
    {
        return new self(
            kind: $this->kind,
            id: $this->id,
            confidence: $this->confidence,
            reason: $this->reason,
            who: $this->who,
            what: $this->what,
            amountCents: $this->amountCents,
            settled: $this->settled,
            reference: $this->reference,
            settledNote: $this->settledNote,
            url: $url,
            email: $this->email,
            memberId: $this->memberId,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'kind' => $this->kind,
            'id' => $this->id,
            'key' => $this->key(),
            'confidence' => $this->confidence,
            'reason' => $this->reason,
            'who' => $this->who,
            'what' => $this->what,
            'amount_cents' => $this->amountCents,
            'settled' => $this->settled,
            'reference' => $this->reference,
            'settled_note' => $this->settledNote,
            'url' => $this->url,
            'email' => $this->email,
            'member_id' => $this->memberId,
        ];
    }
}

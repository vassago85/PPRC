<?php

namespace App\Services\Payments;

/**
 * Runs every money-in line on a statement past the reference resolver and sorts
 * the results into what can be settled safely and what needs a human.
 *
 * The bar for "ready" is deliberately high: exactly one candidate, reached by a
 * reference we're certain of, still owing, and owing precisely what the bank
 * received. Anything less — two possible readings, a name-only match, or the
 * right person but the wrong amount — lands in review, because a wrongly
 * settled entry is far more expensive to unpick than one settled by hand.
 */
class StatementReconciliation
{
    /** One certain candidate, and the amount agrees. Safe to settle in bulk. */
    public const READY = 'ready';

    /** Something was found, but choosing between the options needs a person. */
    public const REVIEW = 'review';

    /** Everything this line points at is already paid — nothing to do. */
    public const SETTLED = 'settled';

    /** Nothing in the line to go on. */
    public const UNMATCHED = 'unmatched';

    public function __construct(protected PaymentReferenceResolver $resolver) {}

    /**
     * @param  array<int, StatementLine>  $lines
     * @return array<int, array<string, mixed>>
     */
    public function review(array $lines): array
    {
        return array_map(fn (StatementLine $line) => $this->reviewLine($line), $lines);
    }

    /**
     * @return array<string, mixed>
     */
    public function reviewLine(StatementLine $line): array
    {
        $candidates = $this->resolver->resolve($line->description);

        $open = array_values(array_filter(
            $candidates,
            fn (PaymentMatch $match) => ! $match->settled,
        ));

        $certain = array_values(array_filter(
            $open,
            fn (PaymentMatch $match) => $match->isExact() && $match->amountCents === $line->amountCents,
        ));

        $status = match (true) {
            $candidates === [] => self::UNMATCHED,
            $open === [] => self::SETTLED,
            count($certain) === 1 => self::READY,
            default => self::REVIEW,
        };

        return [
            'row' => $line->row,
            'date' => $line->date?->toDateString(),
            'amount_cents' => $line->amountCents,
            'description' => $line->description,
            'status' => $status,
            'apply' => $status === self::READY ? $certain[0]->key() : null,
            'candidates' => array_map(fn (PaymentMatch $match) => $match->toArray(), $candidates),
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $reviews
     * @return array<string, int>
     */
    public function summary(array $reviews): array
    {
        $summary = [
            'lines' => count($reviews),
            'total_cents' => 0,
            'ready' => 0,
            'ready_cents' => 0,
            'review' => 0,
            'review_cents' => 0,
            'settled' => 0,
            'settled_cents' => 0,
            'unmatched' => 0,
            'unmatched_cents' => 0,
        ];

        foreach ($reviews as $review) {
            $cents = (int) $review['amount_cents'];
            $status = (string) $review['status'];

            $summary['total_cents'] += $cents;
            $summary[$status]++;
            $summary[$status.'_cents'] += $cents;
        }

        return $summary;
    }
}

<?php

namespace App\Services\Payments;

final class StatementParseResult
{
    public function __construct(
        /** @var array<int, StatementLine> Money-in lines, in file order. */
        public readonly array $credits,

        /** Money-out lines skipped: fees, transfers, payouts. */
        public readonly int $debits,

        /** Rows with no usable amount at all, e.g. interest-tiering notes. */
        public readonly int $ignored,
    ) {}

    public function totalCents(): int
    {
        return array_sum(array_map(fn (StatementLine $line) => $line->amountCents, $this->credits));
    }
}

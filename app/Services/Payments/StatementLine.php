<?php

namespace App\Services\Payments;

use Illuminate\Support\Carbon;

/**
 * One money-in line off a bank statement export.
 */
final class StatementLine
{
    public function __construct(
        /** 1-based row number in the uploaded file, so the admin can find it again. */
        public readonly int $row,
        public readonly ?Carbon $date,
        public readonly int $amountCents,
        public readonly string $description,
    ) {}
}

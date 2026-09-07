<?php

namespace App\Invoices;

final readonly class InvoiceLine
{
    public function __construct(
        public string $description,
        public int $quantity,
        public int $unitCents,
        public int $lineCents,
    ) {}
}

<?php

namespace App\Invoices;

use App\Enums\InvoiceType;
use Carbon\CarbonInterface;

final readonly class InvoiceDocument
{
    /**
     * @param  list<InvoiceLine>  $lines
     */
    public function __construct(
        public InvoiceType $type,
        public int $sourceId,
        public string $number,
        public CarbonInterface $issuedAt,
        public string $payerName,
        public ?string $billToName,
        public ?string $billToEmail,
        public ?string $membershipNumber,
        public array $lines,
        public int $subtotalCents,
        public int $totalCents,
        public string $currency,
        public bool $paid,
        public ?CarbonInterface $paidAt,
        public ?string $paymentMethod,
        public string $statusLabel,
    ) {}

    public function showBankDetails(): bool
    {
        return ! $this->paid;
    }

    public function billToDisplayName(): string
    {
        return filled($this->billToName) ? $this->billToName : $this->payerName;
    }

    public function formatted(int $cents): string
    {
        $sign = $cents < 0 ? '-' : '';

        return $sign.'R '.number_format(abs($cents) / 100, 2);
    }
}

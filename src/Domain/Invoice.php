<?php

namespace YourVendor\InvoiceIQBundle\Domain;

use DateTimeImmutable;

final class Invoice
{
    /** @var InvoiceLine[] */
    private array $lines = [];

    public function __construct(
        private ?string $invoiceNumber = null,
        private ?DateTimeImmutable $date = null,
        private ?string $currency = null,
        private ?float $totalHt = null,
        private ?float $tax = null,
        private ?float $totalTtc = null,
    ) {}

    // --- getters
    public function getInvoiceNumber(): ?string { return $this->invoiceNumber; }
    public function getDate(): ?DateTimeImmutable { return $this->date; }
    public function getCurrency(): ?string { return $this->currency; }
    public function getTotalHt(): ?float { return $this->totalHt; }
    public function getTax(): ?float { return $this->tax; }
    public function getTotalTtc(): ?float { return $this->totalTtc; }

    /** @return InvoiceLine[] */
    public function getLines(): array { return $this->lines; }

    public function addLine(InvoiceLine $line): void
    {
        $this->lines[] = $line;
    }

    /** Représentation stable pour le rapport JSON */
    public function toArray(): array
    {
        return [
            'invoice_number' => $this->invoiceNumber,
            'date'           => $this->date?->format('Y-m-d'),
            'currency'       => $this->currency,
            'totals' => [
                'ht'  => $this->totalHt,
                'tax' => $this->tax,
                'ttc' => $this->totalTtc,
            ],
        ];
    }
}

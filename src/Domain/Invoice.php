<?php

namespace Mlucas\InvoiceIQBundle\Domain;

use DateTimeImmutable;

final class Invoice
{
    /** @var InvoiceLine[] */
    private array $lines = [];

    // NEW: champ optionnel pour la TVA
    private ?string $vatNumber = null;

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

    /** NEW */
    public function getVatNumber(): ?string { return $this->vatNumber; }

    /** @return InvoiceLine[] */
    public function getLines(): array { return $this->lines; }

    public function addLine(InvoiceLine $line): void
    {
        $this->lines[] = $line;
    }

    /**
     * NEW: “builder” immuable pour renseigner/mettre à jour le n° TVA
     */
    public function withVatNumber(?string $vat): self
    {
        $clone = clone $this;
        $clone->vatNumber = $vat ? trim($vat) : null;
        return $clone;
    }

    /**
     * NEW (optionnel) : fabrique depuis un array (utile pour tes scripts sandbox).
     * Attend le même schéma que toArray().
     */
    public static function fromArray(array $a): self
    {
        $date = null;
        if (!empty($a['date'])) {
            $date = new DateTimeImmutable((string)$a['date']);
        }

        $ht  = isset($a['totals']['ht'])  ? (float)$a['totals']['ht']  : null;
        $tax = isset($a['totals']['tax']) ? (float)$a['totals']['tax'] : null;
        $ttc = isset($a['totals']['ttc']) ? (float)$a['totals']['ttc'] : null;

        $inv = new self(
            invoiceNumber: $a['invoice_number'] ?? null,
            date: $date,
            currency: $a['currency'] ?? null,
            totalHt: $ht,
            tax: $tax,
            totalTtc: $ttc
        );

        // essaie plusieurs clés possibles pour la TVA
        foreach (['vat_number', 'vat', 'tva', 'tva_number'] as $k) {
            if (isset($a[$k]) && is_scalar($a[$k])) {
                return $inv->withVatNumber((string)$a[$k]);
            }
        }

        return $inv;
        }

    /** Représentation stable pour le rapport JSON */
    public function toArray(): array
    {
        $out = [
            'invoice_number' => $this->invoiceNumber,
            'date'           => $this->date?->format('Y-m-d'),
            'currency'       => $this->currency,
            'totals' => [
                'ht'  => $this->totalHt,
                'tax' => $this->tax,
                'ttc' => $this->totalTtc,
            ],
        ];

        // NEW: on n’affiche la TVA que si elle est présente
        if ($this->vatNumber !== null) {
            $out['vat_number'] = $this->vatNumber;
        }

        return $out;
    }
}

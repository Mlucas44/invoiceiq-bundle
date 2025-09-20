<?php

namespace Mlucas\InvoiceIQBundle\Domain;

use DateTimeImmutable;

final class Invoice
{
    /** @var InvoiceLine[] */
    private array $lines = [];

    // TVA optionnelle
    private ?string $vatNumber = null;

    // NEW: hash optionnel du fichier source (SHA-256)
    private ?string $sourceFileHash = null;

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
    public function getVatNumber(): ?string { return $this->vatNumber; }
    public function getSourceFileHash(): ?string { return $this->sourceFileHash; }

    /** @return InvoiceLine[] */
    public function getLines(): array { return $this->lines; }

    public function addLine(InvoiceLine $line): void
    {
        $this->lines[] = $line;
    }

    /** Renseigne/maj le n° TVA (immutabilité) */
    public function withVatNumber(?string $vat): self
    {
        $clone = clone $this;
        $clone->vatNumber = $vat ? trim($vat) : null;
        return $clone;
    }

    /** NEW: calcule et pose le hash depuis un chemin de fichier (SHA-256) */
    public function withSourceFile(string $path): self
    {
        $clone = clone $this;
        // @ supprime l’avertissement si le fichier n’existe pas ; retournera null
        $clone->sourceFileHash = @hash_file('sha256', $path) ?: null;
        return $clone;
    }

    /** NEW: calcule et pose le hash depuis des bytes */
    public function withSourceBytes(string $bytes): self
    {
        $clone = clone $this;
        $clone->sourceFileHash = hash('sha256', $bytes);
        return $clone;
    }

    /** NEW: pose directement un hash déjà calculé */
    public function withSourceFileHash(?string $hash): self
    {
        $clone = clone $this;
        $clone->sourceFileHash = $hash ? strtolower(trim($hash)) : null;
        return $clone;
    }

    /**
     * Fabrique depuis un array (même schéma que toArray()).
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

        // TVA (essaie plusieurs clés)
        foreach (['vat_number', 'vat', 'tva', 'tva_number'] as $k) {
            if (isset($a[$k]) && is_scalar($a[$k])) {
                $inv = $inv->withVatNumber((string)$a[$k]);
                break;
            }
        }

        // NEW: hash fourni (optionnel)
        if (isset($a['source_file_hash']) && is_scalar($a['source_file_hash'])) {
            $inv = $inv->withSourceFileHash((string)$a['source_file_hash']);
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

        if ($this->vatNumber !== null) {
            $out['vat_number'] = $this->vatNumber;
        }

        // NEW: utile en debug (tu peux l’omettre côté API publique si tu préfères)
        if ($this->sourceFileHash !== null) {
            $out['source_file_hash'] = $this->sourceFileHash;
        }

        return $out;
    }
}

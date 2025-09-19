<?php

namespace Mlucas\InvoiceIQBundle\Domain;

final class InvoiceLine
{
    public function __construct(
        private string $description,
        private float $quantity,
        private float $unitPrice,
        private ?float $lineTotal = null, // si null, on peut calculer quantity*unitPrice
    ) {}

    public function getDescription(): string { return $this->description; }
    public function getQuantity(): float { return $this->quantity; }
    public function getUnitPrice(): float { return $this->unitPrice; }
    public function getLineTotal(): float
    {
        return $this->lineTotal ?? ($this->quantity * $this->unitPrice);
    }

    public function toArray(): array
    {
        return [
            'description' => $this->description,
            'quantity'    => $this->quantity,
            'unit_price'  => $this->unitPrice,
            'line_total'  => $this->getLineTotal(),
        ];
    }
}

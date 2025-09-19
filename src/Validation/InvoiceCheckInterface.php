<?php

namespace Mlucas\InvoiceIQBundle\Validation;

use Mlucas\InvoiceIQBundle\Domain\Invoice;
use Mlucas\InvoiceIQBundle\Domain\ValidationReport;

interface InvoiceCheckInterface
{
    /**
     * Exécute la vérif et ajoute des issues dans le report si besoin.
     */
    public function check(Invoice $invoice, ValidationReport $report): void;
}

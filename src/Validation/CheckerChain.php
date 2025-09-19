<?php

namespace Mlucas\InvoiceIQBundle\Validation;

use Mlucas\InvoiceIQBundle\Domain\Invoice;
use Mlucas\InvoiceIQBundle\Domain\ValidationReport;

final class CheckerChain
{
    /** @var iterable<InvoiceCheckInterface> */
    private iterable $checks;

    /**
     * @param iterable<InvoiceCheckInterface> $checks
     */
    public function __construct(iterable $checks)
    {
        $this->checks = $checks;
    }

    public function run(Invoice $invoice): ValidationReport
    {
        // IMPORTANT: init à partir de l’Invoice (plus d’erreur de ctor)
        $report = ValidationReport::fromInvoice($invoice);

        foreach ($this->checks as $check) {
            $check->check($invoice, $report);
        }

        return $report;
    }
}

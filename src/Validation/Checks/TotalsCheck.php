<?php

namespace Mlucas\InvoiceIQBundle\Validation\Checks;

use Mlucas\InvoiceIQBundle\Domain\Invoice;
use Mlucas\InvoiceIQBundle\Domain\ValidationIssue;
use Mlucas\InvoiceIQBundle\Domain\ValidationReport;
use Mlucas\InvoiceIQBundle\Validation\InvoiceCheckInterface;

final class TotalsCheck implements InvoiceCheckInterface
{
    public function __construct(
        private bool $enabled,          // invoice_iq.checks.totals
        private float $tolerance = 0.01 // invoice_iq.checks.totals_tolerance
    ) {}

    public function check(Invoice $invoice, ValidationReport $report): void
    {
        if (!$this->enabled) {
            return;
        }

        $ht  = $invoice->totals?->ht;
        $tax = $invoice->totals?->tax;
        $ttc = $invoice->totals?->ttc;

        // Si des valeurs manquent, on ne juge pas ici (la règle est “cohérence”, pas “présence”).
        if ($ht === null || $tax === null || $ttc === null) {
            return;
        }

        $expected = $ht + $tax;
        $diff = abs($expected - $ttc);

        if ($diff > $this->tolerance) {
            // code / message / severity – format v0.1
            $issue = new ValidationIssue(
                code: 'TOTALS_MISMATCH',
                severity: ($diff > 0.5 ? 'error' : 'warning'),
                message: sprintf(
                    'Totaux incohérents: HT + Taxe = %.2f, TTC = %.2f (écart = %.2f > tolérance = %.2f)',
                    $expected, $ttc, $diff, $this->tolerance
                )
            );
            $report->addIssue($issue);
        }
    }
}

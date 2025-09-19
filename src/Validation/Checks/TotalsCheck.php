<?php

namespace Mlucas\InvoiceIQBundle\Validation\Checks;

use Mlucas\InvoiceIQBundle\Domain\Invoice;
use Mlucas\InvoiceIQBundle\Domain\ValidationIssue;
use Mlucas\InvoiceIQBundle\Domain\ValidationReport;
use Mlucas\InvoiceIQBundle\Validation\InvoiceCheckInterface;

final class TotalsCheck implements InvoiceCheckInterface
{
    public function __construct(
        private bool $enabled,
        private float $tolerance = 0.01
    ) {}

    public function check(Invoice $invoice, ValidationReport $report): void
    {
        if (!$this->enabled) {
            return;
        }

        // 🔒 Pas d’accès à des propriétés : on passe par toArray()
        $arr = $invoice->toArray();
        $totals = $arr['totals'] ?? null;

        if (!is_array($totals)) {
            return;
        }

        $ht  = isset($totals['ht'])  ? (float) $totals['ht']  : null;
        $tax = isset($totals['tax']) ? (float) $totals['tax'] : null;
        $ttc = isset($totals['ttc']) ? (float) $totals['ttc'] : null;

        if ($ht === null || $tax === null || $ttc === null) {
            return;
        }

        $expected = $ht + $tax;
        $diff = abs($expected - $ttc);

        if ($diff > $this->tolerance) {
            $severity = ($diff > 0.5) ? 'error' : 'warning';
            $report->addIssue(new ValidationIssue(
                code: 'TOTALS_MISMATCH',
                severity: $severity,
                message: sprintf(
                    'Totaux incohérents: HT + Taxe = %.2f, TTC = %.2f (écart = %.2f > tolérance = %.2f)',
                    $expected, $ttc, $diff, $this->tolerance
                )
            ));
        }
    }
}

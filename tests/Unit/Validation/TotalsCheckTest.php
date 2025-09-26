<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Mlucas\InvoiceIQBundle\Validation\Checks\TotalsCheck;
use Mlucas\InvoiceIQBundle\Domain\Invoice;
use Mlucas\InvoiceIQBundle\Domain\ValidationReport;
use DateTimeImmutable;

final class TotalsCheckTest extends TestCase
{
    public function test_detects_mismatch(): void
    {
        $invoice = new Invoice('F', new \DateTimeImmutable('2025-09-01'), 'EUR', 98.76, 19.75, 120.00);

        $check = new TotalsCheck(enabled: true, tolerance: 0.01);
        $report = ValidationReport::fromInvoice($invoice);

        $check->check($invoice, $report);

        $this->assertNotEmpty($report->getIssues());
    }

    public function test_ok_when_within_tolerance(): void
    {
        $invoice = new Invoice('F', new \DateTimeImmutable('2025-09-01'), 'EUR', 100.00, 20.00, 120.005);

        $check = new TotalsCheck(enabled: true, tolerance: 0.01);
        $report = ValidationReport::fromInvoice($invoice);

        $check->check($invoice, $report);

        $this->assertCount(0, $report->getIssues());
    }
}

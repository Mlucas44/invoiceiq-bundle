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
        $inv = new Invoice('F1', new DateTimeImmutable('2025-09-01'), 'EUR', 100.00, 19.60, 121.00);
        $report = ValidationReport::fromInvoice($inv);

        $check = new TotalsCheck(0.01);
        $check->check($inv, $report);

        $this->assertGreaterThanOrEqual(1, count($report->getIssues()));
        $issue = $report->getIssues()[0];
        $this->assertSame('TOTALS_MISMATCH', $issue->getCode());
        $this->assertSame('error', $issue->getSeverity());
    }

    public function test_ok_when_within_tolerance(): void
    {
        $inv = new Invoice('F2', new DateTimeImmutable('2025-09-01'), 'EUR', 100.00, 20.00, 120.005);
        $report = ValidationReport::fromInvoice($inv);

        $check = new TotalsCheck(0.01);
        $check->check($inv, $report);

        $this->assertCount(0, $report->getIssues());
    }
}

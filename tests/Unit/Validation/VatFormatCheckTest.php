<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Mlucas\InvoiceIQBundle\Validation\Checks\VatFormatCheck;
use Mlucas\InvoiceIQBundle\Domain\Invoice;
use Mlucas\InvoiceIQBundle\Domain\ValidationReport;
use DateTimeImmutable;

final class VatFormatCheckTest extends TestCase
{
    public function test_warns_on_suspect_format(): void
    {
        $inv = (new Invoice('F3', new DateTimeImmutable('2025-09-01'), 'EUR', 100, 20, 120))
            ->withVatNumber('XX123'); // volontairement invalide

        $report = ValidationReport::fromInvoice($inv);

        $check = new VatFormatCheck(true);
        $check->check($inv, $report);

        $this->assertCount(1, $report->getIssues());
        $issue = $report->getIssues()[0];
        $this->assertSame('VAT_FORMAT_SUSPECT', $issue->getCode());
        $this->assertSame('warning', $issue->getSeverity());
    }

    public function test_no_issue_on_validish_format(): void
    {
        $inv = (new Invoice('F4', new DateTimeImmutable('2025-09-01'), 'EUR', 100, 20, 120))
            ->withVatNumber('FRAB123456789'); // modèle FR plausible

        $report = ValidationReport::fromInvoice($inv);

        $check = new VatFormatCheck(true);
        $check->check($inv, $report);

        $this->assertCount(0, $report->getIssues());
    }
}

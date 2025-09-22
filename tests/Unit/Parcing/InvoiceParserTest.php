<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Mlucas\InvoiceIQBundle\Parsing\InvoiceParser;
use Mlucas\InvoiceIQBundle\Domain\Invoice;

final class InvoiceParserTest extends TestCase
{
    public function test_extracts_basic_fields(): void
    {
        $text = <<<TXT
        Facture F2025-001
        Date: 2025-09-01
        Devise: EUR
        Total HT: 98,76
        TVA: 19,75
        Total TTC: 118,51
        TXT;

        $parser  = new InvoiceParser();
        $invoice = $parser->parse($text);

        $this->assertSame('F2025-001', $invoice->getInvoiceNumber());
        $this->assertSame('EUR', $invoice->getCurrency());
        $this->assertSame(98.76, $invoice->getTotalHt());
        $this->assertSame(19.75, $invoice->getTax());
        $this->assertSame(118.51, $invoice->getTotalTtc());
    }
}

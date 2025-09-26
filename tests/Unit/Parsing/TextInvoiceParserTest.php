<?php

use Mlucas\InvoiceIQBundle\Parsing\TextInvoiceParser;
use PHPUnit\Framework\TestCase;

final class TextInvoiceParserTest extends TestCase
{
    public function test_extracts_basic_fields(): void
    {
        $text = <<<TXT
        Invoice: F2025-001
        Date: 2025-09-01
        Currency: EUR
        HT: 98.76
        Tax: 19.75
        TTC: 118.51
        TXT;

        $parser  = new TextInvoiceParser();
        $invoice = $parser->parse($text);

        $this->assertSame('F2025-001', $invoice->getInvoiceNumber());
        $this->assertSame('EUR', $invoice->getCurrency());
        $this->assertSame(98.76, $invoice->getTotalHt());
        $this->assertSame(19.75, $invoice->getTax());
        $this->assertSame(118.51, $invoice->getTotalTtc());
        // si tu veux aussi vérifier la date :
        $this->assertSame('2025-09-01', $invoice->getDate()?->format('Y-m-d'));
    }
}

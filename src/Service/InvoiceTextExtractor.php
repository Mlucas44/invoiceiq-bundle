<?php

namespace YourVendor\InvoiceIQBundle\Service;

use YourVendor\InvoiceIQBundle\Ocr\OcrClientInterface;
use YourVendor\InvoiceIQBundle\Parsing\TextInvoiceParser;
use YourVendor\InvoiceIQBundle\Domain\Invoice;

final class InvoiceTextExtractor
{
    public function __construct(
        private OcrClientInterface $ocr,
        private TextInvoiceParser $parser,
    ) {}

    public function fromFile(string $path): Invoice
    {
        $text = $this->ocr->extractText($path);
        return $this->parser->parse($text);
    }
}

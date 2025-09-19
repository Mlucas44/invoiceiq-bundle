<?php

namespace Mlucas\InvoiceIQBundle\Service;

use Mlucas\InvoiceIQBundle\Ocr\OcrClientInterface;
use Mlucas\InvoiceIQBundle\Parsing\TextInvoiceParser;
use Mlucas\InvoiceIQBundle\Domain\Invoice;

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

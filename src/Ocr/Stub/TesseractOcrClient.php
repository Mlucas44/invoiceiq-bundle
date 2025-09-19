<?php

namespace YourVendor\InvoiceIQBundle\Ocr\Stub;

use YourVendor\InvoiceIQBundle\Ocr\OcrClientInterface;

final class TesseractOcrClient implements OcrClientInterface
{
    public function extractText(string $filePath): string
    {
        // v0.1: stub — on simule un OCR plausible (utile pour débloquer le flux)
        $basename = basename($filePath);
        return <<<TXT
INVOICE: F2025-001
DATE: 2025-09-01
CURRENCY: EUR
TOTAL HT: 98.76
TAX: 19.75
TOTAL TTC: 118.51
SOURCE: {$basename}
TXT;
    }
}

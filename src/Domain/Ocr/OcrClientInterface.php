<?php

namespace Mlucas\InvoiceIQBundle\Domain\Ocr;

interface OcrClientInterface
{
    /**
     * @param string $path Absolute path to an image file (PNG/JPG). PDF is handled by later issues.
     * @return string Non-empty text if OCR succeeds (may contain \n).
     */
    public function extractText(string $path): string;
}

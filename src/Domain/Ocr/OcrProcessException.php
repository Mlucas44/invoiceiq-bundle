<?php

namespace Mlucas\InvoiceIQBundle\Domain\Ocr;

final class OcrProcessException extends \RuntimeException
{
    public function __construct(
        private readonly int $exitCode,
        private readonly string $stderr
    ) {
        parent::__construct(sprintf('OCR process failed (exit %d): %s', $exitCode, $stderr));
    }

    public function exitCode(): int { return $this->exitCode; }
    public function stderr(): string { return $this->stderr; }
}

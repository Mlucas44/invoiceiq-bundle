<?php

namespace Mlucas\InvoiceIQBundle\Infrastructure\Ocr;

use Mlucas\InvoiceIQBundle\Domain\Ocr\OcrClientInterface;
use Mlucas\InvoiceIQBundle\Domain\Ocr\OcrProcessException;
use Symfony\Component\Process\Process;

final class TesseractOcrClient implements OcrClientInterface
{
    public function __construct(
        private readonly string $binaryPath,
        private readonly string $langs = 'fra+eng',
        private readonly int $psm = 3,
        private readonly int $oem = 1,
        private readonly float $timeout = 20.0,
    ) {}

    public function extractText(string $path): string
    {
        $this->assertSupportedImage($path);

        // Build command: tesseract <img> stdout -l langs --psm X --oem Y
        $cmd = [
            $this->binaryPath,
            $path,
            'stdout',
            '-l', $this->langs,
            '--psm', (string)$this->psm,
            '--oem', (string)$this->oem,
        ];

        $process = new Process($cmd, null, null, null, $this->timeout);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new OcrProcessException($process->getExitCode() ?? -1, $process->getErrorOutput());
        }

        // Tesseract may append a trailing newline—trim but keep inner \n
        $text = trim($process->getOutput());

        return $text;
    }

    private function assertSupportedImage(string $path): void
    {
        if (!is_file($path)) {
            throw new \InvalidArgumentException(sprintf('OCR input "%s" not found.', $path));
        }

        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if (!in_array($ext, ['png', 'jpg', 'jpeg'], true)) {
            // PDF support is purposefully deferred to another issue
            throw new \InvalidArgumentException(sprintf('Unsupported extension ".%s" (only PNG/JPG for v0.2).', $ext));
        }
    }
}

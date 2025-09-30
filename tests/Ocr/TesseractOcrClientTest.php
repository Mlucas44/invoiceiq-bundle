<?php

namespace Mlucas\InvoiceIQBundle\Tests\Ocr;

use Mlucas\InvoiceIQBundle\Domain\Ocr\OcrProcessException;
use Mlucas\InvoiceIQBundle\Infrastructure\Ocr\TesseractOcrClient;
use PHPUnit\Framework\TestCase;

final class TesseractOcrClientTest extends TestCase
{
    private function hasTesseract(string $bin): bool
    {
        return is_file($bin) && is_executable($bin);
    }

    public function testRejectsUnsupportedExtension(): void
    {
        $client = new TesseractOcrClient('/usr/bin/tesseract');
        $this->expectException(\InvalidArgumentException::class);
        $client->extractText(__FILE__); // .php => not allowed
    }

    public function testThrowsOcrProcessExceptionOnNonImageGarbage(): void
    {
        // Create a fake .png that is not an image to force process failure (when binary exists)
        $bin = '/usr/bin/tesseract';
        if (!$this->hasTesseract($bin)) {
            $this->markTestSkipped('tesseract binary not available');
        }

        $tmp = tempnam(sys_get_temp_dir(), 'invq_').'.png';
        file_put_contents($tmp, "not an image");

        $client = new TesseractOcrClient($bin, 'eng', 3, 1, 5);

        try {
            $client->extractText($tmp);
            $this->fail('Expected OcrProcessException');
        } catch (OcrProcessException $e) {
            $this->assertNotSame(0, $e->exitCode());
            $this->assertNotSame('', $e->stderr());
        } finally {
            @unlink($tmp);
        }
    }

    public function testExtractsTextOnValidSampleIfAvailable(): void
    {
        $bin = '/usr/bin/tesseract';
        if (!$this->hasTesseract($bin)) {
            $this->markTestSkipped('tesseract binary not available');
        }

        // Provide a tiny 1-bit PNG with the word "TEST". You may keep a sample under tests/fixtures/test.png
        $sample = __DIR__.'/../fixtures/test.png';
        if (!is_file($sample)) {
            $this->markTestSkipped('sample image missing');
        }

        $client = new TesseractOcrClient($bin, 'eng', 6, 1, 10.0);

        $text = $client->extractText($sample);
        $this->assertNotSame('', $text);
    }
}

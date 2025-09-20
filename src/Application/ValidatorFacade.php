<?php

namespace Mlucas\InvoiceIQBundle\Application;

use Mlucas\InvoiceIQBundle\Domain\ValidationReport;
use Mlucas\InvoiceIQBundle\Ocr\OcrClientInterface;
use Mlucas\InvoiceIQBundle\Parsing\TextInvoiceParser;
use Mlucas\InvoiceIQBundle\Validation\CheckerChain;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class ValidatorFacade
{
    public function __construct(
        private readonly OcrClientInterface $ocr,
        private readonly TextInvoiceParser $parser,
        private readonly CheckerChain $checkerChain,
    ) {}

    public function validateUploadedFile(UploadedFile $file): ValidationReport
    {
        // OCR (stub pour v0.1) -> retourne du texte.
        $text = $this->ocr->extractText($file->getPathname());

        // Parsing -> Invoice
        $invoice = $this->parser->parse($text);

        // Fournir le chemin au pipeline (utile pour DuplicateCheck par ex.)
        if (method_exists($invoice, 'withSourceFile')) {
            $invoice = $invoice->withSourceFile($file->getPathname());
        }

        // Exécuter la pipeline de checks et renvoyer le rapport
        return $this->checkerChain->run($invoice);
    }
}

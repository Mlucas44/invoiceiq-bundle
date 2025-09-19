<?php

namespace Mlucas\InvoiceIQBundle\Ocr;

interface OcrClientInterface
{
    /**
     * @param string $filePath Chemin absolu vers le fichier (PDF/JPG/PNG…)
     * @return string Texte brut extrait (ou simulé en v0.1)
     */
    public function extractText(string $filePath): string;
}

<?php

namespace Mlucas\InvoiceIQBundle\Parsing;

use DateTimeImmutable;
use Mlucas\InvoiceIQBundle\Domain\Invoice;

final class TextInvoiceParser
{
    /**
     * Transforme un texte d'OCR en objet Invoice avec champs de base.
     */
    public function parse(string $text): Invoice
    {
        // Normaliser les espaces pour faciliter les regex
        $normalized = preg_replace('/[^\S\r\n]+/', ' ', $text) ?? $text;

        $number   = $this->matchInvoiceNumber($normalized);
        $date     = $this->matchDate($normalized);
        $currency = $this->matchCurrency($normalized);
        [$ht, $tax, $ttc] = $this->matchTotals($normalized);

        return new Invoice($number, $date, $currency, $ht, $tax, $ttc);
    }

    private function matchInvoiceNumber(string $text): ?string
    {
        $patterns = [
            '/\b(?:invoice|facture|inv)\s*[:#]?\s*([A-Z0-9][A-Z0-9\-\/\.]{2,})\b/i',
            '/\b(?:n[°o]\s*|no\.\s*)(?:de\s*)?(?:facture|invoice)?\s*[:#]?\s*([A-Z0-9][A-Z0-9\-\/\.]{2,})\b/i',
        ];
        foreach ($patterns as $re) {
            if (preg_match($re, $text, $m)) {
                return $m[1];
            }
        }
        return null;
    }

    private function matchDate(string $text): ?DateTimeImmutable
    {
        // 2025-09-18
        if (preg_match('/\b(20\d{2})[-\.\/](\d{2})[-\.\/](\d{2})\b/', $text, $m)) {
            return DateTimeImmutable::createFromFormat('Y-m-d', "{$m[1]}-{$m[2]}-{$m[3]}") ?: null;
        }
        // 18/09/2025 ou 18-09-2025
        if (preg_match('/\b(\d{2})[\/\-\.](\d{2})[\/\-\.](20\d{2})\b/', $text, $m)) {
            return DateTimeImmutable::createFromFormat('d-m-Y', "{$m[1]}-{$m[2]}-{$m[3]}") ?: null;
        }
        return null;
    }

    private function matchCurrency(string $text): ?string
    {
        // Codes explicites
        if (preg_match('/\b(EUR|USD|CAD|GBP|CHF)\b/i', $text, $m)) {
            return strtoupper($m[1]);
        }
        // Symboles
        if (preg_match('/€/', $text)) return 'EUR';
        if (preg_match('/£/', $text)) return 'GBP';
        if (preg_match('/CHF/', $text)) return 'CHF';
        if (preg_match('/\$/', $text)) return 'USD'; // heuristique
        return null;
    }

    /**
     * @return array{0:?float,1:?float,2:?float} [ht, tax, ttc]
     */
    private function matchTotals(string $text): array
    {
        // TTC : NE PAS matcher un simple "Total" sinon "Total HT" passera pour TTC.
        $ttc = $this->firstAmount(
            $text,
            '/\b(?:total\s*ttc|ttc|grand\s*total|total\s*due|amount\s*due)\b[^\d\-+]*([-+]?\d+(?:[.,]\d{1,2})?)/i'
        );

        // HT / Subtotal
        $ht  = $this->firstAmount(
            $text,
            '/\b(?:total\s*ht|ht\b|hors\s*taxe|subtotal|net\s*amount)\b[^\d\-+]*([-+]?\d+(?:[.,]\d{1,2})?)/i'
        );

        // Taxes
        $tax = $this->firstAmount(
            $text,
            '/\b(?:tva|taxe|tax|vat)\b[^\d\-+]*([-+]?\d+(?:[.,]\d{1,2})?)/i'
        );

        return [$ht, $tax, $ttc];
    }

    private function firstAmount(string $text, string $regex): ?float
    {
        if (!preg_match($regex, $text, $m)) {
            return null;
        }
        return $this->normalizeAmount($m[1]);
    }

    private function normalizeAmount(?string $raw): ?float
    {
        if ($raw === null) return null;

        // Garder chiffres, séparateurs et signe
        $s = preg_replace('/[^0-9,\.\-]/', '', $raw) ?? '';

        // Si virgule ET point → heuristique: le dernier séparateur est le décimal.
        if (str_contains($s, ',') && str_contains($s, '.')) {
            $lastComma = strrpos($s, ',');
            $lastDot   = strrpos($s, '.');
            if ($lastComma !== false && $lastComma > $lastDot) {
                $s = str_replace('.', '', $s);   // points = milliers
                $s = str_replace(',', '.', $s);  // virgule = décimal
            } else {
                $s = str_replace(',', '', $s);   // virgule = milliers
            }
        } elseif (str_contains($s, ',') && !str_contains($s, '.')) {
            $s = str_replace(',', '.', $s);      // virgule = décimal (fr)
        }

        return is_numeric($s) ? (float) $s : null;
    }
}

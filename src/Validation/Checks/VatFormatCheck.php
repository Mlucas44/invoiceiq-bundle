<?php

namespace Mlucas\InvoiceIQBundle\Validation\Checks;

use Mlucas\InvoiceIQBundle\Domain\ValidationIssue;
use Mlucas\InvoiceIQBundle\Domain\ValidationReport;
use Mlucas\InvoiceIQBundle\Domain\Invoice;
use Mlucas\InvoiceIQBundle\Validation\InvoiceCheckInterface;

final class VatFormatCheck implements InvoiceCheckInterface
{
    public function __construct(
        private bool $enabled = true
    ) {}

    public function check(Invoice $invoice, ValidationReport $report): void
    {
        if (!$this->enabled) {
            return;
        }

        // On récupère les champs via toArray pour éviter tout accès à des props privées
        $arr = $invoice->toArray();

        // On tente plusieurs clés possibles (souvent rencontrées)
        $vat = $this->extractVatNumber($arr);
        if ($vat === null || $vat === '') {
            // pas de TVA => pas d’issue
            return;
        }

        $vat = strtoupper(trim($vat));

        // Règle générique UE (base) : 2 lettres pays + 8 à 12 alphanum
        $generic = '/^[A-Z]{2}[A-Z0-9]{8,12}$/';

        // Si la base est KO -> direct suspect
        if (!preg_match($generic, $vat)) {
            $report->addIssue(new ValidationIssue(
                code: 'VAT_FORMAT_SUSPECT',
                message: sprintf('Format TVA invalide: "%s" (ne respecte pas la base UE)', $vat),
                severity: ValidationIssue::SEVERITY_WARNING
            ));
            return;
        }

        // Règles pays (simple, v0.1; on en met quelques-unes utiles)
        $country = substr($vat, 0, 2);
        $number  = substr($vat, 2);

        $countryRegex = $this->countryRegex($country);

        if ($countryRegex !== null && !preg_match($countryRegex, $number)) {
            $report->addIssue(new ValidationIssue(
                code: 'VAT_FORMAT_SUSPECT',
                message: sprintf('Format TVA suspect pour le pays %s: "%s"', $country, $vat),
                severity: ValidationIssue::SEVERITY_WARNING
            ));
            return;
        }

        // Sinon on considère OK pour v0.1 (pas d’issue)
    }

    private function extractVatNumber(array $arr): ?string
    {
        // structure type (selon ton parser): ["vat_number"] OU "vat" OU "tva"
        // On balaie souplement quelques variantes
        $candidates = ['vat_number', 'vat', 'tva', 'tva_number', 'vatNumber', 'TVA'];
        foreach ($candidates as $key) {
            // recherche case-insensitive
            foreach ($arr as $k => $v) {
                if (strcasecmp($k, $key) === 0 && is_scalar($v)) {
                    return (string) $v;
                }
            }
        }

        // Si tu stockes la TVA dans un sous-tableau "fields", on le balaye aussi
        if (isset($arr['fields']) && is_array($arr['fields'])) {
            return $this->extractVatNumber($arr['fields']);
        }

        return null;
    }

    /**
     * Renvoie une regex par pays (partie après le code pays) ou null si pas de contrainte spécifique.
     * NB: c'est volontairement simple pour v0.1 (on élargira en v0.2).
     */
    private function countryRegex(string $cc): ?string
    {
        return match ($cc) {
            // Allemagne: 9 chiffres
            'DE' => '/^[0-9]{9}$/',

            // France: 2 caractères (lettres ou chiffres) + 9 chiffres (SIREN)
            'FR' => '/^[A-Z0-9]{2}[0-9]{9}$/',

            // Espagne: 1 lettre / 7 chiffres / 1 lettre ou chiffre (simplifié)
            'ES' => '/^[A-Z][0-9]{7}[A-Z0-9]$/',

            // Italie: 11 chiffres
            'IT' => '/^[0-9]{11}$/',

            // Belgique: 10 chiffres
            'BE' => '/^[0-9]{10}$/',

            // Pays-Bas: 9 chiffres + B + 2 chiffres (ex: NL123456789B01)
            'NL' => '/^[0-9]{9}B[0-9]{2}$/',

            // Luxembourg: 8 chiffres
            'LU' => '/^[0-9]{8}$/',

            // Portugal: 9 chiffres
            'PT' => '/^[0-9]{9}$/',

            // Irlande (simplifiée): 1 chiffre + 5 lettres/chiffres + 2 lettres/chiffres
            'IE' => '/^[0-9][A-Z0-9]{5}[A-Z0-9]{2}$/',

            // Pas de règle spécifique → null → on se contente de la règle générique
            default => null,
        };
    }
}

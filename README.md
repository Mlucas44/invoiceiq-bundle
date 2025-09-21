[![CI](https://github.com/Mlucas44/invoiceiq-bundle/actions/workflows/ci.yml/badge.svg)](https://github.com/Mlucas44/invoiceiq-bundle/actions/workflows/ci.yml)

# InvoiceIQBundle

Bundle Symfony **plug-and-play** pour **analyser et valider des factures** (PDF/JPG/PNG).  
v0.1 = MVP (OCR stub, parsing simple, contrôles de base, endpoint HTTP).

## Pourquoi
- Extraire des champs clés (numéro, date, devise, totaux)
- Lancer des **contrôles** (totaux cohérents, format TVA, doublons)
- Renvoyer un **rapport JSON** (status/score/issues)

## État du projet
- `v0.1 (MVP)` en cours — voir **Issues** (milestone v0.1)
- Licence: MIT

## Installation (à partir de v0.1.0)
```bash
composer require your-vendor/invoiceiq-bundle
```
## Configuration

Clé racine : `invoice_iq`.

```yaml
# config/packages/invoice_iq.yaml
invoice_iq:
  ocr:
    provider: 'tesseract'  # valeur par défaut
  checks:
    totals: true           # valeur par défaut
    totals_tolerance: 0.01 # tolérance d’arrondi
    duplicates: true       # valeur par défaut
    vat_format: true       # valeur par défaut
    duplicates_window_days: 30

```
## Contrat JSON (v0.1)

Exemple de `ValidationReport` renvoyé par l’endpoint :

```json
{
  "status": "ALERT",
  "score": 82,
  "fields": {
    "invoice_number": "F2025-001",
    "date": "2025-09-01",
    "currency": "EUR",
    "totals": { "ht": 98.76, "tax": 19.75, "ttc": 118.51 }
  },
  "issues": [
    { "code": "VAT_FORMAT_SUSPECT", "severity": "warning", "message": "Numéro TVA non reconnu" }
  ]
}
```
### OCR (v0.1)
- `ocr.provider`: `tesseract` (par défaut) — implémentation **stub** (ne lance pas le binaire).  
  Utile pour tester le flux end-to-end. Une implémentation réelle sera ajoutée en v0.2.

### Parsing (v0.1)
Le service `TextInvoiceParser` extrait depuis le texte OCR : numéro, date (Y-m-d ou d-m-Y), devise, totaux (HT/Taxe/TTC).
Les montants sont normalisés (virgule/point).


```php
// src/EventSubscriber/InvoiceIQSubscriber.php (dans l’app hôte)
namespace App\EventSubscriber;

use Mlucas\InvoiceIQBundle\Event\PreValidateEvent;
use Mlucas\InvoiceIQBundle\Event\PostValidateEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class InvoiceIQSubscriber implements EventSubscriberInterface
{
    public function __construct(private LoggerInterface $logger) {}

    public static function getSubscribedEvents(): array
    {
        return [
            PreValidateEvent::class  => 'onPre',
            PostValidateEvent::class => 'onPost',
        ];
    }

    public function onPre(PreValidateEvent $e): void
    {
        $this->logger->info('pre_validate', [
            'file'   => $e->originalFilename,
            'size'   => $e->size,
            'mime'   => $e->mimeType,
            'sha256' => $e->sha256,
            'at'     => $e->receivedAt->format(DATE_ATOM),
        ]);
    }

    public function onPost(PostValidateEvent $e): void
    {
        $this->logger->info('post_validate', [
            'sha256'      => $e->sha256,
            'duration_ms' => $e->durationMs,
            'status'      => $e->report->getStatus(),
            'score'       => $e->report->getScore(),
        ]);
    }
}
```
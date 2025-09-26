[![CI](https://github.com/Mlucas44/invoiceiq-bundle/actions/workflows/ci.yml/badge.svg)](https://github.com/Mlucas44/invoiceiq-bundle/actions/workflows/ci.yml)

# InvoiceIQBundle (v0.1)

Bundle Symfony **plug-and-play** pour **analyser et valider des factures** (PDF/JPG/PNG/TXT).  
v0.1 = MVP : OCR *stub* → parsing texte → pipeline de vérifications (Totaux / TVA / Doublons) → **endpoint HTTP** qui renvoie un **JSON contract**.

---

## Sommaire
- [InvoiceIQBundle (v0.1)](#invoiceiqbundle-v01)
  - [Sommaire](#sommaire)
  - [Pourquoi](#pourquoi)
  - [Installation](#installation)
  - [Configuration minimale](#configuration-minimale)
  - [Utilisation (HTTP)](#utilisation-http)
  - [Contrat JSON](#contrat-json)
  - [Événements (pre\_validate / post\_validate)](#événements-pre_validate--post_validate)
  - [Exemple de subscriber](#exemple-de-subscriber)
  - [Stockage optionnel (OFF par défaut)](#stockage-optionnel-off-par-défaut)
  - [Troubleshooting](#troubleshooting)
  - [Versionning \& licence](#versionning--licence)

---

## Pourquoi
- Extraire rapidement les champs clés (numéro, date, devise, totaux).
- Enchaîner des **contrôles** (cohérence des totaux, format de TVA plausible, détection de doublons basés sur le hash).
- Obtenir un **rapport JSON** normalisé (status / score / fields / issues).

---

## Installation

```bash
composer require mlucas44/invoiceiq-bundle:^0.1
```

Ajoutez les routes du bundle (si non importées automatiquement) :

```yml
# config/routes/invoiceiq.yaml (APP HÔTE)
invoiceiq:
  resource: '@InvoiceIQBundle/Resources/config/routes.yaml'
  prefix: /
```


## Configuration minimale

Clé racine : invoice_iq.
```yml
# config/packages/invoice_iq.yaml
invoice_iq:
  ocr:
    provider: 'tesseract'          # v0.1 utilise un stub (aucun appel binaire)
  checks:
    totals: true                   # vérifie HT + Taxe = TTC (avec tolérance)
    duplicates: true               # détection doublons via hash mémoire
    vat_format: true               # vérif heuristique du format TVA
  totals_tolerance: 0.01           # tolérance d’arrondi (ex: 1 centime)
  storage:
    enabled: false                 # OFF par défaut (voir section Stockage)
```
MIME types acceptés v0.1 : application/pdf, image/png, image/jpeg, text/plain.

## Utilisation (HTTP)

Endpoint : POST /_invoiceiq/validate (multipart, champ file)

cURL
```bash
curl -F "file=@/chemin/vers/facture.pdf" http://127.0.0.1:8000/_invoiceiq/validate
```
Postman

- Méthode POST
- URL : http://127.0.0.1:8000/_invoiceiq/validate
- Body → form-data → clé file (type File) → choisissez un fichier

## Contrat JSON
```json
{
  "status": "ALERT",          // "OK" | "ALERT" | "REJECT"
  "score": 75,                // 0..100 (diminue avec les issues)
  "fields": {
    "invoice_number": "F2025-001",
    "date": "2025-09-01",
    "currency": "EUR",
    "vat_number": "FR12345678901",
    "totals": { "ht": 98.76, "tax": 19.75, "ttc": 118.51 }
  },
  "issues": [
    { "code": "TOTALS_MISMATCH", "severity": "error", "message": "Totaux incohérents ..." }
  ],
  "source_file_hash": "1f9f13f2cf2bba5a7731...",   // SHA-256 de l’original
  "storage_key": "2025/09/01/abcd1234.pdf"         // présent si storage.enabled = true
}
```
- status/score : agrégés par la pipeline de checks.
- issues[].severity : "warning" ou "error".

## Événements (pre_validate / post_validate)

Deux hooks Symfony pour étendre côté app hôte :

invoiceiq.pre_validate

- Quand : juste avant le traitement.
- Payload (PreValidateEvent) :
  - originalFilename (string)
  - mimeType (string)
  - size (int)
  - sha256 (string|null)
  - receivedAt (DateTimeImmutable)

invoiceiq.post_validate

- Quand : juste après le traitement.
- Payload (PostValidateEvent) :
  - invoice (Mlucas\InvoiceIQBundle\Domain\Invoice)
  - report (Mlucas\InvoiceIQBundle\Domain\ValidationReport)
  - durationMs (float)
  - sha256 (string|null)

## Exemple de subscriber
```php
<?php
namespace App\Subscriber;

use Mlucas\InvoiceIQBundle\Event\PreValidateEvent;
use Mlucas\InvoiceIQBundle\Event\PostValidateEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Psr\Log\LoggerInterface;

final class InvoiceIQSubscriber implements EventSubscriberInterface
{
    public function __construct(private LoggerInterface $logger) {}

    public static function getSubscribedEvents(): array
    {
        return [
            PreValidateEvent::NAME  => 'onPreValidate',
            PostValidateEvent::NAME => 'onPostValidate',
        ];
    }

    public function onPreValidate(PreValidateEvent $e): void
    {
        $this->logger->info('invoiceiq.pre_validate', [
            'name' => $e->getOriginalFilename(),
            'mime' => $e->getMimeType(),
            'size' => $e->getSize(),
            'hash' => $e->getSha256(),
        ]);
    }

    public function onPostValidate(PostValidateEvent $e): void
    {
        $this->logger->info('invoiceiq.post_validate', [
            'ms'     => $e->getDurationMs(),
            'hash'   => $e->getSha256(),
            'status' => $e->getReport()->getStatus(),
            'score'  => $e->getReport()->getScore(),
        ]);
    }
}
```

## Stockage optionnel (OFF par défaut)
Active un stockage local de l’original (et ses métadonnées) + renvoie storage_key :
```yml
# config/packages/invoice_iq.yaml
invoice_iq:
  storage:
    enabled: true
    adapter: 'local'                                   # v0.1
    local_dir: '%kernel.project_dir%/var/invoiceiq'    # dossier cible
```
- Si enabled: false : aucune écriture disque et pas de storage_key.

## Troubleshooting

- 400 – missing file : le champ file est absent.
- 415 – unsupported media type : mimetype non supporté.
- 200 + issues : parsing OK mais au moins une règle a levé une issue (TOTALS_MISMATCH VAT_FORMAT_SUSPECT, etc.).
- Doublons : détection via store mémoire (hash SHA-256). v0.1 n’écrit pas encore en base.

## Versionning & licence

- Versionning : SemVer (MAJOR.MINOR.PATCH).
- Licence : MIT.
- Voir le CHANGELOG pour l’historique.
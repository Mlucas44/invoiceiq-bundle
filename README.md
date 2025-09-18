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

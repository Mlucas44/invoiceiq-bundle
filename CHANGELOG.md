# Changelog

## [v0.1.0] - 2025-09-26
### Added
- Endpoint HTTP `POST /_invoiceiq/validate` (multipart file) renvoyant un rapport JSON (status/score/fields/issues).
- OCR stub “tesseract” (pas d’appel binaire) pour débloquer le flux E2E.
- Parsing texte : invoice number, date, currency, totals (HT/Tax/TTC) avec normalisation virgule/point.
- Pipeline de vérifications : `TotalsCheck` (tolérance configurable), `VatFormatCheck`, `DuplicateCheck` (hash mémoire).
- Événements Symfony : `invoiceiq.pre_validate`, `invoiceiq.post_validate`.
- Stockage optionnel de l’original (local) + `storage_key` dans le report quand activé.
- Suite de tests v0.1 (unit + 1 fonctionnel) & CI GitHub.

### Changed
- README complet (Install / Config / Usage / API contract / Events / Storage / Troubleshooting).

### Breaking
- none

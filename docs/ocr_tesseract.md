# OCR réel (Tesseract) — v0.2

## Dépendances système
- Ubuntu/Debian: `apt-get install tesseract-ocr tesseract-ocr-fra tesseract-ocr-eng`
- macOS (brew): `brew install tesseract`

## Configuration (bundle)
```yaml
# config/packages/invoice_iq.yaml
invoice_iq:
  ocr:
    tesseract:
      binary_path: '%env(default::string:TESSERACT_BIN)%'
      langs: 'fra+eng'
      psm: 3
      oem: 1
      timeout: 20

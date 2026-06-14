# Changelog

Tutti i cambiamenti notevoli a questo progetto saranno documentati in questo file.

Il formato si basa su [Keep a Changelog](https://keepachangelog.com/it/1.1.0/),
e questo progetto aderisce al [Semantic Versioning](https://semver.org/lang/it/).

## [1.0.0] - 2026-06-14

### Aggiunto
- Decodifica file `.p7m` (PKCS#7 DER) tramite `openssl smime` / `openssl cms`
- Decodifica stringhe PEM e Base64
- Estrazione manuale fragment XML da binari
- Correzione automatica tag XML con distanza di Levenshtein (distance <= 1)
- Sistema di correzione OCR con 30+ pattern di errore comuni
- API statica: `decodeFile()`, `decodeString()`, `getLastCorrections()`
- PHPStan level 8 (massima severita)
- 24 test PHPUnit con fixtures DER, PEM, Base64 e casi negativi
- GitHub Actions CI (PHPUnit, PHPStan, PHP-CS-Fixer, Infection)
- PHP-CS-Fixer con regole PER-CS2.0

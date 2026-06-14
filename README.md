# P7M Decoder

[![CI](https://github.com/controlaltjeff/p7m-fattura-decoder/actions/workflows/ci.yml/badge.svg)](https://github.com/controlaltjeff/p7m-fattura-decoder/actions/workflows/ci.yml)
[![Packagist Version](https://img.shields.io/packagist/v/controlaltjeff/p7m-fattura-decoder)](https://packagist.org/packages/controlaltjeff/p7m-fattura-decoder)
[![PHP Version Require](https://img.shields.io/packagist/php-v/controlaltjeff/p7m-fattura-decoder)](https://packagist.org/packages/controlaltjeff/p7m-fattura-decoder)
[![License](https://img.shields.io/packagist/l/controlaltjeff/p7m-fattura-decoder)](LICENSE)
[![PHPStan Level](https://img.shields.io/badge/PHPStan-level%208-brightgreen)](https://phpstan.org/)

PHP library per decodificare file `.p7m` (PKCS#7 signed) ed estrarre il contenuto XML firmato.

A PHP library to decode P7M (PKCS#7 signed) files and extract the signed XML content. Designed with the Italian Fattura Elettronica system in mind.

## Installazione

```bash
composer require controlaltjeff/p7m-fattura-decoder
```

## Requisiti

- PHP 5.3+ (runtime) / PHP 8.1+ (sviluppo)
- `ext-dom`, `ext-libxml`
- `openssl` CLI (opzionale — usato come strategia di decodifica primaria)

> **Nota per ambienti PHP 5.3/7.x**: i tool di sviluppo (PHPUnit, PHPStan) richiedono PHP 8.1+.
> Installa la libreria con `--no-dev` per evitare conflitti:
> ```bash
> composer require controlaltjeff/p7m-fattura-decoder --no-dev
> ```

## Usage

```php
use Controlaltjeff\P7MDecoder\P7MDecoder;

// Da file
$xml = P7MDecoder::decodeFile('fattura.xml.p7m');

// Da stringa
$xml = P7MDecoder::decodeString($p7mContent);

if ($xml === null) {
    throw new \RuntimeException('Impossibile decodificare il file P7M');
}

// Correzioni automatiche applicate durante la decodifica
$corrections = P7MDecoder::getLastCorrections();
foreach ($corrections as $fix) {
    echo "Tag corretto: {$fix['from']} → {$fix['to']}\n";
}
```

## Strategie di decodifica (8 livelli)

La libreria tenta la decodifica in ordine, fermandosi al primo successo:

1. **Plain XML** — Se già XML valido, restituito direttamente
2. **OpenSSL DER** — `openssl smime -verify -inform DER`
3. **Base64 → DER** — Base64 decode + verifica DER
4. **OpenSSL PEM** — `openssl smime -verify -inform PEM` (include conversione Base64 → PEM)
5. **PHP openssl_pkcs7_verify()** — Fallback via estensione PHP
6. **Estrazione manuale** — Ricerca fragment XML nel binario
7. **Correzione tag** — Ricerca fuzzy (Levenshtein) su tag XML danneggiati (OCR, encoding)
8. **Base64 + estrazione manuale** — Decodifica base64 + fragment XML

### Correzioni automatiche

Oltre alla decodifica, la libreria applica correzioni automatiche su:

- **Tag XML** — Correzione fuzzy con distance ≤ 1 rispetto alla lista tag FatturaPA validi
- **Testo OCR/encoding** — 30+ pattern di correzione per errori tipici di OCR e trasmissione (es. `UnitaMisurah` → `UnitaMisura`, `DettHaglioLinee` → `DettaglioLinee`)

Le correzioni applicate sono accessibili via `P7MDecoder::getLastCorrections()`.

## Sviluppo

```bash
composer install
composer test       # PHPUnit
composer stan       # PHPStan level 8
composer fix-dry    # PHP-CS-Fixer (dry-run)
composer check      # Test + Stan + CS Fixer
```

Per mutation testing (richiede pcov o xdebug):

```bash
composer require --dev infection/infection:^0.29
composer mutate
```

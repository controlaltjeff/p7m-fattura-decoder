# P7M Decoder — PHP Fattura Elettronica PKCS#7

[![CI](https://github.com/controlaltjeff/p7m-fattura-decoder/actions/workflows/ci.yml/badge.svg)](https://github.com/controlaltjeff/p7m-fattura-decoder/actions/workflows/ci.yml)
[![Packagist Version](https://img.shields.io/packagist/v/controlaltjeff/p7m-fattura-decoder)](https://packagist.org/packages/controlaltjeff/p7m-fattura-decoder)
[![Packagist Downloads](https://img.shields.io/packagist/dt/controlaltjeff/p7m-fattura-decoder)](https://packagist.org/packages/controlaltjeff/p7m-fattura-decoder)
[![PHP Version Require](https://img.shields.io/packagist/php-v/controlaltjeff/p7m-fattura-decoder)](https://packagist.org/packages/controlaltjeff/p7m-fattura-decoder)
[![License](https://img.shields.io/packagist/l/controlaltjeff/p7m-fattura-decoder)](LICENSE)
[![PHPStan Level](https://img.shields.io/badge/PHPStan-level%208-brightgreen)](https://phpstan.org/)

PHP library per decodificare file `.p7m` (PKCS#7 signed) ed estrarre il contenuto XML. Progettata per la [Fattura Elettronica italiana](https://www.agenziaentrate.gov.it/portale/web/guest/schede/fatturapa) ma funziona con qualsiasi file P7M.

Il package e' disponibile su [Packagist](https://packagist.org/packages/controlaltjeff/p7m-fattura-decoder) e puo' essere pubblicato anche su altri registri PHP come [Firegento](https://firegento.com/), [Libraries.io](https://libraries.io/), [PHP Packages](https://php.packages.org/) o piattaforme analoghe.

## Indice

- [Installazione](#installazione)
- [Requisiti](#requisiti)
- [Utilizzo rapido](#utilizzo-rapido)
- [API](#api)
- [Come funziona](#come-funziona)
- [Casi d'uso](#casi-duso)
- [Sviluppo](#sviluppo)
- [Contribuire](#contribuire)
- [Licenza](#licenza)

## Installazione

```bash
composer require controlaltjeff/p7m-fattura-decoder
```

## Requisiti

- **PHP 5.3+** (runtime) / PHP 8.1+ (strumenti di sviluppo)
- `ext-dom`, `ext-libxml` (sempre richieste)
- `ext-openssl` (consigliata — usata come strategia di decodifica primaria)
- `openssl` CLI (opzionale — usato per la verifica DER/PEM)

> **Nota per ambienti PHP 5.3/7.x**: gli strumenti di sviluppo (PHPUnit, PHPStan) richiedono PHP 8.1+.
> Installa con `--no-dev` per evitare conflitti:
> ```bash
> composer require controlaltjeff/p7m-fattura-decoder --no-dev
> ```

## Utilizzo rapido

```php
use Controlaltjeff\P7MDecoder\P7MDecoder;

// Decodifica da file
$xml = P7MDecoder::decodeFile('fattura.xml.p7m');

// Decodifica da stringa
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

## API

### `P7MDecoder::decodeFile(string $path): ?string`

Decodifica un file `.p7m` e restituisce il suo contenuto XML, oppure `null` in caso di errore.

```php
$xml = P7MDecoder::decodeFile('/percorso/della/fattura.p7m');
```

### `P7MDecoder::decodeString(string $content): ?string`

Decodifica contenuto P7M da una stringa (DER, PEM, Base64, o XML valido).

```php
$content = file_get_contents('fattura.p7m');
$xml = P7MDecoder::decodeString($content);
```

### `P7MDecoder::getLastCorrections(): array`

Restituisce la lista delle correzioni ai tag applicate durante l'ultimo'operazione di decodifica.

```php
// Dopo una chiamata a decodeFile/decodeString
$corrections = P7MDecoder::getLastCorrections();
// Restituisce: [['from' => 'DettHaglioLinee', 'to' => 'DettaglioLinee', 'distance' => 1], ...]
```

## Come funziona

La libreria tenta la decodifica usando **8 strategie** in ordine, fermandosi al primo successo:

| # | Strategia | Descrizione |
|---|-----------|-------------|
| 1 | Plain XML | Se il contenuto e' gia' XML valido, lo restituisce direttamente |
| 2 | OpenSSL DER | `openssl smime -verify -inform DER` |
| 3 | Base64 → DER | Decodifica Base64 + verifica DER |
| 4 | OpenSSL PEM | `openssl smime -verify -inform PEM` (include conversione Base64 → PEM) |
| 5 | PHP openssl_pkcs7_verify() | Fallback via estensione PHP |
| 6 | Estrazione manuale | Ricerca fragment XML nel binario |
| 7 | Correzione tag | Ricerca fuzzy (Levenshtein) su tag XML danneggiati |
| 8 | Base64 + estrazione manuale | Decodifica Base64 + fragment XML |

### Correzioni automatiche

Oltre alla decodifica, la libreria applica correzioni automatiche su:

- **Tag XML** — Correzione fuzzy con distance ≤ 1 rispetto alla lista tag FatturaPA validi
- **Errori OCR/encoding** — 30+ pattern di correzione per errori tipici di OCR e trasmissione:
  - `UnitaMisurah` → `UnitaMisura`
  - `DettHaglioLinee` → `DettaglioLinee`
  - `Quant2ita` → `Quantita`
  - e molti altri...

Le correzioni applicate sono accessibili via `P7MDecoder::getLastCorrections()`.

## Casi d'uso

### Elaborare file FatturaPA ricevuti dal SDI

```php
use Controlaltjeff\P7MDecoder\P7MDecoder;

// Decodifica un file FatturaPA .p7m ricevuto dal SDI
$xml = P7MDecoder::decodeFile('IT01234567890_FPR12_001.xml.p7m');

if ($xml === null) {
    die('Decodifica fallita');
}

// Analizza l'XML
$dom = new DOMDocument();
$dom->loadXML($xml);

// Accedi ai dati della fattura
$xpath = new DOMXPath($dom);
$numeroFattura = $xpath->query('//Numero')->item(0)->nodeValue;
$importoTotale = $xpath->query('//ImportoTotaleDocumento')->item(0)->nodeValue;
```

### Elaborare piu' file

```php
use Controlaltjeff\P7MDecoder\P7MDecoder;

$files = glob('/percorso/delle/fatture/*.p7m');
foreach ($files as $file) {
    $xml = P7MDecoder::decodeFile($file);
    if ($xml !== null) {
        file_put_contents(str_replace('.p7m', '.xml', $file), $xml);
        echo "Decodificato: " . basename($file) . "\n";
    } else {
        echo "Fallito: " . basename($file) . "\n";
    }
}
```

### Gestire file danneggiati da OCR

```php
use Controlaltjeff\P7MDecoder\P7MDecoder;

$xml = P7MDecoder::decodeFile('fattura-corrotta.p7m');
$corrections = P7MDecoder::getLastCorrections();

if (!empty($corrections)) {
    echo count($corrections) . " correzioni automatiche applicate:\n";
    foreach ($corrections as $fix) {
        echo "  {$fix['from']} → {$fix['to']}\n";
    }
}
```

## Sviluppo

```bash
git clone https://github.com/controlaltjeff/p7m-fattura-decoder.git
cd p7m-fattura-decoder
composer install
composer test       # PHPUnit (24 test)
composer stan       # PHPStan livello 8
composer fix-dry    # PHP-CS-Fixer (dry-run)
composer check      # Test + Stan + CS Fixer
```

Per mutation testing (richiede pcov o xdebug):

```bash
composer require --dev infection/infection:^0.29
composer mutate
```

## Contribuire

I contributi sono ben accetti! Consulta [CONTRIBUTING.md](CONTRIBUTING.md) per le linee guida.

## Licenza

Licenza MIT. Vedi [LICENSE](LICENSE) per i dettagli.

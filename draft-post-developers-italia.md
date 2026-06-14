
Il package e' disponibile su [Packagist](https://packagist.org/packages/controlaltjeff/p7m-fattura-decoder) e puo' essere pubblicato anche su altri registri PHP come [Firegento](https://firegento.com/), [Libraries.io](https://libraries.io/), [PHP Packages](https://php.packages.org/) o piattaforme analoghe.


# P7M Decoder: PHP library open source per decodificare le Fattura Elettroniche PKCS#7

Ciao a tutti,

volevo condividere un package PHP open source che ho creato per risolvere un problema comune nella gestione delle Fatture Elettroniche: la decodifica dei file `.p7m` (PKCS#7 signed) ricevuti dal SDI.

## Il problema

Quando si ricevono le FatturaPA dal Sistema di Interscambio, i file sono firmati digitalmente in formato PKCS#7 (estensione `.p7m`). Per elaborarli, bisogna prima verificarli estrarre l'XML sottostante. Le soluzioni esistenti spesso richiedono configurazioni complesse o non gestiscono i casi reali (file corrotti, encoding errato, errori OCR).

## La soluzione: p7m-fattura-decoder

La libreria tenta la decodifica usando **8 strategie** in ordine, fermandosi al primo successo:

1. **Plain XML** — se il contenuto e' gia' XML valido
2. **OpenSSL DER** — verifica via `openssl smime`
3. **Base64 → DER** — decodifica Base64 + verifica
4. **OpenSSL PEM** — formato PEM
5. **PHP openssl_pkcs7_verify()** — fallback via estensione PHP
6. **Estrazione manuale** — ricerca fragment XML nel binario
7. **Correzione tag** — fuzzy matching (Levenshtein) su tag danneggiati
8. **Base64 + estrazione manuale** — combinazione dei due

In piu', applica **30+ correzioni automatiche** per errori tipici di OCR e trasmissione:

```
DettHaglioLinee → DettaglioLinee
UnitaMisurah → UnitaMisura
Quant2ita → Quantita
```

## Installazione e utilizzo

```bash
composer require controlaltjeff/p7m-fattura-decoder
```

```php
use Controlaltjeff\P7MDecoder\P7MDecoder;

// Decodifica da file
$xml = P7MDecoder::decodeFile('IT01234567890_FPR12_001.xml.p7m');

if ($xml === null) {
    die('Decodifica fallita');
}

// Correzioni applicate
$corrections = P7MDecoder::getLastCorrections();
foreach ($corrections as $fix) {
    echo "Tag corretto: {$fix['from']} → {$fix['to']}\n";
}
```

## Caratteristiche tecniche

- **PHP 5.3+** — compatibile con qualsiasi versione
- **Zero dipendenze** a runtime (solo estensioni PHP standard)
- **PHPStan livello 8** (massima severita')
- **24 test** con fixture DER, PEM, Base64 e casi negativi
- **CI/CD** con GitHub Actions
- **MIT License**

## Dove trovarlo

- **GitHub**: https://github.com/controlaltjeff/p7m-fattura-decoder
- **Packagist**: https://packagist.org/packages/controlaltjeff/p7m-fattura-decoder
- **Docs**: https://controlaltjeff.github.io/p7m-fattura-decoder/

## Feedback

Accetto contributi e suggerimenti! Se avete caso d'uso specifici o file P7M che non vengono decodificati, aprite un issue su GitHub.

Grazie!

# Contribuire a p7m-fattura-decoder

Grazie per il tuo interesse a contribuire!

## Requisiti

- PHP 8.1+ per gli strumenti di sviluppo (test, static analysis)
- Composer
- estensioni PHP: `dom`, `libxml`, `openssl`

## Setup locale

```bash
git clone https://github.com/controlaltjeff/p7m-fattura-decoder.git
cd p7m-fattura-decoder
composer install
```

## Comandi disponibili

| Comando              | Descrizione                          |
| -------------------- | ------------------------------------ |
| `composer test`      | Esegue i test PHPUnit                |
| `composer stan`      | Esegue PHPStan (level 8)             |
| `composer fix`       | Corregge lo code style               |
| `composer fix-dry`   | Verifica lo code style senza modificare |
| `composer check`     | Esegue test + stan + fix-dry         |

## Linee guida

1. Creare un branch dal `main`
2. Scrivere test per le nuove funzionalita
3. Assicurarsi che `composer check` passi
4. Aprire una Pull Request con descrizione chiara

## Style guide

- Seguire PSR-12 / PER-CS2.0
- Usare `array()` sintassi compatibile PHP 5.3
- Non aggiungere type hint nei parametri (compatibilita PHP 5.3)
- Usare annotazioni PHPDoc per i tipi
- Mantenere PHPStan level 8

## Segnalare bug

Aprire una issue con:
- Descrizione del problema
- PHP versione
- Esempio di riproduzione (se possibile)

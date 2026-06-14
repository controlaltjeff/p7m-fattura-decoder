# Politica di Sicurezza

## Versioni supportate

| Versione | Supportato          |
| -------- | ------------------- |
| 1.0.x    | :white_check_mark:  |

## Segnalazione di vulnerabilita

Se scopri una vulnerabilita di sicurezza, **non aprire una issue pubblica**.

Invece, invia una email a `controlaltjeff@gmail.com` con:

- Descrizione della vulnerabilita
- Passi per riprodurla
- Eventuale exploit code
- Versione del package interessata

Risponderemo entro 48 ore e lavoreremo con te per risolvere il problema prima della pubblicazione.

## Note sulla sicurezza

Questo package utilizza `exec()` per chiamare `openssl smime` / `openssl cms`.
I parametri vengono sanitizzati con `escapeshellarg()` per prevenire injection.

Il package non esegue operazioni di rete, non memorizza dati sensibili,
e non ha dipendenze a runtime (solo estensioni PHP standard).

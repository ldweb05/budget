# Prossimo passo

## Stato attuale

Completato:

- collegamento dei movimenti automatici del Salvadanaio al mese (`mese_id`);
- gestione separata di versamenti e prelievi automatici tramite chiave univoca (`mese_id`, `tipo`);
- aggiornamento automatico della quota del Salvadanaio quando cambiano entrata o percentuale;
- modifica dell'entrata mensile;
- modifica della percentuale del Salvadanaio;
- validazione della percentuale (0-100%);
- gestione degli sforamenti con visualizzazione del budget negativo;
- registrazione automatica degli imprevisti nel Salvadanaio;
- versamenti manuali aggiuntivi indipendenti dalla percentuale;
- prelievi manuali dal Salvadanaio;
- isolamento dei dati per utente.

## Prossimo intervento

### Validazione del Budget giornaliero

1. Verificare il comportamento al cambio giorno.
2. Verificare la redistribuzione automatica degli avanzi giornalieri.
3. Verificare la redistribuzione automatica degli sforamenti giornalieri.
4. Verificare che il Salvadanaio venga utilizzato esclusivamente quando il budget mensile complessivo diventa negativo.
5. Confermare la logica con utilizzo reale prima di ulteriori modifiche.

### Prossima evoluzione

Dopo la validazione del Budget giornaliero inizierà lo sviluppo delle **Spese programmate**, dedicate alla gestione di rate e pagamenti futuri pianificati.

## Ambiente operativo

Servizi Docker Compose:

- PHP: `web`
- Database MariaDB: `db`

Container:

- `budget-web`
- `budget-db`

Tutti i comandi devono iniziare con:

cd ~/budget &&

PHP deve essere eseguito esclusivamente nel servizio Docker `web`.

MariaDB deve essere utilizzato esclusivamente nel servizio Docker `db` tramite il client `mariadb`.

Non utilizzare PHP o database installati sul Raspberry.

## Workflow operativo

1. Analisi mirata.
2. Lettura di un solo file.
3. Individuazione della sola modifica necessaria.
4. Patch tramite script Python.
5. Nessuna modifica manuale.
6. Verifica PHP nel container.
7. Test pertinenti.
8. Aggiornamento documentazione.
9. git diff --check
10. git diff
11. git status
12. Richiesta conferma.
13. Commit solo dopo conferma.

## Verifiche finali

- php -l nel container web;
- test funzionali;
- git diff --check;
- git diff;
- git status.

Nota: il progetto non dispone di test automatici.

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
- isolamento dei dati per utente;
- budget giornaliero con redistribuzione degli avanzi e degli sforamenti;
- Spese programmate con piani e singole scadenze;
- generazione automatica delle rate con frequenza in mesi;
- inserimento manuale di scadenze non regolari;
- integrazione delle rate nel budget del mese di competenza;
- pagamento della singola rata;
- modifica di data e importo delle rate non pagate;
- estinzione anticipata delle ultime rate residue;
- eliminazione del piano e delle relative scadenze;
- conservazione della scadenza originaria e della data effettiva di pagamento;
- isolamento multiutente delle Spese programmate.

## Prossimo intervento

### Validazione completa dall'interfaccia delle Spese programmate

1. Creare un piano automatico mensile.
2. Creare un piano manuale con scadenze irregolari.
3. Verificare la visualizzazione delle rate del mese corrente.
4. Verificare l'impatto delle rate sul budget giornaliero.
5. Segnare una rata come pagata.
6. Modificare una rata non pagata.
7. Estinguere anticipatamente le ultime rate residue.
8. Eliminare un piano di prova.
9. Verificare che il Salvadanaio non venga modificato.
10. Verificare che un utente non possa vedere o modificare dati di altri utenti.

### Successivo intervento concreto

Dopo la validazione completa:

- correggere esclusivamente eventuali anomalie emerse;
- eseguire le verifiche finali;
- controllare i diff;
- preparare il commit solo dopo conferma esplicita.

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

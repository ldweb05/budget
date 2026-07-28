# Handover Operativo

## Scopo

Questo documento definisce le regole operative da seguire durante lo sviluppo del progetto Budget.

Il rispetto di queste regole è obbligatorio.

Qualsiasi modifica al progetto deve essere eseguita seguendo il workflow descritto in questo documento.

---

# Workflow operativo obbligatorio

Per ogni modifica:

1. aprire un solo file;
2. leggere esclusivamente quel file;
3. individuare solo la modifica necessaria;
4. preparare una patch tramite script Python;
5. non effettuare modifiche manuali;
6. eseguire lo script;
7. verificare sempre:
   - php -l (all'interno del container Docker quando applicabile);
   - eventuali test interessati;
   - git diff --check;
   - git diff;
8. solo dopo verifica positiva preparare il commit;
9. nessun refactoring;
10. nessuna modifica fuori obiettivo.

---

# Formato delle risposte

Ogni risposta deve sempre riportare:

1. Avanzamento percentuale;
2. Obiettivo del passo corrente;
3. Un solo comando, all'interno di un blocco Markdown.

---

# Regole di sviluppo

- Budget deve rispondere alla domanda: "Quanto posso spendere oggi senza compromettere il resto del mese?";
- il budget giornaliero è il dato principale della Dashboard;
- il Salvadanaio usa la percentuale `percentuale_risparmio` salvata nella tabella `mesi`;
- il risparmio viene calcolato sull'entrata totale;
- le spese fisse sono sempre considerate impegnate;
- le spese variabili riducono il budget disponibile;
- il residuo viene distribuito sui giorni rimanenti del mese;
- una modifica per volta;
- nessun refactoring o intervento non richiesto.

---

# Gestione dei commit

- eseguire il commit solo dopo il completamento delle verifiche obbligatorie;
- includere nel commit esclusivamente i file interessati dalla modifica;
- usare un messaggio breve e descrittivo;
- verificare dopo il commit che il working tree sia pulito.

---

# Gestione delle release

- il repository è gestito esclusivamente in locale;
- non è configurato alcun repository remoto;
- la release corrente è identificata dal tag `v1.2.0-dashboard-v2`;
- il tag punta al commit `5992a65`;
- è disponibile l'archivio locale `budget-v1.2.0-dashboard-v2.tar.gz`.

---

# Stato Dashboard v2

## Stato roadmap

Versione 2.1 completata:

- inserimento spese in meno di 10 secondi;
- Preferiti persistenti;
- ricerca veloce.

Versione 2.2:

- Grafici completati.

Versione 2.3

- Statistiche completate.
- Confronto mensile completato.

Prossimo intervento:

- Analisi annuale.


Sono state completate le seguenti modifiche:

- calcolo del Salvadanaio tramite percentuale configurata nel database;
- calcolo del risparmio sull'entrata totale;
- calcolo del budget disponibile dopo risparmio, spese fisse e spese variabili;
- rimozione del collegamento al Salvadanaio dalla Dashboard;
- maggiore evidenza visiva del budget giornaliero;
- semplificazione del riepilogo;
- autofocus sul campo descrizione della spesa;
- testo del pulsante aggiornato in "Aggiungi Spesa";
- chiarimento che le spese fisse sono già considerate nel budget.

---

# Checklist finale

1. leggere un solo file;
2. applicare la modifica esclusivamente tramite script Python;
3. eseguire `php -l` nel container Docker;
4. eseguire `git diff --check`;
5. eseguire `git diff`;
6. controllare che il diff contenga solo la modifica prevista;
7. creare il commit;
8. verificare che il working tree sia pulito.

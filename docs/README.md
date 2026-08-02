# README

> Documentazione tecnica del progetto Budget.

## Scopo

Questo documento descrive la struttura tecnica e le regole operative verificate del progetto.

Per la filosofia del progetto vedere `start_here.md`.

Per le regole funzionali vedere `functional_spec.md`.

Prima di iniziare qualsiasi sviluppo leggere sempre `prossimo_passo.md`.

---

# Struttura della documentazione

- `start_here.md`: punto di ingresso e filosofia del progetto;
- `vision.md`: visione del prodotto;
- `functional_spec.md`: regole funzionali;
- `ui_guidelines.md`: linee guida dell'interfaccia;
- `roadmap.md`: evoluzione prevista;
- `handover_operativo.md`: indicazioni operative;
- `README.md`: riferimenti tecnici;
- `prossimo_passo.md`: stato corrente, workflow e punto di ripartenza.

---

# Struttura del progetto

Componenti principali verificati:

- `www/index.php`: area utente, dashboard e calcolo del budget personale;
- `www/spese_programmate.php`: gestione di piani, rate, scadenze, pagamenti ed estinzioni anticipate;
- `www/salvadanaio.php`: saldo, storico e movimenti del Salvadanaio;
- `www/admin.php`: gestione amministrativa degli utenti;
- `migrations/006_create_spese_programmate.sql`: creazione delle tabelle dedicate alle Spese programmate;
- `migrations/`: migrazioni del database;
- `docs/`: documentazione ufficiale.

---

# Spese programmate

La funzione utilizza due tabelle dedicate:

- `piani_spese_programmate`, associata direttamente all'utente;
- `scadenze_spese_programmate`, associata al piano.

Le scadenze possono essere generate automaticamente con frequenza in mesi oppure inserite manualmente.

Le rate vengono considerate nel budget del mese di competenza senza essere duplicate in `spese_variabili`.

La competenza viene determinata così:

- mese della scadenza per le rate non pagate;
- mese del pagamento per le rate pagate anticipatamente;
- mese della scadenza originaria per i pagamenti tardivi.

Le operazioni disponibili comprendono:

- creazione del piano;
- modifica di data e importo delle rate non pagate;
- pagamento della singola rata;
- estinzione anticipata delle ultime rate residue;
- eliminazione completa del piano.

Tutte le query verificano l'appartenenza dei dati all'utente autenticato.

---

# Requisiti

Il progetto utilizza Docker Compose.

Servizi verificati:

- applicazione PHP: `web`;
- database MariaDB: `db`.

Container visualizzati da Docker:

- applicazione PHP: `budget-web`;
- database MariaDB: `budget-db`.

---

# Docker

Tutti i comandi devono essere eseguiti dalla directory del progetto e iniziare con:

`cd ~/budget &&`

PHP non deve essere eseguito direttamente sul Raspberry Pi.

Esempio corretto:

`docker compose exec -T web php -l /var/www/html/index.php`

Il database non deve essere interrogato tramite installazioni locali.

Utilizzare esclusivamente il servizio Docker `db` e il client `mariadb`.

Esempio:

`docker compose exec -T db sh -lc 'mariadb -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" "$MYSQL_DATABASE"'`

---

# Installazione

Da completare dopo verifica dell'intero processo di installazione.

---

# Configurazione .env

Da completare dopo verifica delle variabili effettivamente richieste.

---

# Backup e Restore

Da completare dopo verifica delle procedure operative.

---

# Git

Ordine obbligatorio:

1. analisi mirata;
2. modifica minima tramite script Python;
3. verifica PHP nel container;
4. test pertinenti;
5. aggiornamento documentazione;
6. `git diff --check`;
7. `git diff`;
8. `git status`;
9. richiesta di conferma;
10. commit solo dopo conferma esplicita.

Non creare commit automaticamente.

---

# Processo di rilascio

Il processo di rilascio non è ancora formalizzato.

Prima di qualsiasi commit o rilascio verificare sempre `prossimo_passo.md`.

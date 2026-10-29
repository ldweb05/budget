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
- `migrations/008_aggiungi_link_documento_spese_fisse.sql`: colonna `link_documento` della tabella `spese_fisse`;
- `migrations/009_create_spese_scadenza.sql`: tabelle `voci_spese_scadenza` e `rate_spese_scadenza` delle Spese fisse a scadenza;
- `migrations/010_piani_spese_scadenza.sql`: colonna `numero_rate` dei piani e indice non univoco su (`voce_id`, `numero_rata`);
- `migrations/011_create_backup_stato.sql`: tabella `backup_stato` con l'esito dei backup notturni;
- `migrations/`: migrazioni del database;
- `docs/`: documentazione ufficiale.

---

# Spese programmate

La funzione utilizza due tabelle dedicate:

- `piani_spese_programmate`, associata direttamente all'utente;
- `scadenze_spese_programmate`, associata al piano.

Le scadenze possono essere generate automaticamente con frequenza in mesi oppure inserite manualmente.

Le rate sono solo promemoria: non entrano nel calcolo del budget (2026-10-29).

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

# Collegamento documenti alle Spese fisse

La tabella `spese_fisse` contiene la colonna facoltativa `link_documento` (`VARCHAR(1000) NULL`), aggiunta dalla migrazione `008_aggiungi_link_documento_spese_fisse.sql`.

- Il link viene validato in `www/index.php` dalla funzione `normalizza_link_documento()`: sono accettati solo URL `http`/`https` validi e lunghi al massimo 1000 caratteri; un valore vuoto rimuove il link.
- Azioni in `www/index.php`: `add_fissa_nuova` (inserimento con link facoltativo) e `update_link_fissa` (aggiunta, modifica o rimozione del link su una voce esistente, con verifica di `utente_id` tramite join con `mesi`).
- Nessuna integrazione API: viene salvato solo l'URL.
- Per Paperless-ngx usare il link della pagina del documento (`http://<ip-pi>:8000/documents/<id>/details`), non quello dell'API (`/api/documents/<id>/...`), che richiede un token e restituisce `401 Unauthorized` nel browser.

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


# Accesso remoto

Budget non è esposto su Internet.

- Dall'esterno si accede esclusivamente tramite tunnel WireGuard verso la rete di casa.
- Applicazione: `http://192.168.2.215:8085` (servizio `web`).
- phpMyAdmin: `http://192.168.2.215:8083` (servizio `phpmyadmin`).
- Non è necessario alcun port forwarding verso i servizi di Budget.

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

---

# Spese fisse a scadenza

La funzione utilizza due tabelle dedicate, create dalla migrazione `009_create_spese_scadenza.sql`:

- `voci_spese_scadenza` (`utente_id`, `descrizione`, `numero_rate`), i piani di pagamento, associata direttamente all'utente con `ON DELETE CASCADE`; `numero_rate` (totale delle rate) è stato aggiunto dalla migrazione `010_piani_spese_scadenza.sql`;
- `rate_spese_scadenza` (`voce_id`, `numero_rata`, `importo`, `data_scadenza`, `pagata`, `data_pagamento`, `link_documento`), associata alla voce con `ON DELETE CASCADE`.

Tutto il codice è in `www/index.php`:

- azioni: `add_piano_scadenza` (nome + numero di rate), `add_rata_scadenza` (numero, data, importo, link facoltativo, sotto un piano dell'utente), `update_piano_scadenza` (totale rate), `toggle_rata_scadenza` (PAGATO con data di pagamento = oggi, o rimozione), `update_link_rata_scadenza`, `update_rata_scadenza` (numero e piano sempre; importo e data solo se non pagata; il piano di destinazione deve appartenere all'utente), `delete_voce_scadenza`;
- il vincolo univoco su (`voce_id`, `numero_rata`) è stato sostituito da un indice normale, perché il numero è scelto dall'utente e le rate possono essere spostate tra piani;
- funzione `totale_rate_scadenza_pagate()`: somma le rate pagate nel mese (competenza = mese di `data_pagamento`);
- dal 2026-10-29 il totale non viene più sottratto dal budget: le rate sono solo promemoria (la funzione resta per la visualizzazione);
- visualizzazione: sezione "Spese Fisse (NON considerate nel budget)" nella card Spese Fisse (rate in scadenza o pagate nel mese) e card "🗓️ Piani a scadenza (NON nel budget)" nella dashboard.
- l'elenco "Gestisci piani e rate" è ordinato con i piani senza rate in alto, poi per prossima scadenza da pagare, infine i piani saldati; dentro ogni piano per data di scadenza;
- la dashboard mostra "Rata N/Totale" con `COALESCE(numero_rate, numero di rate inserite)`;
- piani collassabili tramite la funzione JS `apriChiudiPiano()`; lo stato aperto è salvato in `localStorage` (`piani_aperti`) con try/catch, solo come comodità del browser;
- filtro testuale lato client sul campo `#ricerca-piani` (tutte le parole devono comparire nel nome del piano, attributo `data-nome`).

Ogni query verifica l'appartenenza dei dati all'utente tramite `voci_spese_scadenza.utente_id`. I link sono validati con `normalizza_link_documento()`.

---

# Disposizione della dashboard e pagine collegate

- `www/index.php`: contenitore `flex flex-col` su telefono e `md:grid md:grid-cols-2` su computer; le due colonne usano `contents md:block`, così su telefono le card seguono le classi `order-1`…`order-6` e su computer restano in due colonne indipendenti senza spazi vuoti.
- Il Pannello Gestione Mese è stato eliminato: "Gestisci Spese Fisse" e "Piani a scadenza" sono card autonome.
- Cambia Password: elemento `<dialog id="dialog-password">` aperto dal pulsante 🔑 del menu, con campo "Ripeti password" (`conferma_password`) e icona 👁️; `update_password` salva solo se le due password coincidono (controllo anche lato client) e mostra l'esito con `?password=aggiornata` o `?password=diverse`.
- Entrata e percentuale: il modulo è in `www/salvadanaio.php` e invia all'azione `update_budget` di `www/index.php` (nessuna logica duplicata) con il campo `torna=salvadanaio`, che fa tornare al Salvadanaio dopo il salvataggio.
- Il link 🐷 della dashboard passa `mese` e `anno` a `salvadanaio.php`, che li valida (altrimenti usa il mese corrente), mostra la percentuale reale nel titolo e riporta 🏠 Home alla dashboard dello stesso mese.

---

# Backup del database

- Script: `/home/lorenzo/budget-backup.sh`, fuori dal repository (riguarda il Raspberry, non il codice). Avviato da crontab ogni notte alle 03:30 (`30 3 * * *`), log in `~/backups/logs/`.
- Contenuto dell'archivio: dump completo di `budget_db` (`--single-transaction --routines --triggers --events`), `.env`, `docker-compose.yml`, istruzioni di ripristino e checksum SHA256.
- Destinazione: disco del router `root@192.168.2.1:/mnt/backup/backup_budget/` via SSH con chiave `~/.ssh/backup_key`; checksum verificato anche sul router; conservati gli ultimi 30 archivi (`REMOTE_KEEP=30`); sul Raspberry resta solo il log.
- Esito: la funzione `registra_esito` dello script scrive in `backup_stato` una riga `ok` a fine backup oppure, tramite il trap `EXIT`, una riga `errore` con la fase (0-9) e l'ultima riga di errore del log; le righe più vecchie di 180 giorni vengono eliminate. `backup_stato` è una tabella di sistema senza `utente_id` perché non contiene dati finanziari.
- Dashboard (`www/index.php`): avviso rosso se l'ultima riga è un errore, avviso giallo se non c'è un `ok` nelle ultime 48 ore (con l'ultimo avvio del Raspberry letto da `/proc/uptime`), altrimenti solo la riga in fondo alla pagina. Le query sono in un `try/catch`: se la tabella manca la dashboard funziona comunque.
- Ripristino: istruzioni in `README_RIPRISTINO.md` dentro ogni archivio. Il ripristino è stato provato il 2026-10-24 caricando il dump in un container MariaDB temporaneo: tutte le tabelle coincidevano con il database attivo.
- Prima di ogni modifica i punti di ripristino manuali vanno in `~/backups/budget/` con `umask 077` (file `600`), sullo stesso disco del Raspberry: non sostituiscono il backup sul router.

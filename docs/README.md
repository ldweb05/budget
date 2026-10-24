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
- il totale viene sottratto in tutti e tre i punti di calcolo del budget: dashboard, riporto dell'avanzo dal mese precedente e controllo degli imprevisti all'inserimento di una spesa;
- visualizzazione: sezione "Spese Fisse (NON considerate nel budget)" nella card Spese Fisse (rate in scadenza o pagate nel mese) e riquadro di gestione nel Pannello Gestione Mese.
- l'elenco "Gestisci piani e rate" è ordinato con i piani senza rate in alto, poi per prossima scadenza da pagare, infine i piani saldati; dentro ogni piano per data di scadenza;
- la dashboard mostra "Rata N/Totale" con `COALESCE(numero_rate, numero di rate inserite)`;
- piani collassabili tramite la funzione JS `apriChiudiPiano()`; lo stato aperto è salvato in `localStorage` (`piani_aperti`) con try/catch, solo come comodità del browser;
- filtro testuale lato client sul campo `#ricerca-piani` (tutte le parole devono comparire nel nome del piano, attributo `data-nome`).

Ogni query verifica l'appartenenza dei dati all'utente tramite `voci_spese_scadenza.utente_id`. I link sono validati con `normalizza_link_documento()`.

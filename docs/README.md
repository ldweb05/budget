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

- `www/index.php`: area utente e gestione del budget personale;
- `www/salvadanaio.php`: saldo, storico e movimenti del Salvadanaio;
- `www/admin.php`: gestione amministrativa degli utenti;
- `migrations/`: migrazioni del database;
- `docs/`: documentazione ufficiale.

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

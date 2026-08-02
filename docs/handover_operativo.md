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
- le rate programmate del mese sono considerate importi già impegnati;
- le rate programmate non vengono duplicate tra le spese variabili;
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

Gestione utenti e autenticazione tramite database completata:

- creata la tabella `utenti`;
- password memorizzate esclusivamente tramite `password_hash()`;
- credenziali verificate tramite `password_verify()`;
- introdotti i ruoli `admin` e `user`;
- cambio password dell'utente autenticato;
- gestione amministrativa di utenti, ruoli e stato attivo;
- autenticazione migrata dal file `.env` al database;
- eliminata la modifica del file `.env` dalla dashboard;
- aggiunta la funzionalità di logout.

Separazione area amministrativa e area utente completata:

- creata `admin.php` come pagina dedicata esclusivamente alla gestione utenti;
- reindirizzati gli utenti con ruolo `admin` da `login.php` verso `admin.php`;
- mantenuto `index.php` riservato agli utenti con ruolo `user`;
- impedito agli amministratori di visualizzare mesi, spese, grafici e Salvadanaio;
- limitata l'area admin a elenco utenti, creazione, modifica, attivazione, disattivazione, eliminazione, cambio password e logout;
- protetta `admin.php` consentendo l'accesso esclusivamente agli amministratori;
- protetta la dashboard finanziaria impedendo l'accesso agli amministratori;
- verificate sintassi, redirect e separazione delle autorizzazioni.

Isolamento completo dei dati finanziari completato:

- ogni tabella finanziaria è associata all'utente proprietario;
- tutte le query devono filtrare tramite `utente_id` oppure tramite join con `mesi` e verifica di `utente_id`;
- l'eliminazione di un utente elimina automaticamente tutti i suoi dati finanziari (`ON DELETE CASCADE`);
- la ricreazione dello stesso username non deve mai rendere visibili dati precedenti.

Spese programmate completate a livello applicativo e database:

- creata la migrazione `006_create_spese_programmate.sql`;
- create le tabelle `piani_spese_programmate` e `scadenze_spese_programmate`;
- aggiunta la pagina `www/spese_programmate.php`;
- supportate scadenze automatiche con frequenza in mesi;
- supportate scadenze manuali con date non regolari;
- aggiunta la visualizzazione delle rate del mese corrente e future;
- integrate le rate nel budget del mese di competenza;
- aggiunto il pagamento della singola rata;
- aggiunta la modifica di data e importo delle rate non pagate;
- aggiunta l'estinzione anticipata delle ultime rate residue;
- aggiunta l'eliminazione del piano e delle relative scadenze;
- mantenute la data di scadenza originaria e la data effettiva di pagamento;
- mantenuto l'isolamento completo per utente;
- mantenuta invariata la logica del Salvadanaio.

Regola di competenza delle rate:

- rata non pagata: mese della scadenza;
- rata pagata anticipatamente: mese del pagamento;
- rata pagata in ritardo: mese della scadenza originaria.

Prossimo intervento:

- eseguire la validazione completa dall'interfaccia;
- correggere esclusivamente eventuali anomalie;
- completare le verifiche finali;
- richiedere conferma prima del commit.


Sono state completate le seguenti modifiche:
- miglioramento responsive per smartphone e tablet;
- aggiunta del versamento automatico nel Salvadanaio durante la creazione di un nuovo mese;
- calcolo della quota iniziale usando l'entrata e la percentuale effettivamente salvate nella tabella `mesi`;
- collegamento della quota automatica al mese tramite `fondo_risparmio.mese_id`;
- aggiornamento della quota mensile senza duplicati tramite vincolo univoco su `mese_id`;
- aggiunta della migrazione `004_collega_salvadanaio_ai_mesi.sql`.

- calcolo del Salvadanaio tramite percentuale configurata nel database;
- calcolo del risparmio sull'entrata totale;
- calcolo del budget disponibile dopo risparmio, spese fisse e spese variabili;
- ripristino del collegamento al Salvadanaio nella Dashboard;
- aggiunta del form per il cambio password, temporaneamente basato sul file `.env`;
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
## Percorso repository locale

Il repository locale del progetto Budget v2 si trova in:

/home/lorenzo/budget

Prima di iniziare qualsiasi attività verificare di trovarsi nella directory corretta:

cd /home/lorenzo/budget
git status

Il workflow operativo deve essere eseguito esclusivamente all'interno di questo repository.

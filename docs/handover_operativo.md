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
- Spese programmate e Spese fisse a scadenza sono solo promemoria e non toccano alcun calcolo;
- gli sforamenti giornalieri passano interi al giorno successivo (conto giornaliero puro);
- gli avanzi giornalieri vengono trasferiti integralmente al giorno immediatamente successivo;
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

- repository remoto: `github.com/ldweb05/budget` (pubblico), remote `git@github.com:ldweb05/budget.git`;
- branch di lavoro e predefinito: `master`; commit diretti, senza pull request;
- essendo il repository pubblico, non devono mai essere committati `.env`, dump SQL, backup o file con dati personali;
- tag di release (solo in locale): `v1.1.0-env`, `v1.1.1`, `v1.2.0-dashboard-v2` (`5992a65`), `v3.0.0` (`5a9927c`, il più recente);
- tag di checkpoint (solo in locale): `checkpoint-salvadanaio`, `checkpoint-password-ui`, `stato-multiutente-completato`, `checkpoint-pre-link-documenti`; su GitHub è presente solo `checkpoint-pre-avanzo-mensile`;
- archivi locali `budget-v1.1.0-env.tar.gz` e `budget-v1.2.0-dashboard-v2.tar.gz`, esclusi da Git.

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

---

# 2026-10-23 — Collegamento documenti alle Spese fisse

## Cosa è stato fatto

- Aggiunta la possibilità di collegare ogni spesa fissa a un documento esterno (Paperless-ngx o Google Drive) tramite semplice link.
- La descrizione della voce diventa cliccabile, in colore orangered con sottolineatura tratteggiata, solo se esiste un link; il documento si apre in una nuova scheda.
- Nel pannello "Gestisci Spese Esistenti" (ex "Elimina Spese Esistenti") ogni voce ha l'azione "Link" / "Modifica link", che mostra un campo per salvare, cambiare o rimuovere il link (campo vuoto = rimozione).
- Il form "Aggiungi Spese Fisse" ha un campo facoltativo "Link documento".

## File toccati

- `migrations/008_aggiungi_link_documento_spese_fisse.sql` (nuovo): colonna `spese_fisse.link_documento VARCHAR(1000) NULL`.
- `www/index.php`: funzione `normalizza_link_documento()`, azione `update_link_fissa`, inserimento con link in `add_fissa_nuova`, visualizzazione del link nell'elenco e nel pannello di gestione.

## Decisioni tecniche

- Solo URL salvato, nessuna integrazione con le API di Paperless o Google: scelta dell'utente per semplicità.
- Un solo link per voce: evita una tabella separata.
- Il link non viene riportato al mese successivo: ogni mese ha il proprio documento (es. bolletta del mese). Il nuovo mese crea comunque solo le voci predefinite Condominio e Telefono, senza copiare quelle del mese precedente.
- Accettati solo URL `http`/`https` validati con `parse_url` e `FILTER_VALIDATE_URL`, per impedire link `javascript:`; output sempre con `htmlspecialchars`; link aperti con `rel="noopener noreferrer"`.
- Query di aggiornamento filtrata per `utente_id` tramite join con `mesi`, come le altre azioni sulle spese fisse.

## Problemi incontrati

- Un link copiato dall'anteprima di Paperless (`/api/documents/<id>/preview/`) restituisce `401 Unauthorized`, perché l'API richiede un token. Va usato il link della pagina del documento (`/documents/<id>/details`).

## Attività collaterali della stessa sessione

- Punto di ripristino in `~/backups/budget/` (dump database e archivio file del 2026-10-23 10:16) e tag `checkpoint-pre-link-documenti`.
- Pulizia del repository: rimossi `www/index.php.old` (commit), `www/index.php.bak_prima_avanzo_v2` e `backup_pre_avanzo_mensile.sql` (non tracciati, conservati nel punto di ripristino).
- Repository GitHub `ldweb05/budget`: pubblicato `master` come branch predefinito; eliminato il vecchio `main`, che conteneva solo il README iniziale.

---

# 2026-10-23 — Spese fisse a scadenza

## Cosa è stato fatto

- Nuove voci di spesa fissa con cadenza non mensile (es. rata F24): ogni voce ha un importo e una data di scadenza reale per ogni rata, inserita manualmente.
- Nella card Spese Fisse della dashboard è stata aggiunta la sezione "Spese Fisse (NON considerate nel budget)": mostra le rate in scadenza nel mese (e quelle pagate nel mese), con checkbox PAGATO, numero rata, data di scadenza, data di pagamento e totale "Ancora da pagare".
- La rata viene scalata dal budget solo quando viene spuntata PAGATO, nel mese del pagamento.
- Nel Pannello Gestione Mese il riquadro "Spese Fisse a scadenza (NON nel budget)" permette di creare la voce, collegare un documento a ogni rata (anche pagata), modificare importo e data delle rate non pagate ed eliminare la voce.

## File toccati

- `migrations/009_create_spese_scadenza.sql` (nuovo): tabelle `voci_spese_scadenza` e `rate_spese_scadenza`.
- `www/index.php`: azioni, funzione `totale_rate_scadenza_pagate()`, sottrazione nei tre punti di calcolo del budget, sezione della dashboard e riquadro di gestione.

## Decisioni tecniche

- Tabelle dedicate invece di estendere le Spese programmate: l'utente considera le programmate (Amazon, PayPal, Scalapay) poco rilevanti e consultabili dal pulsante "Programmate", mentre queste devono restare in vista in dashboard. Un primo tentativo con un'opzione "scala solo quando pagata" sui piani programmati è stato annullato prima del commit (colonne rimosse dal database).
- Date sempre inserite a mano: con cadenze come 120 giorni la data reale non è calcolabile in modo affidabile.
- Competenza = mese del pagamento, anche per i pagamenti in ritardo (diverso dalle Spese programmate).
- Le rate non pagate e scadute restano visibili solo nel mese della scadenza, come richiesto; l'eventuale visualizzazione anche nei mesi successivi è un possibile miglioramento futuro.
- Conferma JavaScript sulla checkbox PAGATO, perché cambia il budget.
- Patch della parte grafica trasferita compressa in base64 e verificata con `md5sum` tra sandbox e Pi.

## Verifiche

- Test preventivo nel sandbox (PHP + MariaDB): creazione, PAGATO e rimozione, link (rifiuto di URL non http/https), modifica solo su rate non pagate, eliminazione, isolamento tra utenti, calcolo del budget e riporto dell'avanzo al mese successivo.
- Test funzionale reale nel browser da parte dell'utente: creazione voce, scadenza, link e calcolo.
- Punto di ripristino: dump e archivio del 2026-10-23 12:34 in `~/backups/budget/`, tag `checkpoint-pre-spese-scadenza`.

---

# 2026-10-23 — Ordinamento delle Spese fisse a scadenza

- Corretto l'ordinamento dell'elenco "Gestisci voci e rate" nel Pannello Gestione Mese, prima alfabetico per descrizione: ora le voci sono ordinate per prossima scadenza da pagare, quelle con tutte le rate pagate vanno in fondo e le rate di ogni voce sono in ordine di data.
- File toccato: `www/index.php` (solo la query dell'elenco; calcoli del budget e card della dashboard invariati).
- Punto di ripristino: tag `checkpoint-pre-ordine-scadenze` e backup del 2026-10-23 17:32 in `~/backups/budget/`.

---

# 2026-10-24 — Piani di pagamento per le Spese fisse a scadenza

## Cosa è stato fatto

- Le Spese fisse a scadenza sono ora organizzate in piani di pagamento: si crea il piano (nome + numero totale di rate) e poi si aggiungono le rate una alla volta sotto il piano (numero, data di scadenza, importo, link facoltativo).
- Il numero della rata è quello reale ("Rata 3/5"), proposto come successivo all'ultimo ma modificabile; anche le rate esistenti possono essere rinumerate e spostate in un altro piano.
- Nel Pannello Gestione Mese i piani sono collassabili (chiusi di default, stato aperto ricordato dal browser) e filtrabili con un campo "Cerca".
- La dashboard mostra "Rata N/Totale" usando il totale del piano.

## File toccati

- `migrations/010_piani_spese_scadenza.sql` (nuovo): colonna `voci_spese_scadenza.numero_rate`; indice univoco (`voce_id`, `numero_rata`) sostituito da indice normale.
- `www/index.php`: azioni `add_piano_scadenza`, `add_rata_scadenza`, `update_piano_scadenza`, `update_rata_scadenza` estesa (rimossa la vecchia `add_voce_scadenza`), riquadro "Gestisci piani e rate", funzione `apriChiudiPiano()`, "Rata N/Totale" in dashboard.

## Decisioni tecniche

- Nessuna nuova tabella: le "voci" diventano piani aggiungendo il totale delle rate, così calcolo del budget, PAGATO e link restano invariati.
- Indice univoco rimosso: con numero scelto dall'utente e spostamento tra piani avrebbe fatto fallire gli aggiornamenti senza avvisi; un eventuale doppione resta visibile e correggibile.
- Stato aperto/chiuso dei piani in `localStorage` con try/catch: è solo una comodità del singolo browser, la pagina funziona anche senza.

## Problemi incontrati

- Inizialmente ogni rata era stata creata come voce separata (sempre "Rata 1"): la nuova struttura a piani risolve il problema.
- Su richiesta dell'utente tutte le voci di prova (9 voci, nessuna rata pagata) sono state eliminate dal database per ricrearle con la nuova struttura; il primo piano ricreato ("Rottamazione 2023", 5 rate con link) è stato verificato.
- Script lunghi incollati nel terminale si troncavano: i trasferimenti in base64 vengono ora divisi in pezzi da circa 1000 caratteri, con controllo `md5sum` del file ricomposto.

## Punto di ripristino

- Tag `checkpoint-pre-piani-scadenza` e backup del 2026-10-24 08:20 in `~/backups/budget/`.

---

# 2026-10-24 — Riorganizzazione della dashboard

## Cosa è stato fatto

- Eliminato il Pannello Gestione Mese: "⚙️ Gestisci Spese Fisse" e "🗓️ Piani a scadenza (NON nel budget)" sono ora card autonome come le altre.
- Nuova disposizione: su computer due colonne indipendenti (sinistra budget, Aggiungi Spesa, Gestisci Spese Fisse, Piani; destra Spese Fisse con le scadenze, Ultime Spese Varie); su telefono una colonna con budget e Spese Fisse per primi.
- "Cambia Password" spostato in un popup aperto dal pulsante 🔑 del menu; il menu va a capo sugli schermi stretti.
- "Modifica Entrata e Salvadanaio" spostato nella pagina Salvadanaio, riferito al mese aperto nella dashboard; il titolo della pagina mostra la percentuale reale al posto di "(15%)" fisso.

## File toccati

- `www/index.php`: menu, `<dialog>` password, nuova disposizione delle card, link 🐷 con mese e anno, ritorno al Salvadanaio nell'azione `update_budget` (campo `torna`).
- `www/salvadanaio.php`: mese di riferimento validato, riquadro "Entrata e Salvadanaio", titolo con la percentuale, 🏠 Home verso il mese corretto.

## Decisioni tecniche

- Due colonne indipendenti invece di un layout "a mattoncini" automatico: aprendo o chiudendo un piano le card non cambiano colonna.
- Ordine su telefono ottenuto con `contents` + `order-N` sullo stesso HTML, senza duplicare markup.
- Popup con l'elemento nativo `<dialog>` (Esc, clic fuori e Annulla per chiudere), più adatto al telefono di un tooltip.
- Il modulo del Salvadanaio riusa l'azione `update_budget` di `index.php` per non duplicare la sincronizzazione del versamento automatico.

## Verifiche

- Test nel sandbox con Tailwind compilato localmente: screenshot a larghezza computer e telefono, apertura e chiusura del popup, salvataggio di entrata e percentuale con ritorno al Salvadanaio e versamento automatico aggiornato.
- Test funzionale reale nel browser da parte dell'utente.
- Punto di ripristino: tag `checkpoint-pre-riorganizzazione` e backup del 2026-10-24 09:13 in `~/backups/budget/`.

---

# 2026-10-24 — Ripeti password nel popup

- Aggiunti al popup "Cambia Password" il campo "Ripeti password" e l'icona 👁️ / 🙈 su entrambi i campi, come nella pagina di login.
- La password viene salvata solo se i due campi coincidono: controllo nel browser (messaggio in rosso, nessun invio) e nell'azione `update_password`; dopo il salvataggio compare "✅ Password aggiornata.".
- File toccato: `www/index.php`. Punto di ripristino: tag `checkpoint-pre-ripeti-password`.

---

# 2026-10-24 — Backup del database e avviso in dashboard

## Situazione trovata

- Esisteva già `~/budget-backup.sh` (crontab, domenica alle 03:30) che copia dump + `.env` sul disco del router con verifica dei checksum; funzionante dal 9 agosto, ma conservava solo 3 archivi e non segnalava gli errori.

## Cosa è stato fatto

- Backup portato a ogni notte alle 03:30 e storico remoto da 3 a 30 archivi (copie di script e crontab in `~/backups/`).
- Prova di ripristino dell'ultimo backup in un container MariaDB temporaneo senza volumi né porte: tutte le 10 tabelle con lo stesso numero di righe del database attivo.
- Nuova tabella `backup_stato` (migrazione 011); lo script registra `ok` a fine backup o `errore` con fase e dettaglio (trap `EXIT`, che intercetta anche gli `exit 1` dei controlli iniziali).
- Dashboard: avviso rosso o giallo in alto solo in caso di problema, riga "Ultimo backup del database" in fondo alla pagina.
- Su telefono rimosso lo scorrimento automatico verso "Aggiungi Spesa" causato da `autofocus`: il cursore va nel campo solo su schermi larghi.
- Nomi dei piani a scadenza in orangered, verde oliva chiaro al passaggio del mouse.
- Sicurezza: `.env` a `600`; punti di ripristino, cartella `~/backups` e log a `600`/`700`; eliminati un backup incompleto del 4 agosto, un residuo del 9 ottobre e una copia del vecchio `.env` con la configurazione Caddy/DuckDNS dismessa.

## File toccati

- Repository: `migrations/011_create_backup_stato.sql`, `www/index.php`.
- Raspberry (fuori dal repository): `~/budget-backup.sh`, crontab dell'utente `lorenzo`.

## Verifiche

- Errore simulato con un host inesistente: registrato `errore`, fase 7, "No route to host"; backup reale successivo registrato `ok`.
- Avviso rosso mostrato con una riga di prova inserita e poi rimossa.
- Punto di ripristino: tag `checkpoint-pre-avviso-backup`, backup del 2026-10-24 09:53 e copia dello script `~/backups/budget-backup.sh.bak-20261024-095306`.

# 2026-10-29 — Conto giornaliero puro e rate solo promemoria

## Problema

- "Oggi puoi spendere" restava fermo a -28 € da giorni: quando il saldo del mese era negativo la dashboard mostrava il saldo mensile invece del conto del giorno.
- Il saldo era negativo per errore: Spese programmate e Spese fisse a scadenza pagate venivano scalate dal budget, mentre per l'utente sono solo promemoria.
- Il prelievo automatico "Imprevisti" ignorava l'avanzo del mese precedente e ha registrato a ottobre 114,77 € non dovuti.

## Cosa è stato fatto (`www/index.php`)

- Budget giornaliero = quota giornaliera × giorno del mese + avanzo del mese precedente − spese varie fino a oggi; quota = (entrata − Salvadanaio − spese fisse) ÷ giorni del mese. Rimossa la redistribuzione degli sforamenti.
- Rate programmate e rate a scadenza pagate tolte dai tre calcoli: dashboard, riporto dell'avanzo al mese successivo, prelievo Imprevisti.
- Il prelievo Imprevisti ora considera anche l'avanzo del mese precedente (stesso saldo di "Disponibilità rimasta").
- Dati: cancellato il prelievo "Imprevisti - Ottobre 2026" (id 10, 114,77 €). Ottobre dopo la correzione: quota 12 €/giorno, saldo del mese +115,37 €, oggi +91,37 €.

## Decisioni

- Le spese fisse normali (es. Condominio, Telefono, Gas) restano nel calcolo.
- Avanzi positivi di fine mese riportati al primo giorno del mese successivo; sforamenti coperti dal Salvadanaio (comportamento già esistente).
- `avanzo_riportato` dei mesi passati non ricalcolato.

## Verifiche

- Test nel sandbox, `php -l` nel container, test nel browser dell'utente (cifre attese, spesa di prova da 1 € senza prelievo Imprevisti).
- Punto di ripristino: tag `checkpoint-pre-conto-giornaliero`, dump del 2026-10-29 10:11 in `~/backups/budget/`.

# 2026-10-29 — Revisione dei calcoli del budget

## Problemi trovati

- Prelievo "Imprevisti" calcolato solo all'inserimento di una spesa: non si riduceva cancellando una spesa e non partiva se il mese andava in rosso per altri motivi.
- Con percentuale 0% l'azione `update_budget` cancellava tutti i movimenti del mese nel Salvadanaio (anche il prelievo Imprevisti).
- Avanzo del mese precedente salvato una sola volta alla creazione del mese: le correzioni successive al mese precedente non venivano riportate.
- `statistiche.php` non filtrava per `utente_id` (mesi di tutti gli utenti) e non contava l'avanzo del mese precedente.
- Mese nuovo creato con entrata 900 € e 15% fissi.

## Cosa è stato fatto

- Nuovo `www/budget_calcoli.php`: `budget_riepilogo_mese()`, `budget_saldo_mese()`, `budget_avanzo_mese()` (avanzo calcolato al momento da novembre 2026), `budget_sincronizza_imprevisti()` (da ottobre 2026).
- `www/index.php`: avanzo spalmato sul mese da novembre 2026 (ottobre invariato, scelta dell'utente); Imprevisti sincronizzati a ogni caricamento della dashboard e dopo `update_budget`; rimosso il vecchio prelievo incrementale in `add_variabile`; `update_budget` con 0% cancella solo il versamento automatico; mese nuovo con entrata 0 e percentuale del mese precedente; avviso "Inserisci l'entrata".
- `www/statistiche.php`: tutte le query filtrate per utente e con prepared statement; stesso calcolo della dashboard.

## Decisioni dell'utente

- Avanzo positivo sommato al budget del mese e diviso per i giorni; Salvadanaio calcolato solo sull'entrata.
- Mattina: "oggi puoi spendere" include già la quota del giorno.
- Spese fisse del mese nuovo: solo Condominio e Telefono (le utenze variabili vanno tra le spese varie).
- Entrata variabile: mese nuovo a 0 €, da inserire dalla pagina Salvadanaio.

## Verifiche

- Sandbox con MariaDB e dati di ottobre: ottobre 91,37 € / 115,37 €; novembre 670 € → 640,37 € (21,35 €/giorno); correzione tardiva di ottobre riportata; prelievo che compare e sparisce; 0% senza perdita del prelievo; altro utente mai visibile.
- Test nel browser dell'utente su ottobre: dashboard, Statistiche, spesa di prova da 200 € con prelievo 84,63 € poi annullato.
- Punto di ripristino: tag `checkpoint-pre-revisione-calcoli`, dump e archivio del 2026-10-29 11:31 in `~/backups/budget/`.

# 2026-10-29 — Entrate extra

## Cosa è stato fatto

- Migrazione `012_create_entrate_extra.sql`: tabella `entrate_extra` e colonna `fondo_risparmio.entrata_extra_id` (univoca, FK con `ON DELETE CASCADE`).
- `www/budget_calcoli.php`: `budget_extra_mese()`, `budget_totale_extra()`, `budget_quota_extra_fino_a()`, `budget_sincronizza_extra()`; l'extra netto entra in `budget_riepilogo_mese()` (saldo, avanzo, Imprevisti, Statistiche).
- `www/index.php`: extra netto sommato alla disponibilità del mese e spalmato dal giorno di inserimento a fine mese in "Oggi puoi spendere"; riga "Entrate extra del mese"; versamenti degli extra ricalcolati in `update_budget`.
- `www/salvadanaio.php`: card "Entrate extra" con inserimento, elenco ed eliminazione (azioni `add_entrata_extra`, `delete_entrata_extra`, con verifica di `utente_id`).

## Decisioni dell'utente

- Percentuale del Salvadanaio applicata anche all'extra (290 € al 10%: 29 € al Salvadanaio, 261 € spalmati).
- Inserimento dalla pagina Salvadanaio; data sempre uguale al giorno di inserimento (per correggere si elimina e si reinserisce).

## Note tecniche

- Il versamento dell'extra ha `mese_id` NULL (il vincolo univoco `mese_id`+`tipo` è già usato dal versamento automatico del mese) ed è collegato all'extra tramite `entrata_extra_id`.

## Verifiche

- Sandbox: migrazione eseguita due volte senza errori; 290 € il 29/10 → +29 € al Salvadanaio, oggi 178,37 €, disponibilità 376,37 €; spalmatura dal giorno 15 su 30 giorni (16,31 € al giorno); cambio percentuale; eliminazione con cascata; extra di un altro utente non eliminabile.
- Test nel browser dell'utente: inserimento, dashboard, Statistiche, eliminazione.
- Punto di ripristino: tag `checkpoint-pre-entrate-extra`, dump e archivio del 2026-10-29 19:12 in `~/backups/budget/`.

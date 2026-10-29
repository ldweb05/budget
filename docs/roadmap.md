# Roadmap - Budget

## Visione

L'obiettivo del progetto non è diventare un software di contabilità.

L'obiettivo è aiutare l'utente a sapere, ogni giorno, quanto può spendere senza compromettere il resto del mese.

---

# Versione 2.0

## Nuova filosofia

- Budget giornaliero come dato principale.
- Saldo del conto in secondo piano.
- Salvadanaio automatico.
- Spese fisse permanenti.
- Ricalcolo automatico del budget giornaliero.

---

## Funzionalità principali

- Percentuale configurabile per il Salvadanaio.
- Pulsante Salvadanaio disponibile nella dashboard.
- Budget giornaliero ricalcolato automaticamente.
- Trasferimento integrale degli avanzi al giorno immediatamente successivo.
- Redistribuzione automatica degli sforamenti sui giorni rimanenti.
- Dashboard completamente riprogettata.
- Possibiltà dell'utente di modificare la somma mensile e la oercentuale destinata al salvadanaio
- Se durante il mese si supera la spesa destinata al mese, la quota giornalera diventa di colore rosso e compare il segno "-" e quella cifra la deve scalare automaticamente dal salvadanaio

---

# Versione 2.1

- Inserimento spese in meno di 10 secondi.
- ✅ Preferiti.
- ✅ Ultime operazioni.
- ✅ Ricerca veloce.

---

# Versione 2.2

- ✅ Grafici.
- ✅ Statistiche.
- ✅ Confronto mensile.
- ✅ Analisi annuale.

---

# Versione 3.0

## Gestione utenti e autenticazione

Completato:

- ✅ Tabella `utenti` nel database.
- ✅ Password memorizzate con `password_hash()`.
- ✅ Autenticazione tramite `password_verify()`.
- ✅ Ruoli `admin` e `user`.
- ✅ Cambio password dell'utente autenticato.
- ✅ Gestione utenti da parte dell'amministratore.
- ✅ Eliminazione della dipendenza dal file `.env` per il login.
- ✅ Logout dell'utente autenticato.
- ✅ Possibilità di visualizzare/nascondere la password nella schermata di login.

## Separazione area admin e area utente

Completato:

- ✅ Creata `admin.php` come dashboard amministrativa dedicata.
- ✅ Reindirizzati gli amministratori da `login.php` verso `admin.php`.
- ✅ Riservato `index.php` agli utenti con ruolo `user`.
- ✅ Impedito agli amministratori l'accesso ai dati finanziari.
- ✅ Limitata l'area admin alla gestione degli utenti.
- ✅ Consentite creazione, modifica, attivazione, disattivazione ed eliminazione utenti.
- ✅ Protetta `admin.php` consentendo l'accesso esclusivamente agli amministratori.
- ✅ Protetta la dashboard finanziaria impedendo l'accesso agli amministratori.
- ✅ Verificati sintassi, redirect e separazione delle autorizzazioni.

## Isolamento dati multiutente

Completato:

- ✅ Tutti i dati finanziari sono isolati per utente.
- ✅ Ogni utente vede esclusivamente i propri mesi, spese, preferiti e Salvadanaio.
- ✅ Eliminando un utente vengono eliminati automaticamente tutti i suoi dati finanziari (ON DELETE CASCADE).
- ✅ La ricreazione di un utente con lo stesso username genera un ambiente completamente vuoto.
- ✅ Miglioramento responsive per utilizzo da smartphone e tablet.


---

## Funzionalità future

- Backup dall'interfaccia.
- Esportazione PDF.
- Esportazione Excel.
- Esportazione CSV.
- PWA.
- Import movimenti bancari.

---

La roadmap è un documento vivo e potrà essere aggiornata nel tempo mantenendo la filosofia del progetto.

---

# Spese programmate

## Stato

Implementazione completata a livello applicativo e database.

Sono disponibili:

- piani associati all'utente autenticato;
- scadenze automatiche con frequenza espressa in mesi;
- scadenze manuali con date non regolari;
- visualizzazione delle rate del mese corrente;
- visualizzazione delle rate future;
- rate solo come promemoria, escluse dal budget (2026-10-29);
- prevenzione dei duplicati tramite vincolo univoco sul numero della rata nel piano;
- pagamento della singola rata;
- modifica di data e importo delle rate non pagate;
- estinzione anticipata delle ultime rate residue;
- eliminazione del piano con cancellazione delle relative scadenze;
- mantenimento della scadenza originaria e della data effettiva di pagamento;
- isolamento completo per utente.

## Regola di competenza

- rata non pagata: mese della scadenza;
- rata pagata anticipatamente: mese del pagamento;
- rata pagata in ritardo: mese della scadenza originaria.

Le rate sono solo promemoria e non riducono il budget.

## Vincoli rispettati

- nessuna trasformazione automatica in spesa fissa;
- nessuna modifica della logica del Salvadanaio;
- nessuna modifica delle funzionalità multiutente già validate.

## Verifiche residue

- prova completa dall'interfaccia utente;
- controllo finale dei diff;
- eventuali rifiniture esclusivamente grafiche o di usabilità.


---

# Collegamento documenti alle Spese fisse

## Stato

✅ Completato il 2026-10-23.

- ✅ Link facoltativo (uno per voce) a un documento Paperless-ngx o Google Drive.
- ✅ Campo link nel form di inserimento di una nuova spesa fissa.
- ✅ Aggiunta, modifica e rimozione del link sulle voci già presenti nel mese.
- ✅ Descrizione cliccabile in colore orangered solo per le voci collegate.
- ✅ Validazione degli URL (`http`/`https`) e isolamento per utente.

---

# Spese fisse a scadenza

## Stato

✅ Completato il 2026-10-23.

- ✅ Voci con rate a date di scadenza reali, inserite manualmente.
- ✅ Sezione "Spese Fisse (NON considerate nel budget)" nella dashboard, visibile nel mese della scadenza.
- ✅ Importo scalato dal budget solo con PAGATO, nel mese del pagamento.
- ✅ Link documento per singola rata, anche dopo il pagamento.
- ✅ Modifica di importo e data delle rate non pagate, eliminazione della voce.
- ✅ Isolamento per utente; Spese programmate invariate.
- ✅ Piani di pagamento con numero totale di rate e rate aggiunte una alla volta (2026-10-24).
- ✅ Numero della rata reale ("Rata N/Totale"), modificabile, e spostamento delle rate tra piani.
- ✅ Piani collassabili e filtro di ricerca testuale.

---

# Riorganizzazione della dashboard

## Stato

✅ Completato il 2026-10-24.

- ✅ Eliminato il Pannello Gestione Mese: card autonome disposte in due colonne senza spazi vuoti.
- ✅ Ordine dedicato per il telefono: budget e Spese Fisse con le scadenze per primi.
- ✅ Cambia Password in un popup dal menu.
- ✅ Campo "Ripeti password" e icona 👁️ per mostrare la password nel popup.
- ✅ Entrata mensile e percentuale spostate nella pagina Salvadanaio, con percentuale reale nel titolo.

---

# Backup del database

## Stato

✅ Completato il 2026-10-24.

- ✅ Backup notturno (prima settimanale) del database e di `.env` sul disco del router, ultimi 30 conservati.
- ✅ Prova di ripristino completata in un container temporaneo.
- ✅ Esito registrato nella tabella `backup_stato` con fase e motivo in caso di errore.
- ✅ Avviso in dashboard (rosso: backup non riuscito; giallo: nessun backup da più di 2 giorni) e riga "Ultimo backup" in fondo alla pagina.
- ✅ Permessi `600` su `.env`, punti di ripristino e log; eliminati vecchi archivi locali con credenziali dismesse.

---

# Revisione dei calcoli (2026-10-29)

- ✅ Avanzo del mese precedente spalmato su tutto il mese (da novembre 2026) e calcolato al momento.
- ✅ Prelievo "Imprevisti" sempre uguale allo sforamento del mese.
- ✅ Bug della percentuale 0% che cancellava il prelievo Imprevisti.
- ✅ Mese nuovo con entrata 0 € e avviso "Inserisci l'entrata".
- ✅ Statistiche allineate alla dashboard e filtrate per utente.

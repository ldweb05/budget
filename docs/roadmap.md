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
- Redistribuzione automatica degli avanzi.
- Redistribuzione automatica degli sforamenti.
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

# Nuova funzionalità proposta — Spese programmate

## Obiettivo

Introdurre una nuova categoria distinta da spese fisse e spese variabili.

Le **Spese programmate** rappresentano impegni economici futuri già conosciuti che devono essere ricordati automaticamente dall'applicazione.

Esempi:

- acquisto Amazon in più rate;
- Agenzia delle Entrate;
- finanziamenti;
- rate del dentista;
- qualsiasi pagamento futuro già pianificato.

## Filosofia

Le spese programmate **non sono spese fisse**, perché hanno una durata limitata.

Non sono nemmeno spese variabili, perché sono già conosciute in anticipo.

L'obiettivo è evitare che l'utente dimentichi pagamenti futuri che influenzano il budget.

## Funzionamento previsto

L'utente crea un unico piano indicando:

- descrizione;
- importo totale;
- numero rate;
- importo rata;
- frequenza;
- data della prima scadenza.

L'applicazione genera automaticamente le scadenze future.

Quando inizia un nuovo mese, la rata prevista entra automaticamente nel budget del mese.

L'utente dovrà solamente confermare il pagamento.

## Beneficio

Il budget giornaliero terrà conto anche degli impegni futuri già conosciuti, rendendo la previsione di spesa più affidabile.

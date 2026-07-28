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

Da completare successivamente:

- Possibilità di visualizzare/nascondere la password nella schermata di login.
- Miglioramento responsive per utilizzo da smartphone e tablet.

## Isolamento dati multiutente

Completato:

- ✅ Tutti i dati finanziari sono isolati per utente.
- ✅ Ogni utente vede esclusivamente i propri mesi, spese, preferiti e Salvadanaio.
- ✅ Eliminando un utente vengono eliminati automaticamente tutti i suoi dati finanziari (ON DELETE CASCADE).
- ✅ La ricreazione di un utente con lo stesso username genera un ambiente completamente vuoto.

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

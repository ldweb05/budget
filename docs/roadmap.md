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

Prossimo intervento:

- Creare `admin.php` come dashboard amministrativa dedicata.
- Reindirizzare gli amministratori da `login.php` verso `admin.php`.
- Mantenere `index.php` riservato agli utenti con ruolo `user`.
- Impedire agli amministratori di accedere ai dati finanziari.
- Mostrare nell'area admin esclusivamente elenco utenti e relative azioni.
- Consentire creazione, modifica, attivazione, disattivazione ed eliminazione utenti.
- Proteggere `admin.php` consentendo l'accesso esclusivamente agli amministratori.
- Proteggere Dashboard, Grafici e Salvadanaio consentendo l'accesso esclusivamente agli utenti.
- Verificare login, redirect, autorizzazioni e logout per entrambi i ruoli.

Da completare successivamente:

- Possibilità di visualizzare/nascondere la password nella schermata di login.
- Miglioramento responsive per utilizzo da smartphone e tablet.

## Funzionalità future

- Backup dall'interfaccia.
- Esportazione PDF.
- Esportazione Excel.
- Esportazione CSV.
- PWA.
- Import movimenti bancari.

---

La roadmap è un documento vivo e potrà essere aggiornata nel tempo mantenendo la filosofia del progetto.

# UI Guidelines - Budget v2

## Obiettivo

L'interfaccia deve essere progettata per prendere decisioni, non per consultare dati.

L'utente deve capire la propria situazione economica in meno di 5 secondi.

---

# Filosofia

La schermata principale deve rispondere ad una sola domanda:

> Quanto posso spendere oggi?

Qualsiasi elemento che non aiuta a rispondere a questa domanda deve essere secondario.

---

# Principi di progettazione

- Massimo tre clic per qualsiasi operazione.
- Inserimento di una spesa in meno di 10 secondi.
- Mobile first.
- Pochi colori.
- Nessuna informazione superflua.
- Nessuna tabella infinita.
- Interfaccia pulita e leggibile.

---

# Dashboard

Elemento principale:

💶 Budget disponibile oggi

Elementi secondari:

- Giorni rimanenti
- Stato del budget
- Ultime operazioni

Non devono comparire automaticamente:

- saldo del Salvadanaio;
- statistiche avanzate;
- grafici complessi.

---

# Inserimento spese

La registrazione di una spesa deve richiedere il minor numero possibile di campi.

I valori ripetitivi devono essere suggeriti automaticamente.

---

# Salvadanaio

Il Salvadanaio deve rimanere invisibile durante l'utilizzo quotidiano.

L'utente lo visualizza solo entrando volontariamente nella relativa sezione.

---

# Disposizione della dashboard

- All'apertura devono essere subito visibili "Oggi puoi spendere massimo" e le Spese Fisse del mese con le scadenze.
- Computer: due colonne indipendenti, senza spazi vuoti. Sinistra: budget, Aggiungi Spesa, Gestisci Spese Fisse, Piani a scadenza. Destra: Spese Fisse (nel budget e NON nel budget), Ultime Spese Varie.
- Telefono: una colonna nell'ordine budget, Spese Fisse e scadenze, Aggiungi Spesa, Ultime Spese, Gestisci Spese Fisse, Piani a scadenza.
- Ogni funzione è una card autonoma con lo stesso stile; non esistono pannelli che raggruppano altre card.
- Le funzioni usate raramente stanno fuori dalla dashboard: Cambia Password in un popup aperto dal pulsante 🔑 del menu, Entrata mensile e percentuale nella pagina Salvadanaio.
- Il menu va a capo automaticamente sugli schermi stretti.

---

# Coerenza

Ogni nuova schermata dovrà rispettare questi principi.

Se una funzione aumenta la complessità senza migliorare la decisione quotidiana dell'utente, dovrà essere ripensata o eliminata.

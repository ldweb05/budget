# Budget v2 - START HERE

# Lo scopo del progetto

Budget NON è un software di contabilità.

Budget NON è un gestionale.

Budget NON è un foglio Excel.

Budget è un assistente personale che ogni giorno deve rispondere ad una sola domanda:

> **Quanto posso spendere oggi senza compromettere il resto del mese?**

Tutto il progetto ruota attorno a questa filosofia.

---

# Obiettivi principali

L'applicazione deve:

- essere estremamente semplice da utilizzare;
- richiedere meno di 10 secondi per registrare una spesa;
- eliminare qualsiasi calcolo mentale da parte dell'utente;
- mostrare un solo dato veramente importante: il budget disponibile per oggi.

---

# I quattro contenitori del denaro

## 1. Salvadanaio

Una percentuale configurabile di ogni nuova entrata viene accantonata automaticamente.

Il Salvadanaio:

- non partecipa al budget giornaliero;
- non viene mostrato nella schermata principale;
- è visibile solo entrando nella relativa sezione.

---

## 2. Spese fisse

Le spese fisse sono considerate impegnate dal primo giorno del mese.

Devono essere sempre incluse nel calcolo del budget disponibile.

---

## 3. Budget variabile

È l'unica somma realmente spendibile.

Ogni nuova spesa aggiorna automaticamente il budget giornaliero.

Se si spende meno del previsto, il residuo viene redistribuito sui giorni rimanenti.

Se si spende di più, anche l'eccedenza viene redistribuita automaticamente.

---

## 4. Storico

Serve esclusivamente per consultazione, statistiche e analisi.

Non deve complicare l'utilizzo quotidiano dell'applicazione.

---

# Documentazione

La documentazione del progetto è contenuta nella cartella `docs/`.

Ordine di lettura consigliato:

1. start_here.md
2. vision.md
3. functional_spec.md
4. ui_guidelines.md
5. roadmap.md
6. handover_operativo.md
7. README.md

# Architettura corrente

L'applicazione è suddivisa in due aree completamente separate.

## Area utente

- `index.php` è riservato agli utenti con ruolo `user`;
- contiene esclusivamente le funzionalità relative al budget personale.

## Area amministrativa

- `admin.php` è riservato agli utenti con ruolo `admin`;
- consente esclusivamente la gestione degli utenti (creazione, modifica, attivazione, eliminazione e cambio password);
- gli amministratori non possono accedere ai dati finanziari.

L'autenticazione reindirizza automaticamente ogni utente nell'area corretta in base al ruolo.

---

Questo documento è il punto di ingresso dell'intero progetto.

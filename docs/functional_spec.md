# Functional Specification - Budget v2

## Scopo

Questo documento descrive il funzionamento dell'applicazione.

Non descrive il codice ma le regole funzionali che dovranno essere rispettate.

---

# Principio fondamentale

L'applicazione deve sempre rispondere ad una sola domanda:

> Quanto posso spendere oggi senza compromettere il resto del mese?

Ogni funzione dovrà contribuire a questo obiettivo.

---

# Entrate

Quando viene registrata una nuova entrata:

1. viene registrata nello storico;
2. viene calcolata automaticamente la percentuale destinata al Salvadanaio;
3. il relativo importo viene accantonato;
4. il budget disponibile viene ricalcolato.

---

# Salvadanaio

Il Salvadanaio:

- utilizza una percentuale configurabile;
- riceve automaticamente una quota di ogni nuova entrata;
- collega ogni quota automatica al relativo mese;
- aggiorna la quota esistente quando cambiano entrata o percentuale, senza creare duplicati;
- rimuove la quota automatica quando il calcolo produce un importo pari a zero;
- non partecipa al budget giornaliero;
- non viene mostrato nella dashboard principale;
- è consultabile solo entrando nella sezione dedicata.

---

# Spese fisse

Le spese fisse:

- sono permanenti;
- vengono considerate impegnate dal primo giorno del mese;
- vengono sempre incluse nel calcolo del budget disponibile.

---

# Budget giornaliero

Il budget giornaliero rappresenta l'unico importo realmente spendibile.

La dashboard deve mostrare principalmente questo valore.

---

# Redistribuzione automatica

Se una giornata termina con un avanzo:

- il residuo viene redistribuito automaticamente sui giorni rimanenti.

Se una giornata termina con uno sforamento:

- anche l'eccedenza viene redistribuita automaticamente sui giorni rimanenti.

Il ricalcolo deve essere completamente automatico.

---

# Dashboard

La schermata iniziale deve mostrare principalmente:

- Budget disponibile oggi;
- Giorni rimanenti;
- Stato del budget.

Le altre informazioni devono essere secondarie.

---

Questo documento rappresenta la base funzionale della versione 2 del progetto.

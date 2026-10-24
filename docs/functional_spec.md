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

- utilizza una percentuale configurabile e modificabile dall'utente;
- entrata mensile e percentuale del mese si modificano dalla pagina Salvadanaio, riferite al mese aperto nella dashboard;
- riceve automaticamente una quota di ogni nuova entrata;
- aggiorna automaticamente la quota quando cambiano entrata o percentuale;
- collega ogni movimento automatico al relativo mese;
- consente versamenti manuali aggiuntivi indipendenti dalla percentuale;
- consente prelievi manuali;
- copre automaticamente gli imprevisti quando il budget mensile viene superato;
- registra gli imprevisti come movimento permanente del Salvadanaio;
- non partecipa al budget giornaliero;
- non viene mostrato nella dashboard principale;
- è consultabile solo entrando nella sezione dedicata.

---

# Spese fisse

Le spese fisse:

- sono permanenti;
- vengono considerate impegnate dal primo giorno del mese;
- vengono sempre incluse nel calcolo del budget disponibile;
- possono essere collegate facoltativamente a un documento esterno (Paperless-ngx o Google Drive) tramite un semplice link, uno per voce;
- se una voce ha un link, la descrizione diventa cliccabile, è evidenziata in colore orangered e apre il documento in una nuova scheda; senza link resta testo normale;
- il link si può aggiungere, modificare o rimuovere anche sulle voci già presenti nel mese;
- il link appartiene al singolo mese e non viene riportato sulle voci del mese successivo;
- il link non influisce in alcun modo sui calcoli del budget.

---

# Spese fisse a scadenza

Le Spese fisse a scadenza sono impegni importanti con date di scadenza reali non mensili (es. rate F24, scadenze ogni 120 giorni).

Sono distinte dalle Spese programmate (acquisti rateali poco rilevanti, consultabili dal pulsante "Programmate") e restano sempre in vista nella dashboard, nella card Spese Fisse, sotto la sezione "Spese Fisse (NON considerate nel budget)".

Sono organizzate in piani di pagamento (es. "Rottamazione 2023", "F24 Cedolare secca 2024"). Ogni piano appartiene all'utente autenticato e contiene:

- nome del piano;
- numero totale di rate previste;
- le rate, aggiunte una alla volta sotto il piano, ciascuna con numero della rata, data di scadenza reale, importo e link facoltativo (nessun calcolo automatico delle date).

Il numero della rata viene proposto come successivo all'ultimo inserito ma è modificabile (un piano già avviato può partire, per esempio, dalla rata 15). Le rate sono mostrate come "Rata N/Totale".

Regole:

- una rata non pagata è visibile solo nel mese della propria scadenza e non viene scalata dal budget;
- la rata viene scalata dal budget solo quando l'utente spunta PAGATO, nel mese del pagamento (data di pagamento = giorno in cui viene spuntata);
- se viene pagata in un mese diverso dalla scadenza, compare anche nel mese del pagamento, come pagata;
- togliendo la spunta l'importo torna disponibile;
- importo e data sono modificabili solo sulle rate non pagate; numero della rata e piano di appartenenza sono sempre modificabili (una rata può essere spostata in un altro piano);
- il numero totale di rate del piano è modificabile;
- ogni rata può essere collegata a un documento esterno (Paperless-ngx o Google Drive) tramite link, anche dopo il pagamento, con la stessa resa delle spese fisse (descrizione cliccabile in orangered);
- l'eliminazione di un piano elimina tutte le sue rate;
- nella card "Piani a scadenza" della dashboard i piani sono collassabili (chiusi di default, il browser ricorda quelli aperti), ordinati per prossima scadenza da pagare (piani senza rate in alto, saldati in fondo) e filtrabili con un campo di ricerca testuale (es. "F24", "Cedolare 2024");
- non modificano il Salvadanaio e rispettano l'isolamento dei dati per utente.

---

# Spese programmate

Le Spese programmate rappresentano impegni economici futuri già conosciuti ma con durata limitata.

Non sono spese fisse e non vengono trasformate automaticamente in spese fisse.

Ogni piano appartiene all'utente autenticato e contiene:

- descrizione;
- importo totale;
- numero delle rate;
- importo della rata;
- modalità delle scadenze;
- eventuale prima scadenza;
- eventuale frequenza in mesi;
- note.

Le scadenze possono essere create in due modalità:

- automatica, con generazione periodica mensile, bimestrale, trimestrale o con altra frequenza espressa in mesi;
- manuale, inserendo direttamente tutte le date reali quando non seguono una periodicità regolare.

Ogni rata è registrata come singola scadenza con:

- numero della rata;
- importo;
- data di scadenza;
- stato pagato;
- eventuale data di pagamento.

Le rate del mese vengono considerate importi già impegnati e riducono il budget disponibile prima del calcolo del budget giornaliero.

La data di competenza viene determinata così:

- una rata non pagata appartiene al mese della scadenza;
- una rata pagata anticipatamente appartiene al mese del pagamento;
- una rata pagata in ritardo resta attribuita al mese della scadenza originaria.

Una rata pagata non viene duplicata tra le spese variabili.

L'utente può:

- segnare una singola rata come pagata;
- modificare data e importo delle sole rate non pagate;
- estinguere anticipatamente una o più delle ultime rate residue;
- eliminare un intero piano e le relative scadenze.

L'estinzione anticipata mantiene le date originariamente programmate nello storico e registra la data effettiva del pagamento.

Le Spese programmate:

- non modificano la percentuale del Salvadanaio;
- non generano automaticamente movimenti nel Salvadanaio;
- non alterano la logica delle spese fisse;
- rispettano sempre l'isolamento dei dati per utente.

---

# Budget giornaliero

Il budget giornaliero rappresenta l'unico importo realmente spendibile.

La dashboard deve mostrare principalmente questo valore.

---

# Redistribuzione automatica

Se una giornata termina con un avanzo:

- l'intero residuo viene trasferito alla disponibilità del giorno immediatamente successivo;
- l'avanzo non viene suddiviso tra tutti i giorni rimanenti;
- se il residuo non viene utilizzato nel giorno successivo, continua a trasferirsi integralmente al giorno seguente.

Se una giornata termina con uno sforamento ma il budget mensile complessivo resta positivo:

- lo sforamento viene redistribuito automaticamente sui giorni rimanenti;
- la quota base dei giorni successivi viene ridotta in modo uniforme.

Quando il budget mensile diventa negativo:

- il budget giornaliero mostra direttamente lo sforamento cumulativo;
- il valore viene evidenziato in rosso;
- ogni nuovo imprevisto incrementa automaticamente il movimento "Imprevisti" del Salvadanaio.

Il ricalcolo deve essere completamente automatico.

---

# Dashboard

La schermata iniziale deve mostrare principalmente:

- Budget disponibile oggi;
- Giorni rimanenti;
- Stato del budget.

Le altre informazioni devono essere secondarie.

---

# Backup del database

- Il database viene salvato automaticamente ogni notte sul disco esterno collegato al router; vengono conservati gli ultimi 30 backup.
- Se l'ultimo backup non è riuscito, la dashboard mostra in alto un avviso rosso con data, motivo (fase in cui si è fermato) e data dell'ultimo backup riuscito.
- Se non risulta alcun backup riuscito da più di 2 giorni, la dashboard mostra un avviso giallo con l'ultimo avvio del Raspberry (utile per riconoscere una mancanza di corrente).
- Se tutto è regolare non compare alcun avviso: in fondo alla pagina resta solo la riga "Ultimo backup del database: … ✓".

---

Questo documento rappresenta la base funzionale della versione 2 del progetto.

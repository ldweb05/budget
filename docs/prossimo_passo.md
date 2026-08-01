# Prossimo passo

## Stato attuale

Completato:

- collegamento dei movimenti automatici del Salvadanaio al mese (`mese_id`);
- eliminazione dei duplicati tramite aggiornamento del movimento esistente;
- creazione automatica della quota del Salvadanaio alla creazione del mese.

## Prossimo intervento

1. Consentire all'utente di modificare la percentuale destinata al Salvadanaio.
2. Consentire all'utente di modificare l'entrata mensile mantenendo sincronizzata la quota del Salvadanaio.
3. Quando il budget disponibile diventa negativo:
   - mostrare il budget giornaliero in rosso con segno "-";
   - scalare automaticamente l'importo negativo dal Salvadanaio;
   - registrare il movimento come prelievo automatico collegato al mese.

## Verifiche previste

- php -l nel container Docker;
- test funzionali della creazione mese;
- test aggiornamento entrata;
- test modifica percentuale;
- git diff --check;
- git diff;
- git status.

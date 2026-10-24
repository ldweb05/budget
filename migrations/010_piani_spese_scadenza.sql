START TRANSACTION;

-- Le voci delle Spese fisse a scadenza diventano piani di pagamento:
-- numero_rate = totale delle rate previste dal piano (facoltativo, NULL per le voci create prima).
ALTER TABLE voci_spese_scadenza
    ADD COLUMN IF NOT EXISTS numero_rate INT(11) NULL DEFAULT NULL AFTER descrizione;

-- Il numero della rata viene ora indicato dall'utente e una rata puo' essere
-- spostata tra piani: l'indice univoco diventa un indice normale.
ALTER TABLE rate_spese_scadenza
    DROP INDEX IF EXISTS unici_voce_numero_rata,
    ADD INDEX IF NOT EXISTS idx_rate_spese_scadenza_voce (voce_id, numero_rata);

COMMIT;

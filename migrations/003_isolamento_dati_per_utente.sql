START TRANSACTION;

ALTER TABLE mesi
    ADD COLUMN utente_id INT(11) NULL AFTER id;

UPDATE mesi
SET utente_id = 5
WHERE utente_id IS NULL;

ALTER TABLE mesi
    MODIFY utente_id INT(11) NOT NULL,
    DROP INDEX unici,
    ADD UNIQUE KEY unici_utente (utente_id, nome, anno),
    ADD KEY idx_mesi_utente (utente_id),
    ADD CONSTRAINT fk_mesi_utente
        FOREIGN KEY (utente_id) REFERENCES utenti(id)
        ON DELETE CASCADE;

ALTER TABLE preferiti_spese
    ADD COLUMN utente_id INT(11) NULL AFTER id;

UPDATE preferiti_spese
SET utente_id = 5
WHERE utente_id IS NULL;

ALTER TABLE preferiti_spese
    MODIFY utente_id INT(11) NOT NULL,
    DROP INDEX descrizione,
    ADD UNIQUE KEY descrizione_utente (utente_id, descrizione),
    ADD KEY idx_preferiti_utente (utente_id),
    ADD CONSTRAINT fk_preferiti_utente
        FOREIGN KEY (utente_id) REFERENCES utenti(id)
        ON DELETE CASCADE;

ALTER TABLE fondo_risparmio
    ADD COLUMN utente_id INT(11) NULL AFTER id;

UPDATE fondo_risparmio
SET utente_id = 5
WHERE utente_id IS NULL;

ALTER TABLE fondo_risparmio
    MODIFY utente_id INT(11) NOT NULL,
    ADD KEY idx_fondo_utente (utente_id),
    ADD CONSTRAINT fk_fondo_utente
        FOREIGN KEY (utente_id) REFERENCES utenti(id)
        ON DELETE CASCADE;

COMMIT;

START TRANSACTION;

ALTER TABLE fondo_risparmio
    ADD COLUMN mese_id INT(11) NULL AFTER utente_id,
    ADD UNIQUE KEY unici_fondo_mese (mese_id),
    ADD CONSTRAINT fk_fondo_mese
        FOREIGN KEY (mese_id) REFERENCES mesi(id)
        ON DELETE CASCADE;

COMMIT;

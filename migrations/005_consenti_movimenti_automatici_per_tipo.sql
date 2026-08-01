START TRANSACTION;

ALTER TABLE fondo_risparmio
    DROP INDEX unici_fondo_mese,
    ADD UNIQUE KEY unici_fondo_mese_tipo (mese_id, tipo);

COMMIT;

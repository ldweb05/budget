START TRANSACTION;

-- Collegamento facoltativo di una spesa fissa a un documento esterno
-- (Paperless-ngx o Google Drive) tramite semplice URL http/https.
ALTER TABLE spese_fisse
    ADD COLUMN IF NOT EXISTS link_documento VARCHAR(1000) NULL DEFAULT NULL AFTER pagato;

COMMIT;

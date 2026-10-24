START TRANSACTION;

-- Esito dei backup notturni del database, scritto dallo script di backup sul
-- Raspberry (~/budget-backup.sh). Tabella di sistema, non contiene dati finanziari:
-- la dashboard la usa solo per mostrare un avviso se il backup non riesce.
CREATE TABLE IF NOT EXISTS backup_stato (
    id INT(11) NOT NULL AUTO_INCREMENT,
    eseguito_il DATETIME NOT NULL,
    esito VARCHAR(10) NOT NULL,
    fase VARCHAR(5) NULL DEFAULT NULL,
    dettaglio VARCHAR(500) NULL DEFAULT NULL,
    archivio VARCHAR(255) NULL DEFAULT NULL,
    PRIMARY KEY (id),
    KEY idx_backup_stato_esito_data (esito, eseguito_il)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

COMMIT;

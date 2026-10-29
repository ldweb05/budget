START TRANSACTION;

-- Entrate extra del mese: spalmate dal giorno di inserimento a fine mese,
-- con il versamento al Salvadanaio (percentuale del mese) collegato all'extra.
CREATE TABLE IF NOT EXISTS entrate_extra (
    id INT(11) NOT NULL AUTO_INCREMENT,
    mese_id INT(11) NOT NULL,
    descrizione VARCHAR(255) NOT NULL,
    importo DECIMAL(10,2) NOT NULL,
    data_entrata DATE NOT NULL,
    creato_il DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_entrate_extra_mese (mese_id),
    CONSTRAINT fk_entrate_extra_mese
        FOREIGN KEY (mese_id) REFERENCES mesi(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE fondo_risparmio
    ADD COLUMN IF NOT EXISTS entrata_extra_id INT(11) NULL AFTER mese_id,
    ADD UNIQUE KEY IF NOT EXISTS unici_fondo_entrata_extra (entrata_extra_id),
    ADD CONSTRAINT fk_fondo_entrata_extra
        FOREIGN KEY IF NOT EXISTS (entrata_extra_id) REFERENCES entrate_extra(id)
        ON DELETE CASCADE;

COMMIT;

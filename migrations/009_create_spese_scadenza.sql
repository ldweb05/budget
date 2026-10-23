START TRANSACTION;

-- Spese fisse a scadenza (es. rate F24): voci importanti mostrate nella
-- dashboard sotto "Spese Fisse (NON considerate nel budget)".
-- Una rata viene scalata dal budget solo quando e' pagata, nel mese del pagamento.
CREATE TABLE IF NOT EXISTS voci_spese_scadenza (
    id INT(11) NOT NULL AUTO_INCREMENT,
    utente_id INT(11) NOT NULL,
    descrizione VARCHAR(255) NOT NULL,
    creato_il DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    aggiornato_il DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_voci_spese_scadenza_utente (utente_id),
    CONSTRAINT fk_voci_spese_scadenza_utente
        FOREIGN KEY (utente_id) REFERENCES utenti(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

CREATE TABLE IF NOT EXISTS rate_spese_scadenza (
    id INT(11) NOT NULL AUTO_INCREMENT,
    voce_id INT(11) NOT NULL,
    numero_rata INT(11) NOT NULL,
    importo DECIMAL(10,2) NOT NULL,
    data_scadenza DATE NOT NULL,
    pagata TINYINT(1) NOT NULL DEFAULT 0,
    data_pagamento DATE NULL DEFAULT NULL,
    link_documento VARCHAR(1000) NULL DEFAULT NULL,
    creato_il DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    aggiornato_il DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY unici_voce_numero_rata (voce_id, numero_rata),
    KEY idx_rate_spese_scadenza_data (data_scadenza),
    KEY idx_rate_spese_scadenza_pagamento (data_pagamento),
    CONSTRAINT fk_rate_spese_scadenza_voce
        FOREIGN KEY (voce_id) REFERENCES voci_spese_scadenza(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

COMMIT;

START TRANSACTION;

CREATE TABLE piani_spese_programmate (
    id INT(11) NOT NULL AUTO_INCREMENT,
    utente_id INT(11) NOT NULL,
    descrizione VARCHAR(255) NOT NULL,
    importo_totale DECIMAL(10,2) NOT NULL,
    numero_rate INT(11) NOT NULL,
    importo_rata DECIMAL(10,2) NOT NULL,
    modalita_scadenze VARCHAR(20) NOT NULL,
    data_prima_scadenza DATE NULL,
    frequenza_mesi INT(11) NULL,
    note TEXT NULL,
    attivo TINYINT(1) NOT NULL DEFAULT 1,
    creato_il DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    aggiornato_il DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_piani_spese_programmate_utente (utente_id),
    CONSTRAINT fk_piani_spese_programmate_utente
        FOREIGN KEY (utente_id) REFERENCES utenti(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

CREATE TABLE scadenze_spese_programmate (
    id INT(11) NOT NULL AUTO_INCREMENT,
    piano_id INT(11) NOT NULL,
    numero_rata INT(11) NOT NULL,
    importo DECIMAL(10,2) NOT NULL,
    data_scadenza DATE NOT NULL,
    pagata TINYINT(1) NOT NULL DEFAULT 0,
    data_pagamento DATE NULL,
    note TEXT NULL,
    creato_il DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    aggiornato_il DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY unici_piano_numero_rata (piano_id, numero_rata),
    KEY idx_scadenze_spese_programmate_data (data_scadenza),
    KEY idx_scadenze_spese_programmate_pagata (pagata),
    CONSTRAINT fk_scadenze_spese_programmate_piano
        FOREIGN KEY (piano_id) REFERENCES piani_spese_programmate(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

COMMIT;

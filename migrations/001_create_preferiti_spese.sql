CREATE TABLE preferiti_spese (
    id INT AUTO_INCREMENT PRIMARY KEY,
    descrizione VARCHAR(255) NOT NULL UNIQUE,
    importo DECIMAL(10,2) NOT NULL
);

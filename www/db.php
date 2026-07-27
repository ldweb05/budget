<?php
$host = 'budget-db';
$user = 'lorenzo';
$password = 'budget_password_2026';
$database = 'budget_db';

$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    die("Connessione fallita: " . $conn->connect_error);
}

// Impostiamo il set di caratteri corretto per evitare problemi con accenti o simboli (€)
$conn->set_charset("utf8mb4");
?>

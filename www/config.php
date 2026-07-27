<?php
// Credenziali di accesso all'applicazione
define('USER_APP', getenv('APP_USER'));

// Metti qui la tua password personalizzata al posto di 'ilmiobudget'
define('PASS_APP', getenv('APP_PASSWORD'));

// Funzione di controllo per proteggere le pagine
function controlla_autenticazione() {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['loggato']) || $_SESSION['loggato'] !== true) {
        header("Location: login.php");
        exit;
    }
}
?>

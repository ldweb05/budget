<?php
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

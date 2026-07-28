<?php
include 'config.php';
controlla_autenticazione();

if (($_SESSION['ruolo'] ?? '') === 'admin') {
    header('Location: admin.php');
    exit;
}

include 'db.php';
date_default_timezone_set('Europe/Rome');
$utente_id = intval($_SESSION['utente_id'] ?? 0);

/*
|--------------------------------------------------------------------------
| TODO - Dashboard Budget v2
|--------------------------------------------------------------------------
|
| La dashboard sarà completamente riprogettata mantenendo inizialmente
| la compatibilità con il codice esistente.
|
| Obiettivi:
| 1. Rendere il budget giornaliero il dato principale.
| 2. Nascondere il saldo del Salvadanaio.
| 3. Considerare sempre le spese fisse come impegnate.
| 4. Ricalcolare automaticamente il budget giornaliero dopo ogni movimento.
| 5. Ridurre l'inserimento di una spesa a meno di 10 secondi.
|
| Prima di modificare la logica verranno introdotte piccole patch,
| ciascuna verificata singolarmente.
|
*/


// 1. Capire quale mese mostrare (di default quello attuale, o quello scelto dall'utente)
$mese_corrente = date('F');
$anno_corrente = date('Y');

if (isset($_GET['mese']) && isset($_GET['anno'])) {
    $mese_attivo = htmlspecialchars($_GET['mese']);
    $anno_attivo = intval($_GET['anno']);
} else {
    $mese_attivo = $mese_corrente;
    $anno_attivo = $anno_corrente;
}

$mesi_it = ["January" => "Gennaio", "February" => "Febbraio", "March" => "Marzo", "April" => "Aprile", "May" => "Maggio", "June" => "Giugno", "July" => "Luglio", "August" => "Agosto", "September" => "Settembre", "October" => "Ottobre", "November" => "Novembre", "December" => "Dicembre"];
$mese_display = $mesi_it[$mese_attivo] . " " . $anno_attivo;

// ---------------------------------------------------------
// GESTIONE AZIONI (POST / GET)
// ---------------------------------------------------------

// Azione: Inizializza Mese Richiesto
if (isset($_POST['crea_mese'])) {
    $m_creare = $_POST['m_creare'];
    $a_creare = intval($_POST['a_creare']);
    
    $ins_mese = $conn->prepare("INSERT IGNORE INTO mesi (utente_id, nome, anno, entrata, percentuale_risparmio) VALUES (?, ?, ?, 900.00, 15)");
    $ins_mese->bind_param("isi", $utente_id, $m_creare, $a_creare);
    $ins_mese->execute();
    $mese_id = $conn->insert_id;

    if ($mese_id > 0) {
        $spese_standard = [
            ['Condominio', 53.00],
            ['Gas', 60.00],
            ['Enel', 60.00],
            ['Acqua', 15.00],
            ['Telefono (Casa+Cell)', 35.00]
        ];
        $ins_fissa = $conn->prepare("INSERT INTO spese_fisse (mese_id, descrizione, importo) VALUES (?, ?, ?)");
        foreach ($spese_standard as $spesa) {
            $ins_fissa->bind_param("isd", $mese_id, $spesa[0], $spesa[1]);
            $ins_fissa->execute();
        }
    }
    header("Location: index.php?mese=$m_creare&anno=$a_creare");
    exit;
}

// Azione: Modifica Budget Entrata + Risparmio Automatico del 15% sul NETTO
if (isset($_POST['update_budget'])) {
    $mese_id = intval($_POST['mese_id']);
    $nuova_entrata = floatval($_POST['nuova_entrata']);
    $data_oggi = date('Y-m-d');
    
    // 1. Aggiorna l'entrata mensile normalmente
    $stmt = $conn->prepare("UPDATE mesi SET entrata = ? WHERE id = ? AND utente_id = ?");
    $stmt->bind_param("dii", $nuova_entrata, $mese_id, $utente_id);
    $stmt->execute();
    $stmt->close();

    // 2. Recupera la percentuale di risparmio configurata per il mese
    $stmt_percentuale = $conn->prepare("SELECT percentuale_risparmio FROM mesi WHERE id = ? AND utente_id = ?");
    $stmt_percentuale->bind_param("ii", $mese_id, $utente_id);
    $stmt_percentuale->execute();
    $percentuale_risparmio = floatval(
        $stmt_percentuale->get_result()->fetch_assoc()['percentuale_risparmio'] ?? 15
    );
    $stmt_percentuale->close();

    // 3. Calcola il risparmio sull'entrata totale, prima di qualsiasi spesa
    $quota_salvadanaio = $nuova_entrata * ($percentuale_risparmio / 100);

    if ($quota_salvadanaio > 0) {
        $percentuale_display = number_format($percentuale_risparmio, 2, ',', '');
        $percentuale_display = rtrim(rtrim($percentuale_display, '0'), ',');

        // Genera la causale automatica usando la percentuale configurata
        $causale_automatica = "Risparmio Automatico " . $percentuale_display . "% - " . $mese_display;

        // 4. Inserisce il movimento nel fondo risparmi del salvadanaio
        $stmt_fondo = $conn->prepare("INSERT INTO fondo_risparmio (utente_id, importo, tipo, causale, data_movimento) VALUES (?, ?, 'versamento', ?, ?)");
        $stmt_fondo->bind_param("idss", $utente_id, $quota_salvadanaio, $causale_automatica, $data_oggi);
        $stmt_fondo->execute();
        $stmt_fondo->close();
    }

    header("Location: index.php?mese=$mese_attivo&anno=$anno_attivo");
    exit;
}

// Azione: Aggiungi Nuova Spesa Fissa
if (isset($_POST['add_fissa_nuova'])) {
    $mese_id = intval($_POST['mese_id']);
    $desc = htmlspecialchars($_POST['descrizione_fissa']);
    $importo = floatval($_POST['importo_fissa']);
    if ($importo > 0 && !empty($desc)) {
        $stmt = $conn->prepare("INSERT INTO spese_fisse (mese_id, descrizione, importo, pagato) SELECT id, ?, ?, 0 FROM mesi WHERE id = ? AND utente_id = ?");
        $stmt->bind_param("sdii", $desc, $importo, $mese_id, $utente_id);
        $stmt->execute();
    }
    header("Location: index.php?mese=$mese_attivo&anno=$anno_attivo");
    exit;
}

// Azione: Elimina una Spesa Fissa
if (isset($_GET['delete_fissa'])) {
    $id_fissa = intval($_GET['delete_fissa']);
    $stmt = $conn->prepare("DELETE sf FROM spese_fisse sf INNER JOIN mesi m ON m.id = sf.mese_id WHERE sf.id = ? AND m.utente_id = ?"); $stmt->bind_param("ii", $id_fissa, $utente_id); $stmt->execute(); $stmt->close();
    header("Location: index.php?mese=$mese_attivo&anno=$anno_attivo");
    exit;
}

// Azione: Toggle Checkbox Spese Fisse
if (isset($_GET['toggle_fissa'])) {
    $id_fissa = intval($_GET['toggle_fissa']);
    $stmt = $conn->prepare("UPDATE spese_fisse sf INNER JOIN mesi m ON m.id = sf.mese_id SET sf.pagato = 1 - sf.pagato WHERE sf.id = ? AND m.utente_id = ?"); $stmt->bind_param("ii", $id_fissa, $utente_id); $stmt->execute(); $stmt->close();
    header("Location: index.php?mese=$mese_attivo&anno=$anno_attivo");
    exit;
}

// Azione: Elimina Spesa Preferita
if (isset($_GET['delete_preferito'])) {
    $id_preferito = intval($_GET['delete_preferito']);

    $stmt = $conn->prepare("DELETE FROM preferiti_spese WHERE id = ? AND utente_id = ?");
    $stmt->bind_param("ii", $id_preferito, $utente_id);
    $stmt->execute();
    $stmt->close();

    header("Location: index.php?mese=$mese_attivo&anno=$anno_attivo");
    exit;
}

// Azione: Salva Spesa Preferita
if (isset($_POST['salva_preferito'])) {
    $desc = trim($_POST['descrizione']);
    $importo = floatval($_POST['importo']);

    if ($importo > 0 && !empty($desc)) {
        $stmt = $conn->prepare(
            "INSERT INTO preferiti_spese (utente_id, descrizione, importo)
             VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE importo = VALUES(importo)"
        );
        $stmt->bind_param("isd", $utente_id, $desc, $importo);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: index.php?mese=$mese_attivo&anno=$anno_attivo");
    exit;
}

// Azione: Inserimento Spesa Variabile
if (isset($_POST['add_variabile'])) {
    $mese_id = intval($_POST['mese_id']);
    $desc = htmlspecialchars($_POST['descrizione']);
    $importo = floatval($_POST['importo']);
    $data_oggi = date('Y-m-d');

    if ($importo > 0 && !empty($desc)) {
        $ins_var = $conn->prepare("INSERT INTO spese_variabili (mese_id, descrizione, importo, data_spesa) SELECT id, ?, ?, ? FROM mesi WHERE id = ? AND utente_id = ?");
        $ins_var->bind_param("sdsii", $desc, $importo, $data_oggi, $mese_id, $utente_id);
        $ins_var->execute();
    }
    header("Location: index.php?mese=$mese_attivo&anno=$anno_attivo");
    exit;
}

// Azione: Elimina Spesa Variabile
if (isset($_GET['delete_variabile'])) {
    $id_var = intval($_GET['delete_variabile']);
    $stmt = $conn->prepare("DELETE sv FROM spese_variabili sv INNER JOIN mesi m ON m.id = sv.mese_id WHERE sv.id = ? AND m.utente_id = ?"); $stmt->bind_param("ii", $id_var, $utente_id); $stmt->execute(); $stmt->close();
    header("Location: index.php?mese=$mese_attivo&anno=$anno_attivo");
    exit;
}

// Azione: Aggiorna Password Utente
if (isset($_POST['update_password'])) {
    $nuova_password = trim($_POST['nuova_password']);
    $utente_id = intval($_SESSION['utente_id'] ?? 0);

    if (!empty($nuova_password) && $utente_id > 0) {
        $password_hash = password_hash($nuova_password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare(
            "UPDATE utenti
             SET password_hash = ?
             WHERE id = ? AND attivo = 1"
        );
        $stmt->bind_param("si", $password_hash, $utente_id);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: index.php");
    exit;
}

// Recupero dati del mese attivo (selezionato)
$query_mese = $conn->prepare("SELECT * FROM mesi WHERE utente_id = ? AND nome = ? AND anno = ?");
$query_mese->bind_param("isi", $utente_id, $mese_attivo, $anno_attivo);
$query_mese->execute();
$risultato_mese = $query_mese->get_result();
$mese_dati = $risultato_mese->fetch_assoc();

// Recuperiamo l'elenco di tutti i mesi storici nel DB per il menu a tendina
$elenco_mesi_stmt = $conn->prepare("SELECT nome, anno FROM mesi WHERE utente_id = ? ORDER BY anno DESC, FIELD(nome, 'December', 'November', 'October', 'September', 'August', 'July', 'June', 'May', 'April', 'March', 'February', 'January')");
$elenco_mesi_stmt->bind_param("i", $utente_id);
$elenco_mesi_stmt->execute();
$elenco_mesi_db = $elenco_mesi_stmt->get_result();
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Budget Familiare</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 font-sans min-h-screen pb-12">

    <header class="bg-[#008080] text-white py-3 shadow-md sticky top-0 z-40 px-4">
        <div class="max-w-4xl mx-auto flex flex-col gap-3 sm:flex-row sm:justify-between sm:items-center">
            <div class="text-left">
                <h1 class="text-lg font-bold tracking-wide">IL MIO BUDGET</h1>
                <p class="text-xs opacity-90"><?php echo $mese_display; ?></p>
            </div>
            <div>
                <div class="flex items-center gap-2">
                <a href="statistiche.php" class="bg-white/20 hover:bg-white/30 text-white text-xs font-semibold px-3 py-1.5 rounded-xl border border-white/20 transition">
                    📊 Grafici
                </a>
                <a href="salvadanaio.php" class="bg-white/20 hover:bg-white/30 text-white text-xs font-semibold px-3 py-1.5 rounded-xl border border-white/20 transition">
                    🐷 Salvadanaio
                </a>
                <a href="logout.php" class="bg-red-600 hover:bg-red-700 text-white text-xs font-semibold px-3 py-1.5 rounded-xl transition">
                    🚪 Esci
                </a>
                <select onchange="location = this.value;" class="bg-white/40 text-white text-xs font-semibold px-3 py-1.5 rounded-xl border border-white/30 focus:outline-none bg-neutral-800">
                    <option value="" disabled selected>Cambia Mese...</option>
                    <option value="index.php" class="text-gray-800">Mese Corrente</option>
                    <?php while($m_opzione = $elenco_mesi_db->fetch_assoc()): ?>
                        <option value="index.php?mese=<?php echo $m_opzione['nome']; ?>&anno=<?php echo $m_opzione['anno']; ?>" class="text-gray-800">
                            <?php echo $mesi_it[$m_opzione['nome']] . " " . $m_opzione['anno']; ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
        </div>
    </header>

    <main class="max-w-4xl mx-auto px-4 mt-6">

    <?php if (!$mese_dati): ?>
        <div class="max-w-md mx-auto bg-white p-6 rounded-2xl shadow-sm text-center">
            <p class="text-gray-600 mb-4">Il mese di <strong><?php echo $mese_display; ?></strong> non è ancora stato configurato.</p>
            <form method="POST">
                <input type="hidden" name="m_creare" value="<?php echo $mese_attivo; ?>">
                <input type="hidden" name="a_creare" value="<?php echo $anno_attivo; ?>">
                <button type="submit" name="crea_mese" class="w-full bg-[#12A0D7] text-white font-semibold py-3 rounded-xl shadow-md hover:opacity-90 transition">
                    Inizia Mese (Carica Dati Standard)
                </button>
            </form>
        </div>
    <?php else: 
        $mese_id = $mese_dati['id'];

        $res_fisse_stmt = $conn->prepare("SELECT SUM(sf.importo) AS totale FROM spese_fisse sf INNER JOIN mesi m ON m.id = sf.mese_id WHERE sf.mese_id = ? AND m.utente_id = ?");
        $res_fisse_stmt->bind_param("ii", $mese_id, $utente_id);
        $res_fisse_stmt->execute();
        $res_fisse = $res_fisse_stmt->get_result();
        $tot_fisse = $res_fisse->fetch_assoc()['totale'] ?? 0;

        $res_var_stmt = $conn->prepare("SELECT SUM(sv.importo) AS totale FROM spese_variabili sv INNER JOIN mesi m ON m.id = sv.mese_id WHERE sv.mese_id = ? AND m.utente_id = ?");
        $res_var_stmt->bind_param("ii", $mese_id, $utente_id);
        $res_var_stmt->execute();
        $res_var = $res_var_stmt->get_result();
        $tot_var = $res_var->fetch_assoc()['totale'] ?? 0;

        $entrata_totale = $mese_dati['entrata'];
        $percentuale_risparmio = floatval($mese_dati['percentuale_risparmio']);
        $quota_risparmio = $entrata_totale * ($percentuale_risparmio / 100);
        $entrata_dopo_risparmio = $entrata_totale - $quota_risparmio;
        $budget_variabile_iniziale = $entrata_dopo_risparmio - $tot_fisse;
        $budget_restante_mese = $budget_variabile_iniziale - $tot_var;

        // Calcolo giorni rimasti intelligente (se guardiamo un mese vecchio, i giorni rimasti sono 1 per bloccare il budget finale)
        $giorni_totali_mese = date('t', strtotime("1 $mese_attivo $anno_attivo"));
        if ($mese_attivo == $mese_corrente && $anno_attivo == $anno_corrente) {
            $giorno_corrente = date('j');
            $giorni_rimasti = ($giorni_totali_mese - $giorno_corrente) + 1;
        } else {
            $giorni_rimasti = 1; // Mese passato completato, mostra il totale rimasto finale
        }
        $budget_giornaliero = $budget_restante_mese / $giorni_rimasti;
        ?>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            
            <div class="space-y-6">
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200 text-center">
                    <h2 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">
                        <?php echo ($giorni_rimasti > 1) ? "Oggi puoi spendere massimo" : "Avanzo finale del mese"; ?>
                    </h2>
                    <div class="text-6xl font-extrabold <?php echo $budget_giornaliero >= 0 ? 'text-green-600' : 'text-red-600'; ?> my-4">
                        <?php echo number_format($budget_giornaliero, 2, ',', '.'); ?> €
                    </div>
                    <p class="text-xs text-gray-500 leading-relaxed">
                        Disponibilità rimasta: <span class="font-semibold"><?php echo number_format($budget_restante_mese, 2, ',', '.'); ?>€</span>
                        <?php if ($giorni_rimasti > 1): ?>
                            <br>Giorni alla fine del mese: <span class="font-semibold"><?php echo $giorni_rimasti; ?></span>
                        <?php endif; ?>
                    </p>
                </div>

                <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-200">
                    <h3 class="text-sm font-bold text-gray-700 mb-3">🛒 Aggiungi Spesa (Uscite, Spesa, Benzina...)</h3>
                    <form method="POST" class="space-y-3">
                        <input type="hidden" name="mese_id" value="<?php echo $mese_id; ?>">
                        <div class="flex flex-col sm:flex-row gap-2">
                            <input type="text" id="descrizione-spesa" name="descrizione" placeholder="Es. Supermercato" required autofocus class="flex-1 px-3 py-2 border rounded-xl bg-gray-50 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <input type="number" id="importo-spesa" step="0.01" name="importo" placeholder="€" required class="w-full sm:w-24 px-3 py-2 border rounded-xl bg-gray-50 text-sm font-bold text-right focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                            <div class="flex flex-wrap gap-2">
                                <?php
                                $preferiti_stmt = $conn->prepare("SELECT id, descrizione, importo FROM preferiti_spese WHERE utente_id = ? ORDER BY id DESC LIMIT 5");
$preferiti_stmt->bind_param("i", $utente_id);
$preferiti_stmt->execute();
$preferiti_query = $preferiti_stmt->get_result();
                                while ($preferito = $preferiti_query->fetch_assoc()):
                                ?>
                                    <span class="inline-flex items-center rounded-full bg-gray-100">
                                        <button
                                            type="button"
                                            class="preferito-spesa pl-2.5 pr-1 py-1 text-xs font-semibold text-gray-600 hover:text-gray-900"
                                            data-descrizione="<?php echo htmlspecialchars($preferito['descrizione'], ENT_QUOTES); ?>"
                                            data-importo="<?php echo $preferito['importo']; ?>"
                                        >
                                            <?php echo htmlspecialchars($preferito['descrizione']); ?>
                                        </button>
                                        <a
                                            href="index.php?mese=<?php echo $mese_attivo; ?>&anno=<?php echo $anno_attivo; ?>&delete_preferito=<?php echo $preferito['id']; ?>"
                                            onclick="return confirm('Vuoi eliminare questo preferito?')"
                                            class="px-2 py-1 text-xs font-bold text-red-400 hover:text-red-600"
                                            aria-label="Elimina preferito"
                                        >
                                            ×
                                        </a>
                                    </span>
                                <?php endwhile; ?>
                            </div>
                            <button type="submit" name="salva_preferito" class="shrink-0 text-xs font-semibold text-[#008080] hover:underline">
                                ☆ Salva preferito
                            </button>
                        </div>
                        <button type="submit" name="add_variabile" class="w-full bg-[#008080] text-white font-semibold py-2.5 rounded-xl text-sm shadow-sm hover:bg-[#006666] transition">
                            Aggiungi Spesa
                        </button>
                    </form>
                </div>
            </div>

            <div class="space-y-6">
                <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-200">
                    <h3 class="text-sm font-bold text-gray-700 mb-3">📌 Spese Fisse (già considerate nel budget)</h3>
                    <ul class="divide-y divide-gray-100">
                        <?php
                        $spese_fisse_stmt = $conn->prepare("SELECT sf.* FROM spese_fisse sf INNER JOIN mesi m ON m.id = sf.mese_id WHERE sf.mese_id = ? AND m.utente_id = ?");
                        $spese_fisse_stmt->bind_param("ii", $mese_id, $utente_id);
                        $spese_fisse_stmt->execute();
                        $spese_fisse_query = $spese_fisse_stmt->get_result();
                        while ($fissa = $spese_fisse_query->fetch_assoc()):
                        ?>
                        <li class="py-2.5 flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <a href="index.php?mese=<?php echo $mese_attivo; ?>&anno=<?php echo $anno_attivo; ?>&toggle_fissa=<?php echo $fissa['id']; ?>" class="w-6 h-6 flex items-center justify-center rounded-md border text-sm <?php echo $fissa['pagato'] ? 'bg-blue-600 border-blue-600 text-white' : 'border-gray-300 bg-white text-transparent'; ?>">
                                    ✓
                                </a>
                                <span class="text-sm <?php echo $fissa['pagato'] ? 'line-through text-gray-400' : 'text-gray-700'; ?>">
                                    <?php echo $fissa['descrizione']; ?>
                                </span>
                            </div>
                            <span class="text-sm font-semibold <?php echo $fissa['pagato'] ? 'text-gray-400' : 'text-gray-600'; ?>">
                                <?php echo number_format($fissa['importo'], 2, ',', '.'); ?> €
                            </span>
                        </li>
                        <?php endwhile; ?>
                    </ul>
                </div>

                <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-200">
                    <h3 class="text-sm font-bold text-gray-700 mb-3">🕒 Ultime Spese Varie</h3>
                    <input
                        type="search"
                        id="ricerca-spese"
                        placeholder="Cerca una spesa..."
                        class="w-full mb-3 px-3 py-2 border rounded-xl bg-gray-50 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                    <ul id="elenco-spese" class="divide-y divide-gray-100 max-h-60 overflow-y-auto">
                        <?php
                        $spese_var_stmt = $conn->prepare("SELECT sv.* FROM spese_variabili sv INNER JOIN mesi m ON m.id = sv.mese_id WHERE sv.mese_id = ? AND m.utente_id = ? ORDER BY sv.id DESC");
                        $spese_var_stmt->bind_param("ii", $mese_id, $utente_id);
                        $spese_var_stmt->execute();
                        $spese_var_query = $spese_var_stmt->get_result();
                        if ($spese_var_query->num_rows == 0):
                            echo "<p class='text-xs text-gray-400 py-2'>Nessuna spesa registrata.</p>";
                        endif;
                        while ($variabile = $spese_var_query->fetch_assoc()):
                        ?>
                        <li class="spesa-variabile py-2 flex justify-between items-center">
                            <div>
                                <p class="descrizione-spesa text-sm text-gray-700 font-medium"><?php echo $variabile['descrizione']; ?></p>
                                <p class="text-[10px] text-gray-400">
                                    <?php echo date('d/m', strtotime($variabile['data_spesa'])); ?> 
                                    • <a href="index.php?mese=<?php echo $mese_attivo; ?>&anno=<?php echo $anno_attivo; ?>&delete_variabile=<?php echo $variabile['id']; ?>" class="text-red-400 hover:underline">Elimina</a>
                                </p>
                            </div>
                            <span class="text-sm font-bold text-red-500">-<?php echo number_format($variabile['importo'], 2, ',', '.'); ?> €</span>
                        </li>
                        <?php endwhile; ?>
                    </ul>
                </div>
            </div>
        </div>

        <div class="mt-8 bg-gray-50 border border-gray-200 rounded-2xl p-5">
            <h3 class="text-sm font-bold text-gray-600 mb-4 flex items-center gap-2">🛠️ Pannello Gestione Mese</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-white p-4 rounded-xl border border-gray-100 shadow-sm">
                    <h4 class="text-xs font-bold text-gray-400 uppercase mb-3">Modifica Entrata Mensile</h4>
                    <form method="POST" class="flex flex-col sm:flex-row gap-2">
                        <input type="hidden" name="mese_id" value="<?php echo $mese_id; ?>">
                        <input type="number" step="0.01" name="nuova_entrata" value="<?php echo $entrata_totale; ?>" class="w-full px-3 py-1.5 border rounded-xl text-sm bg-gray-50 font-semibold focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <button type="submit" name="update_budget" class="bg-[#12A0D7] text-white text-xs font-semibold px-4 rounded-xl hover:opacity-90 transition">Salva</button>
                    </form>
                </div>

                <div class="bg-white p-4 rounded-xl border border-gray-100 shadow-sm">
                    <h4 class="text-xs font-bold text-gray-400 uppercase mb-2">Aggiungi / Rimuovi Spese Fisse</h4>
                    <form method="POST" class="flex flex-col sm:flex-row gap-2 mb-3">
                        <input type="hidden" name="mese_id" value="<?php echo $mese_id; ?>">
                        <input type="text" name="descrizione_fissa" placeholder="Nuova voce fissa" required class="flex-1 px-3 py-1.5 border rounded-xl text-sm bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <input type="number" step="0.01" name="importo_fissa" placeholder="€" required class="w-16 px-2 py-1.5 border rounded-xl text-sm bg-gray-50 font-bold text-right focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <button type="submit" name="add_fissa_nuova" class="bg-gray-800 text-white text-xs font-semibold px-3 rounded-xl hover:bg-gray-900 transition">+</button>
                    </form>
                    <p class="text-[10px] text-gray-400 font-bold mb-1 uppercase">Elimina Spese Esistenti:</p>
                    <div class="max-h-24 overflow-y-auto divide-y divide-gray-100">
                        <?php
                        $elenco_fisse_stmt = $conn->prepare("SELECT sf.* FROM spese_fisse sf INNER JOIN mesi m ON m.id = sf.mese_id WHERE sf.mese_id = ? AND m.utente_id = ?");
                        $elenco_fisse_stmt->bind_param("ii", $mese_id, $utente_id);
                        $elenco_fisse_stmt->execute();
                        $elenco_fisse = $elenco_fisse_stmt->get_result();
                        while($f_item = $elenco_fisse->fetch_assoc()):
                        ?>
                        <div class="py-1.5 flex justify-between items-center text-xs">
                            <span class="text-gray-600"><?php echo $f_item['descrizione']; ?> (<?php echo $f_item['importo']; ?>€)</span>
                            <a href="index.php?mese=<?php echo $mese_attivo; ?>&anno=<?php echo $anno_attivo; ?>&delete_fissa=<?php echo $f_item['id']; ?>" onclick="return confirm('Vuoi eliminare questa spesa fissa?')" class="text-red-500 font-bold hover:underline">Elimina</a>
                        </div>
                        <?php endwhile; ?>
                    </div>
                </div>

                <div class="bg-white p-4 rounded-xl border border-gray-100 shadow-sm">
                    <h4 class="text-xs font-bold text-gray-400 uppercase mb-3">Cambia Password</h4>
                    <form method="POST" class="flex flex-col sm:flex-row gap-2">
                        <input
                            type="password"
                            name="nuova_password"
                            placeholder="Nuova password"
                            required
                            autocomplete="new-password"
                            class="w-full px-3 py-1.5 border rounded-xl text-sm bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500"
                        >
                        <button
                            type="submit"
                            name="update_password"
                            onclick="return confirm('Vuoi cambiare la password di accesso?')"
                            class="bg-gray-800 text-white text-xs font-semibold px-4 rounded-xl hover:bg-gray-900 transition"
                        >
                            Aggiorna
                        </button>
                    </form>
                </div>
            </div>
        </div>

    <?php endif; ?>
    </main>

    <script>
        const descrizioneSpesa = document.getElementById('descrizione-spesa');
        const importoSpesa = document.getElementById('importo-spesa');

        document.querySelectorAll('.preferito-spesa').forEach(function (preferito) {
            preferito.addEventListener('click', function () {
                descrizioneSpesa.value = this.dataset.descrizione;
                importoSpesa.value = this.dataset.importo;
                importoSpesa.focus();
                importoSpesa.select();
            });
        });

        const ricercaSpese = document.getElementById('ricerca-spese');

        if (ricercaSpese) {
            ricercaSpese.addEventListener('input', function () {
                const testo = this.value.toLowerCase().trim();

                document.querySelectorAll('.spesa-variabile').forEach(function (spesa) {
                    const descrizione = spesa.querySelector('.descrizione-spesa').textContent.toLowerCase();
                    spesa.style.display = descrizione.includes(testo) ? '' : 'none';
                });
            });
        }
    </script>
</body>
</html>

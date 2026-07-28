<?php
include 'config.php';
controlla_autenticazione();
include 'db.php';
date_default_timezone_set('Europe/Rome');

$mese_corrente = date('F');
$anno_corrente = date('Y');
$mese_precedente = date('F', strtotime('first day of last month'));
$anno_precedente = date('Y', strtotime('first day of last month'));

$mesi_it = ["January" => "Gennaio", "February" => "Febbraio", "March" => "Marzo", "April" => "Aprile", "May" => "Maggio", "June" => "Giugno", "July" => "Luglio", "August" => "Agosto", "September" => "Settembre", "October" => "Ottobre", "November" => "Novembre", "December" => "Dicembre"];

// 1. RECUPERO DATI MESE CORRENTE PER IL PRIMO GRAFICO
$query_mese = $conn->prepare("SELECT * FROM mesi WHERE nome = ? AND anno = ?");
$query_mese->bind_param("si", $mese_corrente, $anno_corrente);
$query_mese->execute();
$mese_dati = $query_mese->get_result()->fetch_assoc();

$query_precedente = $conn->prepare("SELECT id FROM mesi WHERE nome = ? AND anno = ?");
$query_precedente->bind_param("si", $mese_precedente, $anno_precedente);
$query_precedente->execute();
$mese_precedente_dati = $query_precedente->get_result()->fetch_assoc();

$tot_var_precedente = null;
if ($mese_precedente_dati) {
    $mese_precedente_id = intval($mese_precedente_dati['id']);
    $res_var_precedente = $conn->query("SELECT SUM(importo) AS totale FROM spese_variabili WHERE mese_id = $mese_precedente_id");
    $tot_var_precedente = floatval($res_var_precedente->fetch_assoc()['totale'] ?? 0);
}

?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistiche Budget</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 font-sans min-h-screen pb-12">

    <header class="bg-[#008080] text-white py-4 shadow-md sticky top-0 z-40 px-4">
        <div class="max-w-4xl mx-auto flex justify-between items-center">
            <div>
                <h1 class="text-xl font-bold tracking-wide">📊 RIEPILOGO & STATISTICHE</h1>
                <p class="text-sm opacity-90">Analisi dell'andamento spese</p>
            </div>
            <a href="index.php" class="bg-white/20 hover:bg-white/30 text-white text-xs font-semibold px-4 py-2 rounded-xl border border-white/20 transition">
                🏠 Torna alla Home
            </a>
        </div>
    </header>

    <main class="max-w-4xl mx-auto px-4 mt-6 space-y-6">

        <?php if ($mese_dati): 
            $mese_id = $mese_dati['id'];
            
            // Calcolo spese fisse
            $res_fisse = $conn->query("SELECT SUM(importo) as totale FROM spese_fisse WHERE mese_id = $mese_id");
            $tot_fisse = $res_fisse->fetch_assoc()['totale'] ?? 0;

            // Calcolo spese variabili
            $res_var = $conn->query("SELECT SUM(importo) as totale FROM spese_variabili WHERE mese_id = $mese_id");
            $tot_var = $res_var->fetch_assoc()['totale'] ?? 0;

            $entrata_totale = $mese_dati['entrata'];
            $percentuale_risparmio = floatval($mese_dati['percentuale_risparmio']);
            $quota_risparmio = $entrata_totale * ($percentuale_risparmio / 100);
            
            // Budget iniziale per spese varie (il 100% della nostra barra)
            $budget_variabile_iniziale = $entrata_totale - $quota_risparmio - $tot_fisse;
            $budget_restante_mese = $budget_variabile_iniziale - $tot_var;

            // Calcolo percentuale spesa
            if ($budget_variabile_iniziale > 0) {
                $percentuale_spesa = ($tot_var / $budget_variabile_iniziale) * 100;
                $percentuale_rimasta = 100 - $percentuale_spesa;
            } else {
                $percentuale_spesa = 100;
                $percentuale_rimasta = 0;
            }

            // Arrotondamenti per la grafica
            $percentuale_barra = max(0, min(100, $percentuale_spesa)); 
            
            // Colore dinamico della barra in base a quanto hai speso
            $colore_barra = "bg-[#008080]"; // Ottanio standard
            if ($percentuale_barra > 75) $colore_barra = "bg-orange-500"; // Attenzione
            if ($percentuale_barra >= 100) $colore_barra = "bg-red-600"; // Sforato
        ?>
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200">
            <h3 class="text-base font-bold text-gray-700 mb-1">Stato dei consumi di <?php echo $mesi_it[$mese_corrente]; ?></h3>
            <p class="text-xs text-gray-400 mb-4">Analisi del budget di spesa varia quotidiana (spese fisse e risparmio già protetti)</p>
            
            <div class="flex justify-between text-xs font-bold text-gray-500 mb-1">
                <span>Speso: <?php echo number_format($tot_var, 2, ',', '.'); ?>€ (<?php echo round($percentuale_spesa); ?>%)</span>
                <span>Disponibile: <?php echo number_format($budget_restante_mese, 2, ',', '.'); ?>€</span>
            </div>
            
            <div class="w-full bg-gray-200 h-6 rounded-xl overflow-hidden shadow-inner relative">
                <div class="h-full <?php echo $colore_barra; ?> transition-all duration-500" style="width: <?php echo $percentuale_barra; ?>%"></div>
            </div>
            <div class="flex justify-between text-[10px] text-gray-400 mt-1">
                <span>0€ (Inizio Mese)</span>
                <span>Budget Max (<?php echo number_format($budget_variabile_iniziale, 2, ',', '.'); ?>€)</span>
            </div>
        </div>

        <?php if ($tot_var_precedente !== null):
            $differenza_mensile = $tot_var - $tot_var_precedente;
        ?>
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200">
            <h3 class="text-base font-bold text-gray-700 mb-1">Confronto mensile</h3>
            <p class="text-xs text-gray-400 mb-4">
                Spese variabili rispetto a <?php echo $mesi_it[$mese_precedente] . " " . $anno_precedente; ?>
            </p>

            <div class="grid grid-cols-2 gap-4 text-center">
                <div class="bg-gray-50 rounded-xl p-4">
                    <p class="text-xs text-gray-400">Mese precedente</p>
                    <p class="text-lg font-bold text-gray-700"><?php echo number_format($tot_var_precedente, 2, ',', '.'); ?> €</p>
                </div>
                <div class="bg-gray-50 rounded-xl p-4">
                    <p class="text-xs text-gray-400">Mese corrente</p>
                    <p class="text-lg font-bold text-gray-700"><?php echo number_format($tot_var, 2, ',', '.'); ?> €</p>
                </div>
            </div>

            <p class="mt-4 text-sm font-semibold <?php echo $differenza_mensile <= 0 ? 'text-green-600' : 'text-red-600'; ?>">
                <?php echo $differenza_mensile <= 0 ? 'Riduzione: ' : 'Aumento: +'; ?>
                <?php echo number_format(abs($differenza_mensile), 2, ',', '.'); ?> €
            </p>
        </div>
        <?php endif; ?>

        <?php endif; ?>


        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200">
            <h3 class="text-base font-bold text-gray-700 mb-1">Storico Mesi</h3>
            <p class="text-xs text-gray-400 mb-6">Confronto dei budget residui dei mesi passati</p>

            <div class="space-y-5">
                <?php
                // Prendi tutti i mesi registrati
                $tutti_mesi = $conn->query("SELECT * FROM mesi ORDER BY anno DESC, FIELD(nome, 'December', 'November', 'October', 'September', 'August', 'July', 'June', 'May', 'April', 'March', 'February', 'January')");
                
                if ($tutti_mesi->num_rows == 0) {
                    echo "<p class='text-sm text-gray-400 text-center py-4'>Nessun dato storico presente.</p>";
                }

                while ($m = $tutti_mesi->fetch_assoc()):
                    $m_id = $m['id'];
                    
                    // Calcola spese fisse del mese in ciclo
                    $rf = $conn->query("SELECT SUM(importo) as totale FROM spese_fisse WHERE mese_id = $m_id");
                    $tf = $rf->fetch_assoc()['totale'] ?? 0;

                    // Calcola variabli del mese in ciclo
                    $rv = $conn->query("SELECT SUM(importo) as totale FROM spese_variabili WHERE mese_id = $m_id");
                    $tv = $rv->fetch_assoc()['totale'] ?? 0;

                    $entrata = $m['entrata'];
                    $risp = $entrata * ($m['percentuale_risparmio'] / 100);
                    $b_iniziale = $entrata - $risp - $tf;
                    $b_restante = $b_iniziale - $tv;

                    // Calcolo percentuale di avanzo per la barra
                    // Se b_restante > 0 significa che sono avanzati soldi. Più ne avanzano, più la barra è piena.
                    if ($b_iniziale > 0) {
                        $perc_avanzo = ($b_restante / $b_iniziale) * 100;
                    } else {
                        $perc_avanzo = 0;
                    }
                    $perc_avanzo_barra = max(0, min(100, $perc_avanzo));
                ?>
                <div>
                    <div class="flex justify-between items-center text-sm mb-1">
                        <span class="font-semibold text-gray-700"><?php echo $mesi_it[$m['nome']] . " " . $m['anno']; ?></span>
                        <span class="text-xs font-bold <?php echo $b_restante >= 0 ? 'text-green-600' : 'text-red-600'; ?>">
                            <?php echo $b_restante >= 0 ? 'Avanzati: +' : 'Sforato: '; ?><?php echo number_format($b_restante, 2, ',', '.'); ?> €
                        </span>
                    </div>
                    
                    <div class="flex items-center gap-3">
                        <div class="flex-1 bg-gray-100 h-4 rounded-lg overflow-hidden border border-gray-200">
                            <div class="h-full <?php echo $b_restante >= 0 ? 'bg-[#12A0D7]' : 'bg-red-500'; ?>" style="width: <?php echo $b_restante >= 0 ? $perc_avanzo_barra : 100; ?>%"></div>
                        </div>
                        <span class="text-xs text-gray-400 w-8 text-right"><?php echo $b_restante >= 0 ? round($perc_avanzo).'%' : '0%'; ?></span>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>

    </main>
</body>
</html>

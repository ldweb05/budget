<?php
include 'config.php';
controlla_autenticazione();
include 'db.php';
date_default_timezone_set('Europe/Rome');

// Azione: Prelievo Volontario Manuale
if (isset($_POST['prelievo_manuale'])) {
    $importo = floatval($_POST['importo']);
    $causale = htmlspecialchars($_POST['causale']);
    $data_oggi = date('Y-m-d');

    if ($importo > 0 && !empty($causale)) {
        $stmt = $conn->prepare("INSERT INTO fondo_risparmio (importo, tipo, causale, data_movimento) VALUES (?, 'prelievo', ?, ?)");
        $stmt->bind_param("dss", $importo, $causale, $data_oggi);
        $stmt->execute();
    }
    header("Location: salvadanaio.php");
    exit;
}

// Azione: Versamento Volontario Manuale (Opzionale, se vuoi rimpinguare tu il fondo)
if (isset($_POST['versamento_manuale'])) {
    $importo = floatval($_POST['importo']);
    $causale = htmlspecialchars($_POST['causale']);
    $data_oggi = date('Y-m-d');

    if ($importo > 0 && !empty($causale)) {
        $stmt = $conn->prepare("INSERT INTO fondo_risparmio (importo, tipo, causale, data_movimento) VALUES (?, 'versamento', ?, ?)");
        $stmt->bind_param("dss", $importo, $causale, $data_oggi);
        $stmt->execute();
    }
    header("Location: salvadanaio.php");
    exit;
}

// Calcolo del saldo totale del fondo
$res_versamenti = $conn->query("SELECT SUM(importo) as totale FROM fondo_risparmio WHERE tipo = 'versamento'");
$tot_versamenti = $res_versamenti->fetch_assoc()['totale'] ?? 0;

$res_prelievi = $conn->query("SELECT SUM(importo) as totale FROM fondo_risparmio WHERE tipo = 'prelievo'");
$tot_prelievi = $res_prelievi->fetch_assoc()['totale'] ?? 0;

$saldo_fondo = $tot_versamenti - $tot_prelievi;
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Salvadanaio Risparmi</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 font-sans min-h-screen pb-12">

    <header class="bg-[#008080] text-white py-4 shadow-md sticky top-0 z-40 px-4">
        <div class="max-w-4xl mx-auto flex flex-col gap-3 sm:flex-row sm:justify-between sm:items-center">
            <div>
                <h1 class="text-lg sm:text-xl font-bold tracking-wide">🐷 FONDO RISPARMI (15%)</h1>
                <p class="text-sm opacity-90">Il tuo paracadute finanziario</p>
            </div>
            <a href="index.php" class="bg-white/20 hover:bg-white/30 text-white text-xs font-semibold px-4 py-2 rounded-xl border border-white/20 transition">🏠 Home</a>
        </div>
    </header>

    <main class="max-w-4xl mx-auto px-4 mt-6 grid grid-cols-1 md:grid-cols-3 gap-6">
        
        <div class="md:col-span-1 space-y-6">
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200 text-center">
                <h2 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Totale Risparmi Accumulati</h2>
                <div class="text-4xl font-extrabold text-blue-600 mb-2">
                    <?php echo number_format($saldo_fondo, 2, ',', '.'); ?> €
                </div>
                <p class="text-[11px] text-gray-400 leading-relaxed">
                    Versati totali: +<?php echo number_format($tot_versamenti, 2, ',', '.'); ?>€<br>
                    Prelevati/Sforati totali: -<?php echo number_format($tot_prelievi, 2, ',', '.'); ?>€
                </p>
            </div>

            <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-200">
                <h3 class="text-sm font-bold text-gray-700 mb-3">🛠️ Movimento Manuale</h3>
                <form method="POST" class="space-y-3">
                    <input type="text" name="causale" placeholder="Motivo (es. Tasse, Vacanza)" required class="w-full px-3 py-2 border rounded-xl bg-gray-50 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <input type="number" step="0.01" name="importo" placeholder="Importo €" required class="w-full px-3 py-2 border rounded-xl bg-gray-50 text-sm font-bold focus:outline-none focus:ring-2 focus:ring-blue-500">
                    
                    <div class="grid grid-cols-2 gap-2 pt-1">
                        <button type="submit" name="prelievo_manuale" class="bg-red-500 text-white font-semibold py-2 rounded-xl text-xs shadow-sm hover:bg-red-600 transition">
                            🔻 Preleva
                        </button>
                        <button type="submit" name="versamento_manuale" class="bg-[#008080] text-white font-semibold py-2 rounded-xl text-xs shadow-sm hover:opacity-90 transition">
                            🔺 Versa
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="md:col-span-2">
            <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-200">
                <h3 class="text-sm font-bold text-gray-700 mb-3">📜 Registro Storico dei Risparmi</h3>
                <ul class="divide-y divide-gray-100 max-h-[500px] overflow-y-auto">
                    <?php
                    $movimenti = $conn->query("SELECT * FROM fondo_risparmio ORDER BY id DESC");
                    if ($movimenti->num_rows == 0):
                        echo "<p class='text-xs text-gray-400 py-4 text-center'>Nessun movimento registrato.</p>";
                    endif;
                    while ($m = $movimenti->fetch_assoc()):
                    ?>
                    <li class="py-3 flex justify-between items-center">
                        <div class="pr-4">
                            <p class="text-sm text-gray-700 font-medium leading-tight"><?php echo $m['causale']; ?></p>
                            <p class="text-[10px] text-gray-400 mt-0.5"><?php echo date('d/m/Y', strtotime($m['data_movimento'])); ?></p>
                        </div>
                        <span class="text-sm font-bold shrink-0 <?php echo $m['tipo'] == 'versamento' ? 'text-green-600' : 'text-red-500'; ?>">
                            <?php echo $m['tipo'] == 'versamento' ? '+' : '-'; ?><?php echo number_format($m['importo'], 2, ',', '.'); ?> €
                        </span>
                    </li>
                    <?php endwhile; ?>
                </ul>
            </div>
        </div>

    </main>
</body>
</html>


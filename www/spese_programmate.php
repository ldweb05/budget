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
$errore = '';
$messaggio = '';

function aggiungi_mesi_mantenendo_giorno($data_iniziale, $mesi_da_aggiungere)
{
    $data = new DateTimeImmutable($data_iniziale);
    $giorno_originale = intval($data->format('d'));

    $primo_giorno_mese_destinazione = $data
        ->modify('first day of this month')
        ->modify('+' . intval($mesi_da_aggiungere) . ' months');

    $ultimo_giorno_destinazione = intval(
        $primo_giorno_mese_destinazione->format('t')
    );

    $giorno_destinazione = min(
        $giorno_originale,
        $ultimo_giorno_destinazione
    );

    return $primo_giorno_mese_destinazione
        ->setDate(
            intval($primo_giorno_mese_destinazione->format('Y')),
            intval($primo_giorno_mese_destinazione->format('m')),
            $giorno_destinazione
        )
        ->format('Y-m-d');
}

if (isset($_POST['elimina_piano'])) {
    $piano_id = intval($_POST['piano_id'] ?? 0);

    if ($piano_id > 0) {
        $stmt = $conn->prepare(
            "DELETE FROM piani_spese_programmate
             WHERE id = ?
               AND utente_id = ?"
        );
        $stmt->bind_param(
            "ii",
            $piano_id,
            $utente_id
        );
        $stmt->execute();
        $eliminato = $stmt->affected_rows > 0;
        $stmt->close();

        if ($eliminato) {
            header('Location: spese_programmate.php?eliminato=1');
            exit;
        }
    }

    header('Location: spese_programmate.php');
    exit;
}

if (isset($_POST['modifica_scadenza'])) {
    $scadenza_id = intval($_POST['scadenza_id'] ?? 0);
    $nuova_data_scadenza = trim(
        $_POST['nuova_data_scadenza'] ?? ''
    );
    $nuovo_importo = round(
        floatval($_POST['nuovo_importo'] ?? 0),
        2
    );

    $data_valida = DateTimeImmutable::createFromFormat(
        'Y-m-d',
        $nuova_data_scadenza
    );

    if (
        $scadenza_id > 0 &&
        $nuovo_importo > 0 &&
        $data_valida &&
        $data_valida->format('Y-m-d') === $nuova_data_scadenza
    ) {
        $stmt = $conn->prepare(
            "UPDATE scadenze_spese_programmate s
             INNER JOIN piani_spese_programmate p
                ON p.id = s.piano_id
             SET
                s.data_scadenza = ?,
                s.importo = ?
             WHERE s.id = ?
               AND p.utente_id = ?
               AND s.pagata = 0"
        );
        $stmt->bind_param(
            "sdii",
            $nuova_data_scadenza,
            $nuovo_importo,
            $scadenza_id,
            $utente_id
        );
        $stmt->execute();
        $modificata = $stmt->affected_rows > 0;
        $stmt->close();

        if ($modificata) {
            header(
                'Location: spese_programmate.php?modificata=1'
            );
            exit;
        }
    }

    header('Location: spese_programmate.php');
    exit;
}

if (isset($_POST['segna_rata_pagata'])) {
    $scadenza_id = intval($_POST['scadenza_id'] ?? 0);
    $data_pagamento = trim($_POST['data_pagamento'] ?? date('Y-m-d'));
    $data_valida = DateTimeImmutable::createFromFormat(
        'Y-m-d',
        $data_pagamento
    );

    if (
        $scadenza_id > 0 &&
        $data_valida &&
        $data_valida->format('Y-m-d') === $data_pagamento
    ) {
        $stmt = $conn->prepare(
            "UPDATE scadenze_spese_programmate s
             INNER JOIN piani_spese_programmate p
                ON p.id = s.piano_id
             SET
                s.pagata = 1,
                s.data_pagamento = ?
             WHERE s.id = ?
               AND p.utente_id = ?"
        );
        $stmt->bind_param(
            "sii",
            $data_pagamento,
            $scadenza_id,
            $utente_id
        );
        $stmt->execute();
        $stmt->close();
    }

    header('Location: spese_programmate.php');
    exit;
}

if (isset($_POST['estingui_rate'])) {
    $piano_id = intval($_POST['piano_id'] ?? 0);
    $numero_rate_estinzione = intval(
        $_POST['numero_rate_estinzione'] ?? 0
    );
    $data_pagamento = trim(
        $_POST['data_pagamento_estinzione'] ?? date('Y-m-d')
    );
    $data_valida = DateTimeImmutable::createFromFormat(
        'Y-m-d',
        $data_pagamento
    );

    if (
        $piano_id > 0 &&
        $numero_rate_estinzione > 0 &&
        $data_valida &&
        $data_valida->format('Y-m-d') === $data_pagamento
    ) {
        $stmt_rate = $conn->prepare(
            "SELECT s.id
             FROM scadenze_spese_programmate s
             INNER JOIN piani_spese_programmate p
                ON p.id = s.piano_id
             WHERE s.piano_id = ?
               AND p.utente_id = ?
               AND s.pagata = 0
             ORDER BY s.data_scadenza DESC, s.numero_rata DESC
             LIMIT ?"
        );
        $stmt_rate->bind_param(
            "iii",
            $piano_id,
            $utente_id,
            $numero_rate_estinzione
        );
        $stmt_rate->execute();
        $risultato_rate = $stmt_rate->get_result();

        $scadenze_da_estinguere = [];

        while ($rata = $risultato_rate->fetch_assoc()) {
            $scadenze_da_estinguere[] = intval($rata['id']);
        }

        $stmt_rate->close();

        if ($scadenze_da_estinguere) {
            $conn->begin_transaction();

            try {
                $stmt_pagamento = $conn->prepare(
                    "UPDATE scadenze_spese_programmate s
                     INNER JOIN piani_spese_programmate p
                        ON p.id = s.piano_id
                     SET
                        s.pagata = 1,
                        s.data_pagamento = ?
                     WHERE s.id = ?
                       AND p.utente_id = ?
                       AND s.pagata = 0"
                );

                foreach ($scadenze_da_estinguere as $scadenza_id) {
                    $stmt_pagamento->bind_param(
                        "sii",
                        $data_pagamento,
                        $scadenza_id,
                        $utente_id
                    );

                    if (!$stmt_pagamento->execute()) {
                        throw new RuntimeException(
                            $stmt_pagamento->error
                        );
                    }
                }

                $stmt_pagamento->close();
                $conn->commit();

                header(
                    'Location: spese_programmate.php?estinte=' .
                    count($scadenze_da_estinguere)
                );
                exit;
            } catch (Throwable $eccezione) {
                $conn->rollback();
                $errore = 'Non è stato possibile registrare l’estinzione anticipata.';
            }
        }
    }
}

if (isset($_POST['crea_piano'])) {
    $descrizione = trim($_POST['descrizione'] ?? '');
    $importo_totale = round(floatval($_POST['importo_totale'] ?? 0), 2);
    $numero_rate = intval($_POST['numero_rate'] ?? 0);
    $importo_rata = round(floatval($_POST['importo_rata'] ?? 0), 2);
    $modalita = $_POST['modalita_scadenze'] ?? '';
    $data_prima_scadenza = trim($_POST['data_prima_scadenza'] ?? '');
    $frequenza_mesi = intval($_POST['frequenza_mesi'] ?? 0);
    $date_manuali_testo = trim($_POST['date_manuali'] ?? '');
    $note = trim($_POST['note'] ?? '');

    $modalita_valide = ['automatica', 'manuale'];
    $scadenze = [];

    if (
        $utente_id <= 0 ||
        $descrizione === '' ||
        $importo_totale <= 0 ||
        $numero_rate <= 0 ||
        $importo_rata <= 0 ||
        !in_array($modalita, $modalita_valide, true)
    ) {
        $errore = 'Compilare correttamente tutti i campi obbligatori.';
    }

    if ($errore === '' && $modalita === 'automatica') {
        $data_valida = DateTimeImmutable::createFromFormat(
            'Y-m-d',
            $data_prima_scadenza
        );

        if (
            !$data_valida ||
            $data_valida->format('Y-m-d') !== $data_prima_scadenza ||
            $frequenza_mesi <= 0
        ) {
            $errore = 'Indicare una prima scadenza valida e una frequenza maggiore di zero.';
        } else {
            for ($indice = 0; $indice < $numero_rate; $indice++) {
                $scadenze[] = aggiungi_mesi_mantenendo_giorno(
                    $data_prima_scadenza,
                    $indice * $frequenza_mesi
                );
            }
        }
    }

    if ($errore === '' && $modalita === 'manuale') {
        $righe = preg_split(
            '/\R+/',
            $date_manuali_testo,
            -1,
            PREG_SPLIT_NO_EMPTY
        );

        foreach ($righe as $riga) {
            $data_scadenza = trim($riga);
            $data_valida = DateTimeImmutable::createFromFormat(
                'Y-m-d',
                $data_scadenza
            );

            if (
                !$data_valida ||
                $data_valida->format('Y-m-d') !== $data_scadenza
            ) {
                $errore = 'Le date manuali devono essere nel formato AAAA-MM-GG, una per riga.';
                break;
            }

            $scadenze[] = $data_scadenza;
        }

        if ($errore === '' && count($scadenze) !== $numero_rate) {
            $errore = 'Il numero delle date manuali deve coincidere con il numero delle rate.';
        }
    }

    if ($errore === '') {
        $somma_rate_precedenti = round(
            $importo_rata * max(0, $numero_rate - 1),
            2
        );
        $ultima_rata = round(
            $importo_totale - $somma_rate_precedenti,
            2
        );

        if ($ultima_rata <= 0) {
            $errore = 'Importo totale, numero rate e importo rata non sono coerenti.';
        }
    }

    if ($errore === '') {
        $conn->begin_transaction();

        try {
            $data_prima_db = $modalita === 'automatica'
                ? $data_prima_scadenza
                : null;

            $frequenza_db = $modalita === 'automatica'
                ? $frequenza_mesi
                : null;

            $stmt_piano = $conn->prepare(
                "INSERT INTO piani_spese_programmate
                    (
                        utente_id,
                        descrizione,
                        importo_totale,
                        numero_rate,
                        importo_rata,
                        modalita_scadenze,
                        data_prima_scadenza,
                        frequenza_mesi,
                        note
                    )
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );

            $stmt_piano->bind_param(
                "isdidssis",
                $utente_id,
                $descrizione,
                $importo_totale,
                $numero_rate,
                $importo_rata,
                $modalita,
                $data_prima_db,
                $frequenza_db,
                $note
            );

            if (!$stmt_piano->execute()) {
                throw new RuntimeException($stmt_piano->error);
            }

            $piano_id = intval($conn->insert_id);
            $stmt_piano->close();

            $stmt_scadenza = $conn->prepare(
                "INSERT INTO scadenze_spese_programmate
                    (piano_id, numero_rata, importo, data_scadenza)
                 VALUES (?, ?, ?, ?)"
            );

            foreach ($scadenze as $indice => $data_scadenza) {
                $numero_rata = $indice + 1;
                $importo_scadenza = $numero_rata === $numero_rate
                    ? $ultima_rata
                    : $importo_rata;

                $stmt_scadenza->bind_param(
                    "iids",
                    $piano_id,
                    $numero_rata,
                    $importo_scadenza,
                    $data_scadenza
                );

                if (!$stmt_scadenza->execute()) {
                    throw new RuntimeException($stmt_scadenza->error);
                }
            }

            $stmt_scadenza->close();
            $conn->commit();

            header('Location: spese_programmate.php?creato=1');
            exit;
        } catch (Throwable $eccezione) {
            $conn->rollback();
            $errore = 'Il piano non è stato salvato.';
        }
    }
}

if (isset($_GET['creato'])) {
    $messaggio = 'Piano e scadenze creati correttamente.';
}

if (isset($_GET['estinte'])) {
    $numero_estinte = intval($_GET['estinte']);

    if ($numero_estinte > 0) {
        $messaggio = $numero_estinte === 1
            ? 'Rata estinta anticipatamente.'
            : $numero_estinte . ' rate estinte anticipatamente.';
    }
}

if (isset($_GET['eliminato'])) {
    $messaggio = 'Piano eliminato correttamente.';
}

if (isset($_GET['modificata'])) {
    $messaggio = 'Scadenza modificata correttamente.';
}

$inizio_mese = date('Y-m-01');
$fine_mese = date('Y-m-t');
$data_oggi = date('Y-m-d');

$stmt_mese = $conn->prepare(
    "SELECT
        s.id,
        s.numero_rata,
        s.importo,
        s.data_scadenza,
        s.pagata,
        s.data_pagamento,
        p.descrizione,
        p.numero_rate
     FROM scadenze_spese_programmate s
     INNER JOIN piani_spese_programmate p
        ON p.id = s.piano_id
     WHERE p.utente_id = ?
       AND s.data_scadenza BETWEEN ? AND ?
     ORDER BY s.data_scadenza, p.id, s.numero_rata"
);
$stmt_mese->bind_param(
    "iss",
    $utente_id,
    $inizio_mese,
    $fine_mese
);
$stmt_mese->execute();
$rate_mese = $stmt_mese->get_result();

$stmt_future = $conn->prepare(
    "SELECT
        s.id,
        s.numero_rata,
        s.importo,
        s.data_scadenza,
        s.pagata,
        s.data_pagamento,
        p.descrizione,
        p.numero_rate
     FROM scadenze_spese_programmate s
     INNER JOIN piani_spese_programmate p
        ON p.id = s.piano_id
     WHERE p.utente_id = ?
       AND s.data_scadenza >= ?
     ORDER BY s.data_scadenza, p.id, s.numero_rata"
);
$stmt_future->bind_param(
    "is",
    $utente_id,
    $data_oggi
);
$stmt_future->execute();
$rate_future = $stmt_future->get_result();

$stmt_piani = $conn->prepare(
    "SELECT
        p.id,
        p.descrizione,
        COUNT(s.id) AS rate_residue
     FROM piani_spese_programmate p
     INNER JOIN scadenze_spese_programmate s
        ON s.piano_id = p.id
       AND s.pagata = 0
     WHERE p.utente_id = ?
     GROUP BY p.id, p.descrizione
     HAVING COUNT(s.id) > 0
     ORDER BY p.descrizione"
);
$stmt_piani->bind_param("i", $utente_id);
$stmt_piani->execute();
$piani_con_rate_residue = $stmt_piani->get_result();

$stmt_elenco_piani = $conn->prepare(
    "SELECT
        p.id,
        p.descrizione,
        p.importo_totale,
        p.numero_rate,
        p.modalita_scadenze,
        COUNT(s.id) AS scadenze_totali,
        SUM(CASE WHEN s.pagata = 1 THEN 1 ELSE 0 END) AS rate_pagate
     FROM piani_spese_programmate p
     LEFT JOIN scadenze_spese_programmate s
        ON s.piano_id = p.id
     WHERE p.utente_id = ?
     GROUP BY
        p.id,
        p.descrizione,
        p.importo_totale,
        p.numero_rate,
        p.modalita_scadenze
     ORDER BY p.id DESC"
);
$stmt_elenco_piani->bind_param("i", $utente_id);
$stmt_elenco_piani->execute();
$elenco_piani = $stmt_elenco_piani->get_result();
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >
    <title>Spese programmate</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 font-sans min-h-screen pb-12">

<header class="bg-[#008080] text-white py-4 shadow-md sticky top-0 z-40 px-4">
    <div class="max-w-5xl mx-auto flex flex-col gap-3 sm:flex-row sm:justify-between sm:items-center">
        <div>
            <h1 class="text-lg sm:text-xl font-bold tracking-wide">
                📅 SPESE PROGRAMMATE
            </h1>
            <p class="text-sm opacity-90">
                Rate e pagamenti futuri già conosciuti
            </p>
        </div>

        <a
            href="index.php"
            class="bg-white/20 hover:bg-white/30 text-white text-xs font-semibold px-4 py-2 rounded-xl border border-white/20 transition"
        >
            🏠 Home
        </a>
    </div>
</header>

<main class="max-w-5xl mx-auto px-4 mt-6 space-y-6">

    <?php if ($errore !== ''): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm">
            <?php echo htmlspecialchars($errore); ?>
        </div>
    <?php endif; ?>

    <?php if ($messaggio !== ''): ?>
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl text-sm">
            <?php echo htmlspecialchars($messaggio); ?>
        </div>
    <?php endif; ?>

    <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-200">
        <h2 class="text-sm font-bold text-gray-700 mb-4">
            ➕ Nuovo piano
        </h2>

        <form method="POST" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <input
                    type="text"
                    name="descrizione"
                    placeholder="Descrizione"
                    required
                    class="w-full px-3 py-2 border rounded-xl bg-gray-50 text-sm"
                >

                <input
                    type="number"
                    name="importo_totale"
                    step="0.01"
                    min="0.01"
                    placeholder="Importo totale €"
                    required
                    class="w-full px-3 py-2 border rounded-xl bg-gray-50 text-sm"
                >

                <input
                    type="number"
                    name="numero_rate"
                    min="1"
                    placeholder="Numero rate"
                    required
                    class="w-full px-3 py-2 border rounded-xl bg-gray-50 text-sm"
                >

                <input
                    type="number"
                    name="importo_rata"
                    step="0.01"
                    min="0.01"
                    placeholder="Importo rata €"
                    required
                    class="w-full px-3 py-2 border rounded-xl bg-gray-50 text-sm"
                >
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-500 mb-2">
                    Modalità delle scadenze
                </label>

                <select
                    id="modalita-scadenze"
                    name="modalita_scadenze"
                    required
                    class="w-full px-3 py-2 border rounded-xl bg-gray-50 text-sm"
                >
                    <option value="automatica">
                        Generazione automatica
                    </option>
                    <option value="manuale">
                        Inserimento manuale delle date
                    </option>
                </select>
            </div>

            <div
                id="campi-automatici"
                class="grid grid-cols-1 md:grid-cols-2 gap-3"
            >
                <div>
                    <label class="block text-xs font-bold text-gray-500 mb-1">
                        Prima scadenza
                    </label>
                    <input
                        type="date"
                        name="data_prima_scadenza"
                        class="w-full px-3 py-2 border rounded-xl bg-gray-50 text-sm"
                    >
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-500 mb-1">
                        Frequenza in mesi
                    </label>
                    <input
                        type="number"
                        name="frequenza_mesi"
                        min="1"
                        value="1"
                        class="w-full px-3 py-2 border rounded-xl bg-gray-50 text-sm"
                    >
                </div>
            </div>

            <div id="campi-manuali" class="hidden">
                <label class="block text-xs font-bold text-gray-500 mb-1">
                    Date delle rate
                </label>

                <textarea
                    name="date_manuali"
                    rows="5"
                    placeholder="2026-07-31&#10;2026-11-30&#10;2027-02-28"
                    class="w-full px-3 py-2 border rounded-xl bg-gray-50 text-sm font-mono"
                ></textarea>

                <p class="text-[11px] text-gray-400 mt-1">
                    Inserire una data per riga nel formato AAAA-MM-GG.
                </p>
            </div>

            <textarea
                name="note"
                rows="2"
                placeholder="Note facoltative"
                class="w-full px-3 py-2 border rounded-xl bg-gray-50 text-sm"
            ></textarea>

            <button
                type="submit"
                name="crea_piano"
                class="w-full bg-[#008080] text-white font-semibold py-3 rounded-xl text-sm hover:bg-[#006666] transition"
            >
                Crea piano e scadenze
            </button>
        </form>
    </div>

    <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-200">
        <h2 class="text-sm font-bold text-gray-700 mb-4">
            🗂️ Piani esistenti
        </h2>

        <ul class="divide-y divide-gray-100">
            <?php if ($elenco_piani->num_rows === 0): ?>
                <li class="py-4 text-xs text-gray-400 text-center">
                    Nessun piano presente.
                </li>
            <?php endif; ?>

            <?php while ($piano = $elenco_piani->fetch_assoc()): ?>
                <li class="py-3 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm font-semibold text-gray-700">
                            <?php echo htmlspecialchars($piano['descrizione']); ?>
                        </p>

                        <p class="text-[11px] text-gray-400">
                            <?php echo number_format($piano['importo_totale'], 2, ',', '.'); ?> €
                            ·
                            <?php echo intval($piano['rate_pagate']); ?>
                            di
                            <?php echo intval($piano['scadenze_totali']); ?>
                            rate pagate
                            ·
                            <?php
                            echo $piano['modalita_scadenze'] === 'automatica'
                                ? 'automatico'
                                : 'date manuali';
                            ?>
                        </p>
                    </div>

                    <form method="POST" class="shrink-0">
                        <input
                            type="hidden"
                            name="piano_id"
                            value="<?php echo intval($piano['id']); ?>"
                        >

                        <button
                            type="submit"
                            name="elimina_piano"
                            onclick="return confirm('Eliminare il piano e tutte le sue scadenze? Lo storico delle rate pagate verrà eliminato definitivamente.')"
                            class="text-xs font-semibold text-red-500 hover:underline"
                        >
                            Elimina piano
                        </button>
                    </form>
                </li>
            <?php endwhile; ?>
        </ul>
    </div>

    <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-200">
        <h2 class="text-sm font-bold text-gray-700 mb-4">
            ⚡ Estinzione anticipata
        </h2>

        <?php if ($piani_con_rate_residue->num_rows === 0): ?>
            <p class="text-xs text-gray-400">
                Non ci sono rate residue da estinguere.
            </p>
        <?php else: ?>
            <form
                method="POST"
                class="grid grid-cols-1 md:grid-cols-4 gap-3 items-end"
            >
                <div class="md:col-span-2">
                    <label class="block text-xs font-bold text-gray-500 mb-1">
                        Piano
                    </label>
                    <select
                        name="piano_id"
                        required
                        class="w-full px-3 py-2 border rounded-xl bg-gray-50 text-sm"
                    >
                        <?php while ($piano = $piani_con_rate_residue->fetch_assoc()): ?>
                            <option value="<?php echo intval($piano['id']); ?>">
                                <?php
                                echo htmlspecialchars($piano['descrizione']) .
                                    ' — ' .
                                    intval($piano['rate_residue']) .
                                    ' residue';
                                ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-500 mb-1">
                        Ultime rate
                    </label>
                    <input
                        type="number"
                        name="numero_rate_estinzione"
                        min="1"
                        value="1"
                        required
                        class="w-full px-3 py-2 border rounded-xl bg-gray-50 text-sm"
                    >
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-500 mb-1">
                        Data pagamento
                    </label>
                    <input
                        type="date"
                        name="data_pagamento_estinzione"
                        value="<?php echo date('Y-m-d'); ?>"
                        required
                        class="w-full px-3 py-2 border rounded-xl bg-gray-50 text-sm"
                    >
                </div>

                <button
                    type="submit"
                    name="estingui_rate"
                    onclick="return confirm('Registrare l’estinzione anticipata delle ultime rate selezionate?')"
                    class="md:col-span-4 bg-amber-500 text-white font-semibold py-2.5 rounded-xl text-sm hover:bg-amber-600 transition"
                >
                    Estingui anticipatamente
                </button>
            </form>
        <?php endif; ?>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-200">
            <h2 class="text-sm font-bold text-gray-700 mb-3">
                📌 Rate del mese corrente
            </h2>

            <ul class="divide-y divide-gray-100">
                <?php if ($rate_mese->num_rows === 0): ?>
                    <li class="py-4 text-xs text-gray-400 text-center">
                        Nessuna rata prevista questo mese.
                    </li>
                <?php endif; ?>

                <?php while ($rata = $rate_mese->fetch_assoc()): ?>
                    <li class="py-3 flex justify-between items-center gap-4">
                        <div>
                            <p class="text-sm font-semibold text-gray-700">
                                <?php echo htmlspecialchars($rata['descrizione']); ?>
                            </p>
                            <p class="text-[11px] text-gray-400">
                                Rata
                                <?php echo intval($rata['numero_rata']); ?>
                                di
                                <?php echo intval($rata['numero_rate']); ?>
                                ·
                                <?php echo date('d/m/Y', strtotime($rata['data_scadenza'])); ?>
                            </p>
                        </div>

                        <div class="text-right shrink-0">
                            <span class="block text-sm font-bold <?php echo $rata['pagata'] ? 'text-green-600' : 'text-red-500'; ?>">
                                <?php echo number_format($rata['importo'], 2, ',', '.'); ?> €
                            </span>

                            <?php if (!$rata['pagata']): ?>
                                <form
                                    method="POST"
                                    class="mt-2 flex flex-col gap-1"
                                >
                                    <input
                                        type="hidden"
                                        name="scadenza_id"
                                        value="<?php echo intval($rata['id']); ?>"
                                    >
                                    <input
                                        type="date"
                                        name="nuova_data_scadenza"
                                        value="<?php echo htmlspecialchars($rata['data_scadenza']); ?>"
                                        required
                                        class="w-32 px-2 py-1 border rounded-lg bg-gray-50 text-[10px]"
                                    >
                                    <input
                                        type="number"
                                        name="nuovo_importo"
                                        step="0.01"
                                        min="0.01"
                                        value="<?php echo htmlspecialchars($rata['importo']); ?>"
                                        required
                                        class="w-32 px-2 py-1 border rounded-lg bg-gray-50 text-[10px]"
                                    >
                                    <button
                                        type="submit"
                                        name="modifica_scadenza"
                                        class="text-[10px] font-semibold text-blue-600 hover:underline"
                                    >
                                        Modifica
                                    </button>
                                </form>

                                <form method="POST" class="mt-1">
                                    <input
                                        type="hidden"
                                        name="scadenza_id"
                                        value="<?php echo intval($rata['id']); ?>"
                                    >
                                    <input
                                        type="hidden"
                                        name="data_pagamento"
                                        value="<?php echo date('Y-m-d'); ?>"
                                    >
                                    <button
                                        type="submit"
                                        name="segna_rata_pagata"
                                        class="text-[10px] font-semibold text-[#008080] hover:underline"
                                    >
                                        Segna pagata
                                    </button>
                                </form>
                            <?php else: ?>
                                <span class="text-[10px] text-green-600">
                                    Pagata
                                </span>
                            <?php endif; ?>
                        </div>
                    </li>
                <?php endwhile; ?>
            </ul>
        </div>

        <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-200">
            <h2 class="text-sm font-bold text-gray-700 mb-3">
                🔭 Prossime scadenze
            </h2>

            <ul class="divide-y divide-gray-100 max-h-[500px] overflow-y-auto">
                <?php if ($rate_future->num_rows === 0): ?>
                    <li class="py-4 text-xs text-gray-400 text-center">
                        Nessuna scadenza futura.
                    </li>
                <?php endif; ?>

                <?php while ($rata = $rate_future->fetch_assoc()): ?>
                    <li class="py-3 flex justify-between items-center gap-4">
                        <div>
                            <p class="text-sm font-semibold text-gray-700">
                                <?php echo htmlspecialchars($rata['descrizione']); ?>
                            </p>
                            <p class="text-[11px] text-gray-400">
                                Rata
                                <?php echo intval($rata['numero_rata']); ?>
                                di
                                <?php echo intval($rata['numero_rate']); ?>
                                ·
                                <?php echo date('d/m/Y', strtotime($rata['data_scadenza'])); ?>
                            </p>
                        </div>

                        <div class="text-right shrink-0">
                            <span class="block text-sm font-bold <?php echo $rata['pagata'] ? 'text-green-600' : 'text-gray-700'; ?>">
                                <?php echo number_format($rata['importo'], 2, ',', '.'); ?> €
                            </span>

                            <?php if (!$rata['pagata']): ?>
                                <form
                                    method="POST"
                                    class="mt-2 flex flex-col gap-1"
                                >
                                    <input
                                        type="hidden"
                                        name="scadenza_id"
                                        value="<?php echo intval($rata['id']); ?>"
                                    >
                                    <input
                                        type="date"
                                        name="nuova_data_scadenza"
                                        value="<?php echo htmlspecialchars($rata['data_scadenza']); ?>"
                                        required
                                        class="w-32 px-2 py-1 border rounded-lg bg-gray-50 text-[10px]"
                                    >
                                    <input
                                        type="number"
                                        name="nuovo_importo"
                                        step="0.01"
                                        min="0.01"
                                        value="<?php echo htmlspecialchars($rata['importo']); ?>"
                                        required
                                        class="w-32 px-2 py-1 border rounded-lg bg-gray-50 text-[10px]"
                                    >
                                    <button
                                        type="submit"
                                        name="modifica_scadenza"
                                        class="text-[10px] font-semibold text-blue-600 hover:underline"
                                    >
                                        Modifica
                                    </button>
                                </form>

                                <form method="POST" class="mt-1">
                                    <input
                                        type="hidden"
                                        name="scadenza_id"
                                        value="<?php echo intval($rata['id']); ?>"
                                    >
                                    <input
                                        type="hidden"
                                        name="data_pagamento"
                                        value="<?php echo date('Y-m-d'); ?>"
                                    >
                                    <button
                                        type="submit"
                                        name="segna_rata_pagata"
                                        class="text-[10px] font-semibold text-[#008080] hover:underline"
                                    >
                                        Paga ora
                                    </button>
                                </form>
                            <?php else: ?>
                                <span class="text-[10px] text-green-600">
                                    Pagata il
                                    <?php echo date('d/m/Y', strtotime($rata['data_pagamento'])); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </li>
                <?php endwhile; ?>
            </ul>
        </div>

    </div>
</main>

<script>
    const modalitaScadenze = document.getElementById('modalita-scadenze');
    const campiAutomatici = document.getElementById('campi-automatici');
    const campiManuali = document.getElementById('campi-manuali');

    function aggiornaModalitaScadenze() {
        const manuale = modalitaScadenze.value === 'manuale';

        campiAutomatici.classList.toggle('hidden', manuale);
        campiManuali.classList.toggle('hidden', !manuale);
    }

    modalitaScadenze.addEventListener(
        'change',
        aggiornaModalitaScadenze
    );

    aggiornaModalitaScadenze();
</script>

</body>
</html>

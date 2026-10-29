<?php
include 'config.php';
controlla_autenticazione();

if (($_SESSION['ruolo'] ?? '') === 'admin') {
    header('Location: admin.php');
    exit;
}

include 'db.php';
include 'budget_calcoli.php';
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

    // Calcolo dell'avanzo da riportare dal mese precedente (solo se positivo).
    $avanzo_da_riportare = 0.0;
    $percentuale_nuova = 15.0;
    $data_mese_creare = DateTime::createFromFormat('F Y', "$m_creare $a_creare");

    if ($data_mese_creare) {
        $data_mese_precedente = clone $data_mese_creare;
        $data_mese_precedente->modify('-1 month');
        $nome_mese_precedente = $data_mese_precedente->format('F');
        $anno_mese_precedente = intval($data_mese_precedente->format('Y'));

        $stmt_mese_prec = $conn->prepare("SELECT * FROM mesi WHERE utente_id = ? AND nome = ? AND anno = ?");
        $stmt_mese_prec->bind_param("isi", $utente_id, $nome_mese_precedente, $anno_mese_precedente);
        $stmt_mese_prec->execute();
        $mese_precedente = $stmt_mese_prec->get_result()->fetch_assoc();
        $stmt_mese_prec->close();

        if ($mese_precedente) {
            $mese_id_precedente = $mese_precedente['id'];

            $stmt_fisse_prec = $conn->prepare("SELECT COALESCE(SUM(importo), 0) AS totale FROM spese_fisse WHERE mese_id = ?");
            $stmt_fisse_prec->bind_param("i", $mese_id_precedente);
            $stmt_fisse_prec->execute();
            $tot_fisse_prec = floatval($stmt_fisse_prec->get_result()->fetch_assoc()['totale'] ?? 0);
            $stmt_fisse_prec->close();

            $stmt_var_prec = $conn->prepare("SELECT COALESCE(SUM(importo), 0) AS totale FROM spese_variabili WHERE mese_id = ?");
            $stmt_var_prec->bind_param("i", $mese_id_precedente);
            $stmt_var_prec->execute();
            $tot_var_prec = floatval($stmt_var_prec->get_result()->fetch_assoc()['totale'] ?? 0);
            $stmt_var_prec->close();

            $numero_mese_precedente = intval($data_mese_precedente->format('n'));

            $stmt_programmate_prec = $conn->prepare(
                "SELECT COALESCE(SUM(s.importo), 0) AS totale
                 FROM scadenze_spese_programmate s
                 INNER JOIN piani_spese_programmate p
                    ON p.id = s.piano_id
                 WHERE p.utente_id = ?
                   AND YEAR(
                        CASE
                            WHEN s.pagata = 1
                             AND s.data_pagamento < s.data_scadenza
                                THEN s.data_pagamento
                            ELSE s.data_scadenza
                        END
                   ) = ?
                   AND MONTH(
                        CASE
                            WHEN s.pagata = 1
                             AND s.data_pagamento < s.data_scadenza
                                THEN s.data_pagamento
                            ELSE s.data_scadenza
                        END
                   ) = ?"
            );
            $stmt_programmate_prec->bind_param(
                "iii",
                $utente_id,
                $anno_mese_precedente,
                $numero_mese_precedente
            );
            $stmt_programmate_prec->execute();
            $tot_programmate_prec = floatval(
                $stmt_programmate_prec->get_result()->fetch_assoc()['totale'] ?? 0
            );
            $stmt_programmate_prec->close();
            $tot_scadenza_prec = totale_rate_scadenza_pagate($conn, $utente_id, $anno_mese_precedente, $numero_mese_precedente);

            $entrata_prec = floatval($mese_precedente['entrata']);
            $percentuale_prec = floatval($mese_precedente['percentuale_risparmio']);
            $percentuale_nuova = $percentuale_prec;
            $avanzo_riportato_prec = floatval($mese_precedente['avanzo_riportato'] ?? 0);
            $quota_risparmio_prec = $entrata_prec * ($percentuale_prec / 100);

            $budget_finale_prec =
                ($entrata_prec - $quota_risparmio_prec)
                - $tot_fisse_prec
                + $avanzo_riportato_prec
                - $tot_var_prec;

            if ($budget_finale_prec > 0) {
                $avanzo_da_riportare = $budget_finale_prec;
            }
        }
    }

    $ins_mese = $conn->prepare("INSERT IGNORE INTO mesi (utente_id, nome, anno, entrata, percentuale_risparmio, avanzo_riportato) VALUES (?, ?, ?, 0, ?, ?)");
    $ins_mese->bind_param("isidd", $utente_id, $m_creare, $a_creare, $percentuale_nuova, $avanzo_da_riportare);
    $ins_mese->execute();
    $mese_id = $conn->insert_id;

    if ($mese_id > 0) {
        $stmt_mese = $conn->prepare("SELECT entrata, percentuale_risparmio FROM mesi WHERE id = ? AND utente_id = ?");
        $stmt_mese->bind_param("ii", $mese_id, $utente_id);
        $stmt_mese->execute();
        $mese_creato = $stmt_mese->get_result()->fetch_assoc();
        $stmt_mese->close();

        $entrata_iniziale = floatval($mese_creato['entrata'] ?? 0);
        $percentuale_iniziale = floatval($mese_creato['percentuale_risparmio'] ?? 0);
        $quota_salvadanaio = $entrata_iniziale * ($percentuale_iniziale / 100);

        if ($quota_salvadanaio > 0) {
            $percentuale_display = rtrim(rtrim(number_format($percentuale_iniziale, 2, ',', ''), '0'), ',');
            $nome_mese_display = $mesi_it[$m_creare] ?? $m_creare;
            $causale_automatica = "Risparmio Automatico " . $percentuale_display . "% - " . $nome_mese_display . " " . $a_creare;
            $data_oggi = date('Y-m-d');

            $ins_fondo = $conn->prepare("INSERT INTO fondo_risparmio (utente_id, mese_id, importo, tipo, causale, data_movimento) VALUES (?, ?, ?, 'versamento', ?, ?)");
            $ins_fondo->bind_param("iidss", $utente_id, $mese_id, $quota_salvadanaio, $causale_automatica, $data_oggi);
            $ins_fondo->execute();
            $ins_fondo->close();
        }

        $spese_standard = [
            ['Condominio', 53.00],
            ['Telefono', 25.00]
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

// Azione: Modifica Entrata e Percentuale + Sincronizzazione Risparmio Automatico
if (isset($_POST['update_budget'])) {
    $mese_id = intval($_POST['mese_id']);
    $nuova_entrata = floatval($_POST['nuova_entrata']);
    $percentuale_risparmio = floatval($_POST['nuova_percentuale']);
    $data_oggi = date('Y-m-d');
    // Il modulo si trova nella pagina Salvadanaio: dopo il salvataggio si torna lì
    $pagina_ritorno = (($_POST['torna'] ?? '') === 'salvadanaio') ? 'salvadanaio.php' : 'index.php';

    if ($percentuale_risparmio < 0 || $percentuale_risparmio > 100) {
        header("Location: $pagina_ritorno?mese=" . urlencode($mese_attivo) . "&anno=$anno_attivo");
        exit;
    }

    // 1. Aggiorna entrata e percentuale del mese
    $stmt = $conn->prepare("UPDATE mesi SET entrata = ?, percentuale_risparmio = ? WHERE id = ? AND utente_id = ?");
    $stmt->bind_param("ddii", $nuova_entrata, $percentuale_risparmio, $mese_id, $utente_id);
    $stmt->execute();
    $stmt->close();

    // 2. Calcola il risparmio sull'entrata totale, prima di qualsiasi spesa
    $quota_salvadanaio = $nuova_entrata * ($percentuale_risparmio / 100);

    if ($quota_salvadanaio > 0) {
        $percentuale_display = number_format($percentuale_risparmio, 2, ',', '');
        $percentuale_display = rtrim(rtrim($percentuale_display, '0'), ',');

        // Genera la causale automatica usando la percentuale configurata
        $causale_automatica = "Risparmio Automatico " . $percentuale_display . "% - " . $mese_display;

        // 4. Inserisce o aggiorna il versamento automatico collegato al mese
        $stmt_fondo = $conn->prepare(
            "INSERT INTO fondo_risparmio (utente_id, mese_id, importo, tipo, causale, data_movimento)
             VALUES (?, ?, ?, 'versamento', ?, ?)
             ON DUPLICATE KEY UPDATE
                utente_id = VALUES(utente_id),
                importo = VALUES(importo),
                tipo = VALUES(tipo),
                causale = VALUES(causale),
                data_movimento = VALUES(data_movimento)"
        );
        $stmt_fondo->bind_param("iidss", $utente_id, $mese_id, $quota_salvadanaio, $causale_automatica, $data_oggi);
        $stmt_fondo->execute();
        $stmt_fondo->close();
    } else {
        $stmt_elimina_fondo = $conn->prepare("DELETE FROM fondo_risparmio WHERE mese_id = ? AND utente_id = ? AND tipo = 'versamento'");
        $stmt_elimina_fondo->bind_param("ii", $mese_id, $utente_id);
        $stmt_elimina_fondo->execute();
        $stmt_elimina_fondo->close();
    }

    $stmt_nome_mese = $conn->prepare("SELECT nome, anno FROM mesi WHERE id = ? AND utente_id = ?");
    $stmt_nome_mese->bind_param("ii", $mese_id, $utente_id);
    $stmt_nome_mese->execute();
    $mese_modificato = $stmt_nome_mese->get_result()->fetch_assoc();
    $stmt_nome_mese->close();
    if ($mese_modificato) {
        budget_sincronizza_extra($conn, $utente_id, $mese_id, $percentuale_risparmio);
        budget_sincronizza_imprevisti($conn, $utente_id, $mese_modificato['nome'], $mese_modificato['anno'], $mesi_it);
    }

    header("Location: $pagina_ritorno?mese=" . urlencode($mese_attivo) . "&anno=$anno_attivo");
    exit;
}

// Validazione del link a un documento esterno (Paperless-ngx, Google Drive):
// restituisce null se vuoto, false se non valido, altrimenti l'URL ripulito.
function normalizza_link_documento($valore) {
    $valore = trim((string) $valore);
    if ($valore === '') {
        return null;
    }
    if (strlen($valore) > 1000) {
        return false;
    }
    $schema = strtolower((string) parse_url($valore, PHP_URL_SCHEME));
    if (!in_array($schema, ['http', 'https'], true) || filter_var($valore, FILTER_VALIDATE_URL) === false) {
        return false;
    }
    return $valore;
}

// Azione: Aggiungi Nuova Spesa Fissa
if (isset($_POST['add_fissa_nuova'])) {
    $mese_id = intval($_POST['mese_id']);
    $desc = htmlspecialchars($_POST['descrizione_fissa']);
    $importo = floatval($_POST['importo_fissa']);
    $link_documento = normalizza_link_documento($_POST['link_fissa'] ?? '');
    if ($link_documento === false) {
        $link_documento = null;
    }
    if ($importo > 0 && !empty($desc)) {
        $stmt = $conn->prepare("INSERT INTO spese_fisse (mese_id, descrizione, importo, pagato, link_documento) SELECT id, ?, ?, 0, ? FROM mesi WHERE id = ? AND utente_id = ?");
        $stmt->bind_param("sdsii", $desc, $importo, $link_documento, $mese_id, $utente_id);
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

// Azione: Collega, modifica o rimuove il documento di una Spesa Fissa
if (isset($_POST['update_link_fissa'])) {
    $id_fissa = intval($_POST['id_fissa']);
    $link_documento = normalizza_link_documento($_POST['link_fissa'] ?? '');
    if ($link_documento !== false) {
        $stmt = $conn->prepare("UPDATE spese_fisse sf INNER JOIN mesi m ON m.id = sf.mese_id SET sf.link_documento = ? WHERE sf.id = ? AND m.utente_id = ?");
        $stmt->bind_param("sii", $link_documento, $id_fissa, $utente_id);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: index.php?mese=$mese_attivo&anno=$anno_attivo");
    exit;
}

// ---------------------------------------------------------
// SPESE FISSE A SCADENZA (NON considerate nel budget finché non pagate)
// Ogni rata compare nel mese della scadenza e viene scalata dal budget
// solo quando è pagata, nel mese del pagamento.
// ---------------------------------------------------------

function data_scadenza_valida($valore) {
    $valore = trim((string) $valore);
    $data = DateTimeImmutable::createFromFormat('Y-m-d', $valore);
    return ($data && $data->format('Y-m-d') === $valore) ? $valore : false;
}

// Totale delle rate a scadenza PAGATE nel mese indicato (competenza = mese del pagamento)
function totale_rate_scadenza_pagate($conn, $utente_id, $anno, $numero_mese) {
    $stmt = $conn->prepare(
        "SELECT COALESCE(SUM(r.importo), 0) AS totale
         FROM rate_spese_scadenza r
         INNER JOIN voci_spese_scadenza v ON v.id = r.voce_id
         WHERE v.utente_id = ?
           AND r.pagata = 1
           AND YEAR(r.data_pagamento) = ?
           AND MONTH(r.data_pagamento) = ?"
    );
    $stmt->bind_param("iii", $utente_id, $anno, $numero_mese);
    $stmt->execute();
    $totale = floatval($stmt->get_result()->fetch_assoc()['totale'] ?? 0);
    $stmt->close();
    return $totale;
}

// Azione: Crea un nuovo piano di pagamento (nome + numero di rate)
if (isset($_POST['add_piano_scadenza'])) {
    $desc_piano = trim($_POST['descrizione_piano'] ?? '');
    $numero_rate_piano = intval($_POST['numero_rate_piano'] ?? 0);
    if ($desc_piano !== '' && mb_strlen($desc_piano) <= 255 && $numero_rate_piano > 0 && $numero_rate_piano <= 600) {
        $stmt = $conn->prepare("INSERT INTO voci_spese_scadenza (utente_id, descrizione, numero_rate) VALUES (?, ?, ?)");
        $stmt->bind_param("isi", $utente_id, $desc_piano, $numero_rate_piano);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: index.php?mese=$mese_attivo&anno=$anno_attivo");
    exit;
}

// Azione: Aggiunge una rata (numero, scadenza, importo, link facoltativo) sotto un piano
if (isset($_POST['add_rata_scadenza'])) {
    $id_piano = intval($_POST['id_piano'] ?? 0);
    $numero_rata = intval($_POST['numero_rata'] ?? 0);
    $data_rata = data_scadenza_valida($_POST['data_rata'] ?? '');
    $importo_rata = round(floatval($_POST['importo_rata'] ?? 0), 2);
    $link_documento = normalizza_link_documento($_POST['link_rata'] ?? '');
    if ($link_documento === false) {
        $link_documento = null;
    }
    if ($id_piano > 0 && $numero_rata > 0 && $numero_rata <= 600 && $data_rata !== false && $importo_rata > 0) {
        $stmt = $conn->prepare(
            "INSERT INTO rate_spese_scadenza (voce_id, numero_rata, importo, data_scadenza, link_documento)
             SELECT id, ?, ?, ?, ? FROM voci_spese_scadenza WHERE id = ? AND utente_id = ?"
        );
        $stmt->bind_param("idssii", $numero_rata, $importo_rata, $data_rata, $link_documento, $id_piano, $utente_id);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: index.php?mese=$mese_attivo&anno=$anno_attivo");
    exit;
}

// Azione: Modifica il numero totale di rate di un piano
if (isset($_POST['update_piano_scadenza'])) {
    $id_piano = intval($_POST['id_piano'] ?? 0);
    $numero_rate_piano = intval($_POST['numero_rate_piano'] ?? 0);
    if ($numero_rate_piano > 0 && $numero_rate_piano <= 600) {
        $stmt = $conn->prepare("UPDATE voci_spese_scadenza SET numero_rate = ? WHERE id = ? AND utente_id = ?");
        $stmt->bind_param("iii", $numero_rate_piano, $id_piano, $utente_id);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: index.php?mese=$mese_attivo&anno=$anno_attivo");
    exit;
}

// Azione: Segna o toglie PAGATO su una rata a scadenza (data pagamento = oggi)
if (isset($_GET['toggle_rata_scadenza'])) {
    $id_rata = intval($_GET['toggle_rata_scadenza']);
    $data_oggi = date('Y-m-d');
    $stmt = $conn->prepare(
        "UPDATE rate_spese_scadenza
         SET data_pagamento = IF(pagata = 1, NULL, ?),
             pagata = 1 - pagata
         WHERE id = ?
           AND voce_id IN (SELECT id FROM voci_spese_scadenza WHERE utente_id = ?)"
    );
    $stmt->bind_param("sii", $data_oggi, $id_rata, $utente_id);
    $stmt->execute();
    $stmt->close();
    header("Location: index.php?mese=$mese_attivo&anno=$anno_attivo");
    exit;
}

// Azione: Collega, modifica o rimuove il documento di una rata a scadenza
if (isset($_POST['update_link_rata_scadenza'])) {
    $id_rata = intval($_POST['id_rata']);
    $link_documento = normalizza_link_documento($_POST['link_rata'] ?? '');
    if ($link_documento !== false) {
        $stmt = $conn->prepare(
            "UPDATE rate_spese_scadenza r
             INNER JOIN voci_spese_scadenza v ON v.id = r.voce_id
             SET r.link_documento = ?
             WHERE r.id = ? AND v.utente_id = ?"
        );
        $stmt->bind_param("sii", $link_documento, $id_rata, $utente_id);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: index.php?mese=$mese_attivo&anno=$anno_attivo");
    exit;
}

// Azione: Modifica una rata a scadenza
// - numero della rata e piano di appartenenza: sempre modificabili (anche se pagata)
// - importo e data di scadenza: solo se la rata non è pagata
if (isset($_POST['update_rata_scadenza'])) {
    $id_rata = intval($_POST['id_rata']);
    $numero_rata = intval($_POST['numero_rata'] ?? 0);
    $id_piano_destinazione = intval($_POST['id_piano'] ?? 0);

    $stmt = $conn->prepare("SELECT COUNT(*) AS n FROM voci_spese_scadenza WHERE id = ? AND utente_id = ?");
    $stmt->bind_param("ii", $id_piano_destinazione, $utente_id);
    $stmt->execute();
    $piano_valido = intval($stmt->get_result()->fetch_assoc()['n'] ?? 0) === 1;
    $stmt->close();

    if ($piano_valido && $numero_rata > 0 && $numero_rata <= 600) {
        $stmt = $conn->prepare(
            "UPDATE rate_spese_scadenza r
             INNER JOIN voci_spese_scadenza v ON v.id = r.voce_id
             SET r.numero_rata = ?, r.voce_id = ?
             WHERE r.id = ? AND v.utente_id = ?"
        );
        $stmt->bind_param("iiii", $numero_rata, $id_piano_destinazione, $id_rata, $utente_id);
        $stmt->execute();
        $stmt->close();
    }

    if (isset($_POST['importo_rata']) || isset($_POST['data_rata'])) {
        $importo_rata = round(floatval($_POST['importo_rata'] ?? 0), 2);
        $data_rata = data_scadenza_valida($_POST['data_rata'] ?? '');
        if ($importo_rata > 0 && $data_rata !== false) {
            $stmt = $conn->prepare(
                "UPDATE rate_spese_scadenza r
                 INNER JOIN voci_spese_scadenza v ON v.id = r.voce_id
                 SET r.importo = ?, r.data_scadenza = ?
                 WHERE r.id = ? AND v.utente_id = ? AND r.pagata = 0"
            );
            $stmt->bind_param("dsii", $importo_rata, $data_rata, $id_rata, $utente_id);
            $stmt->execute();
            $stmt->close();
        }
    }
    header("Location: index.php?mese=$mese_attivo&anno=$anno_attivo");
    exit;
}

// Azione: Elimina una voce a scadenza e tutte le sue rate
if (isset($_GET['delete_voce_scadenza'])) {
    $id_voce = intval($_GET['delete_voce_scadenza']);
    $stmt = $conn->prepare("DELETE FROM voci_spese_scadenza WHERE id = ? AND utente_id = ?");
    $stmt->bind_param("ii", $id_voce, $utente_id);
    $stmt->execute();
    $stmt->close();
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
        $stmt_budget = $conn->prepare(
            "SELECT
                m.entrata,
                m.percentuale_risparmio,
                m.avanzo_riportato,
                COALESCE((SELECT SUM(sf.importo) FROM spese_fisse sf WHERE sf.mese_id = m.id), 0) AS totale_fisse,
                COALESCE((SELECT SUM(sv.importo) FROM spese_variabili sv WHERE sv.mese_id = m.id), 0) AS totale_variabili,
                COALESCE((
                    SELECT SUM(ssp.importo)
                    FROM scadenze_spese_programmate ssp
                    INNER JOIN piani_spese_programmate psp
                        ON psp.id = ssp.piano_id
                    WHERE psp.utente_id = m.utente_id
                      AND YEAR(
                            CASE
                                WHEN ssp.pagata = 1
                                 AND ssp.data_pagamento < ssp.data_scadenza
                                    THEN ssp.data_pagamento
                                ELSE ssp.data_scadenza
                            END
                          ) = m.anno
                      AND MONTH(
                            CASE
                                WHEN ssp.pagata = 1
                                 AND ssp.data_pagamento < ssp.data_scadenza
                                    THEN ssp.data_pagamento
                                ELSE ssp.data_scadenza
                            END
                          ) = MONTH(STR_TO_DATE(m.nome, '%M'))
                ), 0) AS totale_programmate,
                COALESCE((
                    SELECT SUM(rss.importo)
                    FROM rate_spese_scadenza rss
                    INNER JOIN voci_spese_scadenza vss
                        ON vss.id = rss.voce_id
                    WHERE vss.utente_id = m.utente_id
                      AND rss.pagata = 1
                      AND YEAR(rss.data_pagamento) = m.anno
                      AND MONTH(rss.data_pagamento) = MONTH(STR_TO_DATE(m.nome, '%M'))
                ), 0) AS totale_scadenza
             FROM mesi m
             WHERE m.id = ? AND m.utente_id = ?"
        );
        $stmt_budget->bind_param("ii", $mese_id, $utente_id);
        $stmt_budget->execute();
        $dati_budget = $stmt_budget->get_result()->fetch_assoc();
        $stmt_budget->close();

        if ($dati_budget) {
            $entrata = floatval($dati_budget['entrata']);
            $percentuale = floatval($dati_budget['percentuale_risparmio']);
            $totale_fisse = floatval($dati_budget['totale_fisse']);
            $totale_variabili = floatval($dati_budget['totale_variabili']);
            $totale_programmate = floatval(
                $dati_budget['totale_programmate']
            );
            $totale_scadenza = floatval($dati_budget['totale_scadenza']);
            $quota_risparmio = $entrata * ($percentuale / 100);

            $budget_prima =
                $entrata
                - $quota_risparmio
                - $totale_fisse
                + floatval($dati_budget['avanzo_riportato'] ?? 0)
                - $totale_variabili;

            $ins_var = $conn->prepare(
                "INSERT INTO spese_variabili (mese_id, descrizione, importo, data_spesa)
                 VALUES (?, ?, ?, ?)"
            );
            $ins_var->bind_param("isds", $mese_id, $desc, $importo, $data_oggi);
            $ins_var->execute();
            $ins_var->close();

        }
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
    $conferma_password = trim($_POST['conferma_password'] ?? '');
    $utente_id = intval($_SESSION['utente_id'] ?? 0);

    if ($nuova_password !== $conferma_password) {
        header("Location: index.php?password=diverse");
        exit;
    }

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
        header("Location: index.php?password=aggiornata");
        exit;
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
                <div class="flex flex-wrap items-center gap-2">
                <a href="statistiche.php" class="bg-white/20 hover:bg-white/30 text-white text-xs font-semibold px-3 py-1.5 rounded-xl border border-white/20 transition">
                    📊 Grafici
                </a>
                <a href="spese_programmate.php" class="bg-white/20 hover:bg-white/30 text-white text-xs font-semibold px-3 py-1.5 rounded-xl border border-white/20 transition">
                    📅 Programmate
                </a>
                <a href="salvadanaio.php?mese=<?php echo urlencode($mese_attivo); ?>&anno=<?php echo intval($anno_attivo); ?>" class="bg-white/20 hover:bg-white/30 text-white text-xs font-semibold px-3 py-1.5 rounded-xl border border-white/20 transition">
                    🐷 Salvadanaio
                </a>
                <button type="button" onclick="document.getElementById('dialog-password').showModal(); document.getElementById('nuova-password').focus();" class="bg-white/20 hover:bg-white/30 text-white text-xs font-semibold px-3 py-1.5 rounded-xl border border-white/20 transition">
                    🔑 Password
                </button>
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

    <?php
    // ---------------------------------------------------------
    // Stato del backup notturno (tabella backup_stato, scritta da ~/budget-backup.sh)
    // ---------------------------------------------------------
    $bk_ultimo = null;
    $bk_ultimo_ok = null;
    try {
        $bk_ris = $conn->query("SELECT eseguito_il, esito, fase, dettaglio FROM backup_stato ORDER BY eseguito_il DESC, id DESC LIMIT 1");
        $bk_ultimo = $bk_ris ? $bk_ris->fetch_assoc() : null;
        $bk_ris = $conn->query("SELECT eseguito_il FROM backup_stato WHERE esito = 'ok' ORDER BY eseguito_il DESC, id DESC LIMIT 1");
        $bk_ultimo_ok = $bk_ris ? $bk_ris->fetch_assoc() : null;
    } catch (Throwable $e) {
        $bk_ultimo = null;
        $bk_ultimo_ok = null;
    }
    $bk_motivi = [
        '0' => 'configurazione o database non disponibili (file .env, chiave SSH o container del database)',
        '1' => 'lettura della configurazione del database non riuscita',
        '2' => 'creazione del dump del database non riuscita',
        '3' => 'copia dei file di configurazione non riuscita',
        '4' => 'dump del database incompleto o corrotto',
        '5' => 'calcolo dei checksum non riuscito',
        '6' => 'archivio corrotto o spazio esaurito sul Raspberry',
        '7' => 'router o disco di backup non raggiungibile (rete assente, router spento o disco non montato)',
        '8' => 'copia sul router non riuscita o archivio corrotto durante la copia (checksum diverso)',
        '9' => 'pulizia finale non riuscita (il backup potrebbe comunque essere presente sul router)',
    ];
    $bk_formatta = function ($data_ora) {
        $t = strtotime($data_ora);
        if (date('Y-m-d', $t) === date('Y-m-d')) {
            return 'oggi alle ' . date('H:i', $t);
        }
        if (date('Y-m-d', $t) === date('Y-m-d', strtotime('-1 day'))) {
            return 'ieri alle ' . date('H:i', $t);
        }
        return 'il ' . date('d/m/Y', $t) . ' alle ' . date('H:i', $t);
    };
    $bk_avvio_raspberry = null;
    $bk_uptime = @file_get_contents('/proc/uptime');
    if ($bk_uptime !== false) {
        $bk_avvio_raspberry = time() - intval(floatval(explode(' ', trim($bk_uptime))[0]));
    }
    $bk_ok_recente = $bk_ultimo_ok && strtotime($bk_ultimo_ok['eseguito_il']) >= strtotime('-48 hours');
    $bk_errore_recente = $bk_ultimo && $bk_ultimo['esito'] === 'errore';
    ?>
    <?php if ($bk_errore_recente): ?>
        <div class="max-w-4xl mx-auto px-4 mt-4">
            <div class="bg-red-50 border border-red-200 text-red-700 text-xs px-4 py-3 rounded-xl">
                <p class="font-bold">⚠️ Backup del database non riuscito <?php echo htmlspecialchars($bk_formatta($bk_ultimo['eseguito_il'])); ?></p>
                <p class="mt-1">Motivo: <?php echo htmlspecialchars($bk_motivi[$bk_ultimo['fase']] ?? ('errore nella fase ' . $bk_ultimo['fase'])); ?> (fase <?php echo htmlspecialchars($bk_ultimo['fase']); ?>/9).</p>
                <?php if (!empty($bk_ultimo['dettaglio'])): ?>
                    <p class="mt-1 font-mono text-[10px] text-red-500 break-all"><?php echo htmlspecialchars($bk_ultimo['dettaglio']); ?></p>
                <?php endif; ?>
                <p class="mt-1 text-red-600">Ultimo backup riuscito: <?php echo $bk_ultimo_ok ? htmlspecialchars($bk_formatta($bk_ultimo_ok['eseguito_il'])) : 'nessuno registrato'; ?>.</p>
            </div>
        </div>
    <?php elseif (!$bk_ok_recente): ?>
        <div class="max-w-4xl mx-auto px-4 mt-4">
            <div class="bg-amber-50 border border-amber-200 text-amber-800 text-xs px-4 py-3 rounded-xl">
                <p class="font-bold">⚠️ <?php echo $bk_ultimo_ok ? 'Nessun backup del database da ' . max(2, intval((time() - strtotime($bk_ultimo_ok['eseguito_il'])) / 86400)) . ' giorni' : 'Nessun backup del database registrato'; ?></p>
                <p class="mt-1">Lo script non è partito o non ha potuto registrare l'esito: probabilmente il Raspberry era spento (es. mancanza di corrente) oppure il database era fermo.</p>
                <p class="mt-1">
                    Ultimo backup riuscito: <?php echo $bk_ultimo_ok ? htmlspecialchars($bk_formatta($bk_ultimo_ok['eseguito_il'])) : 'nessuno'; ?>.
                    <?php if ($bk_avvio_raspberry): ?>
                        Ultimo avvio del Raspberry: <?php echo htmlspecialchars($bk_formatta(date('Y-m-d H:i:s', $bk_avvio_raspberry))); ?>.
                    <?php endif; ?>
                </p>
            </div>
        </div>
    <?php endif; ?>

    <?php if (($_GET['password'] ?? '') === 'aggiornata'): ?>
        <div class="max-w-4xl mx-auto px-4 mt-4"><p class="bg-green-50 border border-green-200 text-green-700 text-xs font-semibold px-4 py-2 rounded-xl">✅ Password aggiornata.</p></div>
    <?php elseif (($_GET['password'] ?? '') === 'diverse'): ?>
        <div class="max-w-4xl mx-auto px-4 mt-4"><p class="bg-red-50 border border-red-200 text-red-700 text-xs font-semibold px-4 py-2 rounded-xl">Le due password non coincidono: la password non è stata cambiata.</p></div>
    <?php endif; ?>

    <dialog id="dialog-password" onclick="if (event.target === this) { this.close(); }" class="rounded-2xl p-0 w-[calc(100%-2rem)] max-w-sm shadow-xl backdrop:bg-black/50">
        <form method="POST" onsubmit="return verificaNuovaPassword(this)" class="p-5 space-y-3">
            <h3 class="text-sm font-bold text-gray-700">🔑 Cambia Password</h3>
            <div class="relative">
                <input
                    type="password"
                    id="nuova-password"
                    name="nuova_password"
                    placeholder="Nuova password"
                    required
                    autocomplete="new-password"
                    class="w-full px-3 py-2 pr-10 border rounded-xl text-sm bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                <button type="button" onclick="mostraNascondiPassword(this)" aria-label="Mostra o nascondi la password" class="absolute right-3 top-2 text-gray-400 hover:text-gray-600">👁️</button>
            </div>
            <div class="relative">
                <input
                    type="password"
                    id="conferma-password"
                    name="conferma_password"
                    placeholder="Ripeti password"
                    required
                    autocomplete="new-password"
                    class="w-full px-3 py-2 pr-10 border rounded-xl text-sm bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                <button type="button" onclick="mostraNascondiPassword(this)" aria-label="Mostra o nascondi la password" class="absolute right-3 top-2 text-gray-400 hover:text-gray-600">👁️</button>
            </div>
            <p id="errore-password" class="hidden text-xs text-red-600 font-semibold">Le due password non coincidono.</p>
            <div class="flex justify-end gap-2">
                <button type="button" onclick="this.closest('dialog').close()" class="border border-gray-300 text-gray-600 text-xs font-semibold px-4 py-2 rounded-xl hover:bg-gray-50 transition">Annulla</button>
                <button
                    type="submit"
                    name="update_password"
                    class="bg-gray-800 text-white text-xs font-semibold px-4 py-2 rounded-xl hover:bg-gray-900 transition"
                >
                    Aggiorna
                </button>
            </div>
        </form>
        <script>
            function mostraNascondiPassword(pulsante) {
                const campo = pulsante.previousElementSibling;
                const visibile = campo.type === 'password';
                campo.type = visibile ? 'text' : 'password';
                pulsante.textContent = visibile ? '🙈' : '👁️';
            }
            function verificaNuovaPassword(modulo) {
                const errore = document.getElementById('errore-password');
                if (modulo.nuova_password.value.trim() !== modulo.conferma_password.value.trim()) {
                    errore.classList.remove('hidden');
                    modulo.conferma_password.focus();
                    return false;
                }
                errore.classList.add('hidden');
                return confirm('Vuoi cambiare la password di accesso?');
            }
        </script>
    </dialog>

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
        $tot_var = floatval($res_var->fetch_assoc()['totale'] ?? 0);

        $numero_mese_attivo = intval(
            date('n', strtotime("1 $mese_attivo $anno_attivo"))
        );

        $res_programmate_stmt = $conn->prepare(
            "SELECT COALESCE(SUM(s.importo), 0) AS totale
             FROM scadenze_spese_programmate s
             INNER JOIN piani_spese_programmate p
                ON p.id = s.piano_id
             WHERE p.utente_id = ?
               AND YEAR(
                    CASE
                        WHEN s.pagata = 1
                         AND s.data_pagamento < s.data_scadenza
                            THEN s.data_pagamento
                        ELSE s.data_scadenza
                    END
               ) = ?
               AND MONTH(
                    CASE
                        WHEN s.pagata = 1
                         AND s.data_pagamento < s.data_scadenza
                            THEN s.data_pagamento
                        ELSE s.data_scadenza
                    END
               ) = ?"
        );
        $res_programmate_stmt->bind_param(
            "iii",
            $utente_id,
            $anno_attivo,
            $numero_mese_attivo
        );
        $res_programmate_stmt->execute();
        $tot_programmate = floatval(
            $res_programmate_stmt
                ->get_result()
                ->fetch_assoc()['totale'] ?? 0
        );
        $res_programmate_stmt->close();
        $tot_scadenza_pagate = totale_rate_scadenza_pagate($conn, $utente_id, $anno_attivo, $numero_mese_attivo);

        $entrata_totale = $mese_dati['entrata'];
        $percentuale_risparmio = floatval($mese_dati['percentuale_risparmio']);
        $quota_risparmio = $entrata_totale * ($percentuale_risparmio / 100);
        $entrata_dopo_risparmio = $entrata_totale - $quota_risparmio;
        // Avanzo positivo riportato dal mese precedente (0 se il mese precedente
        // ha chiuso in negativo, o se non esiste ancora un mese precedente).
        // Da novembre 2026 l'avanzo è calcolato al momento e spalmato sul mese.
        $avanzo_riportato = budget_avanzo_mese($conn, $utente_id, $mese_attivo, $anno_attivo);
        $avanzo_spalmato = budget_avanzo_spalmato($mese_attivo, $anno_attivo);
        $avanzo_primo_giorno = $avanzo_spalmato ? 0.0 : $avanzo_riportato;
        $budget_variabile_iniziale =
            $entrata_dopo_risparmio
            - $tot_fisse
            + ($avanzo_spalmato ? $avanzo_riportato : 0.0);
        $budget_restante_mese = $budget_variabile_iniziale + $avanzo_primo_giorno - $tot_var;
        budget_sincronizza_imprevisti($conn, $utente_id, $mese_attivo, $anno_attivo, $mesi_it);
        // Entrate extra: al netto del Salvadanaio, spalmate dal giorno di inserimento a fine mese.
        $entrate_extra_mese = budget_extra_mese($conn, $mese_id, $percentuale_risparmio);
        $totale_extra_mese = budget_totale_extra($entrate_extra_mese);
        $budget_restante_mese += $totale_extra_mese;

        // Calcolo giorni rimasti intelligente (se guardiamo un mese vecchio, i giorni rimasti sono 1 per bloccare il budget finale)
        $giorni_totali_mese = date('t', strtotime("1 $mese_attivo $anno_attivo"));
        if ($mese_attivo == $mese_corrente && $anno_attivo == $anno_corrente) {
            $giorno_corrente = date('j');
            $giorni_rimasti = ($giorni_totali_mese - $giorno_corrente) + 1;
            $data_oggi = date('Y-m-d');
            $inizio_mese_attivo = sprintf(
                '%04d-%02d-01',
                $anno_attivo,
                $numero_mese_attivo
            );

            $res_spese_giornaliere_stmt = $conn->prepare(
                "SELECT
                    sv.data_spesa,
                    SUM(sv.importo) AS totale
                 FROM spese_variabili sv
                 INNER JOIN mesi m
                    ON m.id = sv.mese_id
                 WHERE sv.mese_id = ?
                   AND m.utente_id = ?
                   AND sv.data_spesa BETWEEN ? AND ?
                 GROUP BY sv.data_spesa
                 ORDER BY sv.data_spesa"
            );
            $res_spese_giornaliere_stmt->bind_param(
                "iiss",
                $mese_id,
                $utente_id,
                $inizio_mese_attivo,
                $data_oggi
            );
            $res_spese_giornaliere_stmt->execute();
            $risultato_spese_giornaliere =
                $res_spese_giornaliere_stmt->get_result();

            $spese_per_giorno = [];

            while (
                $spesa_giornaliera =
                    $risultato_spese_giornaliere->fetch_assoc()
            ) {
                $spese_per_giorno[
                    $spesa_giornaliera['data_spesa']
                ] = floatval($spesa_giornaliera['totale']);
            }

            $res_spese_giornaliere_stmt->close();

            // Conto giornaliero puro: ogni giorno matura la stessa quota
            // (budget variabile del mese / giorni del mese). Avanzi e sforamenti
            // passano interi al giorno successivo, anche se negativi.
            $quota_giornaliera = $budget_variabile_iniziale / $giorni_totali_mese;
            $spese_fino_a_oggi = array_sum($spese_per_giorno);
            $budget_giornaliero =
                $quota_giornaliera * $giorno_corrente
                + $avanzo_primo_giorno
                + budget_quota_extra_fino_a($entrate_extra_mese, $mese_attivo, $anno_attivo, $giorno_corrente)
                - $spese_fino_a_oggi;
        } else {
            $giorni_rimasti = 1;
            $budget_giornaliero = $budget_restante_mese;
        }
        ?>

        <!-- Telefono: una colonna, ordine dato da order-N. Computer: due colonne indipendenti, senza buchi. -->
        <div class="flex flex-col gap-6 md:grid md:grid-cols-2 md:items-start">

            <div class="contents md:block md:space-y-6">
                <div class="order-1 md:order-none bg-white p-6 rounded-2xl shadow-sm border border-gray-200 text-center">
                    <?php if ($entrata_totale <= 0): ?>
                    <p class="text-sm text-gray-600 my-4">Inserisci l'entrata di <strong><?php echo $mese_display; ?></strong> per calcolare quanto puoi spendere.</p>
                    <a href="salvadanaio.php?mese=<?php echo urlencode($mese_attivo); ?>&anno=<?php echo $anno_attivo; ?>" class="inline-block bg-[#12A0D7] text-white text-sm font-semibold px-4 py-2 rounded-xl hover:opacity-90 transition">Inserisci l'entrata</a>
                    <?php else: ?>
                    <h2 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">
                        <?php echo ($giorni_rimasti > 1) ? "Oggi puoi spendere massimo" : "Avanzo finale del mese"; ?>
                    </h2>
                    <div class="text-6xl font-extrabold <?php echo $budget_giornaliero >= 0 ? 'text-green-600' : 'text-red-600'; ?> my-4">
                        <?php echo number_format($budget_giornaliero, 2, ',', '.'); ?> €
                    </div>
                    <p class="text-xs text-gray-500 leading-relaxed">
                        Disponibilità rimasta: <span class="font-semibold"><?php echo number_format($budget_restante_mese, 2, ',', '.'); ?>€</span>
                        <?php if ($totale_extra_mese > 0): ?>
                            <br>Entrate extra del mese (al netto del Salvadanaio): <span class="font-semibold">+<?php echo number_format($totale_extra_mese, 2, ',', '.'); ?>€</span>
                        <?php endif; ?>
                        <?php if ($tot_programmate > 0): ?>
                            <br>Rate programmate del mese:
                            <span class="font-semibold">
                                <?php echo number_format($tot_programmate, 2, ',', '.'); ?>€
                            </span>
                        <?php endif; ?>
                        <?php if ($giorni_rimasti > 1): ?>
                            <br>Giorni alla fine del mese: <span class="font-semibold"><?php echo $giorni_rimasti; ?></span>
                        <?php endif; ?>
                    </p>
                    <?php endif; ?>
                </div>

                <div class="order-3 md:order-none bg-white p-5 rounded-2xl shadow-sm border border-gray-200">
                    <h3 class="text-sm font-bold text-gray-700 mb-3">🛒 Aggiungi Spesa (Uscite, Spesa, Benzina...)</h3>
                    <form method="POST" class="space-y-3">
                        <input type="hidden" name="mese_id" value="<?php echo $mese_id; ?>">
                        <div class="flex flex-col sm:flex-row gap-2">
                            <input type="text" id="descrizione-spesa" name="descrizione" placeholder="Es. Supermercato" required class="flex-1 px-3 py-2 border rounded-xl bg-gray-50 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
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
                <div class="order-5 md:order-none bg-white p-5 rounded-2xl shadow-sm border border-gray-200">
                    <h3 class="text-sm font-bold text-gray-700 mb-3">⚙️ Gestisci Spese Fisse</h3>
                    <form method="POST" class="flex flex-col sm:flex-row sm:flex-wrap gap-2 mb-3">
                        <input type="hidden" name="mese_id" value="<?php echo $mese_id; ?>">
                        <input type="text" name="descrizione_fissa" placeholder="Nuova voce fissa" required class="flex-1 px-3 py-1.5 border rounded-xl text-sm bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <input type="number" step="0.01" name="importo_fissa" placeholder="€" required class="w-16 px-2 py-1.5 border rounded-xl text-sm bg-gray-50 font-bold text-right focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <button type="submit" name="add_fissa_nuova" class="bg-gray-800 text-white text-xs font-semibold px-3 rounded-xl hover:bg-gray-900 transition">+</button>
                        <input type="url" name="link_fissa" maxlength="1000" placeholder="Link documento (Paperless o Drive, facoltativo)" class="sm:basis-full px-3 py-1.5 border rounded-xl text-xs bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </form>
                    <p class="text-[10px] text-gray-400 font-bold mb-1 uppercase">Gestisci Spese Esistenti:</p>
                    <div class="max-h-48 overflow-y-auto divide-y divide-gray-100">
                        <?php
                        $elenco_fisse_stmt = $conn->prepare("SELECT sf.* FROM spese_fisse sf INNER JOIN mesi m ON m.id = sf.mese_id WHERE sf.mese_id = ? AND m.utente_id = ?");
                        $elenco_fisse_stmt->bind_param("ii", $mese_id, $utente_id);
                        $elenco_fisse_stmt->execute();
                        $elenco_fisse = $elenco_fisse_stmt->get_result();
                        while($f_item = $elenco_fisse->fetch_assoc()):
                        ?>
                        <div class="riga-fissa py-1.5 text-xs">
                            <div class="flex justify-between items-center gap-2">
                                <span class="text-gray-600"><?php echo $f_item['descrizione']; ?> (<?php echo $f_item['importo']; ?>€)</span>
                                <span class="flex items-center gap-3 shrink-0">
                                    <button type="button" onclick="this.closest('.riga-fissa').querySelector('.form-link-fissa').classList.toggle('hidden')" class="text-[#008080] font-bold hover:underline"><?php echo !empty($f_item['link_documento']) ? 'Modifica link' : 'Link'; ?></button>
                                    <a href="index.php?mese=<?php echo $mese_attivo; ?>&anno=<?php echo $anno_attivo; ?>&delete_fissa=<?php echo $f_item['id']; ?>" onclick="return confirm('Vuoi eliminare questa spesa fissa?')" class="text-red-500 font-bold hover:underline">Elimina</a>
                                </span>
                            </div>
                            <form method="POST" class="form-link-fissa hidden mt-1.5 flex gap-2">
                                <input type="hidden" name="id_fissa" value="<?php echo intval($f_item['id']); ?>">
                                <input type="url" name="link_fissa" maxlength="1000" value="<?php echo htmlspecialchars($f_item['link_documento'] ?? '', ENT_QUOTES); ?>" placeholder="Incolla il link (vuoto = rimuovi)" class="flex-1 min-w-0 px-2 py-1 border rounded-lg text-xs bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <button type="submit" name="update_link_fissa" class="bg-gray-800 text-white text-xs font-semibold px-3 rounded-lg hover:bg-gray-900 transition">Salva</button>
                            </form>
                        </div>
                        <?php endwhile; ?>
                    </div>
                </div>

                <div class="order-6 md:order-none bg-white p-5 rounded-2xl shadow-sm border border-gray-200">
                    <h3 class="text-sm font-bold text-gray-700 mb-3">🗓️ Piani a scadenza (NON nel budget)</h3>
                    <form method="POST" class="flex gap-2 mb-3">
                        <input type="text" name="descrizione_piano" maxlength="255" placeholder="Nuovo piano (es. Cedolare Secca 2024 F24)" required class="flex-1 min-w-0 px-3 py-1.5 border rounded-xl text-sm bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <input type="number" step="1" min="1" max="600" name="numero_rate_piano" placeholder="N. rate" required class="w-20 px-2 py-1.5 border rounded-xl text-sm bg-gray-50 font-bold text-right focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <button type="submit" name="add_piano_scadenza" class="bg-gray-800 text-white text-xs font-semibold px-3 rounded-xl hover:bg-gray-900 transition">Crea piano</button>
                    </form>
                    <?php
                    // Piani dell'utente: prima quelli ancora senza rate, poi per prossima scadenza da pagare, in fondo quelli saldati
                    $rs_piani_stmt = $conn->prepare(
                        "SELECT v.id, v.descrizione, v.numero_rate,
                                (SELECT COUNT(*) FROM rate_spese_scadenza r1 WHERE r1.voce_id = v.id) AS rate_inserite,
                                (SELECT COALESCE(MAX(r2.numero_rata), 0) FROM rate_spese_scadenza r2 WHERE r2.voce_id = v.id) AS ultimo_numero,
                                (SELECT MIN(r3.data_scadenza) FROM rate_spese_scadenza r3 WHERE r3.voce_id = v.id AND r3.pagata = 0) AS prossima_scadenza
                         FROM voci_spese_scadenza v
                         WHERE v.utente_id = ?
                         ORDER BY (rate_inserite = 0) DESC, (prossima_scadenza IS NULL), prossima_scadenza, v.id"
                    );
                    $rs_piani_stmt->bind_param("i", $utente_id);
                    $rs_piani_stmt->execute();
                    $rs_piani = $rs_piani_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                    $rs_piani_stmt->close();

                    $rs_rate_stmt = $conn->prepare(
                        "SELECT r.id, r.voce_id, r.numero_rata, r.importo, r.data_scadenza, r.pagata, r.data_pagamento, r.link_documento
                         FROM rate_spese_scadenza r
                         INNER JOIN voci_spese_scadenza v ON v.id = r.voce_id
                         WHERE v.utente_id = ?
                         ORDER BY r.data_scadenza, r.numero_rata"
                    );
                    $rs_rate_stmt->bind_param("i", $utente_id);
                    $rs_rate_stmt->execute();
                    $rs_rate_per_piano = [];
                    foreach ($rs_rate_stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $rs_r) {
                        $rs_rate_per_piano[intval($rs_r['voce_id'])][] = $rs_r;
                    }
                    $rs_rate_stmt->close();
                    ?>
                    <div class="flex justify-between items-center mb-1 gap-2">
                        <p class="text-[10px] text-gray-400 font-bold uppercase">Gestisci piani e rate:</p>
                    </div>
                    <input
                        type="search"
                        id="ricerca-piani"
                        placeholder="Cerca (es. F24, Cedolare, 2024, Nepta)..."
                        oninput="const parole = this.value.toLowerCase().trim().split(/\s+/).filter(Boolean); document.querySelectorAll('.gruppo-piano').forEach(function (g) { g.classList.toggle('hidden', !parole.every(function (p) { return g.dataset.nome.includes(p); })); });"
                        class="w-full mb-2 px-3 py-1.5 border rounded-xl bg-gray-50 text-xs focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                    <div class="max-h-96 overflow-y-auto divide-y divide-gray-100">
                        <?php if (count($rs_piani) === 0): ?>
                            <p class="text-xs text-gray-400 py-2">Nessun piano presente.</p>
                        <?php endif; ?>
                        <?php foreach ($rs_piani as $rs_piano):
                            $rs_piano_id = intval($rs_piano['id']);
                            $rs_totale = $rs_piano['numero_rate'] !== null ? intval($rs_piano['numero_rate']) : 0;
                            $rs_rate = $rs_rate_per_piano[$rs_piano_id] ?? [];
                        ?>
                        <div class="gruppo-piano py-2 text-xs" data-id="<?php echo $rs_piano_id; ?>" data-nome="<?php echo htmlspecialchars(mb_strtolower($rs_piano['descrizione']), ENT_QUOTES); ?>">
                            <div class="flex justify-between items-center gap-2">
                                <span class="min-w-0">
                                    <button type="button" onclick="apriChiudiPiano(this.closest('.gruppo-piano'))" class="text-left font-bold text-[#FF4500] hover:text-[#A3A33F] transition-colors"><span class="freccia-piano inline-block w-3 text-gray-400">▸</span> <?php echo htmlspecialchars($rs_piano['descrizione']); ?></button>
                                    <span class="text-[10px] text-gray-400">
                                        · <?php echo intval($rs_piano['rate_inserite']); ?><?php echo $rs_totale > 0 ? '/' . $rs_totale : ''; ?> rate inserite
                                    </span>
                                </span>
                                <span class="flex items-center gap-3 shrink-0">
                                    <button type="button" onclick="const g = this.closest('.gruppo-piano'); apriChiudiPiano(g, true); g.querySelector('.form-aggiungi-rata').classList.toggle('hidden')" class="text-[#008080] font-bold hover:underline">+ Rata</button>
                                    <button type="button" onclick="const g = this.closest('.gruppo-piano'); apriChiudiPiano(g, true); g.querySelector('.form-totale-piano').classList.toggle('hidden')" class="text-gray-600 font-bold hover:underline">N. rate</button>
                                    <a href="index.php?mese=<?php echo $mese_attivo; ?>&anno=<?php echo $anno_attivo; ?>&delete_voce_scadenza=<?php echo $rs_piano_id; ?>" onclick="return confirm('Vuoi eliminare questo piano e TUTTE le sue rate?')" class="text-red-500 font-bold hover:underline">Elimina</a>
                                </span>
                            </div>
                            <div class="contenuto-piano hidden">
                            <form method="POST" class="form-aggiungi-rata hidden mt-1.5 grid grid-cols-2 gap-2">
                                <input type="hidden" name="id_piano" value="<?php echo $rs_piano_id; ?>">
                                <label class="flex items-center gap-1 text-[10px] text-gray-500">N. rata
                                    <input type="number" step="1" min="1" max="600" name="numero_rata" required value="<?php echo intval($rs_piano['ultimo_numero']) + 1; ?>" class="w-full min-w-0 px-2 py-1 border rounded-lg text-xs bg-gray-50 font-bold text-right focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </label>
                                <input type="number" step="0.01" min="0.01" name="importo_rata" placeholder="€ importo" required class="min-w-0 px-2 py-1 border rounded-lg text-xs bg-gray-50 font-bold text-right focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <input type="date" name="data_rata" required class="min-w-0 px-2 py-1 border rounded-lg text-xs bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <input type="url" name="link_rata" maxlength="1000" placeholder="Link (facoltativo)" class="min-w-0 px-2 py-1 border rounded-lg text-xs bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <button type="submit" name="add_rata_scadenza" class="col-span-2 bg-gray-800 text-white text-xs font-semibold py-1.5 rounded-lg hover:bg-gray-900 transition">Aggiungi rata</button>
                            </form>
                            <form method="POST" class="form-totale-piano hidden mt-1.5 flex gap-2">
                                <input type="hidden" name="id_piano" value="<?php echo $rs_piano_id; ?>">
                                <input type="number" step="1" min="1" max="600" name="numero_rate_piano" required value="<?php echo $rs_totale > 0 ? $rs_totale : ''; ?>" placeholder="Totale rate del piano" class="flex-1 min-w-0 px-2 py-1 border rounded-lg text-xs bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <button type="submit" name="update_piano_scadenza" class="bg-gray-800 text-white text-xs font-semibold px-3 rounded-lg hover:bg-gray-900 transition">Salva</button>
                            </form>
                            <?php if (count($rs_rate) === 0): ?>
                                <p class="pl-2 pt-1 text-[10px] text-gray-400">Nessuna rata inserita: usa "+ Rata".</p>
                            <?php endif; ?>
                            <?php foreach ($rs_rate as $rs_item):
                                $rs_item_pagata = intval($rs_item['pagata']) === 1;
                            ?>
                            <div class="riga-rata-scadenza py-1.5 pl-2">
                                <div class="flex justify-between items-center gap-2">
                                    <span class="<?php echo $rs_item_pagata ? 'text-gray-400' : 'text-gray-600'; ?>">
                                        Rata <?php echo intval($rs_item['numero_rata']); ?><?php echo $rs_totale > 0 ? '/' . $rs_totale : ''; ?> · <?php echo date('d/m/Y', strtotime($rs_item['data_scadenza'])); ?> · <?php echo number_format($rs_item['importo'], 2, ',', '.'); ?>€
                                        <?php if ($rs_item_pagata && !empty($rs_item['data_pagamento'])): ?>
                                            · pagata il <?php echo date('d/m/Y', strtotime($rs_item['data_pagamento'])); ?>
                                        <?php endif; ?>
                                    </span>
                                    <span class="flex items-center gap-3 shrink-0">
                                        <button type="button" onclick="this.closest('.riga-rata-scadenza').querySelector('.form-link-rata').classList.toggle('hidden')" class="text-[#008080] font-bold hover:underline"><?php echo !empty($rs_item['link_documento']) ? 'Modifica link' : 'Link'; ?></button>
                                        <button type="button" onclick="this.closest('.riga-rata-scadenza').querySelector('.form-modifica-rata').classList.toggle('hidden')" class="text-gray-600 font-bold hover:underline">Modifica</button>
                                    </span>
                                </div>
                                <form method="POST" class="form-link-rata hidden mt-1.5 flex gap-2">
                                    <input type="hidden" name="id_rata" value="<?php echo intval($rs_item['id']); ?>">
                                    <input type="url" name="link_rata" maxlength="1000" value="<?php echo htmlspecialchars($rs_item['link_documento'] ?? '', ENT_QUOTES); ?>" placeholder="Incolla il link (vuoto = rimuovi)" class="flex-1 min-w-0 px-2 py-1 border rounded-lg text-xs bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <button type="submit" name="update_link_rata_scadenza" class="bg-gray-800 text-white text-xs font-semibold px-3 rounded-lg hover:bg-gray-900 transition">Salva</button>
                                </form>
                                <form method="POST" class="form-modifica-rata hidden mt-1.5 grid grid-cols-2 gap-2">
                                    <input type="hidden" name="id_rata" value="<?php echo intval($rs_item['id']); ?>">
                                    <label class="flex items-center gap-1 text-[10px] text-gray-500">N. rata
                                        <input type="number" step="1" min="1" max="600" name="numero_rata" required value="<?php echo intval($rs_item['numero_rata']); ?>" class="w-full min-w-0 px-2 py-1 border rounded-lg text-xs bg-gray-50 font-bold text-right focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    </label>
                                    <select name="id_piano" aria-label="Piano" class="min-w-0 px-2 py-1 border rounded-lg text-xs bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        <?php foreach ($rs_piani as $rs_opzione): ?>
                                            <option value="<?php echo intval($rs_opzione['id']); ?>" <?php echo intval($rs_opzione['id']) === $rs_piano_id ? 'selected' : ''; ?>><?php echo htmlspecialchars($rs_opzione['descrizione']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <?php if (!$rs_item_pagata): ?>
                                        <input type="date" name="data_rata" required value="<?php echo htmlspecialchars($rs_item['data_scadenza'], ENT_QUOTES); ?>" class="min-w-0 px-2 py-1 border rounded-lg text-xs bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        <input type="number" step="0.01" min="0.01" name="importo_rata" required value="<?php echo htmlspecialchars($rs_item['importo'], ENT_QUOTES); ?>" class="min-w-0 px-2 py-1 border rounded-lg text-xs bg-gray-50 font-bold text-right focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <?php else: ?>
                                        <p class="col-span-2 text-[10px] text-gray-400">Rata pagata: data e importo non modificabili.</p>
                                    <?php endif; ?>
                                    <button type="submit" name="update_rata_scadenza" class="col-span-2 bg-gray-800 text-white text-xs font-semibold py-1.5 rounded-lg hover:bg-gray-900 transition">Salva</button>
                                </form>
                            </div>
                            <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <script>
                        // Piani collassabili: lo stato aperto/chiuso viene ricordato nel browser
                        function apriChiudiPiano(g, apri) {
                            const c = g.querySelector('.contenuto-piano');
                            const aperto = apri === true ? true : c.classList.contains('hidden');
                            c.classList.toggle('hidden', !aperto);
                            g.querySelector('.freccia-piano').textContent = aperto ? '▾' : '▸';
                            try {
                                let s = JSON.parse(localStorage.getItem('piani_aperti') || '[]');
                                s = s.filter(function (id) { return id !== g.dataset.id; });
                                if (aperto) { s.push(g.dataset.id); }
                                localStorage.setItem('piani_aperti', JSON.stringify(s));
                            } catch (e) {}
                        }
                        (function () {
                            try {
                                const s = JSON.parse(localStorage.getItem('piani_aperti') || '[]');
                                document.querySelectorAll('.gruppo-piano').forEach(function (g) {
                                    if (s.includes(g.dataset.id)) { apriChiudiPiano(g, true); }
                                });
                            } catch (e) {}
                        })();
                    </script>
                </div>
            </div>

            <div class="contents md:block md:space-y-6">
                <div class="order-2 md:order-none bg-white p-5 rounded-2xl shadow-sm border border-gray-200">
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
                                    <?php if (!empty($fissa['link_documento'])): ?>
                                        <a href="<?php echo htmlspecialchars($fissa['link_documento'], ENT_QUOTES); ?>" target="_blank" rel="noopener noreferrer" class="text-[#FF4500] font-medium underline decoration-dotted underline-offset-2 hover:opacity-80"><?php echo $fissa['descrizione']; ?></a>
                                    <?php else: ?>
                                        <?php echo $fissa['descrizione']; ?>
                                    <?php endif; ?>
                                </span>
                            </div>
                            <span class="text-sm font-semibold <?php echo $fissa['pagato'] ? 'text-gray-400' : 'text-gray-600'; ?>">
                                <?php echo number_format($fissa['importo'], 2, ',', '.'); ?> €
                            </span>
                        </li>
                        <?php endwhile; ?>
                    </ul>

                    <?php
                    // Rate a scadenza del mese: scadono in questo mese oppure sono state pagate in questo mese
                    $rs_inizio_mese = sprintf('%04d-%02d-01', $anno_attivo, $numero_mese_attivo);
                    $rs_fine_mese = date('Y-m-t', strtotime($rs_inizio_mese));
                    $rs_stmt = $conn->prepare(
                        "SELECT r.id, r.numero_rata, r.importo, r.data_scadenza, r.pagata, r.data_pagamento, r.link_documento,
                                v.descrizione,
                                COALESCE(v.numero_rate, (SELECT COUNT(*) FROM rate_spese_scadenza r2 WHERE r2.voce_id = r.voce_id)) AS totale_rate
                         FROM rate_spese_scadenza r
                         INNER JOIN voci_spese_scadenza v ON v.id = r.voce_id
                         WHERE v.utente_id = ?
                           AND (
                                r.data_scadenza BETWEEN ? AND ?
                                OR (r.pagata = 1 AND r.data_pagamento BETWEEN ? AND ?)
                           )
                         ORDER BY r.data_scadenza, v.id, r.numero_rata"
                    );
                    $rs_stmt->bind_param("issss", $utente_id, $rs_inizio_mese, $rs_fine_mese, $rs_inizio_mese, $rs_fine_mese);
                    $rs_stmt->execute();
                    $rs_query = $rs_stmt->get_result();
                    $rs_da_pagare = 0.0;
                    ?>
                    <h3 class="text-sm font-bold text-gray-700 mt-5 mb-1 pt-4 border-t border-gray-100">📌 Spese Fisse (NON considerate nel budget)</h3>
                    <p class="text-[10px] text-gray-400 mb-2">Vengono scalate dal budget solo quando le spunti come pagate, nel mese del pagamento.</p>
                    <ul class="divide-y divide-gray-100">
                        <?php if ($rs_query->num_rows === 0): ?>
                            <li class="py-2 text-xs text-gray-400">Nessuna scadenza in questo mese.</li>
                        <?php endif; ?>
                        <?php while ($rs_rata = $rs_query->fetch_assoc()):
                            $rs_pagata = intval($rs_rata['pagata']) === 1;
                            if (!$rs_pagata) {
                                $rs_da_pagare += floatval($rs_rata['importo']);
                            }
                        ?>
                        <li class="py-2.5 flex items-center justify-between gap-2">
                            <div class="flex items-center gap-3 min-w-0">
                                <a href="index.php?mese=<?php echo $mese_attivo; ?>&anno=<?php echo $anno_attivo; ?>&toggle_rata_scadenza=<?php echo intval($rs_rata['id']); ?>" onclick="return confirm('<?php echo $rs_pagata ? 'Togliere il PAGATO? Importo di nuovo disponibile nel budget.' : 'Segnare come PAGATA oggi? Importo scalato dal budget di questo mese.'; ?>')" title="Pagato" class="w-6 h-6 shrink-0 flex items-center justify-center rounded-md border text-sm <?php echo $rs_pagata ? 'bg-blue-600 border-blue-600 text-white' : 'border-gray-300 bg-white text-transparent'; ?>">
                                    ✓
                                </a>
                                <div class="min-w-0">
                                    <span class="text-sm <?php echo $rs_pagata ? 'line-through text-gray-400' : 'text-gray-700'; ?>">
                                        <?php if (!empty($rs_rata['link_documento'])): ?>
                                            <a href="<?php echo htmlspecialchars($rs_rata['link_documento'], ENT_QUOTES); ?>" target="_blank" rel="noopener noreferrer" class="text-[#FF4500] font-medium underline decoration-dotted underline-offset-2 hover:opacity-80"><?php echo htmlspecialchars($rs_rata['descrizione']); ?></a>
                                        <?php else: ?>
                                            <?php echo htmlspecialchars($rs_rata['descrizione']); ?>
                                        <?php endif; ?>
                                    </span>
                                    <p class="text-[10px] text-gray-400">
                                        Rata <?php echo intval($rs_rata['numero_rata']); ?>/<?php echo intval($rs_rata['totale_rate']); ?>
                                        · scadenza <?php echo date('d/m/Y', strtotime($rs_rata['data_scadenza'])); ?>
                                        <?php if ($rs_pagata && !empty($rs_rata['data_pagamento'])): ?>
                                            · pagata il <?php echo date('d/m/Y', strtotime($rs_rata['data_pagamento'])); ?>
                                        <?php endif; ?>
                                    </p>
                                </div>
                            </div>
                            <span class="text-sm font-semibold shrink-0 <?php echo $rs_pagata ? 'text-gray-400' : 'text-[#FF4500]'; ?>">
                                <?php echo number_format($rs_rata['importo'], 2, ',', '.'); ?> €
                            </span>
                        </li>
                        <?php endwhile; $rs_stmt->close(); ?>
                    </ul>
                    <?php if ($rs_da_pagare > 0): ?>
                        <p class="text-xs text-right text-gray-500 mt-2">Ancora da pagare: <span class="font-semibold text-[#FF4500]"><?php echo number_format($rs_da_pagare, 2, ',', '.'); ?> €</span></p>
                    <?php endif; ?>
                </div>

                <div class="order-4 md:order-none bg-white p-5 rounded-2xl shadow-sm border border-gray-200">
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

    <?php endif; ?>
    </main>

    <footer class="max-w-4xl mx-auto px-4 mt-8 text-center text-[11px] text-gray-400">
        <?php if ($bk_ultimo_ok): ?>
            Ultimo backup del database: <?php echo htmlspecialchars($bk_formatta($bk_ultimo_ok['eseguito_il'])); ?> <?php echo $bk_ok_recente && !$bk_errore_recente ? '✓' : '⚠️'; ?>
        <?php else: ?>
            Ultimo backup del database: nessuno registrato ⚠️
        <?php endif; ?>
    </footer>

    <script>
        const descrizioneSpesa = document.getElementById('descrizione-spesa');
        if (descrizioneSpesa && window.matchMedia('(min-width: 768px)').matches) {
            descrizioneSpesa.focus();
        }
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

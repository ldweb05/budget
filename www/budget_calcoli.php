<?php
// Calcoli condivisi del budget (index.php e statistiche.php).
//
// Regole (docs/functional_spec.md):
// - saldo del mese = entrata - Salvadanaio (% sulla sola entrata) - spese fisse
//   + avanzo del mese precedente - spese varie;
// - Spese programmate e Spese fisse a scadenza sono solo promemoria;
// - da novembre 2026 l'avanzo del mese precedente è calcolato al momento
//   (solo se positivo) e spalmato su tutto il mese; fino a ottobre 2026 resta
//   quello salvato in mesi.avanzo_riportato e si somma al primo giorno;
// - da ottobre 2026 il prelievo "Imprevisti" del Salvadanaio è sempre uguale
//   allo sforamento del mese (0 se il mese è in attivo o senza entrata).

const BUDGET_INDICE_AVANZO_SPALMATO = 2026 * 12 + 11;   // novembre 2026
const BUDGET_INDICE_IMPREVISTI_SINCRONI = 2026 * 12 + 10; // ottobre 2026

function budget_indice_mese($nome, $anno) {
    $numero = intval(date('n', strtotime("1 $nome " . intval($anno))));
    return intval($anno) * 12 + $numero;
}

function budget_avanzo_spalmato($nome, $anno) {
    return budget_indice_mese($nome, $anno) >= BUDGET_INDICE_AVANZO_SPALMATO;
}

// Dati del mese dell'utente con i totali di spese fisse e varie (null se il mese non esiste).
function budget_dati_mese($conn, $utente_id, $nome, $anno) {
    $stmt = $conn->prepare(
        "SELECT
            m.id, m.nome, m.anno, m.entrata, m.percentuale_risparmio, m.avanzo_riportato,
            COALESCE((SELECT SUM(sf.importo) FROM spese_fisse sf WHERE sf.mese_id = m.id), 0) AS totale_fisse,
            COALESCE((SELECT SUM(sv.importo) FROM spese_variabili sv WHERE sv.mese_id = m.id), 0) AS totale_variabili
         FROM mesi m
         WHERE m.utente_id = ? AND m.nome = ? AND m.anno = ?"
    );
    $anno = intval($anno);
    $stmt->bind_param("isi", $utente_id, $nome, $anno);
    $stmt->execute();
    $dati = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $dati ?: null;
}

// Avanzo del mese precedente da usare nel mese indicato (sempre >= 0).
function budget_avanzo_mese($conn, $utente_id, $nome, $anno, $profondita = 0) {
    if (!budget_avanzo_spalmato($nome, $anno)) {
        $dati = budget_dati_mese($conn, $utente_id, $nome, $anno);
        return $dati ? floatval($dati['avanzo_riportato']) : 0.0;
    }
    if ($profondita > 120) {
        return 0.0;
    }
    $precedente = new DateTime("first day of $nome " . intval($anno));
    $precedente->modify('-1 month');
    $saldo_prec = budget_saldo_mese(
        $conn,
        $utente_id,
        $precedente->format('F'),
        intval($precedente->format('Y')),
        $profondita + 1
    );
    return $saldo_prec === null ? 0.0 : max(0.0, $saldo_prec);
}

// Componenti del budget del mese (null se il mese non esiste).
function budget_riepilogo_mese($conn, $utente_id, $nome, $anno, $profondita = 0) {
    $dati = budget_dati_mese($conn, $utente_id, $nome, $anno);
    if (!$dati) {
        return null;
    }
    $entrata = floatval($dati['entrata']);
    $quota_risparmio = $entrata * (floatval($dati['percentuale_risparmio']) / 100);
    $totale_fisse = floatval($dati['totale_fisse']);
    $totale_variabili = floatval($dati['totale_variabili']);
    $avanzo = budget_avanzo_mese($conn, $utente_id, $nome, $anno, $profondita);
    $extra = budget_extra_mese($conn, intval($dati['id']), floatval($dati['percentuale_risparmio']));
    $extra_netto = budget_totale_extra($extra);
    $budget_mese = $entrata - $quota_risparmio - $totale_fisse + $avanzo + $extra_netto;
    return [
        'mese_id' => intval($dati['id']),
        'entrata' => $entrata,
        'quota_risparmio' => $quota_risparmio,
        'totale_fisse' => $totale_fisse,
        'totale_variabili' => $totale_variabili,
        'avanzo' => $avanzo,
        'extra_netto' => $extra_netto,
        'budget_mese' => $budget_mese,
        'saldo' => $budget_mese - $totale_variabili,
    ];
}

function budget_saldo_mese($conn, $utente_id, $nome, $anno, $profondita = 0) {
    $riepilogo = budget_riepilogo_mese($conn, $utente_id, $nome, $anno, $profondita);
    return $riepilogo ? $riepilogo['saldo'] : null;
}

// Allinea il prelievo "Imprevisti" del mese allo sforamento reale del mese.
function budget_sincronizza_imprevisti($conn, $utente_id, $nome, $anno, $mesi_it) {
    if (budget_indice_mese($nome, $anno) < BUDGET_INDICE_IMPREVISTI_SINCRONI) {
        return;
    }
    $riepilogo = budget_riepilogo_mese($conn, $utente_id, $nome, $anno);
    if (!$riepilogo) {
        return;
    }
    $mese_id = $riepilogo['mese_id'];
    $prelievo = $riepilogo['entrata'] > 0 ? round(max(0.0, -$riepilogo['saldo']), 2) : 0.0;

    if ($prelievo > 0) {
        $causale = "Imprevisti - " . ($mesi_it[$nome] ?? $nome) . " " . intval($anno);
        $data_oggi = date('Y-m-d');
        $stmt = $conn->prepare(
            "INSERT INTO fondo_risparmio (utente_id, mese_id, importo, tipo, causale, data_movimento)
             VALUES (?, ?, ?, 'prelievo', ?, ?)
             ON DUPLICATE KEY UPDATE
                data_movimento = IF(importo <> VALUES(importo), VALUES(data_movimento), data_movimento),
                importo = VALUES(importo),
                causale = VALUES(causale)"
        );
        $stmt->bind_param("iidss", $utente_id, $mese_id, $prelievo, $causale, $data_oggi);
    } else {
        $stmt = $conn->prepare("DELETE FROM fondo_risparmio WHERE utente_id = ? AND mese_id = ? AND tipo = 'prelievo'");
        $stmt->bind_param("ii", $utente_id, $mese_id);
    }
    $stmt->execute();
    $stmt->close();
}

// Entrate extra del mese con la parte da spendere (importo meno quota al Salvadanaio).
function budget_extra_mese($conn, $mese_id, $percentuale) {
    $stmt = $conn->prepare("SELECT id, descrizione, importo, data_entrata FROM entrate_extra WHERE mese_id = ? ORDER BY data_entrata, id");
    $stmt->bind_param("i", $mese_id);
    $stmt->execute();
    $righe = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    foreach ($righe as &$riga) {
        $riga['importo'] = floatval($riga['importo']);
        $riga['quota_salvadanaio'] = round($riga['importo'] * floatval($percentuale) / 100, 2);
        $riga['netto'] = $riga['importo'] - $riga['quota_salvadanaio'];
    }
    unset($riga);
    return $righe;
}

function budget_totale_extra($extra) {
    return array_sum(array_column($extra, 'netto'));
}

// Parte delle entrate extra maturata fino al giorno indicato: ogni extra è
// diviso in parti uguali dal giorno di inserimento all'ultimo giorno del mese.
function budget_quota_extra_fino_a($extra, $nome, $anno, $giorno) {
    $inizio = date('Y-m-d', strtotime("1 $nome " . intval($anno)));
    $fine = date('Y-m-t', strtotime($inizio));
    $giorni_mese = intval(date('t', strtotime($inizio)));
    $totale = 0.0;
    foreach ($extra as $e) {
        if ($e['data_entrata'] < $inizio) {
            $partenza = 1;
        } elseif ($e['data_entrata'] > $fine) {
            $partenza = $giorni_mese;
        } else {
            $partenza = intval(date('j', strtotime($e['data_entrata'])));
        }
        $giorni_utili = $giorni_mese - $partenza + 1;
        $giorni_maturati = min($giorno - $partenza + 1, $giorni_utili);
        if ($giorni_maturati > 0) {
            $totale += $e['netto'] * $giorni_maturati / $giorni_utili;
        }
    }
    return $totale;
}

// Allinea i versamenti al Salvadanaio delle entrate extra del mese alla percentuale del mese.
function budget_sincronizza_extra($conn, $utente_id, $mese_id, $percentuale) {
    $perc_display = rtrim(rtrim(number_format(floatval($percentuale), 2, ',', ''), '0'), ',');
    foreach (budget_extra_mese($conn, $mese_id, $percentuale) as $e) {
        $id_extra = intval($e['id']);
        if ($e['quota_salvadanaio'] > 0) {
            $causale = htmlspecialchars("Risparmio " . $perc_display . "% - Entrata extra " . $e['descrizione']);
            $quota = $e['quota_salvadanaio'];
            $data = $e['data_entrata'];
            $stmt = $conn->prepare(
                "INSERT INTO fondo_risparmio (utente_id, entrata_extra_id, importo, tipo, causale, data_movimento)
                 VALUES (?, ?, ?, 'versamento', ?, ?)
                 ON DUPLICATE KEY UPDATE importo = VALUES(importo), causale = VALUES(causale)"
            );
            $stmt->bind_param("iidss", $utente_id, $id_extra, $quota, $causale, $data);
        } else {
            $stmt = $conn->prepare("DELETE FROM fondo_risparmio WHERE utente_id = ? AND entrata_extra_id = ?");
            $stmt->bind_param("ii", $utente_id, $id_extra);
        }
        $stmt->execute();
        $stmt->close();
    }
}

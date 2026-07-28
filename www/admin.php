<?php
include 'config.php';
controlla_autenticazione();
include 'db.php';

if (($_SESSION['ruolo'] ?? '') !== 'admin') {
    header('Location: index.php');
    exit;
}

// Azione: Crea Utente
if (isset($_POST['create_user'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $ruolo = ($_POST['ruolo'] ?? '') === 'admin' ? 'admin' : 'user';

    if ($username !== '' && $password !== '') {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare(
            "INSERT INTO utenti (username, password_hash, ruolo)
             VALUES (?, ?, ?)"
        );
        $stmt->bind_param("sss", $username, $password_hash, $ruolo);
        $stmt->execute();
        $stmt->close();
    }

    header('Location: admin.php');
    exit;
}

// Azione: Aggiorna Ruolo e Stato Utente
if (isset($_POST['update_user'])) {
    $id_utente = intval($_POST['id_utente']);
    $ruolo = ($_POST['ruolo'] ?? '') === 'admin' ? 'admin' : 'user';
    $attivo = isset($_POST['attivo']) ? 1 : 0;
    $utente_corrente = intval($_SESSION['utente_id'] ?? 0);

    if ($id_utente > 0 && $id_utente !== $utente_corrente) {
        $stmt = $conn->prepare(
            "UPDATE utenti
             SET ruolo = ?, attivo = ?
             WHERE id = ?"
        );
        $stmt->bind_param("sii", $ruolo, $attivo, $id_utente);
        $stmt->execute();
        $stmt->close();
    }

    header('Location: admin.php');
    exit;
}

// Azione: Elimina Utente
if (isset($_POST['delete_user'])) {
    $id_utente = intval($_POST['id_utente']);
    $utente_corrente = intval($_SESSION['utente_id'] ?? 0);

    if ($id_utente > 0 && $id_utente !== $utente_corrente) {
        $stmt = $conn->prepare("DELETE FROM utenti WHERE id = ?");
        $stmt->bind_param("i", $id_utente);
        $stmt->execute();
        $stmt->close();
    }

    header('Location: admin.php');
    exit;
}

// Azione: Aggiorna Password Amministratore
if (isset($_POST['update_password'])) {
    $nuova_password = trim($_POST['nuova_password']);
    $utente_id = intval($_SESSION['utente_id'] ?? 0);

    if ($nuova_password !== '' && $utente_id > 0) {
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

    header('Location: admin.php');
    exit;
}

$utenti_query = $conn->query(
    "SELECT id, username, ruolo, attivo
     FROM utenti
     ORDER BY username"
);
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Amministrazione Utenti</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 font-sans min-h-screen pb-12">
    <header class="bg-gray-900 text-white py-3 shadow-md px-4">
        <div class="max-w-4xl mx-auto flex justify-between items-center">
            <div>
                <h1 class="text-lg font-bold tracking-wide">AMMINISTRAZIONE</h1>
                <p class="text-xs text-gray-300">Gestione utenti</p>
            </div>
            <a href="logout.php" class="bg-red-600 hover:bg-red-700 text-white text-xs font-semibold px-3 py-1.5 rounded-xl transition">
                🚪 Esci
            </a>
        </div>
    </header>

    <main class="max-w-4xl mx-auto px-4 mt-6 space-y-6">
        <section class="bg-white p-5 rounded-2xl shadow-sm border border-gray-200">
            <h2 class="text-sm font-bold text-gray-700 mb-4">Crea nuovo utente</h2>

            <form method="POST" class="grid grid-cols-1 md:grid-cols-4 gap-2">
                <input
                    type="text"
                    name="username"
                    placeholder="Username"
                    required
                    class="px-3 py-2 border rounded-xl text-sm bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                <input
                    type="password"
                    name="password"
                    placeholder="Password"
                    required
                    autocomplete="new-password"
                    class="px-3 py-2 border rounded-xl text-sm bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                <select name="ruolo" class="px-3 py-2 border rounded-xl text-sm bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="user">Utente</option>
                    <option value="admin">Amministratore</option>
                </select>
                <button type="submit" name="create_user" class="bg-blue-600 text-white text-xs font-semibold px-4 py-2 rounded-xl hover:bg-blue-700 transition">
                    Crea Utente
                </button>
            </form>
        </section>

        <section class="bg-white p-5 rounded-2xl shadow-sm border border-gray-200">
            <h2 class="text-sm font-bold text-gray-700 mb-4">Utenti registrati</h2>

            <div class="space-y-2">
                <?php while ($utente = $utenti_query->fetch_assoc()): ?>
                    <?php $utente_corrente = intval($utente['id']) === intval($_SESSION['utente_id'] ?? 0); ?>

                    <form method="POST" class="grid grid-cols-1 md:grid-cols-5 gap-2 items-center border-t border-gray-100 pt-3">
                        <input type="hidden" name="id_utente" value="<?php echo intval($utente['id']); ?>">

                        <span class="text-sm font-semibold text-gray-700">
                            <?php echo htmlspecialchars($utente['username'], ENT_QUOTES, 'UTF-8'); ?>
                            <?php if ($utente_corrente): ?>
                                <span class="text-xs font-normal text-gray-400">(tu)</span>
                            <?php endif; ?>
                        </span>

                        <select
                            name="ruolo"
                            <?php echo $utente_corrente ? 'disabled' : ''; ?>
                            class="px-3 py-2 border rounded-xl text-sm bg-gray-50 disabled:opacity-50"
                        >
                            <option value="user" <?php echo $utente['ruolo'] === 'user' ? 'selected' : ''; ?>>Utente</option>
                            <option value="admin" <?php echo $utente['ruolo'] === 'admin' ? 'selected' : ''; ?>>Amministratore</option>
                        </select>

                        <label class="flex items-center gap-2 text-xs text-gray-600">
                            <input
                                type="checkbox"
                                name="attivo"
                                value="1"
                                <?php echo intval($utente['attivo']) === 1 ? 'checked' : ''; ?>
                                <?php echo $utente_corrente ? 'disabled' : ''; ?>
                            >
                            Attivo
                        </label>

                        <button
                            type="submit"
                            name="update_user"
                            <?php echo $utente_corrente ? 'disabled' : ''; ?>
                            class="bg-gray-800 text-white text-xs font-semibold px-3 py-2 rounded-xl disabled:opacity-40"
                        >
                            Aggiorna
                        </button>

                        <button
                            type="submit"
                            name="delete_user"
                            <?php echo $utente_corrente ? 'disabled' : ''; ?>
                            onclick="return confirm('Vuoi eliminare questo utente?')"
                            class="bg-red-500 text-white text-xs font-semibold px-3 py-2 rounded-xl disabled:opacity-40"
                        >
                            Elimina
                        </button>
                    </form>
                <?php endwhile; ?>
            </div>
        </section>

        <section class="bg-white p-5 rounded-2xl shadow-sm border border-gray-200">
            <h2 class="text-sm font-bold text-gray-700 mb-4">Cambia password amministratore</h2>

            <form method="POST" class="flex flex-col md:flex-row gap-2">
                <input
                    type="password"
                    name="nuova_password"
                    placeholder="Nuova password"
                    required
                    autocomplete="new-password"
                    class="w-full px-3 py-2 border rounded-xl text-sm bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                <button
                    type="submit"
                    name="update_password"
                    onclick="return confirm('Vuoi cambiare la password di accesso?')"
                    class="bg-gray-800 text-white text-xs font-semibold px-4 py-2 rounded-xl hover:bg-gray-900 transition"
                >
                    Aggiorna
                </button>
            </form>
        </section>
    </main>
</body>
</html>

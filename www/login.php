<?php
include 'config.php';
include 'db.php';
session_start();

$errore = '';

if (isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $stmt = $conn->prepare(
        "SELECT id, username, password_hash, ruolo
         FROM utenti
         WHERE username = ? AND attivo = 1
         LIMIT 1"
    );
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $utente = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($utente && password_verify($password, $utente['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['loggato'] = true;
        $_SESSION['utente_id'] = (int) $utente['id'];
        $_SESSION['username'] = $utente['username'];
        $_SESSION['ruolo'] = $utente['ruolo'];

        $destinazione = $utente['ruolo'] === 'admin' ? 'admin.php' : 'index.php';
        header("Location: " . $destinazione);
        exit;
    }

    $errore = 'Credenziali errate! Riprova.';
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Budget</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 font-sans flex items-center justify-center min-h-screen px-4">

    <div class="w-full max-w-md bg-white p-4 sm:p-6 rounded-2xl shadow-md border border-gray-200">
        <div class="text-center mb-6">
            <div class="text-4xl mb-2">🔵</div>
            <h1 class="text-xl font-bold text-gray-700 tracking-wide">ACCESSO AL BUDGET</h1>
            <p class="text-xs text-gray-400">Inserisci le credenziali per continuare</p>
        </div>

        <?php if (!empty($errore)): ?>
            <div class="bg-red-50 text-red-600 text-xs p-3 rounded-xl mb-4 font-semibold border border-red-100 text-center">
                <?php echo $errore; ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-4">
            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Utente</label>
                <input type="text" name="username" required class="w-full px-3 py-2.5 border rounded-xl bg-gray-50 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Password</label>
                <div class="relative">
                    <input id="password" type="password" name="password" required class="w-full px-3 py-2.5 pr-10 border rounded-xl bg-gray-50 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <button type="button" id="togglePassword" class="absolute right-3 top-2.5 text-gray-400 hover:text-gray-600">
                        👁️
                    </button>
                </div>
            </div>
            <button type="submit" name="login" class="w-full bg-[#12A0D7] text-white font-semibold py-3 rounded-xl text-sm shadow-sm hover:opacity-95 transition mt-2">
                Accedi
            </button>
        </form>
    </div>

    <script>
        const passwordInput = document.getElementById('password');
        const togglePassword = document.getElementById('togglePassword');

        togglePassword.addEventListener('click', function () {
            const tipo = passwordInput.type === 'password' ? 'text' : 'password';
            passwordInput.type = tipo;
            this.textContent = tipo === 'password' ? '👁️' : '🙈';
        });
    </script>

</body>
</html>

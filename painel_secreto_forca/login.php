<?php
session_start();
header('X-Robots-Tag: noindex, nofollow, noarchive, nosnippet', true);
header('Referrer-Policy: no-referrer', true);
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0', true);
header('X-Frame-Options: DENY', true);

if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Anti-brute-force delay & session lock
    $_SESSION['login_attempts'] = ($_SESSION['login_attempts'] ?? 0) + 1;
    if ($_SESSION['login_attempts'] > 5 && (time() - ($_SESSION['last_attempt_time'] ?? 0)) < 60) {
        $error = 'Muitas tentativas. Aguarde 1 minuto antes de tentar novamente.';
    } else {
        $_SESSION['last_attempt_time'] = time();
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';

        require_once '../api/db.php';
        if ($conn->connect_error) {
            die("Connection failed");
        }

        $stmt = $conn->prepare('SELECT id, password_hash FROM admins WHERE username = ?');
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            if (password_verify($password, $row['password_hash'])) {
                session_regenerate_id(true);
                $_SESSION['login_attempts'] = 0;
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_id'] = $row['id'];
                header('Location: index.php');
                exit;
            } else {
                usleep(500000);
                $error = 'Credenciais inválidas.';
            }
        } else {
            usleep(500000);
            $error = 'Credenciais inválidas.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="robots" content="noindex, nofollow, noarchive, nosnippet">
    <meta name="googlebot" content="noindex, nofollow">
    <meta name="referrer" content="no-referrer">
    <title>Login - Forcax Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-[#121213] text-white flex items-center justify-center min-h-screen">
    <div class="bg-[#1e1e1e] p-8 rounded-xl shadow-2xl w-full max-w-md border border-slate-700">
        <h1 class="text-3xl font-black mb-6 text-center tracking-wider">FORCAX <span class="text-emerald-400">ADMIN</span></h1>
        <?php if ($error): ?>
            <div class="bg-red-500/20 text-red-400 border border-red-500 p-3 rounded mb-4 text-sm text-center">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        <form method="POST" class="space-y-4">
            <div>
                <label class="block text-sm text-slate-400 mb-1">Usuário</label>
                <input type="text" name="username" class="w-full bg-[#121213] border border-slate-700 rounded-lg p-3 text-white focus:outline-none focus:border-emerald-500 transition-colors" required>
            </div>
            <div>
                <label class="block text-sm text-slate-400 mb-1">Senha</label>
                <input type="password" name="password" class="w-full bg-[#121213] border border-slate-700 rounded-lg p-3 text-white focus:outline-none focus:border-emerald-500 transition-colors" required>
            </div>
            <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-500 text-white font-bold py-3 rounded-lg transition-colors mt-4">
                ENTRAR
            </button>
        </form>
    </div>
</body>
</html>

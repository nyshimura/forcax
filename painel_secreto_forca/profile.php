<?php
session_start();
header('X-Robots-Tag: noindex, nofollow, noarchive, nosnippet', true);
header('Referrer-Policy: no-referrer', true);
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0', true);
header('X-Frame-Options: DENY', true);

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

require_once '../api/db.php';
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$admin_id = $_SESSION['admin_id'];
$message = '';
$error = '';

// Fetch current admin info
$stmt = $conn->prepare("SELECT username FROM admins WHERE id = ?");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$admin = $stmt->get_result()->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'update_profile') {
        $new_username = $_POST['username'] ?? '';
        $new_password = $_POST['password'] ?? '';
        
        if (!empty($new_password)) {
            $hash = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE admins SET username = ?, password_hash = ? WHERE id = ?");
            $stmt->bind_param("ssi", $new_username, $hash, $admin_id);
        } else {
            $stmt = $conn->prepare("UPDATE admins SET username = ? WHERE id = ?");
            $stmt->bind_param("si", $new_username, $admin_id);
        }
        
        if ($stmt->execute()) {
            $message = 'Perfil atualizado com sucesso!';
            $admin['username'] = $new_username;
        } else {
            $error = 'Erro ao atualizar perfil: ' . $conn->error;
        }
    }
    
    if (isset($_POST['action']) && $_POST['action'] === 'rename_folder') {
        $new_folder = preg_replace('/[^a-zA-Z0-9_-]/', '', $_POST['new_folder'] ?? '');
        $reserved = ['api', 'css', 'js', 'uploads'];
        if (empty($new_folder) || in_array(strtolower($new_folder), $reserved)) {
            $error = 'Nome de pasta inválido ou reservado pelo sistema.';
        } else {
            $current_folder = basename(__DIR__);
            $parent_dir = dirname(__DIR__);
            $target_path = $parent_dir . DIRECTORY_SEPARATOR . $new_folder;

            if ($new_folder === $current_folder) {
                $error = 'O painel já está usando este endereço.';
            } else if (file_exists($target_path)) {
                $error = 'Já existe uma pasta ou arquivo com este nome.';
            } else {
                // Save new slug in settings table for reference
                $stmtSlug = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('admin_slug', ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
                $stmtSlug->bind_param("s", $new_folder);
                $stmtSlug->execute();

                // Try direct rename first (works on Linux / InfinityFree and most PHP setups)
                if (@rename(__DIR__, $target_path)) {
                    header("Location: ../$new_folder/profile.php?success=1");
                    exit;
                } else {
                    // Fallback via temporary parent script (needed on Windows if CWD locks the directory)
                    $temp_script = "<?php\n"
                        . "usleep(150000);\n"
                        . "if (@rename(__DIR__ . '/$current_folder', __DIR__ . '/$new_folder')) {\n"
                        . "    @unlink(__FILE__);\n"
                        . "    header('Location: $new_folder/profile.php?success=1');\n"
                        . "    exit;\n"
                        . "} else {\n"
                        . "    @unlink(__FILE__);\n"
                        . "    header('Location: $current_folder/profile.php?rename_error=1');\n"
                        . "    exit;\n"
                        . "}\n";
                    file_put_contents($parent_dir . DIRECTORY_SEPARATOR . 'rename_admin_temp.php', $temp_script);
                    header('Location: ../rename_admin_temp.php');
                    exit;
                }
            }
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
    <title>Meu Perfil - Forcax Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-[#121213] text-white min-h-screen p-4 md:p-8">
    <div class="max-w-3xl mx-auto">
        <div class="flex justify-between items-center mb-8 bg-[#1e1e1e] p-6 rounded-2xl border border-slate-700">
            <h1 class="text-2xl font-black uppercase tracking-wider">MEU <span class="text-emerald-400">PERFIL</span></h1>
            <div class="flex gap-4">
                <a href="index.php" class="bg-slate-700 hover:bg-slate-600 text-white px-4 py-2 rounded-lg font-bold transition-colors">VOLTAR</a>
            </div>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="bg-emerald-500/20 text-emerald-400 border border-emerald-500 p-4 rounded-xl mb-6 font-medium text-center">
                Pasta do painel renomeada com sucesso!
            </div>
        <?php endif; ?>

        <?php if ($message): ?>
            <div class="bg-emerald-500/20 text-emerald-400 border border-emerald-500 p-4 rounded-xl mb-6 font-medium text-center">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="bg-rose-500/20 text-rose-400 border border-rose-500 p-4 rounded-xl mb-6 font-medium text-center">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <!-- Profile Settings -->
            <div class="bg-[#1e1e1e] border border-slate-700 p-6 rounded-2xl h-fit">
                <h2 class="text-xl font-bold mb-4">Atualizar Credenciais</h2>
                <form method="POST" class="space-y-4">
                    <input type="hidden" name="action" value="update_profile">
                    
                    <div>
                        <label class="block text-sm text-slate-400 mb-1">Nome de Usuário</label>
                        <input type="text" name="username" value="<?= htmlspecialchars($admin['username']) ?>" class="w-full bg-[#121213] border border-slate-700 rounded-lg p-3 text-white focus:outline-none focus:border-emerald-500 transition-colors" required>
                    </div>

                    <div>
                        <label class="block text-sm text-slate-400 mb-1">Nova Senha (deixe em branco para não alterar)</label>
                        <input type="password" name="password" class="w-full bg-[#121213] border border-slate-700 rounded-lg p-3 text-white focus:outline-none focus:border-emerald-500 transition-colors">
                    </div>

                    <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-500 text-white font-bold py-3 rounded-lg transition-colors mt-4">
                        ATUALIZAR PERFIL
                    </button>
                </form>
            </div>

            <!-- Rename Folder -->
            <div class="bg-[#1e1e1e] border border-slate-700 p-6 rounded-2xl h-fit">
                <h2 class="text-xl font-bold mb-4">Segurança: Endereço do Painel</h2>
                <p class="text-sm text-slate-400 mb-4">
                    Para evitar ataques de robôs, você pode mudar o endereço (nome da pasta) do seu painel a qualquer momento.
                </p>
                <div class="bg-black/30 p-3 rounded-lg mb-4 font-mono text-sm break-all">
                    Endereço atual: <br><span class="text-emerald-400">/<?= htmlspecialchars(basename(__DIR__)) ?>/</span>
                </div>
                
                <form method="POST" class="space-y-4" onsubmit="return confirm('ATENÇÃO: Você será redirecionado para o novo endereço imediatamente após renomear. Lembre-se do novo nome ou você perderá acesso ao painel!');">
                    <input type="hidden" name="action" value="rename_folder">
                    
                    <div>
                        <label class="block text-sm text-slate-400 mb-1">Novo nome para a pasta</label>
                        <input type="text" name="new_folder" pattern="[a-zA-Z0-9_-]+" title="Apenas letras, números, traços e underlines" placeholder="Ex: painel_seguro_xyz" class="w-full bg-[#121213] border border-slate-700 rounded-lg p-3 text-white focus:outline-none focus:border-emerald-500 transition-colors" required>
                    </div>

                    <button type="submit" class="w-full bg-amber-600 hover:bg-amber-500 text-white font-bold py-3 rounded-lg transition-colors mt-4">
                        RENOMEAR PASTA
                    </button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>

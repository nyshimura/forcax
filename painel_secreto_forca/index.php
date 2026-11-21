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

// Ensure settings table exists
$conn->query("CREATE TABLE IF NOT EXISTS settings (
    setting_key VARCHAR(50) PRIMARY KEY,
    setting_value TEXT
)");

// Ensure default settings exist
$current_admin_slug = basename(__DIR__);
$stmtSlugInit = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('admin_slug', ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
$stmtSlugInit->bind_param("s", $current_admin_slug);
$stmtSlugInit->execute();
$conn->query("INSERT IGNORE INTO settings (setting_key, setting_value) VALUES ('ad_provider', 'adsterra')");
$conn->query("INSERT IGNORE INTO settings (setting_key, setting_value) VALUES ('adsterra_code', '')");
$conn->query("INSERT IGNORE INTO settings (setting_key, setting_value) VALUES ('adsterra_social_bar', '')");
$conn->query("INSERT IGNORE INTO settings (setting_key, setting_value) VALUES ('adsense_code', '')");
$conn->query("INSERT IGNORE INTO settings (setting_key, setting_value) VALUES ('donate_link', '')");

// Handle form submission for new sponsor
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $target_link = $_POST['target_link'] ?? '';
    $start_date = $_POST['start_date'] ?? '';
    $end_date = $_POST['end_date'] ?? '';
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    if (isset($_FILES['banner']) && $_FILES['banner']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        
        $ext = strtolower(pathinfo($_FILES['banner']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if (in_array($ext, $allowed)) {
            $filename = uniqid('banner_') . '.' . $ext;
            $dest = $upload_dir . $filename;
            
            if (move_uploaded_file($_FILES['banner']['tmp_name'], $dest)) {
                $image_url = 'uploads/' . $filename;
                
                $stmt = $conn->prepare("INSERT INTO sponsors (image_url, target_link, start_date, end_date, is_active) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("ssssi", $image_url, $target_link, $start_date, $end_date, $is_active);
                $stmt->execute();
            }
        }
    }
    header('Location: index.php');
    exit;
}

// Handle toggling active state
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle') {
    $id = intval($_POST['id']);
    $conn->query("UPDATE sponsors SET is_active = NOT is_active WHERE id = $id");
    header('Location: index.php');
    exit;
}

// Handle deleting sponsor
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $id = intval($_POST['id']);
    $result = $conn->query("SELECT image_url FROM sponsors WHERE id = $id");
    if ($row = $result->fetch_assoc()) {
        $file_path = '../' . $row['image_url'];
        if (file_exists($file_path)) {
            unlink($file_path);
        }
        $conn->query("DELETE FROM sponsors WHERE id = $id");
    }
    header('Location: index.php');
    exit;
}

// Fetch all sponsors
$sponsors = $conn->query("SELECT * FROM sponsors ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);

// Handle settings update
$message = '';
$error_msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_settings') {
    $ad_provider = $_POST['ad_provider'] ?? 'adsterra';
    $adsterra_code = $_POST['adsterra_code'] ?? '';
    $adsterra_social_bar = $_POST['adsterra_social_bar'] ?? '';
    $adsense_code = $_POST['adsense_code'] ?? '';
    $donate_link = $_POST['donate_link'] ?? '';
    $new_admin_slug = preg_replace('/[^a-zA-Z0-9_-]/', '', $_POST['admin_slug'] ?? '');

    $settingsToSave = [
        'ad_provider' => $ad_provider,
        'adsterra_code' => $adsterra_code,
        'adsterra_social_bar' => $adsterra_social_bar,
        'adsense_code' => $adsense_code,
        'donate_link' => $donate_link
    ];

    $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
    foreach ($settingsToSave as $k => $v) {
        $stmt->bind_param("ss", $k, $v);
        $stmt->execute();
    }

    // Handle optional panel slug rename directly from main settings if user changed it
    $current_folder = basename(__DIR__);
    if (!empty($new_admin_slug) && $new_admin_slug !== $current_folder) {
        $reserved = ['api', 'css', 'js', 'uploads'];
        $parent_dir = dirname(__DIR__);
        $target_path = $parent_dir . DIRECTORY_SEPARATOR . $new_admin_slug;
        if (in_array(strtolower($new_admin_slug), $reserved) || file_exists($target_path)) {
            $error_msg = "Configurações salvas, mas o slug '$new_admin_slug' já existe ou é reservado.";
        } else {
            $stmtSlug = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('admin_slug', ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
            $stmtSlug->bind_param("s", $new_admin_slug);
            $stmtSlug->execute();

            if (@rename(__DIR__, $target_path)) {
                header("Location: ../$new_admin_slug/index.php?slug_changed=1");
                exit;
            } else {
                $temp_script = "<?php\n"
                    . "usleep(150000);\n"
                    . "if (@rename(__DIR__ . '/$current_folder', __DIR__ . '/$new_admin_slug')) {\n"
                    . "    @unlink(__FILE__);\n"
                    . "    header('Location: $new_admin_slug/index.php?slug_changed=1');\n"
                    . "    exit;\n"
                    . "} else {\n"
                    . "    @unlink(__FILE__);\n"
                    . "    header('Location: $current_folder/index.php');\n"
                    . "    exit;\n"
                    . "}\n";
                file_put_contents($parent_dir . DIRECTORY_SEPARATOR . 'rename_admin_temp.php', $temp_script);
                header('Location: ../rename_admin_temp.php');
                exit;
            }
        }
    }

    $message = "Configurações salvas com sucesso!";
}
if (isset($_GET['slug_changed'])) {
    $message = "Configurações salvas e endereço (slug) do Painel Admin atualizado com sucesso para /" . basename(__DIR__) . "/!";
}

// Load settings
$settings = [];
$res = $conn->query("SELECT setting_key, setting_value FROM settings");
while ($row = $res->fetch_assoc()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="robots" content="noindex, nofollow, noarchive, nosnippet">
    <meta name="googlebot" content="noindex, nofollow">
    <meta name="referrer" content="no-referrer">
    <title>Painel Admin - Forcax</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-[#121213] text-white min-h-screen p-4 md:p-8">
    <div class="max-w-5xl mx-auto">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8 bg-[#1e1e1e] p-6 rounded-2xl border border-slate-700">
            <div>
                <h1 class="text-2xl font-black uppercase tracking-wider">FORCAX <span class="text-emerald-400">ADMIN</span></h1>
                <div class="text-xs text-slate-400 font-mono mt-1 flex items-center gap-2">
                    <span>Slug atual do painel: <strong class="text-emerald-400">/<?= htmlspecialchars(basename(__DIR__)) ?>/</strong></span>
                    <a href="profile.php" class="text-amber-400 hover:underline font-sans font-bold"><i class="fas fa-edit"></i> Mudar Slug / Senha</a>
                </div>
            </div>
            <div class="flex gap-3">
                <a href="../index.php" target="_blank" class="bg-slate-700 hover:bg-slate-600 text-white px-4 py-2 rounded-lg font-bold transition-colors text-sm flex items-center gap-1.5"><i class="fas fa-gamepad"></i> VER JOGO</a>
                <a href="profile.php" class="bg-blue-600 hover:bg-blue-500 text-white px-4 py-2 rounded-lg font-bold transition-colors text-sm flex items-center gap-1.5"><i class="fas fa-user-shield"></i> MEU PERFIL &amp; SLUG</a>
                <a href="logout.php" class="bg-rose-600 hover:bg-rose-500 text-white px-4 py-2 rounded-lg font-bold transition-colors text-sm flex items-center gap-1.5"><i class="fas fa-sign-out-alt"></i> SAIR</a>
            </div>
        </div>

        <?php if(!empty($message)): ?>
            <div class="bg-emerald-500/20 text-emerald-400 p-4 rounded-lg mb-6 font-bold border border-emerald-500/50 flex items-center">
                <i class="fas fa-check-circle mr-2 text-xl"></i> <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-8">
            <!-- Add New Sponsor -->
            <div class="bg-[#1e1e1e] border border-slate-700 p-6 rounded-2xl h-fit">
                <h2 class="text-xl font-bold mb-4">Novo Patrocinador</h2>
                <form method="POST" enctype="multipart/form-data" class="space-y-4">
                    <input type="hidden" name="action" value="add">
                    
                    <div>
                        <label class="block text-sm text-slate-400 mb-1">Banner (Imagem)</label>
                        <input type="file" name="banner" accept="image/*" class="w-full text-sm text-slate-400 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-emerald-500/10 file:text-emerald-400 hover:file:bg-emerald-500/20" required>
                    </div>

                    <div>
                        <label class="block text-sm text-slate-400 mb-1">Link de Destino</label>
                        <input type="url" name="target_link" placeholder="https://..." class="w-full bg-[#121213] border border-slate-700 rounded-lg p-3 text-white focus:outline-none focus:border-emerald-500 transition-colors" required>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm text-slate-400 mb-1">Início</label>
                            <input type="date" name="start_date" class="w-full bg-[#121213] border border-slate-700 rounded-lg p-3 text-white focus:outline-none focus:border-emerald-500 transition-colors" required>
                        </div>
                        <div>
                            <label class="block text-sm text-slate-400 mb-1">Fim</label>
                            <input type="date" name="end_date" class="w-full bg-[#121213] border border-slate-700 rounded-lg p-3 text-white focus:outline-none focus:border-emerald-500 transition-colors" required>
                        </div>
                    </div>

                    <div class="flex items-center">
                        <input type="checkbox" name="is_active" id="is_active" class="w-4 h-4 text-emerald-500 bg-[#121213] border-slate-700 rounded focus:ring-emerald-500" checked>
                        <label for="is_active" class="ml-2 text-sm text-slate-300">Ativo</label>
                    </div>

                    <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-500 text-white font-bold py-3 rounded-lg transition-colors mt-4">
                        SALVAR PATROCÍNIO
                    </button>
                </form>
            </div>

            <!-- List Sponsors -->
            <div class="md:col-span-2 space-y-4">
                <h2 class="text-xl font-bold mb-4">Patrocinadores Cadastrados</h2>
                <?php if (empty($sponsors)): ?>
                    <div class="text-slate-500 italic">Nenhum patrocinador cadastrado.</div>
                <?php else: ?>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <?php foreach ($sponsors as $sponsor): ?>
                            <?php 
                                $today = date('Y-m-d');
                                $is_valid_date = ($today >= $sponsor['start_date'] && $today <= $sponsor['end_date']);
                                $status_color = ($sponsor['is_active'] && $is_valid_date) ? 'text-emerald-400' : 'text-rose-400';
                                $status_text = ($sponsor['is_active'] && $is_valid_date) ? 'Ativo' : 'Inativo / Expirado';
                            ?>
                            <div class="bg-[#1e1e1e] border border-slate-700 p-4 rounded-xl flex flex-col">
                                <img src="../<?= htmlspecialchars($sponsor['image_url']) ?>" class="w-full h-32 object-contain bg-black/50 rounded-lg mb-4" alt="Banner">
                                
                                <a href="<?= htmlspecialchars($sponsor['target_link']) ?>" target="_blank" rel="noopener noreferrer" class="text-blue-400 text-sm mb-2 truncate hover:underline">
                                    <?= htmlspecialchars($sponsor['target_link']) ?>
                                </a>
                                
                                <div class="text-xs text-slate-400 mb-2">
                                    Período: <?= date('d/m/Y', strtotime($sponsor['start_date'])) ?> até <?= date('d/m/Y', strtotime($sponsor['end_date'])) ?>
                                </div>
                                
                                <div class="text-sm font-bold mb-4 <?= $status_color ?>">
                                    <?= $status_text ?>
                                </div>
                                
                                <div class="mt-auto flex gap-2">
                                    <form method="POST" class="flex-1">
                                        <input type="hidden" name="action" value="toggle">
                                        <input type="hidden" name="id" value="<?= $sponsor['id'] ?>">
                                        <button type="submit" class="w-full h-10 <?= $sponsor['is_active'] ? 'bg-amber-600 hover:bg-amber-500' : 'bg-emerald-600 hover:bg-emerald-500' ?> text-white font-bold rounded-lg transition-colors text-sm">
                                            <?= $sponsor['is_active'] ? 'DESATIVAR' : 'ATIVAR' ?>
                                        </button>
                                    </form>
                                    
                                    <form method="POST" onsubmit="return confirm('Tem certeza que deseja excluir?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $sponsor['id'] ?>">
                                        <button type="submit" class="h-10 px-4 bg-rose-600 hover:bg-rose-500 text-white font-bold rounded-lg transition-colors">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Global Settings Section -->
        <div class="bg-[#1e1e1e] border border-slate-700 p-6 rounded-2xl mb-8">
            <h2 class="text-2xl font-bold mb-6 flex items-center border-b border-slate-700 pb-4">
                <i class="fas fa-money-bill-wave text-emerald-400 mr-3"></i> Configurações e Monetização
            </h2>
            
            <form method="POST" class="space-y-6">
                <input type="hidden" name="action" value="save_settings">
                
                <!-- Provider Selector -->
                <div class="p-6 bg-indigo-950/30 rounded-xl border border-indigo-500/30">
                    <label class="block text-white font-bold mb-2"><i class="fas fa-toggle-on text-indigo-400 mr-2"></i>Provedor de Anúncios Ativo (Salas VIP &amp; Palavras Extras):</label>
                    <select name="ad_provider" class="w-full bg-[#121213] border border-slate-700 text-white font-bold rounded-lg p-3 focus:outline-none focus:border-indigo-500 transition-colors">
                        <option value="adsterra" <?= ($settings['ad_provider'] ?? 'adsterra') === 'adsterra' ? 'selected' : '' ?>>🔥 Adsterra (Recomendado para forca.free.nf / Domínios Gratuitos)</option>
                        <option value="adsense" <?= ($settings['ad_provider'] ?? '') === 'adsense' ? 'selected' : '' ?>>🟢 Google AdSense H5 Games (Para Domínio Próprio Aprovado)</option>
                        <option value="simulation" <?= ($settings['ad_provider'] ?? '') === 'simulation' ? 'selected' : '' ?>>🧪 Apenas Simulação (Contador de Teste sem Anúncio Real)</option>
                    </select>
                    <p class="text-xs text-slate-400 mt-2">
                        Escolha qual rede será exibida quando o jogador criar/entrar em sala ou pedir palavra extra. Se o código do provedor escolhido estiver vazio, o jogo usa a simulação automaticamente como fallback seguro.
                    </p>
                </div>

                <!-- Adsterra Config -->
                <div class="p-6 bg-amber-950/20 rounded-xl border border-amber-500/30 space-y-4">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-black text-amber-400 uppercase tracking-wider"><i class="fas fa-bolt mr-2"></i>Configuração Adsterra</h3>
                        <a href="https://beta.publishers.adsterra.com/" target="_blank" rel="noopener noreferrer" class="text-xs bg-amber-500/20 hover:bg-amber-500/30 text-amber-300 border border-amber-500/40 px-3 py-1.5 rounded-lg font-bold transition-colors">
                            Abrir Painel Adsterra <i class="fas fa-external-link-alt ml-1"></i>
                        </a>
                    </div>

                    <div>
                        <label class="block text-white font-bold mb-1 text-sm">1. Código do Banner / Native Banner para o Modal de Recompensa (300x250 ou 320x50):</label>
                        <textarea name="adsterra_code" rows="5" class="w-full bg-[#121213] border border-slate-700 text-amber-300 font-mono text-xs rounded-lg p-3 focus:outline-none focus:border-amber-500 transition-colors" placeholder="Cole aqui o código <script>...</script> do Banner 300x250 ou Native Banner do Adsterra"><?= htmlspecialchars($settings['adsterra_code'] ?? '') ?></textarea>
                        <p class="text-xs text-slate-400 mt-1">
                            Este anúncio aparece dentro do modal com contagem regressiva de 5 segundos toda vez que o jogador cria/entra numa Sala VIP ou pede `+1 Palavra`.
                        </p>
                    </div>

                    <div>
                        <label class="block text-white font-bold mb-1 text-sm">2. Código Global Adsterra (Opcional — Social Bar / Popunder / Rodapé):</label>
                        <textarea name="adsterra_social_bar" rows="3" class="w-full bg-[#121213] border border-slate-700 text-amber-300 font-mono text-xs rounded-lg p-3 focus:outline-none focus:border-amber-500 transition-colors" placeholder="Opcional: Cole aqui o script da Social Bar do Adsterra para carregar automaticamente na página"><?= htmlspecialchars($settings['adsterra_social_bar'] ?? '') ?></textarea>
                    </div>
                </div>

                <!-- Google AdSense H5 Games Config -->
                <div class="p-6 bg-emerald-900/10 rounded-xl border border-emerald-500/20">
                    <label class="block text-white font-bold mb-2"><i class="fab fa-google text-emerald-400 mr-2"></i>Código Google AdSense H5 Games (Publisher ID ou Script):</label>
                    <input type="text" name="adsense_code" value="<?= htmlspecialchars($settings['adsense_code'] ?? '') ?>" class="w-full bg-[#121213] border border-slate-700 text-emerald-400 font-mono rounded-lg p-3 focus:outline-none focus:border-emerald-500 transition-colors" placeholder="Ex: ca-pub-1234567890123456">
                    <p class="text-sm text-slate-400 mt-2">
                        <i class="fas fa-lightbulb text-amber-500"></i> 
                        <strong>Quando tiver um domínio próprio (.com / .com.br):</strong> Basta mudar o seletor acima para <b>Google AdSense H5 Games</b> e colar seu `ca-pub-XXXXXXXX` aqui. O jogo carregará a API oficial de Rewarded Ads (`adBreak`) do Google automaticamente!
                    </p>
                </div>

                <div class="p-6 bg-rose-900/10 rounded-xl border border-rose-500/20">
                    <label class="block text-white font-bold mb-2">Link para Doação (Ex: Buy Me a Coffee / Ko-fi / LivePix):</label>
                    <input type="url" name="donate_link" value="<?= htmlspecialchars($settings['donate_link'] ?? '') ?>" class="w-full bg-[#121213] border border-slate-700 text-rose-400 rounded-lg p-3 focus:outline-none focus:border-rose-500 transition-colors" placeholder="https://livepix.gg/seunome ou https://ko-fi.com/seunome">
                    <p class="text-sm text-slate-400 mt-2">
                        <i class="fas fa-heart text-rose-500"></i> 
                        <strong>Dica:</strong> Insira o seu link de doação. Se deixar em branco, o botão não aparecerá no perfil do jogador.
                    </p>
                </div>

                <!-- Admin Slug Configuration directly in main panel -->
                <div class="p-6 bg-slate-800/40 rounded-xl border border-slate-600/50">
                    <label class="block text-white font-bold mb-2"><i class="fas fa-link text-amber-400 mr-2"></i>Endereço (Slug) do Painel Secreto:</label>
                    <div class="flex items-center gap-2">
                        <span class="text-slate-400 font-mono text-sm">/</span>
                        <input type="text" name="admin_slug" pattern="[a-zA-Z0-9_-]+" value="<?= htmlspecialchars(basename(__DIR__)) ?>" class="flex-1 bg-[#121213] border border-slate-700 text-amber-400 font-mono font-bold rounded-lg p-3 focus:outline-none focus:border-amber-500 transition-colors" required>
                        <span class="text-slate-400 font-mono text-sm">/</span>
                    </div>
                    <p class="text-xs text-slate-400 mt-2">
                        <i class="fas fa-shield-alt text-amber-400"></i>
                        Se você alterar este nome e clicar em Salvar, a pasta do painel será renomeada automaticamente e você será redirecionado na hora para o novo endereço!
                    </p>
                </div>

                <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-500 text-white font-bold uppercase tracking-wider py-4 rounded-xl transition-colors shadow-lg">
                    <i class="fas fa-save mr-2"></i> SALVAR CONFIGURAÇÕES GLOBAIS
                </button>
            </form>
        </div>
    </div>
</body>
</html>

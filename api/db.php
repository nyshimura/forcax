<?php
// api/db.php
$isLocalhost = in_array($_SERVER['HTTP_HOST'] ?? 'localhost', ['localhost', '127.0.0.1', '::1'])
    || strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost:') === 0;

if ($isLocalhost) {
    $host = 'localhost';
    $db   = 'forca';
    $user = 'root';
    $pass = '';
} else {
    // Produção: InfinityFree (forca.free.nf)
    // Obs.: Atualize $host e $pass com o MySQL Hostname (ex: sqlXXX.infinityfree.com) e a senha da sua conta InfinityFree
    $host = getenv('DB_HOST') ?: 'sql100.infinityfree.com';
    $db   = getenv('DB_NAME') ?: 'if0_43041475_forca';
    $user = getenv('DB_USER') ?: 'if0_43041475';
    $pass = getenv('DB_PASS') ?: '';
}
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    echo json_encode(["error" => "Database connection failed"]);
    exit;
}

// Suporte para os scripts antigos que usam mysqli diretamente
try {
    $conn = new mysqli($host, $user, $pass, $db);
    if (!$conn->connect_error) {
        $conn->set_charset($charset);
    }
} catch (Exception $e) {
    $conn = new stdClass();
    $conn->connect_error = "Connection failed: " . $e->getMessage();
}
?>

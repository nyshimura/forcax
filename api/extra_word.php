<?php
session_start();
header('Content-Type: application/json');

// Authentication not required for now to get extra words

require_once __DIR__ . '/db.php';
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'error' => 'Database error']);
    exit;
}

$lang = $_GET['lang'] ?? 'pt';
$mode = (int)($_GET['mode'] ?? 1);

// Generate random extra words (ignoring the daily deterministic logic)
$words = [];
for ($i = 0; $i < $mode; $i++) {
    // Pick a random word that hasn't been used recently, or just a random used word if we're simulating
    $stmt = $conn->prepare("SELECT word FROM dictionary WHERE language = ? ORDER BY RAND() LIMIT 1");
    $stmt->bind_param("s", $lang);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $words[] = [
            'word' => mb_strtoupper($row['word'], 'UTF-8'),
            'hint' => 'Palavra VIP'
        ];
    } else {
        // Fallback
        $words[] = ['word' => 'EXTRA', 'hint' => 'Palavra de segurança'];
    }
}

echo json_encode(['success' => true, 'words' => $words]);
?>

<?php
// api/save_game.php
header('Content-Type: application/json');
require_once 'db.php';

$uuid = isset($_SERVER['HTTP_X_UUID']) ? $_SERVER['HTTP_X_UUID'] : '';
if (empty($uuid)) {
    echo json_encode(['error' => 'Missing UUID']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$mode = isset($input['mode']) ? (int)$input['mode'] : 1;
$isWin = isset($input['is_win']) ? (int)$input['is_win'] : 0;
$mistakes = isset($input['mistakes']) ? (int)$input['mistakes'] : 0;

$stmt = $pdo->prepare("SELECT id FROM players WHERE uuid = ?");
$stmt->execute([$uuid]);
$player = $stmt->fetch();

if (!$player) {
    echo json_encode(['error' => 'Player not found']);
    exit;
}

$playerId = $player['id'];
$date = date('Y-m-d');
$guesses = isset($input['guesses']) ? json_encode($input['guesses']) : '[]';

$stmt = $pdo->prepare("INSERT INTO games (player_id, mode, date, is_win, mistakes, guesses) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->execute([$playerId, $mode, $date, $isWin, $mistakes, $guesses]);

echo json_encode(['success' => true]);
?>

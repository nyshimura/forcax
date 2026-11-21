<?php
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'error' => 'Database error']);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$room_code = $data['room_code'] ?? '';
$uuid      = $data['uuid'] ?? '';

if (!$room_code || !$uuid) {
    echo json_encode(['success' => false, 'error' => 'Missing data']);
    exit;
}

// Check if room exists and caller is host or cohost
$stmt = $conn->prepare("SELECT id, host_uuid, status, mode, language, max_mistakes FROM rooms WHERE room_code = ?");
$stmt->bind_param("s", $room_code);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows === 0) {
    echo json_encode(['success' => false, 'error' => 'Room not found']);
    exit;
}
$room = $res->fetch_assoc();
$room_id = $room['id'];

$is_authorized = ($room['host_uuid'] === $uuid);
if (!$is_authorized) {
    $stmt_ch = $conn->prepare("SELECT is_cohost FROM room_players WHERE room_id = ? AND uuid = ?");
    $stmt_ch->bind_param("is", $room_id, $uuid);
    $stmt_ch->execute();
    $res_ch = $stmt_ch->get_result();
    if ($row_ch = $res_ch->fetch_assoc()) {
        $is_authorized = ($row_ch['is_cohost'] == 1);
    }
}

if (!$is_authorized) {
    echo json_encode(['success' => false, 'error' => 'Not authorized']);
    exit;
}

// Block starting a new match if the room is currently playing and there are still active players (finished = 0)
if ($room['status'] === 'playing') {
    $stmtActive = $conn->prepare("SELECT COUNT(*) as active_count FROM room_players WHERE room_id = ? AND finished = 0");
    $stmtActive->bind_param("i", $room_id);
    $stmtActive->execute();
    $activeCount = (int)($stmtActive->get_result()->fetch_assoc()['active_count'] ?? 0);
    if ($activeCount > 0) {
        echo json_encode([
            'success' => false,
            'error' => "Aguarde! Ainda há $activeCount jogador(es) ativo(s) na partida em andamento."
        ]);
        exit;
    }
}

$mode         = (int)$room['mode'];
$max_mistakes = (int)($room['max_mistakes'] ?? 6);
$lang         = $room['language'] ?: 'pt';

// Generate fresh words with real hints
require_once 'word_generator.php';
$words = generateDailyWords($pdo, $lang, $mode);
$words_json = json_encode($words);

// Update room with new words, started_at = NOW(), and set status back to playing
$stmt = $conn->prepare("UPDATE rooms SET status = 'playing', words = ?, started_at = NOW() WHERE id = ?");
$stmt->bind_param("si", $words_json, $room_id);
$stmt->execute();

// Reset all players' progress and AFK timers for the new round
$stmt = $conn->prepare("UPDATE room_players SET mistakes = 0, words_completed = 0, finished = 0, last_action = NOW(), last_seen = NOW() WHERE room_id = ?");
$stmt->bind_param("i", $room_id);
$stmt->execute();

$started_at = null;
$rRes = $conn->query("SELECT started_at FROM rooms WHERE id = $room_id");
if ($r = $rRes->fetch_assoc()) {
    $started_at = $r['started_at'];
}
$match_id = md5(($started_at ?? '') . '|' . $words_json);

echo json_encode([
    'success'      => true,
    'match_id'     => $match_id,
    'started_at'   => $started_at,
    'words'        => $words,
    'max_mistakes' => $max_mistakes
]);
?>

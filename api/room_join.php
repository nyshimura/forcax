<?php
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';

$input = json_decode(file_get_contents('php://input'), true);
$room_code = $input['room_code'] ?? '';
$player_uuid = $input['uuid'] ?? '';
$player_name = $input['name'] ?? 'Visitante';

if (empty($room_code) || empty($player_uuid)) {
    echo json_encode(['success' => false, 'error' => 'Invalid parameters']);
    exit;
}

// Fixed missing max_players column
$stmt = $conn->prepare("SELECT id, status, mode, max_mistakes, host_uuid, is_private, max_players FROM rooms WHERE room_code = ?");
$stmt->bind_param("s", $room_code);
$stmt->execute();
$room = $stmt->get_result()->fetch_assoc();

if (!$room) {
    echo json_encode(['success' => false, 'error' => 'Sala não encontrada.']);
    exit;
}


$room_id = $room['id'];
$max_players = (int)$room['max_players'];
$max_mistakes = (int)($room['max_mistakes'] ?? 6);

// Fixed column player_uuid to uuid
$stmt = $conn->prepare("SELECT COUNT(DISTINCT uuid) as c FROM room_players WHERE room_id = ?");
$stmt->bind_param("i", $room_id);
$stmt->execute();
$current_players = $stmt->get_result()->fetch_assoc()['c'];

// Check if full
$stmt = $conn->prepare("SELECT 1 FROM room_players WHERE room_id = ? AND uuid = ?");
$stmt->bind_param("is", $room_id, $player_uuid);
$stmt->execute();
$already_in_room = $stmt->get_result()->num_rows > 0;

if (!$already_in_room && $current_players >= $max_players && $player_uuid !== $room['host_uuid']) {
    echo json_encode(['success' => false, 'error' => 'A sala está cheia.']);
    exit;
}

if ($room['is_private'] && !$already_in_room && $player_uuid !== $room['host_uuid']) {
    // Verificar se tem request aceito ('accepted' ou 'approved')
    $stmt = $conn->prepare("SELECT 1 FROM room_requests WHERE room_id = ? AND uuid = ? AND status IN ('accepted', 'approved')");
    $stmt->bind_param("is", $room_id, $player_uuid);
    $stmt->execute();
    if ($stmt->get_result()->num_rows === 0) {
        echo json_encode(['success' => false, 'error' => 'Esta sala é privada e você não foi aceito.']);
        exit;
    }
}

$stmt = $conn->prepare("DELETE FROM room_players WHERE uuid = ? AND room_id != ?");
$stmt->bind_param("si", $player_uuid, $room_id);
$stmt->execute();

// Ensure last_seen and last_action are refreshed on join
$stmt = $conn->prepare("INSERT INTO room_players (room_id, uuid, player_name, last_seen, last_action) VALUES (?, ?, ?, NOW(), NOW()) ON DUPLICATE KEY UPDATE player_name = ?, last_seen = NOW()");
$stmt->bind_param("isss", $room_id, $player_uuid, $player_name, $player_name);
$stmt->execute();

echo json_encode([
    'success' => true,
    'room_id' => $room_id,
    'mode' => (int)$room['mode'],
    'max_mistakes' => $max_mistakes,
    'status' => $room['status'],
    'max_players' => $max_players,
    'is_private' => $room['is_private'],
    'is_host' => ($player_uuid === $room['host_uuid'])
]);
?>

<?php
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';

$data = json_decode(file_get_contents("php://input"));
$room_code = $data->room_code ?? '';
$host_uuid = $data->host_uuid ?? '';
$request_id = $data->request_id ?? 0;
$action = $data->action ?? ''; // 'accept' or 'reject'

if (!$room_code || !$host_uuid || !$request_id || !in_array($action, ['accept', 'reject'])) {
    echo json_encode(['success' => false, 'error' => 'Missing data']);
    exit;
}

// Verificar se o usuário é dono ou co-host da sala
$stmt = $conn->prepare("SELECT id, host_uuid, max_players FROM rooms WHERE room_code = ?");
$stmt->bind_param("s", $room_code);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    echo json_encode(['success' => false, 'error' => 'Room not found']);
    exit;
}
$roomRow = $res->fetch_assoc();
$room_id = $roomRow['id'];
$max_players = $roomRow['max_players'];

$is_authorized = ($roomRow['host_uuid'] === $host_uuid);
if (!$is_authorized) {
    $stmt_ch = $conn->prepare("SELECT is_cohost FROM room_players WHERE room_id = ? AND uuid = ?");
    $stmt_ch->bind_param("is", $room_id, $host_uuid);
    $stmt_ch->execute();
    $res_ch = $stmt_ch->get_result();
    if ($row_ch = $res_ch->fetch_assoc()) {
        $is_authorized = ($row_ch['is_cohost'] == 1);
    }
}

if (!$is_authorized) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

if ($action === 'accept') {
    $stmt = $conn->prepare("SELECT COUNT(DISTINCT uuid) as c FROM room_players WHERE room_id = ?");
    $stmt->bind_param("i", $room_id);
    $stmt->execute();
    $current_players = $stmt->get_result()->fetch_assoc()['c'];

    if ($current_players >= $max_players) {
        echo json_encode(['success' => false, 'error' => 'A sala já está cheia.']);
        exit;
    }
}

$new_status = $action === 'accept' ? 'accepted' : 'rejected';

$stmt = $conn->prepare("UPDATE room_requests SET status = ? WHERE id = ? AND room_id = ?");
$stmt->bind_param("sii", $new_status, $request_id, $room_id);
$stmt->execute();

if ($action === 'accept') {
    // Pegar dados do request
    $stmt = $conn->prepare("SELECT uuid, player_name FROM room_requests WHERE id = ?");
    $stmt->bind_param("i", $request_id);
    $stmt->execute();
    $req = $stmt->get_result()->fetch_assoc();
    
    if ($req) {
        $player_uuid = $req['uuid'];
        $player_name = $req['player_name'];
        
        // Remove the player from all other rooms and delete their requests for other rooms
        $stmt = $conn->prepare("DELETE FROM room_players WHERE uuid = ? AND room_id != ?");
        $stmt->bind_param("si", $player_uuid, $room_id);
        $stmt->execute();

        $stmt = $conn->prepare("DELETE FROM room_requests WHERE uuid = ? AND room_id != ?");
        $stmt->bind_param("si", $player_uuid, $room_id);
        $stmt->execute();
        
        // Inserir na sala atual
        $stmt = $conn->prepare("INSERT INTO room_players (room_id, uuid, player_name) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE player_name = ?");
        $stmt->bind_param("isss", $room_id, $player_uuid, $player_name, $player_name);
        $stmt->execute();
    }
}

echo json_encode(['success' => true]);
?>

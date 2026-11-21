<?php
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';

$data = json_decode(file_get_contents("php://input"));
$room_code = $data->room_code ?? '';
$uuid = $data->uuid ?? '';
$player_name = $data->player_name ?? 'Visitante';

if (!$room_code || !$uuid) {
    echo json_encode(['success' => false, 'error' => 'Missing data']);
    exit;
}

// Encontrar ID da sala
$stmt = $conn->prepare("SELECT id, is_private, max_players, host_uuid FROM rooms WHERE room_code = ? AND status IN ('waiting', 'playing', 'finished')");
$stmt->bind_param("s", $room_code);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    echo json_encode(['success' => false, 'error' => 'Sala não encontrada ou não está mais aguardando.']);
    exit;
}

$room = $res->fetch_assoc();
$room_id = $room['id'];
$is_private = (int)$room['is_private'];
$max_players = (int)$room['max_players'];
$host_uuid = $room['host_uuid'];

if ($uuid === $host_uuid) {
    echo json_encode(['success' => true, 'status' => 'approved']);
    exit;
}

if ($is_private === 0) {
    // Check if room is full
    $stmt = $conn->prepare("SELECT COUNT(DISTINCT uuid) as c FROM room_players WHERE room_id = ?");
    $stmt->bind_param("i", $room_id);
    $stmt->execute();
    $current = $stmt->get_result()->fetch_assoc()['c'];
    
    // Check if player is already in room
    $stmt = $conn->prepare("SELECT 1 FROM room_players WHERE room_id = ? AND uuid = ?");
    $stmt->bind_param("is", $room_id, $uuid);
    $stmt->execute();
    $already_in = $stmt->get_result()->num_rows > 0;
    
    if (!$already_in && $current >= $max_players) {
        echo json_encode(['success' => false, 'error' => 'A sala está cheia.']);
        exit;
    }
    
    echo json_encode(['success' => true, 'status' => 'approved']);
    exit;
}

// Checar se já existe um request pendente ou aceito
$stmt = $conn->prepare("SELECT id, status FROM room_requests WHERE room_id = ? AND uuid = ?");
$stmt->bind_param("is", $room_id, $uuid);
$stmt->execute();
$req_res = $stmt->get_result();

$request_id = null;
$status = 'pending';

if ($req_res->num_rows > 0) {
    $row = $req_res->fetch_assoc();
    $request_id = $row['id'];
    $status = $row['status'];
    if ($status === 'accepted') {
        $status = 'approved';
    } else if ($status === 'rejected') {
        $stmt = $conn->prepare("UPDATE room_requests SET status = 'pending', player_name = ? WHERE id = ?");
        $stmt->bind_param("si", $player_name, $request_id);
        $stmt->execute();
        $status = 'pending';
    }
} else {
    $stmt = $conn->prepare("INSERT INTO room_requests (room_id, player_name, uuid, status) VALUES (?, ?, ?, 'pending')");
    $stmt->bind_param("iss", $room_id, $player_name, $uuid);
    $stmt->execute();
    $request_id = $conn->insert_id;
}

echo json_encode(['success' => true, 'status' => $status, 'request_id' => $request_id]);
?>

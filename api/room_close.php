<?php
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';

$data = json_decode(file_get_contents("php://input"));
$room_code = $data->room_code ?? '';
$uuid = $data->uuid ?? '';

if (!$room_code || !$uuid) {
    echo json_encode(['success' => false, 'error' => 'Missing data']);
    exit;
}

$stmt = $conn->prepare("SELECT id FROM rooms WHERE room_code = ? AND host_uuid = ?");
$stmt->bind_param("ss", $room_code, $uuid);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows > 0) {
    $room_id = $res->fetch_assoc()['id'];
    $conn->query("DELETE FROM room_requests WHERE room_id = $room_id");
    $conn->query("DELETE FROM room_players WHERE room_id = $room_id");
    $conn->query("DELETE FROM rooms WHERE id = $room_id");
    echo json_encode(['success' => true, 'message' => 'Sala excluída permanentemente.']);
} else {
    echo json_encode(['success' => false, 'error' => 'Acesso negado. Apenas o criador da sala pode excluí-la.']);
}

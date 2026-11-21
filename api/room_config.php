<?php
header('Content-Type: application/json');

require_once __DIR__ . '/db.php';
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'error' => 'Database error']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$uuid = isset($input['uuid']) ? $input['uuid'] : '';
$room_code = isset($input['room_code']) ? $input['room_code'] : '';
$max_players = isset($input['max_players']) ? (int)$input['max_players'] : 4;
$is_private = isset($input['is_private']) ? (int)$input['is_private'] : 1;

if (empty($uuid) || empty($room_code)) {
    echo json_encode(['success' => false, 'error' => 'Dados inválidos.']);
    exit;
}

$stmt = $conn->prepare("UPDATE rooms SET max_players = ?, is_private = ? WHERE room_code = ? AND host_uuid = ? AND status = 'waiting'");
$stmt->bind_param("iiss", $max_players, $is_private, $room_code, $uuid);

if ($stmt->execute() && $stmt->affected_rows > 0) {
    echo json_encode(['success' => true]);
} else {
    // Maybe no rows affected because values didn't change, which is fine, just return success if no error.
    if ($stmt->error) {
        echo json_encode(['success' => false, 'error' => 'Erro ao atualizar.']);
    } else {
        echo json_encode(['success' => true]);
    }
}
?>

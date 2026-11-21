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

$stmt = $conn->prepare("SELECT id FROM rooms WHERE room_code = ?");
$stmt->bind_param("s", $room_code);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows > 0) {
    $room_id = $res->fetch_assoc()['id'];
    
    // Remove do jogo
    $stmt = $conn->prepare("DELETE FROM room_players WHERE room_id = ? AND uuid = ?");
    $stmt->bind_param("is", $room_id, $uuid);
    $stmt->execute();
    
    // Remove também da fila de espera caso estivesse aguardando
    $stmt = $conn->prepare("DELETE FROM room_requests WHERE room_id = ? AND uuid = ?");
    $stmt->bind_param("is", $room_id, $uuid);
    $stmt->execute();

    // Se a sala ficou completamente vazia, excluir a sala imediatamente para não ficar abandonada na lista
    $stmtCount = $conn->prepare("SELECT COUNT(*) as c FROM room_players WHERE room_id = ?");
    $stmtCount->bind_param("i", $room_id);
    $stmtCount->execute();
    $remaining = (int)($stmtCount->get_result()->fetch_assoc()['c'] ?? 0);
    if ($remaining === 0) {
        $conn->query("DELETE FROM room_requests WHERE room_id = $room_id");
        $conn->query("DELETE FROM rooms WHERE id = $room_id");
    }
}

echo json_encode(['success' => true]);
?>

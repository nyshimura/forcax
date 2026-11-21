<?php
header('Content-Type: application/json');
require_once 'db.php';

$input = json_decode(file_get_contents('php://input'), true);
$room_code = $input['room_code'] ?? '';
$host_uuid = $input['host_uuid'] ?? '';
$target_uuid = $input['target_uuid'] ?? '';
$action = $input['action'] ?? 'promote'; // promote or demote

if (empty($room_code) || empty($host_uuid) || empty($target_uuid)) {
    echo json_encode(['error' => 'Parâmetros inválidos']);
    exit;
}

$stmt = $pdo->prepare("SELECT id, host_uuid FROM rooms WHERE room_code = ?");
$stmt->execute([$room_code]);
$room = $stmt->fetch();

if (!$room) {
    echo json_encode(['error' => 'Sala não encontrada']);
    exit;
}

if ($room['host_uuid'] !== $host_uuid) {
    echo json_encode(['error' => 'Apenas o criador da sala pode promover administradores.']);
    exit;
}

$is_cohost = ($action === 'promote') ? 1 : 0;
$stmt = $pdo->prepare("UPDATE room_players SET is_cohost = ? WHERE room_id = ? AND uuid = ?");
$stmt->execute([$is_cohost, $room['id'], $target_uuid]);

echo json_encode(['success' => true]);
?>

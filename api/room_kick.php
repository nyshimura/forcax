<?php
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';

$data = json_decode(file_get_contents("php://input"));
$room_code = $data->room_code ?? '';
$host_uuid = $data->host_uuid ?? '';
$target_uuid = $data->target_uuid ?? '';

if (!$room_code || !$host_uuid || !$target_uuid) {
    echo json_encode(['success' => false, 'error' => 'Missing data']);
    exit;
}

$stmt = $conn->prepare("SELECT id, host_uuid FROM rooms WHERE room_code = ?");
$stmt->bind_param("s", $room_code);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    echo json_encode(['success' => false, 'error' => 'Acesso negado.']);
    exit;
}
$roomRow = $res->fetch_assoc();
$room_id = $roomRow['id'];

$is_host = ($roomRow['host_uuid'] === $host_uuid);
$is_cohost = false;
if (!$is_host) {
    $stmt_ch = $conn->prepare("SELECT is_cohost FROM room_players WHERE room_id = ? AND uuid = ?");
    $stmt_ch->bind_param("is", $room_id, $host_uuid);
    $stmt_ch->execute();
    $res_ch = $stmt_ch->get_result();
    if ($row_ch = $res_ch->fetch_assoc()) {
        $is_cohost = ($row_ch['is_cohost'] == 1);
    }
}

if (!$is_host && !$is_cohost) {
    echo json_encode(['success' => false, 'error' => 'Acesso negado.']);
    exit;
}

// Prevent kicking yourself or the room host
if ($host_uuid === $target_uuid || $target_uuid === $roomRow['host_uuid']) {
    echo json_encode(['success' => false, 'error' => 'Você não pode expulsar este jogador.']);
    exit;
}

$stmt = $conn->prepare("DELETE FROM room_players WHERE room_id = ? AND uuid = ?");
$stmt->bind_param("is", $room_id, $target_uuid);
$stmt->execute();

$stmt = $conn->prepare("UPDATE room_requests SET status = 'rejected' WHERE room_id = ? AND uuid = ?");
$stmt->bind_param("is", $room_id, $target_uuid);
$stmt->execute();

echo json_encode(['success' => true]);
?>

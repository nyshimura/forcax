<?php
header('Content-Type: application/json');

require_once __DIR__ . '/db.php';
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'error' => 'Database error']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$uuid = isset($input['uuid']) ? $input['uuid'] : '';

if (empty($uuid)) {
    echo json_encode(['success' => false, 'has_room' => false]);
    exit;
}

// Sanitizar salas abandonadas antes de checar
require_once __DIR__ . '/rooms_cleanup.php';

$stmt = $conn->prepare("
    SELECT r.room_code, r.mode 
    FROM rooms r
    WHERE r.host_uuid = ? 
      AND r.status IN ('waiting','playing','finished')
      AND EXISTS (
          SELECT 1 FROM room_players rp 
          WHERE rp.room_id = r.id AND rp.last_seen >= NOW() - INTERVAL 2 MINUTE
      )
    ORDER BY r.created_at DESC LIMIT 1
");
$stmt->bind_param("s", $uuid);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    echo json_encode(['success' => true, 'has_room' => true, 'room_code' => $row['room_code'], 'mode' => $row['mode']]);
} else {
    echo json_encode(['success' => true, 'has_room' => false]);
}
?>

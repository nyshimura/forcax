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
    echo json_encode(['success' => false, 'error' => 'Sessão inválida.']);
    exit;
}

// Sanitizar salas abandonadas e jogadores desconectados antes de listar
require_once __DIR__ . '/rooms_cleanup.php';

// Selecionar apenas salas ativas (com pelo menos 1 jogador online nos últimos 2 minutos)
$query = "
    SELECT 
        r.id, 
        r.room_code, 
        r.mode, 
        r.max_players, 
        r.max_mistakes,
        r.is_private,
        r.host_uuid,
        r.status,
        (SELECT COUNT(*) FROM room_players rp WHERE rp.room_id = r.id) as current_players,
        (SELECT rp.player_name FROM room_players rp WHERE rp.room_id = r.id AND rp.uuid = r.host_uuid LIMIT 1) as host_name
    FROM rooms r
    WHERE EXISTS (
        SELECT 1 FROM room_players rp2
        WHERE rp2.room_id = r.id
          AND rp2.last_seen >= NOW() - INTERVAL 2 MINUTE
    )
    ORDER BY r.created_at DESC
";

$stmt = $conn->prepare($query);
$stmt->execute();
$res = $stmt->get_result();
$rooms = [];

if ($res) {
    while ($row = $res->fetch_assoc()) {
        $rooms[] = [
            'code' => $row['room_code'],
            'mode' => (int)$row['mode'],
            'max_players' => (int)($row['max_players'] ?: 4),
            'max_mistakes' => (int)($row['max_mistakes'] ?: 6),
            'current_players' => (int)$row['current_players'],
            'is_private' => (bool)$row['is_private'],
            'host_name' => $row['host_name'] ?: 'Host Desconhecido',
            'is_host' => ($uuid === $row['host_uuid']),
            'status' => $row['status']
        ];
    }
}

echo json_encode([
    'success' => true,
    'rooms' => $rooms
]);
?>

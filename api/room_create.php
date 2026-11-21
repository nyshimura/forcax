<?php
header('Content-Type: application/json');

require_once __DIR__ . '/db.php';
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'error' => 'Database error']);
    exit;
}

// Sanitizar salas abandonadas e jogadores inativos
require_once __DIR__ . '/rooms_cleanup.php';

$input = json_decode(file_get_contents('php://input'), true);
$mode = isset($input['mode']) ? (int)$input['mode'] : 1;
$lang = isset($input['lang']) ? $input['lang'] : 'pt';
$uuid = isset($input['uuid']) ? $input['uuid'] : '';
$max_mistakes = isset($input['max_mistakes']) ? (int)$input['max_mistakes'] : 6;
$is_private = isset($input['is_private']) ? (int)$input['is_private'] : 1;

if (empty($uuid)) {
    echo json_encode(['success' => false, 'error' => 'Sessão inválida.']);
    exit;
}

require_once 'word_generator.php';
$words = generateDailyWords($pdo, $lang, $mode);
$words_json = json_encode($words);

function generateCode() {
    return strtoupper(substr(md5(uniqid(rand(), true)), 0, 4));
}
$room_code = generateCode();

$stmt = $conn->prepare("UPDATE rooms SET status = 'finished' WHERE host_uuid = ? AND status = 'waiting'");
$stmt->bind_param("s", $uuid);
$stmt->execute();

$stmt = $conn->prepare("INSERT INTO rooms (host_uuid, room_code, mode, language, words, is_private, max_mistakes, max_players) VALUES (?, ?, ?, ?, ?, ?, ?, 4)");
$stmt->bind_param("ssissii", $uuid, $room_code, $mode, $lang, $words_json, $is_private, $max_mistakes);

if ($stmt->execute()) {
    $room_id = $conn->insert_id;
    echo json_encode(['success' => true, 'room_code' => $room_code, 'room_id' => $room_id]);
} else {
    echo json_encode(['success' => false, 'error' => 'Erro ao criar sala.']);
}
?>

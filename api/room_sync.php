<?php
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/word_generator.php';

$input = json_decode(file_get_contents('php://input'), true);
$room_code = $input['room_code'] ?? '';
$uuid = $input['uuid'] ?? '';
$action = $input['action'] ?? 'ping'; // ping, update, start

if (empty($room_code) || empty($uuid)) {
    echo json_encode(['success' => false]);
    exit;
}

$stmt = $conn->prepare("SELECT * FROM rooms WHERE room_code = ?");
$stmt->bind_param("s", $room_code);
$stmt->execute();
$room = $stmt->get_result()->fetch_assoc();

if (!$room) {
    echo json_encode(['success' => false, 'error' => 'A sala foi encerrada ou não existe mais.']);
    exit;
}

$room_id = $room['id'];

if ($uuid !== $room['host_uuid']) {
    $stmt = $conn->prepare("SELECT 1 FROM room_players WHERE room_id = ? AND uuid = ?");
    $stmt->bind_param("is", $room_id, $uuid);
    $stmt->execute();
    if ($stmt->get_result()->num_rows === 0) {
        echo json_encode(['success' => false, 'error' => 'Você foi removido da sala.']);
        exit;
    }
}

// Get my cohost status
$is_my_cohost = false;
$stmt_ch = $conn->prepare("SELECT is_cohost FROM room_players WHERE room_id = ? AND uuid = ?");
$stmt_ch->bind_param("is", $room_id, $uuid);
$stmt_ch->execute();
$res_ch = $stmt_ch->get_result();
if ($row_ch = $res_ch->fetch_assoc()) {
    $is_my_cohost = ($row_ch['is_cohost'] == 1);
}

// If action is start and host or cohost is calling
if ($action === 'start') {
    if ($uuid === $room['host_uuid'] || $is_my_cohost) {
        if ($room['status'] === 'playing') {
            $stmtActive = $conn->query("SELECT COUNT(*) as active_count FROM room_players WHERE room_id = $room_id AND finished = 0");
            $activeCount = (int)($stmtActive->fetch_assoc()['active_count'] ?? 0);
            if ($activeCount > 0) {
                echo json_encode([
                    'success' => false,
                    'keep_room' => true,
                    'error' => 'Aguarde todos os jogadores ativos terminarem a partida atual!'
                ]);
                exit;
            }
        }
        if ($room['status'] === 'finished' || $room['status'] === 'playing') {
            $mode = (int)($room['mode'] ?? 1);
            $lang = $room['language'] ?? 'pt';
            $new_words = generateDailyWords($pdo, $lang, $mode);
            $words_json = json_encode($new_words);
            $stmt = $conn->prepare("UPDATE rooms SET status = 'playing', words = ?, started_at = NOW() WHERE id = ?");
            $stmt->bind_param("si", $words_json, $room_id);
            $stmt->execute();
            $room['words'] = $words_json;
        } else {
            $stmt = $conn->prepare("UPDATE rooms SET status = 'playing', started_at = NOW() WHERE id = ?");
            $stmt->bind_param("i", $room_id);
            $stmt->execute();
        }
        $room['status'] = 'playing';
        
        $conn->query("UPDATE room_players SET finished = 0, mistakes = 0, words_completed = 0, last_seen = NOW(), last_action = NOW() WHERE room_id = $room_id");
    }
}

// If action is close
if ($action === 'close') {
    if ($uuid === $room['host_uuid']) {
        $conn->query("DELETE FROM room_requests WHERE room_id = $room_id");
        $conn->query("DELETE FROM room_players WHERE room_id = $room_id");
        $conn->query("DELETE FROM rooms WHERE id = $room_id");
        echo json_encode(['success' => false, 'error' => 'A sala foi encerrada e excluída.']);
        exit;
    }
}

// If action is update_name
if ($action === 'update_name') {
    $new_name = $input['name'] ?? 'Visitante';
    $stmt = $conn->prepare("UPDATE room_players SET player_name = ?, last_seen = NOW() WHERE room_id = ? AND uuid = ?");
    $stmt->bind_param("sis", $new_name, $room_id, $uuid);
    $stmt->execute();
}

// Update my own progress if action is update
if ($action === 'update') {
    $client_match_id = $input['match_id'] ?? '';
    $current_match_id = md5(($room['started_at'] ?? '') . '|' . ($room['words'] ?? ''));

    $mistakes = (int)($input['mistakes'] ?? 0);
    $completed = (int)($input['completed'] ?? 0);
    $finished = (int)($input['finished'] ?? 0);
    $score_increment = (int)($input['score_increment'] ?? 0);
    $letters_found = isset($input['letters_found']) ? (int)$input['letters_found'] : null;
    
    // Only accept progress/score updates if room is playing AND match_id matches the current active match
    if ($room['status'] === 'playing' && (empty($client_match_id) || $client_match_id === $current_match_id)) {
        $stmt = $conn->prepare("UPDATE room_players SET mistakes = ?, words_completed = ?, finished = ?, score = score + ?, last_seen = NOW(), last_action = NOW() WHERE room_id = ? AND uuid = ?");
        $stmt->bind_param("iiiiis", $mistakes, $completed, $finished, $score_increment, $room_id, $uuid);
        $stmt->execute();
    } else {
        $stmt = $conn->prepare("UPDATE room_players SET last_seen = NOW() WHERE room_id = ? AND uuid = ?");
        $stmt->bind_param("is", $room_id, $uuid);
        $stmt->execute();
    }
} else if ($action === 'ping') {
    // Just a ping
    $stmt = $conn->prepare("UPDATE room_players SET last_seen = NOW() WHERE room_id = ? AND uuid = ?");
    $stmt->bind_param("is", $room_id, $uuid);
    $stmt->execute();
}

$conn->query("UPDATE rooms SET created_at = created_at WHERE id = $room_id"); // dummy update for last_activity if needed

// --- INACTIVITY LOGIC ---
if ($room['status'] === 'waiting') {
    // Kick players inactive (disconnected) for > 90s
    $conn->query("DELETE FROM room_players WHERE room_id = $room_id AND last_seen < NOW() - INTERVAL 90 SECOND AND uuid != '" . $conn->real_escape_string($room['host_uuid']) . "'");
} else if ($room['status'] === 'playing') {
    // Mark players as dead (AFK) if disconnected for > 45s OR no guess action for > 120s
    $conn->query("UPDATE room_players SET finished = -1 WHERE room_id = $room_id AND finished = 0 AND (last_seen < NOW() - INTERVAL 45 SECOND OR last_action < NOW() - INTERVAL 120 SECOND)");
}
// -----------------------

// Count active players still playing in the current match
$stmtActive = $conn->query("SELECT COUNT(*) as active_count FROM room_players WHERE room_id = $room_id AND finished = 0");
$activePlayersCount = (int)($stmtActive->fetch_assoc()['active_count'] ?? 0);

// Check if anyone finished (won) or if all players finished (won or lost) to end the room
$stmt = $conn->query("SELECT COUNT(*) as c FROM room_players WHERE room_id = $room_id AND finished = 1");
$finisherCount = (int)$stmt->fetch_assoc()['c'];
if ($finisherCount > 0 && $room['status'] === 'playing') {
    $conn->query("UPDATE rooms SET status = 'finished' WHERE id = $room_id");
    $room['status'] = 'finished';
    $activePlayersCount = 0;
} else if ($room['status'] === 'playing') {
    $stmtTotal = $conn->query("SELECT COUNT(*) as total, SUM(CASE WHEN finished != 0 THEN 1 ELSE 0 END) as done FROM room_players WHERE room_id = $room_id");
    $counts = $stmtTotal->fetch_assoc();
    if ($counts['total'] > 0 && $counts['total'] == $counts['done']) {
        $conn->query("UPDATE rooms SET status = 'finished' WHERE id = $room_id");
        $room['status'] = 'finished';
        $activePlayersCount = 0;
    }
}

// Get all players
$players = [];
if ($room['status'] === 'waiting') {
    $res = $conn->query("SELECT p.uuid as uuid, p.player_name, (p.uuid = r.host_uuid) as is_host, p.is_cohost, p.mistakes, p.words_completed, p.finished, p.score, TIME_TO_SEC(TIMEDIFF(NOW(), p.last_action)) as idle_seconds FROM room_players p JOIN rooms r ON p.room_id = r.id WHERE p.room_id = $room_id ORDER BY is_host DESC, is_cohost DESC");
} else {
    $res = $conn->query("SELECT p.uuid as uuid, p.player_name, (p.uuid = r.host_uuid) as is_host, p.is_cohost, p.mistakes, p.words_completed, p.finished, p.score, TIME_TO_SEC(TIMEDIFF(NOW(), p.last_action)) as idle_seconds FROM room_players p JOIN rooms r ON p.room_id = r.id WHERE p.room_id = $room_id ORDER BY p.score DESC, p.finished DESC, p.words_completed DESC, p.mistakes ASC");
}
while ($row = $res->fetch_assoc()) {
    // Inject can_edit for legacy UI compatibility
    $row['can_edit'] = 0;
    $row['score'] = (int)$row['score'];
    $row['is_host'] = (bool)$row['is_host'];
    $row['is_cohost'] = (bool)$row['is_cohost'];
    $players[] = $row;
}

// Ensure we fetch the started_at time and server elapsed_seconds if playing/finished
$started_at = $room['started_at'] ?? null;
$elapsed_seconds = 0;
$rRes = $conn->query("SELECT started_at, words, mode, max_mistakes, GREATEST(0, TIMESTAMPDIFF(SECOND, started_at, NOW())) as elapsed_seconds FROM rooms WHERE id = $room_id");
if ($r = $rRes->fetch_assoc()) {
    $started_at = $r['started_at'];
    $room['words'] = $r['words'];
    $room['mode'] = (int)$r['mode'];
    $room['max_mistakes'] = (int)($r['max_mistakes'] ?? 6);
    $elapsed_seconds = (int)($r['elapsed_seconds'] ?? 0);
}

$match_id = md5(($started_at ?? '') . '|' . ($room['words'] ?? ''));

if ($room['status'] === 'finished' && !empty($players)) {
    require_once __DIR__ . '/multiplayer_stats_helper.php';
    recordMultiplayerMatchResults($conn, $room_code, $match_id, $players);
}

$response = [
    'success' => true,
    'status' => $room['status'],
    'mode' => (int)($room['mode'] ?? 1),
    'match_id' => $match_id,
    'started_at' => $started_at,
    'elapsed_seconds' => $elapsed_seconds,
    'active_players_count' => $activePlayersCount,
    'max_mistakes' => (int)($room['max_mistakes'] ?? 6),
    'is_host' => ($uuid === $room['host_uuid']),
    'is_cohost' => $is_my_cohost,
    'players' => $players
];

if ($uuid === $room['host_uuid'] || $is_my_cohost) {
    $reqs = [];
    $resReq = $conn->query("
        SELECT rr.id, rr.player_name, rr.uuid, rr.status
        FROM room_requests rr 
        WHERE rr.room_id = $room_id AND rr.status = 'pending'
    ");
    while ($r = $resReq->fetch_assoc()) {
        $r['is_busy'] = false;
        $reqs[] = $r;
    }
    $response['requests'] = $reqs;
}

if ($room['status'] === 'playing' || $room['status'] === 'finished') {
    $raw_words = json_decode($room['words'], true) ?: [];
    // Anti-spoiler: Check if current caller has lost (finished == -1) while the match is still 'playing'
    $my_finished_state = 0;
    foreach ($players as $pl) {
        if ($pl['uuid'] === $uuid) {
            $my_finished_state = (int)$pl['finished'];
            break;
        }
    }
    if ($room['status'] === 'playing' && $my_finished_state === -1 && $activePlayersCount > 0) {
        // Mask the secret word so a eliminated player cannot see or inspect it until the match finishes
        $response['words'] = array_map(function($w) {
            $len = isset($w['length']) ? (int)$w['length'] : mb_strlen($w['word'] ?? '', 'UTF-8');
            return [
                'word' => str_repeat('?', $len),
                'length' => $len,
                'hint' => $w['hint'] ?? '',
                'masked' => true
            ];
        }, $raw_words);
        $response['words_hidden'] = true;
    } else {
        $response['words'] = $raw_words;
        $response['words_hidden'] = false;
    }
}

echo json_encode($response);
?>

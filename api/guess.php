<?php
// api/guess.php
header('Content-Type: application/json');
require_once 'db.php';

$uuid = isset($_SERVER['HTTP_X_UUID']) ? $_SERVER['HTTP_X_UUID'] : '';
if (empty($uuid)) {
    echo json_encode(['error' => 'Missing UUID']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$mode = isset($input['mode']) ? (int)$input['mode'] : 1;
$guesses = isset($input['guesses']) ? $input['guesses'] : [];
$room_code = isset($input['room_code']) ? trim($input['room_code']) : '';

$guesses = array_map(function($g) { return mb_strtoupper($g, 'UTF-8'); }, $guesses);

$stmt = $pdo->prepare("SELECT language FROM players WHERE uuid = ?");
$stmt->execute([$uuid]);
$player = $stmt->fetch();
$lang = $player ? $player['language'] : 'pt';

function removeAccents($str) {
    $unwanted_array = array('Š'=>'S', 'š'=>'s', 'Ž'=>'Z', 'ž'=>'z', 'À'=>'A', 'Á'=>'A', 'Â'=>'A', 'Ã'=>'A', 'Ä'=>'A', 'Å'=>'A', 'Æ'=>'A', 'Ç'=>'C', 'È'=>'E', 'É'=>'E',
                            'Ê'=>'E', 'Ë'=>'E', 'Ì'=>'I', 'Í'=>'I', 'Î'=>'I', 'Ï'=>'I', 'Ñ'=>'N', 'Ò'=>'O', 'Ó'=>'O', 'Ô'=>'O', 'Õ'=>'O', 'Ö'=>'O', 'Ø'=>'O', 'Ù'=>'U',
                            'Ú'=>'U', 'Û'=>'U', 'Ü'=>'U', 'Ý'=>'Y', 'Þ'=>'B', 'ß'=>'Ss', 'à'=>'a', 'á'=>'a', 'â'=>'a', 'ã'=>'a', 'ä'=>'a', 'å'=>'a', 'æ'=>'a', 'ç'=>'c',
                            'è'=>'e', 'é'=>'e', 'ê'=>'e', 'ë'=>'e', 'ì'=>'i', 'í'=>'i', 'î'=>'i', 'ï'=>'i', 'ð'=>'o', 'ñ'=>'n', 'ò'=>'o', 'ó'=>'o', 'ô'=>'o', 'õ'=>'o',
                            'ö'=>'o', 'ø'=>'o', 'ù'=>'u', 'ú'=>'u', 'û'=>'u', 'ý'=>'y', 'þ'=>'b', 'ÿ'=>'y');
    return strtr($str, $unwanted_array);
}

$activeWords = [];
$roomMaxMistakes = null;

if (!empty($room_code)) {
    $stmtRoom = $pdo->prepare("SELECT mode, max_mistakes, words FROM rooms WHERE room_code = ? LIMIT 1");
    $stmtRoom->execute([$room_code]);
    $roomRow = $stmtRoom->fetch();
    if ($roomRow) {
        $mode = (int)($roomRow['mode'] ?: 1);
        $roomMaxMistakes = (int)($roomRow['max_mistakes'] ?: 6);
        $roomWordsDecoded = json_decode($roomRow['words'], true);
        if (is_array($roomWordsDecoded)) {
            foreach ($roomWordsDecoded as $rw) {
                if (is_array($rw) && !empty($rw['word'])) {
                    $activeWords[] = $rw['word'];
                } else if (is_string($rw) && !empty($rw)) {
                    $activeWords[] = $rw;
                }
            }
        }
    }
}

if (empty($activeWords)) {
    if (isset($input['override_words']) && is_array($input['override_words']) && count($input['override_words']) == $mode) {
        $activeWords = $input['override_words'];
    } else {
        $date = date('Y-m-d');
        $stmt = $pdo->prepare("SELECT * FROM daily_words WHERE date = ? AND language = ?");
        $stmt->execute([$date, $lang]);
        $daily = $stmt->fetch();

        if (!$daily) {
            echo json_encode(['error' => 'No daily words']);
            exit;
        }

        if ($mode == 1) {
            $activeWords[] = $daily["word_1"];
        } else if ($mode == 2) {
            $activeWords[] = $daily["word_2"];
            $activeWords[] = $daily["word_3"];
        } else if ($mode == 4) {
            $activeWords[] = $daily["word_4"];
            $activeWords[] = $daily["word_5"];
            $activeWords[] = $daily["word_6"];
            $activeWords[] = $daily["word_7"];
        }
    }
}

$state = [];
$mistakes = 0;
$wordsCompleted = 0;

foreach ($activeWords as $word) {
    $wordChars = mb_str_split($word, 1, 'UTF-8');
    $normalizedChars = array_map('removeAccents', $wordChars);
    
    $wordState = [];
    $isCompleted = true;
    
    foreach ($wordChars as $i => $char) {
        $normChar = $normalizedChars[$i];
        if (in_array($normChar, $guesses)) {
            $wordState[] = $char;
        } else {
            $wordState[] = null;
            $isCompleted = false;
        }
    }
    
    if ($isCompleted) {
        $wordsCompleted++;
    }
    
    $state[] = [
        'revealed' => $wordState,
        'completed' => $isCompleted
    ];
}

foreach ($guesses as $g) {
    $found = false;
    foreach ($activeWords as $word) {
        $normalizedWord = removeAccents($word);
        if (mb_strpos($normalizedWord, $g, 0, 'UTF-8') !== false) {
            $found = true;
            break;
        }
    }
    if (!$found) {
        $mistakes++;
    }
}

$maxMistakes = 6;
if ($mode == 2) $maxMistakes = 8;
if ($mode == 4) $maxMistakes = 11;
if ($roomMaxMistakes !== null && $roomMaxMistakes > 0) {
    $maxMistakes = $roomMaxMistakes;
}

$isWin = ($wordsCompleted === count($activeWords));
$isLoss = ($mistakes >= $maxMistakes);
$isGameOver = $isWin || $isLoss;

$fullWords = [];
$hideWordsUntilMatchEnds = false;

if ($isGameOver) {
    if (!empty($room_code) && !$isWin) {
        // Check if there are still other active players in the room (excluding this player who just lost)
        $stmtRoomId = $pdo->prepare("SELECT id, status FROM rooms WHERE room_code = ? LIMIT 1");
        $stmtRoomId->execute([$room_code]);
        $rInfo = $stmtRoomId->fetch();
        if ($rInfo && $rInfo['status'] === 'playing') {
            $stmtOthers = $pdo->prepare("SELECT COUNT(*) FROM room_players WHERE room_id = ? AND uuid != ? AND finished = 0");
            $stmtOthers->execute([$rInfo['id'], $uuid]);
            $othersAlive = (int)$stmtOthers->fetchColumn();
            if ($othersAlive > 0) {
                $hideWordsUntilMatchEnds = true;
            }
        }
    }
    if (!$hideWordsUntilMatchEnds) {
        $fullWords = $activeWords;
    }
}

echo json_encode([
    'state' => $state,
    'mistakes' => $mistakes,
    'max_mistakes' => $maxMistakes,
    'is_win' => $isWin,
    'is_loss' => $isLoss,
    'is_game_over' => $isGameOver,
    'hide_words' => $hideWordsUntilMatchEnds,
    'full_words' => $fullWords
]);
?>

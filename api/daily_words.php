<?php
// api/daily_words.php
header('Content-Type: application/json');
require_once 'db.php';
require_once 'word_generator.php';

// Retrieve UUID from header
$uuid = isset($_SERVER['HTTP_X_UUID']) ? $_SERVER['HTTP_X_UUID'] : '';
if (empty($uuid)) {
    echo json_encode(['error' => 'Missing UUID']);
    exit;
}

$ip = $_SERVER['REMOTE_ADDR'];
$countryCode = 'BR'; // Default
$countryName = 'Brazil';
$lang = 'pt';

// Simple mapping for common countries to wiki languages
$langMap = [
    'BR' => 'pt', 'PT' => 'pt',
    'US' => 'en', 'GB' => 'en', 'CA' => 'en', 'AU' => 'en',
    'ES' => 'es', 'MX' => 'es', 'AR' => 'es',
    'FR' => 'fr', 'DE' => 'de', 'IT' => 'it'
];

if ($ip !== '127.0.0.1' && $ip !== '::1') {
    // Try to get real location with short timeout so it never hangs page load
    $geoUrl = "http://ip-api.com/json/{$ip}";
    $ctx = stream_context_create(['http' => ['timeout' => 2]]);
    $geoData = @json_decode(@file_get_contents($geoUrl, false, $ctx), true);
    if ($geoData && isset($geoData['status']) && $geoData['status'] === 'success') {
        $countryCode = $geoData['countryCode'];
        $countryName = $geoData['country'];
        $lang = isset($langMap[$countryCode]) ? $langMap[$countryCode] : strtolower($countryCode);
    }
}

// Upsert player
$stmt = $pdo->prepare("INSERT INTO players (uuid, country_code, country_name, language) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE country_code=VALUES(country_code), country_name=VALUES(country_name)");
$stmt->execute([$uuid, $countryCode, $countryName, $lang]);

// Get player ID
$stmt = $pdo->prepare("SELECT id FROM players WHERE uuid = ?");
$stmt->execute([$uuid]);
$player = $stmt->fetch();
$playerId = $player['id'];

// Check daily words
$date = date('Y-m-d');
$stmt = $pdo->prepare("SELECT * FROM daily_words WHERE date = ? AND language = ?");
$stmt->execute([$date, $lang]);
$daily = $stmt->fetch();

if (!$daily) {
    // Fetch new words from Dictionary -> Wiki
    $newWords = generateDailyWords($pdo, $lang, 7);
    $stmt = $pdo->prepare("INSERT INTO daily_words (date, language, word_1, word_2, word_3, word_4, word_5, word_6, word_7, hint_1, hint_2, hint_3, hint_4, hint_5, hint_6, hint_7, source) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'dictionary')");
    $stmt->execute([
        $date, $lang, 
        $newWords[0]['word'], $newWords[1]['word'], $newWords[2]['word'], $newWords[3]['word'], $newWords[4]['word'], $newWords[5]['word'], $newWords[6]['word'],
        $newWords[0]['hint'], $newWords[1]['hint'], $newWords[2]['hint'], $newWords[3]['hint'], $newWords[4]['hint'], $newWords[5]['hint'], $newWords[6]['hint']
    ]);
    
    $stmt = $pdo->prepare("SELECT * FROM daily_words WHERE id = ?");
    $stmt->execute([$pdo->lastInsertId()]);
    $daily = $stmt->fetch();
}

// Return lengths and hints
$response = [
    'language' => $lang,
    'country' => $countryCode,
    'date' => $date,
    'words' => [
        ['id' => 1, 'length' => mb_strlen($daily['word_1'], 'UTF-8'), 'hint' => $daily['hint_1']],
        ['id' => 2, 'length' => mb_strlen($daily['word_2'], 'UTF-8'), 'hint' => $daily['hint_2']],
        ['id' => 3, 'length' => mb_strlen($daily['word_3'], 'UTF-8'), 'hint' => $daily['hint_3']],
        ['id' => 4, 'length' => mb_strlen($daily['word_4'], 'UTF-8'), 'hint' => $daily['hint_4']],
        ['id' => 5, 'length' => mb_strlen($daily['word_5'], 'UTF-8'), 'hint' => $daily['hint_5']],
        ['id' => 6, 'length' => mb_strlen($daily['word_6'], 'UTF-8'), 'hint' => $daily['hint_6']],
        ['id' => 7, 'length' => mb_strlen($daily['word_7'], 'UTF-8'), 'hint' => $daily['hint_7']]
    ]
];

// Check if player already played today for each mode
$modes = [1, 2, 4];
$playedModes = [];
$stmt = $pdo->prepare("SELECT mode, is_win, mistakes, guesses FROM games WHERE player_id = ? AND date = ?");
$stmt->execute([$playerId, $date]);
while ($row = $stmt->fetch()) {
    $row['guesses'] = json_decode($row['guesses'] ?: '[]');
    $row['full_words'] = [];
    
    if ($row['mode'] == 1) {
        $row['full_words'][] = $daily["word_1"];
    } else if ($row['mode'] == 2) {
        $row['full_words'][] = $daily["word_2"];
        $row['full_words'][] = $daily["word_3"];
    } else if ($row['mode'] == 4) {
        $row['full_words'][] = $daily["word_4"];
        $row['full_words'][] = $daily["word_5"];
        $row['full_words'][] = $daily["word_6"];
        $row['full_words'][] = $daily["word_7"];
    }
    
    $playedModes[$row['mode']] = $row;
}
$response['played_modes'] = $playedModes;

echo json_encode($response);
?>

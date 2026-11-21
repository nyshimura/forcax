<?php
header('Content-Type: application/json');
require_once 'db.php';

$uuid = isset($_SERVER['HTTP_X_UUID']) ? $_SERVER['HTTP_X_UUID'] : '';
$mode = isset($_GET['mode']) ? (int)$_GET['mode'] : 1;

if (empty($uuid)) {
    echo json_encode(['error' => 'Missing UUID']);
    exit;
}

// Get player ID and country
$stmt = $pdo->prepare("SELECT id, country_code FROM players WHERE uuid = ?");
$stmt->execute([$uuid]);
$player = $stmt->fetch();

if (!$player) {
    echo json_encode(['error' => 'Player not found']);
    exit;
}

$playerId = $player['id'];
$countryCode = $player['country_code'];

// 1. Personal Stats
$stmt = $pdo->prepare("SELECT date, is_win, mistakes FROM games WHERE player_id = ? AND mode = ? ORDER BY date ASC");
$stmt->execute([$playerId, $mode]);
$games = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalGames = count($games);
$wins = 0;
$currentStreak = 0;
$maxStreak = 0;
$mistakesDist = array_fill(0, ($mode === 1 ? 6 : ($mode === 2 ? 8 : 11)) + 1, 0);

$lastDate = null;

foreach ($games as $game) {
    if ($game['is_win']) {
        $wins++;
        
        // Calculate Streak
        if ($lastDate) {
            $diff = (strtotime($game['date']) - strtotime($lastDate)) / (60 * 60 * 24);
            if ($diff == 1) {
                $currentStreak++;
            } else {
                $currentStreak = 1;
            }
        } else {
            $currentStreak = 1;
        }
        
        if ($currentStreak > $maxStreak) {
            $maxStreak = $currentStreak;
        }
        
        // Distribution of mistakes for wins
        $mistakesDist[$game['mistakes']]++;
    } else {
        $currentStreak = 0;
    }
    
    $lastDate = $game['date'];
}

// Reset streak if missed a day
if ($lastDate) {
    $diff = (strtotime(date('Y-m-d')) - strtotime($lastDate)) / (60 * 60 * 24);
    if ($diff > 1) {
        $currentStreak = 0;
    }
}

$winRate = $totalGames > 0 ? round(($wins / $totalGames) * 100) : 0;

// 2. Regional Stats (Global vs Country)
// Country Win Rate
$stmt = $pdo->prepare("SELECT COUNT(*) as total, SUM(is_win) as wins FROM games g JOIN players p ON g.player_id = p.id WHERE p.country_code = ? AND g.mode = ?");
$stmt->execute([$countryCode, $mode]);
$countryStats = $stmt->fetch();
$countryWinRate = $countryStats['total'] > 0 ? round(($countryStats['wins'] / $countryStats['total']) * 100) : 0;

// Global Win Rate
$stmt = $pdo->prepare("SELECT COUNT(*) as total, SUM(is_win) as wins FROM games WHERE mode = ?");
$stmt->execute([$mode]);
$globalStats = $stmt->fetch();
$globalWinRate = $globalStats['total'] > 0 ? round(($globalStats['wins'] / $globalStats['total']) * 100) : 0;

echo json_encode([
    'personal' => [
        'total' => $totalGames,
        'win_rate' => $winRate,
        'current_streak' => $currentStreak,
        'max_streak' => $maxStreak,
        'distribution' => $mistakesDist
    ],
    'regional' => [
        'country_code' => $countryCode,
        'country_win_rate' => $countryWinRate,
        'global_win_rate' => $globalWinRate
    ]
]);
?>

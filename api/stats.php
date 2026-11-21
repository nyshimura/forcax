<?php
header('Content-Type: application/json');
require_once 'db.php';
require_once __DIR__ . '/multiplayer_stats_helper.php';

$uuid = isset($_SERVER['HTTP_X_UUID']) ? $_SERVER['HTTP_X_UUID'] : '';
$modeRaw = isset($_GET['mode']) ? $_GET['mode'] : '1';

if ($modeRaw === 'multiplayer') {
    ensureMultiplayerGamesTable($pdo);

    $personal = [
        'total' => 0,
        'wins' => 0,
        'draws' => 0,
        'win_rate' => 0,
        'best_score' => 0,
        'total_score' => 0
    ];

    if (!empty($uuid)) {
        $stmtMp = $pdo->prepare("SELECT COUNT(*) as total, COALESCE(SUM(is_win), 0) as wins, COALESCE(SUM(is_draw), 0) as draws, COALESCE(MAX(score), 0) as best_score, COALESCE(SUM(score), 0) as total_score FROM multiplayer_games WHERE player_uuid = ?");
        $stmtMp->execute([$uuid]);
        $rowMp = $stmtMp->fetch(PDO::FETCH_ASSOC);
        if ($rowMp) {
            $t = (int)$rowMp['total'];
            $w = (int)$rowMp['wins'];
            $personal = [
                'total' => $t,
                'wins' => $w,
                'draws' => (int)$rowMp['draws'],
                'win_rate' => $t > 0 ? round(($w / $t) * 100) : 0,
                'best_score' => (int)$rowMp['best_score'],
                'total_score' => (int)$rowMp['total_score']
            ];
        }
    }

    $globalMatches = (int)$pdo->query("SELECT COUNT(DISTINCT match_id) FROM multiplayer_games")->fetchColumn();

    $stmtRank = $pdo->query("
        SELECT
            player_uuid,
            SUBSTRING_INDEX(GROUP_CONCAT(player_name ORDER BY id DESC SEPARATOR '|||'), '|||', 1) as player_name,
            COUNT(*) as matches,
            COALESCE(SUM(is_win), 0) as wins,
            COALESCE(SUM(is_draw), 0) as draws,
            COALESCE(MAX(score), 0) as best_score
        FROM multiplayer_games
        GROUP BY player_uuid
        ORDER BY wins DESC, best_score DESC, matches ASC
        LIMIT 10
    ");
    $ranking = [];
    if ($stmtRank) {
        while ($r = $stmtRank->fetch(PDO::FETCH_ASSOC)) {
            $ranking[] = [
                'player_name' => $r['player_name'],
                'matches' => (int)$r['matches'],
                'wins' => (int)$r['wins'],
                'draws' => (int)$r['draws'],
                'best_score' => (int)$r['best_score'],
                'is_me' => (!empty($uuid) && $r['player_uuid'] === $uuid)
            ];
        }
    }

    echo json_encode([
        'mode' => 'multiplayer',
        'personal' => $personal,
        'global_matches' => $globalMatches,
        'ranking' => $ranking
    ]);
    exit;
}

$mode = (int)$modeRaw;

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

// All modes total games
$allModesTotal = (int)$pdo->query("SELECT COUNT(*) FROM games")->fetchColumn();

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
        'country_total_games' => (int)$countryStats['total'],
        'global_win_rate' => $globalWinRate,
        'global_total_games' => (int)$globalStats['total'],
        'all_modes_total_games' => $allModesTotal
    ]
]);
?>

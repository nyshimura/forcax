<?php
// api/multiplayer_stats_helper.php

function ensureMultiplayerGamesTable($pdoOrConn) {
    $sql = "CREATE TABLE IF NOT EXISTS `multiplayer_games` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `match_id` varchar(64) NOT NULL,
        `room_code` varchar(10) NOT NULL,
        `player_uuid` varchar(64) NOT NULL,
        `player_name` varchar(50) NOT NULL,
        `is_win` tinyint(1) NOT NULL DEFAULT 0,
        `is_draw` tinyint(1) NOT NULL DEFAULT 0,
        `score` int(11) NOT NULL DEFAULT 0,
        `mistakes` int(11) NOT NULL DEFAULT 0,
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        PRIMARY KEY (`id`),
        UNIQUE KEY `uniq_match_player` (`match_id`, `player_uuid`),
        KEY `idx_player_uuid` (`player_uuid`),
        KEY `idx_score` (`score`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

    if ($pdoOrConn instanceof PDO) {
        $pdoOrConn->exec($sql);
    } else if ($pdoOrConn instanceof mysqli) {
        $pdoOrConn->query($sql);
    }
}

function recordMultiplayerMatchResults(mysqli $conn, string $roomCode, string $matchId, array $players): void {
    if (empty($matchId) || empty($players)) return;

    ensureMultiplayerGamesTable($conn);

    // Determine winner(s) / tie among players (already ordered by score DESC, finished DESC)
    $first = $players[0];
    $topScore = (int)($first['score'] ?? 0);
    $topFinished = (int)($first['finished'] ?? 0);
    $hasWinner = ($topScore > 0 || $topFinished === 1);

    $firstCount = 0;
    if ($hasWinner) {
        foreach ($players as $p) {
            if ((int)($p['score'] ?? 0) === $topScore && (int)($p['finished'] ?? 0) === $topFinished) {
                $firstCount++;
            }
        }
    }
    $isTie = ($firstCount > 1);

    $stmt = $conn->prepare("INSERT IGNORE INTO multiplayer_games (match_id, room_code, player_uuid, player_name, is_win, is_draw, score, mistakes) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    if (!$stmt) return;

    foreach ($players as $p) {
        $pUuid = (string)($p['uuid'] ?? '');
        $pName = substr((string)($p['player_name'] ?? 'Jogador'), 0, 50);
        $pScore = (int)($p['score'] ?? 0);
        $pFinished = (int)($p['finished'] ?? 0);
        $pMistakes = (int)($p['mistakes'] ?? 0);

        if (empty($pUuid)) continue;

        $isFirst = $hasWinner && ($pScore === $topScore && $pFinished === $topFinished);
        $isWin = $isFirst ? 1 : 0;
        $isDraw = ($isFirst && $isTie) ? 1 : 0;

        $stmt->bind_param("ssssiiii", $matchId, $roomCode, $pUuid, $pName, $isWin, $isDraw, $pScore, $pMistakes);
        $stmt->execute();
    }
}

<?php
require_once __DIR__ . '/db.php';

// 1. Delete players disconnected for > 90s in waiting/finished rooms
$conn->query("
    DELETE rp FROM room_players rp
    JOIN rooms r ON rp.room_id = r.id
    WHERE r.status IN ('waiting', 'finished')
      AND rp.last_seen < NOW() - INTERVAL 90 SECOND
");

// 2. Delete rooms where NO player has been seen in the last 2 minutes (abandoned rooms)
//    OR rooms with 0 players created more than 1 minute ago
//    OR rooms older than 12 hours
$conn->query("
    DELETE r FROM rooms r
    WHERE r.created_at < NOW() - INTERVAL 12 HOUR
       OR (
           r.created_at < NOW() - INTERVAL 60 SECOND
           AND NOT EXISTS (
               SELECT 1 FROM room_players rp
               WHERE rp.room_id = r.id
                 AND rp.last_seen >= NOW() - INTERVAL 2 MINUTE
           )
       )
");

// 3. Clean up orphaned room_players and room_requests
$conn->query("DELETE FROM room_players WHERE room_id NOT IN (SELECT id FROM rooms)");
$conn->query("DELETE FROM room_requests WHERE room_id NOT IN (SELECT id FROM rooms)");
?>

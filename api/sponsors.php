<?php
header('Content-Type: application/json');

require_once __DIR__ . '/db.php';
if ($conn->connect_error) {
    echo json_encode([]);
    exit;
}

$today = date('Y-m-d');
$sql = "SELECT image_url, target_link FROM sponsors WHERE is_active = 1 AND start_date <= ? AND end_date >= ? ORDER BY RAND() LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $today, $today);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    echo json_encode($row);
} else {
    echo json_encode(null);
}

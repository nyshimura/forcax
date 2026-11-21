<?php
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';

$id = $_GET['id'] ?? 0;

if (!$id) {
    // Falha silenciosa para polling inválido
    echo json_encode(['success' => false]);
    exit;
}

$stmt = $conn->prepare("SELECT status FROM room_requests WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows > 0) {
    $row = $res->fetch_assoc();
    echo json_encode(['success' => true, 'status' => $row['status']]);
} else {
    echo json_encode(['success' => false]);
}
?>

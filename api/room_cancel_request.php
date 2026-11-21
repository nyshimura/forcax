<?php
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';

$data = json_decode(file_get_contents("php://input"));
$request_id = $data->request_id ?? 0;

if (!$request_id) {
    echo json_encode(['success' => false, 'error' => 'Missing ID']);
    exit;
}

$stmt = $conn->prepare("DELETE FROM room_requests WHERE id = ?");
$stmt->bind_param("i", $request_id);
$stmt->execute();

echo json_encode(['success' => true]);
?>

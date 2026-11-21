<?php
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';
if ($conn->connect_error) {
    echo json_encode(['error' => 'Database error']);
    exit;
}

$settings = [
    'ad_provider' => 'adsterra',
    'adsterra_code' => '',
    'adsterra_social_bar' => '',
    'adsense_code' => '',
    'donate_link' => ''
];
$res = $conn->query("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('ad_provider', 'adsterra_code', 'adsterra_social_bar', 'adsense_code', 'donate_link')");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
}
echo json_encode($settings);
?>

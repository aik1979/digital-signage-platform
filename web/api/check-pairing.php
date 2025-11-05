<?php
/**
 * Check Pairing Status API
 * Used by the pairing page to detect when device has been paired
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Database.php';

$db = Database::getInstance();

$deviceId = $_GET['device_id'] ?? '';

if (empty($deviceId)) {
    http_response_code(400);
    die(json_encode(['error' => 'Device ID required']));
}

// Check if device has been paired
$pairing = $db->fetchOne(
    "SELECT dp.*, s.device_key 
     FROM device_pairing dp
     LEFT JOIN screens s ON dp.screen_id = s.id
     WHERE dp.device_id = ?
     ORDER BY dp.created_at DESC
     LIMIT 1",
    [$deviceId]
);

if (!$pairing) {
    echo json_encode([
        'paired' => false,
        'expired' => false
    ]);
    exit;
}

// Check if paired
if ($pairing['status'] === 'paired' && $pairing['device_key']) {
    $viewerUrl = rtrim(APP_URL, '/') . '/viewer-v2.php?key=' . $pairing['device_key'];
    
    echo json_encode([
        'paired' => true,
        'expired' => false,
        'viewer_url' => $viewerUrl,
        'screen_id' => $pairing['screen_id']
    ]);
    exit;
}

// Check if expired
if (strtotime($pairing['expires_at']) < time()) {
    echo json_encode([
        'paired' => false,
        'expired' => true
    ]);
    exit;
}

// Still pending
echo json_encode([
    'paired' => false,
    'expired' => false,
    'expires_in' => strtotime($pairing['expires_at']) - time()
]);

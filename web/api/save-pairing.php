<?php
/**
 * Save Pairing API
 * Called by the pairing page after successful pairing to save the viewer URL
 * This allows the device to redirect to the viewer automatically
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';

// Get parameters
$pairingCode = $_GET['code'] ?? '';
$viewerUrl = $_GET['url'] ?? '';

if (empty($pairingCode)) {
    http_response_code(400);
    die(json_encode(['success' => false, 'error' => 'Pairing code required']));
}

// Get pairing info
$pairing = $db->fetchOne(
    "SELECT * FROM device_pairing WHERE pairing_code = ? AND status = 'paired'",
    [$pairingCode]
);

if (!$pairing) {
    http_response_code(404);
    die(json_encode(['success' => false, 'error' => 'Pairing not found or not completed']));
}

// Get screen info
$screen = $db->fetchOne(
    "SELECT device_key FROM screens WHERE id = ?",
    [$pairing['screen_id']]
);

if (!$screen) {
    http_response_code(404);
    die(json_encode(['success' => false, 'error' => 'Screen not found']));
}

// Build viewer URL if not provided
if (empty($viewerUrl)) {
    $viewerUrl = rtrim(APP_URL, '/') . '/viewer-v2.php?key=' . $screen['device_key'];
}

// Return success with viewer URL
echo json_encode([
    'success' => true,
    'viewer_url' => $viewerUrl,
    'device_key' => $screen['device_key']
]);

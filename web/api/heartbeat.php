<?php
/**
 * Heartbeat API - Update screen online status
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Database.php';

$db = Database::getInstance();

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
$deviceKey = $input['device_key'] ?? '';

if (empty($deviceKey)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Device key required']);
    exit;
}

// Update heartbeat
$result = $db->update('screens', [
    'last_heartbeat' => date('Y-m-d H:i:s'),
    'is_online' => 1,
    'last_ip' => $_SERVER['REMOTE_ADDR'] ?? null
], 'device_key = :key', ['key' => $deviceKey]);

if ($result) {
    echo json_encode(['success' => true, 'message' => 'Heartbeat received']);
} else {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Screen not found']);
}

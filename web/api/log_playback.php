<?php
/**
 * Playback Log API - Track content playback for analytics
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Database.php';

$db = Database::getInstance();

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
$deviceKey = $input['device_key'] ?? '';
$contentId = intval($input['content_id'] ?? 0);

if (empty($deviceKey) || !$contentId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Device key and content ID required']);
    exit;
}

// Get screen ID from device key
$screen = $db->fetchOne("SELECT id FROM screens WHERE device_key = ?", [$deviceKey]);

if (!$screen) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Screen not found']);
    exit;
}

// Log playback
try {
    $db->insert('playback_log', [
        'screen_id' => $screen['id'],
        'content_id' => $contentId,
        'played_at' => date('Y-m-d H:i:s')
    ]);
    
    echo json_encode(['success' => true, 'message' => 'Playback logged']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to log playback']);
}

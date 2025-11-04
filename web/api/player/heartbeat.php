<?php
/**
 * Player API - Heartbeat
 * 
 * Receives status updates from players and stores heartbeat data.
 * 
 * Endpoint: POST /api/player/heartbeat.php
 */

require_once __DIR__ . '/auth.php';

// Set CORS headers for API access
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendErrorResponse('Method not allowed', 'METHOD_NOT_ALLOWED', 405);
}

// Get JSON request body
$data = getJsonRequestBody();
if (!$data) {
    sendErrorResponse('Invalid JSON in request body', 'INVALID_JSON', 400);
}

// Extract device key
$device_key = $data['device_key'] ?? '';

// Log API request
logApiRequest('heartbeat', $device_key, $data);

// Check rate limit (120 requests per minute for heartbeat)
if (!checkRateLimit($device_key . '_heartbeat', 120)) {
    sendErrorResponse('Rate limit exceeded', 'RATE_LIMIT_EXCEEDED', 429);
}

// Validate device key
$screen = validateDeviceKey($device_key);
if (!$screen) {
    sendErrorResponse('Invalid device key', 'INVALID_DEVICE_KEY', 401);
}

// Extract heartbeat data
$status = $data['status'] ?? 'unknown';
$current_item_id = $data['current_item_id'] ?? null;
$uptime = $data['uptime'] ?? null;
$ip_address = $data['ip_address'] ?? $_SERVER['REMOTE_ADDR'];
$player_version = $data['player_version'] ?? null;

// Extract system info
$system_info = $data['system_info'] ?? [];
$cpu_temp = $system_info['cpu_temp'] ?? null;
$memory_usage = $system_info['memory_usage'] ?? null;
$disk_usage = $system_info['disk_usage'] ?? null;

// Update screen status
updateScreenStatus($screen['id'], $status, $ip_address, $player_version);

// Store heartbeat data in database
$stmt = $conn->prepare("
    INSERT INTO player_heartbeats (
        screen_id,
        status,
        current_item_id,
        uptime,
        ip_address,
        player_version,
        cpu_temp,
        memory_usage,
        disk_usage,
        created_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
");

$stmt->bind_param(
    "isiissdii",
    $screen['id'],
    $status,
    $current_item_id,
    $uptime,
    $ip_address,
    $player_version,
    $cpu_temp,
    $memory_usage,
    $disk_usage
);

$success = $stmt->execute();
$stmt->close();

if (!$success) {
    sendErrorResponse('Failed to store heartbeat data', 'DATABASE_ERROR', 500);
}

// Build response
$response = [
    'success' => true,
    'message' => 'Heartbeat received',
    'timestamp' => date('Y-m-d H:i:s'),
    'screen_id' => (int)$screen['id'],
    'commands' => [] // Future: return pending commands for the player
];

// Send successful response
sendJsonResponse($response, 200);

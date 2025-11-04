<?php
/**
 * Player API - Get Configuration
 * 
 * Returns configuration settings for a specific player.
 * 
 * Endpoint: GET /api/player/config.php?device_key=xxx
 */

require_once __DIR__ . '/auth.php';

// Set CORS headers for API access
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendErrorResponse('Method not allowed', 'METHOD_NOT_ALLOWED', 405);
}

// Get device key from query parameter
$device_key = $_GET['device_key'] ?? '';

// Log API request
logApiRequest('config', $device_key);

// Check rate limit
if (!checkRateLimit($device_key . '_config', 60)) {
    sendErrorResponse('Rate limit exceeded', 'RATE_LIMIT_EXCEEDED', 429);
}

// Validate device key
$screen = validateDeviceKey($device_key);
if (!$screen) {
    sendErrorResponse('Invalid device key', 'INVALID_DEVICE_KEY', 401);
}

// Update last seen timestamp
updateLastSeen($screen['id']);

// Build configuration response
// These can be customized per screen in the future
$config = [
    'refresh_interval' => 300, // Check for playlist updates every 5 minutes (seconds)
    'heartbeat_interval' => 60, // Send heartbeat every 60 seconds
    'cache_size_mb' => 1024, // Maximum cache size in MB
    'log_level' => 'info', // Logging level: debug, info, warning, error
    'retry_attempts' => 3, // Number of retry attempts for failed requests
    'retry_delay' => 5, // Delay between retries in seconds
    'display_settings' => [
        'rotation' => 0, // Screen rotation: 0, 90, 180, 270
        'brightness' => 100, // Screen brightness: 0-100
        'transition_effect' => 'fade', // Transition effect: fade, slide, none
        'transition_duration' => 1000 // Transition duration in milliseconds
    ],
    'network_settings' => [
        'timeout' => 30, // Network request timeout in seconds
        'max_retries' => 3 // Maximum network retry attempts
    ],
    'content_settings' => [
        'preload_next' => true, // Preload next content item
        'cache_enabled' => true, // Enable local content caching
        'video_autoplay' => true, // Autoplay videos
        'video_loop' => false // Loop individual videos
    ]
];

// Build response
$response = [
    'success' => true,
    'timestamp' => date('Y-m-d H:i:s'),
    'screen' => [
        'id' => (int)$screen['id'],
        'name' => $screen['name']
    ],
    'config' => $config
];

// Send successful response
sendJsonResponse($response, 200);

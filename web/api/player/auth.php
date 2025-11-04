<?php
/**
 * Player API Authentication Helper
 * 
 * Provides authentication and validation functions for player API endpoints.
 */

// Include database configuration
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/db.php';

/**
 * Validate device key and return screen information
 * 
 * @param string $device_key The device key to validate
 * @return array|false Screen data if valid, false otherwise
 */
function validateDeviceKey($device_key) {
    global $conn;
    
    if (empty($device_key)) {
        return false;
    }
    
    // Prepare statement to prevent SQL injection
    $stmt = $conn->prepare("SELECT * FROM screens WHERE device_key = ?");
    $stmt->bind_param("s", $device_key);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return false;
    }
    
    $screen = $result->fetch_assoc();
    $stmt->close();
    
    return $screen;
}

/**
 * Update last seen timestamp for a screen
 * 
 * @param int $screen_id The screen ID
 * @return bool Success status
 */
function updateLastSeen($screen_id) {
    global $conn;
    
    $stmt = $conn->prepare("UPDATE screens SET last_seen = NOW() WHERE id = ?");
    $stmt->bind_param("i", $screen_id);
    $success = $stmt->execute();
    $stmt->close();
    
    return $success;
}

/**
 * Update screen status and metadata
 * 
 * @param int $screen_id The screen ID
 * @param string $status Status (online/offline/playing)
 * @param string $ip_address IP address
 * @param string $player_version Player version
 * @return bool Success status
 */
function updateScreenStatus($screen_id, $status, $ip_address = null, $player_version = null) {
    global $conn;
    
    $stmt = $conn->prepare("
        UPDATE screens 
        SET status = ?, 
            ip_address = COALESCE(?, ip_address),
            player_version = COALESCE(?, player_version),
            last_seen = NOW()
        WHERE id = ?
    ");
    $stmt->bind_param("sssi", $status, $ip_address, $player_version, $screen_id);
    $success = $stmt->execute();
    $stmt->close();
    
    return $success;
}

/**
 * Send JSON response
 * 
 * @param array $data Response data
 * @param int $status_code HTTP status code
 */
function sendJsonResponse($data, $status_code = 200) {
    http_response_code($status_code);
    header('Content-Type: application/json');
    echo json_encode($data, JSON_PRETTY_PRINT);
    exit;
}

/**
 * Send error response
 * 
 * @param string $error Error message
 * @param string $error_code Error code
 * @param int $status_code HTTP status code
 */
function sendErrorResponse($error, $error_code = 'ERROR', $status_code = 400) {
    sendJsonResponse([
        'success' => false,
        'error' => $error,
        'error_code' => $error_code
    ], $status_code);
}

/**
 * Get request body as JSON
 * 
 * @return array|false Decoded JSON data or false on error
 */
function getJsonRequestBody() {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        return false;
    }
    
    return $data;
}

/**
 * Log API request for debugging and analytics
 * 
 * @param string $endpoint Endpoint name
 * @param string $device_key Device key
 * @param array $data Additional data to log
 */
function logApiRequest($endpoint, $device_key, $data = []) {
    $log_entry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'endpoint' => $endpoint,
        'device_key' => $device_key,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        'data' => $data
    ];
    
    // Log to file (create logs directory if it doesn't exist)
    $log_dir = __DIR__ . '/../../../logs';
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    
    $log_file = $log_dir . '/player-api-' . date('Y-m-d') . '.log';
    file_put_contents($log_file, json_encode($log_entry) . "\n", FILE_APPEND);
}

/**
 * Check rate limit for device
 * 
 * @param string $device_key Device key
 * @param int $max_requests Maximum requests per minute
 * @return bool True if within limit, false if exceeded
 */
function checkRateLimit($device_key, $max_requests = 60) {
    $cache_file = sys_get_temp_dir() . '/dsp_ratelimit_' . md5($device_key);
    
    $now = time();
    $window = 60; // 1 minute window
    
    // Read existing requests
    $requests = [];
    if (file_exists($cache_file)) {
        $requests = json_decode(file_get_contents($cache_file), true) ?: [];
    }
    
    // Remove requests outside the time window
    $requests = array_filter($requests, function($timestamp) use ($now, $window) {
        return ($now - $timestamp) < $window;
    });
    
    // Check if limit exceeded
    if (count($requests) >= $max_requests) {
        return false;
    }
    
    // Add current request
    $requests[] = $now;
    file_put_contents($cache_file, json_encode($requests));
    
    return true;
}

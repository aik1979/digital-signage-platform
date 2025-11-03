<?php
/**
 * Helper Functions
 */

/**
 * Sanitize input string
 */
function sanitize($input) {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

/**
 * Generate CSRF token
 */
function generateCsrfToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verifyCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Display flash message
 */
function setFlashMessage($type, $message) {
    $_SESSION['flash_message'] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Get and clear flash message
 */
function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $flash = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $flash;
    }
    return null;
}

/**
 * Format file size
 */
function formatFileSize($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}

/**
 * Generate unique device key
 */
function generateDeviceKey() {
    return 'DSP-' . strtoupper(bin2hex(random_bytes(16)));
}

/**
 * Format time ago
 */
function timeAgo($datetime) {
    if (empty($datetime)) {
        return 'Never';
    }
    
    $timestamp = strtotime($datetime);
    $diff = time() - $timestamp;
    
    if ($diff < 60) {
        return 'Just now';
    } elseif ($diff < 3600) {
        $mins = floor($diff / 60);
        return $mins . ' minute' . ($mins > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    } else {
        return date('M j, Y', $timestamp);
    }
}

/**
 * Check if screen is online (heartbeat within last 2 minutes)
 */
function isScreenOnline($lastHeartbeat) {
    if (empty($lastHeartbeat)) {
        return false;
    }
    
    $timestamp = strtotime($lastHeartbeat);
    $diff = time() - $timestamp;
    
    return $diff < 120; // 2 minutes
}

/**
 * Get file extension
 */
function getFileExtension($filename) {
    return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
}

/**
 * Validate image file
 */
function isValidImage($file) {
    $allowedTypes = ALLOWED_IMAGE_TYPES;
    $maxSize = MAX_IMAGE_SIZE;
    
    if (!in_array($file['type'], $allowedTypes)) {
        return ['valid' => false, 'error' => 'Invalid image type. Only JPG and PNG are allowed.'];
    }
    
    if ($file['size'] > $maxSize) {
        return ['valid' => false, 'error' => 'Image size exceeds ' . formatFileSize($maxSize)];
    }
    
    return ['valid' => true];
}

/**
 * Validate video file
 */
function isValidVideo($file) {
    $allowedTypes = ALLOWED_VIDEO_TYPES;
    $maxSize = MAX_VIDEO_SIZE;
    
    if (!in_array($file['type'], $allowedTypes)) {
        return ['valid' => false, 'error' => 'Invalid video type. Only MP4 and MOV are allowed.'];
    }
    
    if ($file['size'] > $maxSize) {
        return ['valid' => false, 'error' => 'Video size exceeds ' . formatFileSize($maxSize)];
    }
    
    return ['valid' => true];
}

/**
 * Log activity
 */
function logActivity($db, $userId, $action, $entityType = null, $entityId = null, $description = null) {
    try {
        $db->insert('activity_log', [
            'user_id' => $userId,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'description' => $description,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
    } catch (Exception $e) {
        // Silent fail for logging
    }
}

/**
 * Redirect helper
 */
function redirect($page, $params = []) {
    $url = '?page=' . $page;
    if (!empty($params)) {
        $url .= '&' . http_build_query($params);
    }
    header('Location: ' . $url);
    exit;
}

/**
 * JSON response helper
 */
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

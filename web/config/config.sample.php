<?php
/**
 * Digital Signage Platform - Configuration File
 * 
 * Copy this file to config.php and update with your settings
 */

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'signage_db');
define('DB_USER', 'signage_user');
define('DB_PASS', 'your_secure_password_here');
define('DB_CHARSET', 'utf8mb4');

// Application Settings
define('APP_NAME', 'Digital Signage Platform');
define('APP_URL', 'https://yourdomain.com');
define('APP_ENV', 'production'); // development or production

// Security Settings
define('SESSION_LIFETIME', 7200); // 2 hours in seconds
define('PASSWORD_MIN_LENGTH', 8);
define('API_RATE_LIMIT', 100); // requests per minute per device

// File Upload Settings
define('MAX_IMAGE_SIZE', 10485760); // 10MB in bytes
define('MAX_VIDEO_SIZE', 52428800); // 50MB in bytes
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/jpg']);
define('ALLOWED_VIDEO_TYPES', ['video/mp4', 'video/quicktime']);

// Paths
define('UPLOAD_PATH', __DIR__ . '/../uploads/content/');
define('THUMBNAIL_PATH', __DIR__ . '/../uploads/thumbnails/');
define('LOG_PATH', __DIR__ . '/../logs/');

// Timezone
date_default_timezone_set('Europe/London');

// Error Reporting
if (APP_ENV === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', LOG_PATH . 'php-errors.log');
}

// Session Configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 1); // Requires HTTPS
ini_set('session.gc_maxlifetime', SESSION_LIFETIME);
ini_set('session.save_path', __DIR__ . '/../sessions');

// CORS Settings (for API)
define('CORS_ALLOWED_ORIGINS', '*'); // Restrict in production
define('CORS_ALLOWED_METHODS', 'GET, POST, PUT, DELETE, OPTIONS');
define('CORS_ALLOWED_HEADERS', 'Content-Type, Authorization, X-Device-Key');

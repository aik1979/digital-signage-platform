<?php
/**
 * Clean URL handler for public viewer
 * Redirects /view/TOKEN to public_viewer.php?token=TOKEN
 */

// Get token from URL path
$requestUri = $_SERVER['REQUEST_URI'] ?? '';
$path = parse_url($requestUri, PHP_URL_PATH);

// Extract token from path like /view/pl_abc123
if (preg_match('#/view/([a-zA-Z0-9_-]+)#', $path, $matches)) {
    $token = $matches[1];
    header('Location: public_viewer.php?token=' . urlencode($token));
    exit;
}

// If no token found, show error
http_response_code(404);
die('Error: Invalid URL format. Use: /view/TOKEN');

<?php
/**
 * URL Shortener Redirect Handler
 * Access via: /s/SHORT_CODE or s.php?c=SHORT_CODE
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/Database.php';

$db = Database::getInstance();

// Get short code from URL
$shortCode = isset($_GET['c']) ? trim($_GET['c']) : '';

if (empty($shortCode)) {
    http_response_code(404);
    die('Error: Invalid short URL');
}

// Look up short URL
$shortUrl = $db->fetchOne(
    "SELECT * FROM short_urls WHERE short_code = ? AND is_active = 1",
    [$shortCode]
);

if (!$shortUrl) {
    http_response_code(404);
    die('Error: Short URL not found or expired');
}

// Check expiration
if ($shortUrl['expires_at'] && strtotime($shortUrl['expires_at']) < time()) {
    http_response_code(410);
    die('Error: This short URL has expired');
}

// Increment click counter
$db->query(
    "UPDATE short_urls SET clicks = clicks + 1 WHERE id = ?",
    [$shortUrl['id']]
);

// Redirect to original URL
header('Location: ' . $shortUrl['original_url'], true, 302);
exit;

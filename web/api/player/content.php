<?php
/**
 * Player API - Get Content File
 * 
 * Serves media files to authenticated players.
 * 
 * Endpoint: GET /api/player/content.php?device_key=xxx&content_id=123
 */

require_once __DIR__ . '/auth.php';

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendErrorResponse('Method not allowed', 'METHOD_NOT_ALLOWED', 405);
}

// Get parameters
$device_key = $_GET['device_key'] ?? '';
$content_id = $_GET['content_id'] ?? '';

// Log API request
logApiRequest('content', $device_key, ['content_id' => $content_id]);

// Check rate limit (300 requests per minute for content downloads)
if (!checkRateLimit($device_key . '_content', 300)) {
    sendErrorResponse('Rate limit exceeded', 'RATE_LIMIT_EXCEEDED', 429);
}

// Validate device key
$screen = validateDeviceKey($device_key);
if (!$screen) {
    sendErrorResponse('Invalid device key', 'INVALID_DEVICE_KEY', 401);
}

// Validate content ID
if (empty($content_id) || !is_numeric($content_id)) {
    sendErrorResponse('Invalid content ID', 'INVALID_CONTENT_ID', 400);
}

// Get content details
$stmt = $conn->prepare("SELECT * FROM content WHERE id = ?");
$stmt->bind_param("i", $content_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();
    sendErrorResponse('Content not found', 'CONTENT_NOT_FOUND', 404);
}

$content = $result->fetch_assoc();
$stmt->close();

// Verify the screen has access to this content
// Check if content is in any playlist assigned to this screen
$stmt = $conn->prepare("
    SELECT COUNT(*) as has_access
    FROM playlist_items pi
    INNER JOIN screen_playlists sp ON pi.playlist_id = sp.playlist_id
    WHERE sp.screen_id = ? AND pi.content_id = ?
");
$stmt->bind_param("ii", $screen['id'], $content_id);
$stmt->execute();
$result = $stmt->get_result();
$access_check = $result->fetch_assoc();
$stmt->close();

if ($access_check['has_access'] == 0) {
    sendErrorResponse('Access denied to this content', 'PERMISSION_DENIED', 403);
}

// Build file path
$file_path = __DIR__ . '/../../' . $content['file_path'];

// Check if file exists
if (!file_exists($file_path)) {
    sendErrorResponse('Content file not found on server', 'FILE_NOT_FOUND', 404);
}

// Update last seen
updateLastSeen($screen['id']);

// Determine content type
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime_type = finfo_file($finfo, $file_path);
finfo_close($finfo);

// Set headers for file download
header('Content-Type: ' . $mime_type);
header('Content-Length: ' . filesize($file_path));
header('Content-Disposition: inline; filename="' . basename($file_path) . '"');
header('Cache-Control: public, max-age=86400'); // Cache for 24 hours
header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 86400) . ' GMT');

// Serve the file
readfile($file_path);
exit;

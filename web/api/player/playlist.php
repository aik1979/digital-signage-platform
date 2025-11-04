<?php
/**
 * Player API - Get Playlist
 * 
 * Returns playlist data for a specific device key.
 * 
 * Endpoint: GET /api/player/playlist.php?device_key=xxx
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
logApiRequest('playlist', $device_key);

// Check rate limit (60 requests per minute)
if (!checkRateLimit($device_key, 60)) {
    sendErrorResponse('Rate limit exceeded', 'RATE_LIMIT_EXCEEDED', 429);
}

// Validate device key
$screen = validateDeviceKey($device_key);
if (!$screen) {
    sendErrorResponse('Invalid device key', 'INVALID_DEVICE_KEY', 401);
}

// Update last seen timestamp
updateLastSeen($screen['id']);

// Get assigned playlist for this screen
$stmt = $conn->prepare("
    SELECT p.* 
    FROM playlists p
    INNER JOIN screen_playlists sp ON p.id = sp.playlist_id
    WHERE sp.screen_id = ?
    LIMIT 1
");
$stmt->bind_param("i", $screen['id']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();
    sendErrorResponse('No playlist assigned to this screen', 'NO_PLAYLIST_ASSIGNED', 404);
}

$playlist = $result->fetch_assoc();
$stmt->close();

// Get playlist items with content details
$stmt = $conn->prepare("
    SELECT 
        pi.id as playlist_item_id,
        pi.order_index,
        pi.duration,
        c.id as content_id,
        c.name as content_name,
        c.type as content_type,
        c.file_path,
        c.file_size,
        c.thumbnail_path
    FROM playlist_items pi
    INNER JOIN content c ON pi.content_id = c.id
    WHERE pi.playlist_id = ?
    ORDER BY pi.order_index ASC
");
$stmt->bind_param("i", $playlist['id']);
$stmt->execute();
$items_result = $stmt->get_result();

$items = [];
while ($item = $items_result->fetch_assoc()) {
    // Build full URL for content
    $base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") 
                . "://" . $_SERVER['HTTP_HOST'];
    
    $content_url = $base_url . '/' . $item['file_path'];
    $thumbnail_url = $item['thumbnail_path'] ? $base_url . '/' . $item['thumbnail_path'] : null;
    
    // Determine content type (image or video)
    $type = 'image';
    $extension = strtolower(pathinfo($item['file_path'], PATHINFO_EXTENSION));
    if (in_array($extension, ['mp4', 'webm', 'ogg', 'mov', 'avi'])) {
        $type = 'video';
    }
    
    $items[] = [
        'id' => (int)$item['playlist_item_id'],
        'content_id' => (int)$item['content_id'],
        'name' => $item['content_name'],
        'type' => $type,
        'url' => $content_url,
        'thumbnail_url' => $thumbnail_url,
        'duration' => (int)$item['duration'],
        'order' => (int)$item['order_index'],
        'file_size' => (int)$item['file_size']
    ];
}

$stmt->close();

// Build response
$response = [
    'success' => true,
    'timestamp' => date('Y-m-d H:i:s'),
    'screen' => [
        'id' => (int)$screen['id'],
        'name' => $screen['name'],
        'device_key' => $screen['device_key'],
        'last_seen' => $screen['last_seen']
    ],
    'playlist' => [
        'id' => (int)$playlist['id'],
        'name' => $playlist['name'],
        'description' => $playlist['description'],
        'item_count' => count($items),
        'items' => $items
    ],
    'config' => [
        'refresh_interval' => 300, // Check for updates every 5 minutes
        'transition_duration' => 1000 // 1 second transition
    ]
];

// Send successful response
sendJsonResponse($response, 200);

<?php
/**
 * Check for Playlist Updates
 * Returns version info to determine if viewer needs to reload content
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Database.php';

$db = Database::getInstance();

// Get parameters
$deviceKey = $_GET['device_key'] ?? '';
$currentVersion = $_GET['version'] ?? '';

if (empty($deviceKey)) {
    http_response_code(400);
    die(json_encode(['error' => 'Device key required']));
}

// Get screen info
$screen = $db->fetchOne(
    "SELECT s.*, u.id as user_id 
     FROM screens s 
     JOIN users u ON s.user_id = u.id 
     WHERE s.device_key = ? AND s.is_active = 1",
    [$deviceKey]
);

if (!$screen) {
    http_response_code(404);
    die(json_encode(['error' => 'Screen not found']));
}

// Update heartbeat
$db->update('screens', [
    'last_heartbeat' => date('Y-m-d H:i:s'),
    'is_online' => 1
], 'id = :id', ['id' => $screen['id']]);

// Determine active playlist
$currentDay = date('w');
$currentTime = date('H:i:s');
$currentDate = date('Y-m-d');

$activeSchedule = $db->fetchOne(
    "SELECT playlist_id 
     FROM schedules 
     WHERE screen_id = ? 
     AND is_active = 1
     AND FIND_IN_SET(?, days_of_week) > 0
     AND start_time <= ?
     AND end_time >= ?
     AND (start_date IS NULL OR start_date <= ?)
     AND (end_date IS NULL OR end_date >= ?)
     ORDER BY priority DESC
     LIMIT 1",
    [$screen['id'], $currentDay, $currentTime, $currentTime, $currentDate, $currentDate]
);

$playlistId = null;
if ($activeSchedule) {
    $playlistId = $activeSchedule['playlist_id'];
} elseif ($screen['current_playlist_id']) {
    $playlistId = $screen['current_playlist_id'];
} else {
    $defaultPlaylist = $db->fetchOne(
        "SELECT id FROM playlists WHERE user_id = ? AND is_default = 1 AND is_active = 1 LIMIT 1",
        [$screen['user_id']]
    );
    $playlistId = $defaultPlaylist ? $defaultPlaylist['id'] : null;
}

if (!$playlistId) {
    http_response_code(404);
    die(json_encode(['error' => 'No playlist assigned']));
}

// Get playlist info and generate version hash
$playlist = $db->fetchOne("SELECT * FROM playlists WHERE id = ?", [$playlistId]);

// Get playlist items
$items = $db->fetchAll(
    "SELECT 
        c.id,
        c.file_path,
        c.file_type,
        c.duration,
        c.title,
        pi.duration_override,
        pi.sort_order,
        COALESCE(pi.duration_override, c.duration, 10) as display_duration
     FROM playlist_items pi
     JOIN content c ON pi.content_id = c.id
     WHERE pi.playlist_id = ?
     AND c.is_active = 1
     ORDER BY pi.sort_order ASC",
    [$playlistId]
);

// Generate version hash based on playlist content
$versionData = [
    'playlist_id' => $playlistId,
    'playlist_updated' => $playlist['updated_at'] ?? $playlist['created_at'],
    'items' => array_map(function($item) {
        return [
            'id' => $item['id'],
            'path' => $item['file_path'],
            'duration' => $item['display_duration'],
            'order' => $item['sort_order']
        ];
    }, $items),
    'transition' => $playlist['transition']
];

$newVersion = md5(json_encode($versionData));

// Check if update needed
$needsUpdate = ($currentVersion !== $newVersion);

echo json_encode([
    'success' => true,
    'needs_update' => $needsUpdate,
    'version' => $newVersion,
    'playlist_id' => $playlistId,
    'playlist_name' => $playlist['name'] ?? 'Unnamed',
    'item_count' => count($items),
    'transition' => $playlist['transition'] ?? 'fade',
    'items' => $needsUpdate ? $items : null // Only send items if update needed
]);

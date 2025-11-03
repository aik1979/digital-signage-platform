<?php
/**
 * Screen Viewer - Browser-based display for testing
 * Access via: viewer.php?key=DEVICE_KEY
 */

session_start();
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/Database.php';
require_once __DIR__ . '/includes/functions.php';

$db = Database::getInstance();

// Get device key from URL
$deviceKey = isset($_GET['key']) ? trim($_GET['key']) : '';

if (empty($deviceKey)) {
    die('Error: No device key provided. Use: viewer.php?key=YOUR_DEVICE_KEY');
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
    die('Error: Invalid device key or screen is inactive.');
}

// Update heartbeat
$db->update('screens', [
    'last_heartbeat' => date('Y-m-d H:i:s'),
    'is_online' => 1,
    'last_ip' => $_SERVER['REMOTE_ADDR'] ?? null
], 'id = :id', ['id' => $screen['id']]);

// Determine which playlist to show based on schedule
$currentDay = date('w'); // 0=Sunday, 6=Saturday
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

// Use scheduled playlist, assigned playlist, or default playlist
$playlistId = null;
if ($activeSchedule) {
    $playlistId = $activeSchedule['playlist_id'];
} elseif ($screen['current_playlist_id']) {
    $playlistId = $screen['current_playlist_id'];
} else {
    // Get default playlist
    $defaultPlaylist = $db->fetchOne(
        "SELECT id FROM playlists WHERE user_id = ? AND is_default = 1 AND is_active = 1 LIMIT 1",
        [$screen['user_id']]
    );
    $playlistId = $defaultPlaylist ? $defaultPlaylist['id'] : null;
}

if (!$playlistId) {
    die('Error: No playlist assigned to this screen.');
}

// Get playlist items with content
$items = $db->fetchAll(
    "SELECT 
        c.id,
        c.file_path,
        c.file_type,
        c.duration,
        c.title,
        pi.duration_override,
        COALESCE(pi.duration_override, c.duration, 10) as display_duration
     FROM playlist_items pi
     JOIN content c ON pi.content_id = c.id
     WHERE pi.playlist_id = ?
     AND c.is_active = 1
     ORDER BY pi.sort_order ASC",
    [$playlistId]
);

if (empty($items)) {
    die('Error: Playlist is empty.');
}

// Convert to JSON for JavaScript
$itemsJson = json_encode($items);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($screen['name']); ?> - Digital Signage</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: #000;
            overflow: hidden;
            font-family: Arial, sans-serif;
        }
        
        #viewer {
            width: 100vw;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }
        
        .content-item {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
            display: none;
        }
        
        .content-item.active {
            display: block;
            animation: fadeIn 0.5s;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        #info {
            position: fixed;
            bottom: 10px;
            right: 10px;
            background: rgba(0, 0, 0, 0.7);
            color: #fff;
            padding: 10px 15px;
            border-radius: 5px;
            font-size: 12px;
            z-index: 1000;
        }
        
        #info.hidden {
            display: none;
        }
        
        .loading {
            color: #fff;
            font-size: 24px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div id="viewer">
        <div class="loading">Loading content...</div>
    </div>
    
    <div id="info">
        <div><strong><?php echo htmlspecialchars($screen['name']); ?></strong></div>
        <div id="currentItem">Item 1 of <?php echo count($items); ?></div>
        <div id="timer">--</div>
        <div style="margin-top: 5px; font-size: 10px; opacity: 0.7;">Press 'i' to toggle info</div>
    </div>

    <script>
        const items = <?php echo $itemsJson; ?>;
        let currentIndex = 0;
        let timer = null;
        const viewer = document.getElementById('viewer');
        const info = document.getElementById('info');
        const currentItemEl = document.getElementById('currentItem');
        const timerEl = document.getElementById('timer');
        
        function showItem(index) {
            // Clear viewer
            viewer.innerHTML = '';
            
            const item = items[index];
            let element;
            
            if (item.file_type === 'image') {
                element = document.createElement('img');
                element.src = item.file_path;
                element.className = 'content-item active';
                element.alt = item.title || 'Content';
            } else if (item.file_type === 'video') {
                element = document.createElement('video');
                element.src = item.file_path;
                element.className = 'content-item active';
                element.autoplay = true;
                element.muted = false;
                element.controls = false;
                
                // When video ends, move to next item
                element.addEventListener('ended', () => {
                    nextItem();
                });
            }
            
            viewer.appendChild(element);
            
            // Update info
            currentItemEl.textContent = `Item ${index + 1} of ${items.length}`;
            
            // Set timer for images only (videos handle their own timing)
            if (item.file_type === 'image') {
                let remaining = parseInt(item.display_duration);
                timerEl.textContent = `${remaining}s remaining`;
                
                clearInterval(timer);
                timer = setInterval(() => {
                    remaining--;
                    timerEl.textContent = `${remaining}s remaining`;
                    
                    if (remaining <= 0) {
                        clearInterval(timer);
                        nextItem();
                    }
                }, 1000);
            } else {
                timerEl.textContent = 'Playing video...';
            }
            
            // Log playback
            logPlayback(item.id);
        }
        
        function nextItem() {
            currentIndex = (currentIndex + 1) % items.length;
            showItem(currentIndex);
        }
        
        function logPlayback(contentId) {
            // Send playback log to server
            fetch('api/log_playback.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    device_key: '<?php echo $deviceKey; ?>',
                    content_id: contentId
                })
            }).catch(err => console.error('Failed to log playback:', err));
        }
        
        // Keyboard controls
        document.addEventListener('keydown', (e) => {
            if (e.key === 'i' || e.key === 'I') {
                info.classList.toggle('hidden');
            } else if (e.key === 'ArrowRight') {
                clearInterval(timer);
                nextItem();
            } else if (e.key === 'ArrowLeft') {
                clearInterval(timer);
                currentIndex = (currentIndex - 1 + items.length) % items.length;
                showItem(currentIndex);
            } else if (e.key === 'f' || e.key === 'F') {
                if (!document.fullscreenElement) {
                    document.documentElement.requestFullscreen();
                } else {
                    document.exitFullscreen();
                }
            }
        });
        
        // Heartbeat every 30 seconds
        setInterval(() => {
            fetch('api/heartbeat.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({device_key: '<?php echo $deviceKey; ?>'})
            });
        }, 30000);
        
        // Start playback
        if (items.length > 0) {
            showItem(0);
        } else {
            viewer.innerHTML = '<div class="loading">No content in playlist</div>';
        }
    </script>
</body>
</html>

<?php
/**
 * Enhanced Screen Viewer with Auto-Updates
 * Browser-based display with live content updates
 * Access via: viewer-v2.php?key=DEVICE_KEY
 */

session_start();
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/Database.php';
require_once __DIR__ . '/includes/functions.php';

$db = Database::getInstance();

// Get device key from URL
$deviceKey = isset($_GET['key']) ? trim($_GET['key']) : '';

// Check if info overlay should be shown (default: hidden)
$showInfo = isset($_GET['info']) && $_GET['info'] === '1';

if (empty($deviceKey)) {
    die('Error: No device key provided. Use: viewer-v2.php?key=YOUR_DEVICE_KEY');
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

// Determine which playlist to show
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
    die('Error: No playlist assigned to this screen.');
}

// Get playlist with transition
$playlist = $db->fetchOne("SELECT * FROM playlists WHERE id = ?", [$playlistId]);
$transition = $playlist['transition'] ?? 'fade';

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

if (empty($items)) {
    die('Error: Playlist is empty.');
}

// Generate initial version
$versionData = [
    'playlist_id' => $playlistId,
    'items' => array_map(function($item) {
        return ['id' => $item['id'], 'path' => $item['file_path'], 'duration' => $item['display_duration']];
    }, $items),
    'transition' => $transition
];
$initialVersion = md5(json_encode($versionData));

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
            position: absolute;
            opacity: 0;
        }
        
        .content-item.active {
            opacity: 1;
        }
        
        /* Transitions */
        .transition-fade .content-item.active {
            animation: fadeIn 0.8s ease-in-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .transition-slide .content-item.active {
            animation: slideIn 0.8s ease-in-out;
        }
        
        @keyframes slideIn {
            from { 
                opacity: 0;
                transform: translateX(100%);
            }
            to { 
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        .transition-zoom .content-item.active {
            animation: zoomIn 0.8s ease-in-out;
        }
        
        @keyframes zoomIn {
            from { 
                opacity: 0;
                transform: scale(0.5);
            }
            to { 
                opacity: 1;
                transform: scale(1);
            }
        }
        
        .transition-none .content-item.active {
            animation: none;
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
        
        .status-indicator {
            position: fixed;
            top: 10px;
            right: 10px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #4CAF50;
            z-index: 1001;
            box-shadow: 0 0 10px rgba(76, 175, 80, 0.8);
        }
        
        .status-indicator.offline {
            background: #f44336;
            box-shadow: 0 0 10px rgba(244, 67, 54, 0.8);
        }
        
        .loading {
            color: #fff;
            font-size: 24px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="status-indicator" id="statusIndicator" title="Online"></div>
    
    <div id="viewer" class="transition-<?php echo htmlspecialchars($transition); ?>">
        <div class="loading">Loading content...</div>
    </div>
    
    <div id="info" class="<?php echo $showInfo ? '' : 'hidden'; ?>">
        <div><strong><?php echo htmlspecialchars($screen['name']); ?></strong></div>
        <div id="currentItem">Item 1 of <?php echo count($items); ?></div>
        <div id="timer">--</div>
        <div id="updateStatus" style="margin-top: 5px; font-size: 10px; opacity: 0.7;">Checking for updates...</div>
        <div style="margin-top: 5px; font-size: 10px; opacity: 0.7;">Press 'i' to toggle info</div>
    </div>

    <script>
        let items = <?php echo $itemsJson; ?>;
        let transition = '<?php echo $transition; ?>';
        let currentVersion = '<?php echo $initialVersion; ?>';
        const deviceKey = '<?php echo $deviceKey; ?>';
        
        let currentIndex = 0;
        let timer = null;
        let updateCheckInterval = null;
        
        const viewer = document.getElementById('viewer');
        const info = document.getElementById('info');
        const currentItemEl = document.getElementById('currentItem');
        const timerEl = document.getElementById('timer');
        const updateStatusEl = document.getElementById('updateStatus');
        const statusIndicator = document.getElementById('statusIndicator');
        
        function showItem(index) {
            // Remove previous items
            const oldItems = viewer.querySelectorAll('.content-item');
            oldItems.forEach(item => {
                item.classList.remove('active');
                setTimeout(() => item.remove(), 1000);
            });
            
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
                element.muted = true; // Must be muted for autoplay to work
                element.playsInline = true; // Required for mobile
                element.controls = false;
                element.loop = false;
                
                // Handle video end
                element.addEventListener('ended', () => {
                    nextItem();
                });
                
                // Handle video errors
                element.addEventListener('error', (e) => {
                    console.error('Video error:', e);
                    console.error('Video source:', item.file_path);
                    // Skip to next item on error
                    setTimeout(() => nextItem(), 1000);
                });
                
                // Ensure video plays
                element.addEventListener('loadeddata', () => {
                    element.play().catch(err => {
                        console.error('Play error:', err);
                        // Try again with muted
                        element.muted = true;
                        element.play().catch(err2 => {
                            console.error('Play error (muted):', err2);
                            setTimeout(() => nextItem(), 1000);
                        });
                    });
                });
            }
            
            viewer.appendChild(element);
            
            // Update info
            currentItemEl.textContent = `Item ${index + 1} of ${items.length}`;
            
            // Set timer for images
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
            // Don't advance if only 1 item - just stay on it
            if (items.length > 1) {
                currentIndex = (currentIndex + 1) % items.length;
                showItem(currentIndex);
            }
        }
        
        function logPlayback(contentId) {
            fetch('api/log_playback.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    device_key: deviceKey,
                    content_id: contentId
                })
            }).catch(err => console.error('Failed to log playback:', err));
        }
        
        // Check for updates
        async function checkForUpdates() {
            try {
                const response = await fetch(`api/check-updates.php?device_key=${deviceKey}&version=${currentVersion}`);
                const data = await response.json();
                
                if (data.success) {
                    // Update status indicator
                    statusIndicator.classList.remove('offline');
                    statusIndicator.title = 'Online';
                    
                    if (data.needs_update) {
                        console.log('Update detected, reloading content...');
                        updateStatusEl.textContent = 'Update available, reloading...';
                        
                        // Update items and version
                        items = data.items;
                        currentVersion = data.version;
                        
                        // Update transition if changed
                        if (data.transition !== transition) {
                            transition = data.transition;
                            viewer.className = `transition-${transition}`;
                        }
                        
                        // Restart from first item
                        clearInterval(timer);
                        currentIndex = 0;
                        showItem(0);
                        
                        updateStatusEl.textContent = 'Content updated!';
                        setTimeout(() => {
                            updateStatusEl.textContent = 'Checking for updates...';
                        }, 3000);
                    } else {
                        updateStatusEl.textContent = 'Content up to date';
                    }
                }
            } catch (error) {
                console.error('Update check failed:', error);
                statusIndicator.classList.add('offline');
                statusIndicator.title = 'Offline - Connection error';
                updateStatusEl.textContent = 'Connection error';
            }
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
            } else if (e.key === 'r' || e.key === 'R') {
                // Manual refresh
                checkForUpdates();
            }
        });
        
        // Start playback
        if (items.length > 0) {
            showItem(0);
        } else {
            viewer.innerHTML = '<div class="loading">No content in playlist</div>';
        }
        
        // Check for updates every 30 seconds
        updateCheckInterval = setInterval(checkForUpdates, 30000);
        
        // Initial update check after 5 seconds
        setTimeout(checkForUpdates, 5000);
        
        // Full page reload every 5 minutes (cache clearing) - only if multiple items
        if (items.length > 1) {
            setInterval(() => {
                console.log('Performing periodic full reload...');
                window.location.reload();
            }, 300000); // 5 minutes
        }
    </script>
</body>
</html>

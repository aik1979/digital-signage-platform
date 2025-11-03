<?php
/**
 * Public Playlist Viewer - Shareable URL for browser-based display
 * Access via: public_viewer.php?token=SHARE_TOKEN or view/SHARE_TOKEN
 */

session_start();
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/Database.php';
require_once __DIR__ . '/includes/functions.php';

$db = Database::getInstance();

// Get share token from URL
$shareToken = isset($_GET['token']) ? trim($_GET['token']) : '';

if (empty($shareToken)) {
    die('Error: Invalid or missing share token.');
}

// Get playlist info using share token
$playlist = $db->fetchOne(
    "SELECT id, name, transition, is_active, share_enabled FROM playlists WHERE share_token = ? AND is_active = 1",
    [$shareToken]
);

if (!$playlist) {
    die('Error: Playlist not found or inactive.');
}

if (!$playlist['share_enabled']) {
    die('Error: Public sharing is disabled for this playlist.');
}

$transition = $playlist['transition'] ?? 'fade';
$playlistId = $playlist['id'];

// Get playlist items with content
$items = $db->fetchAll(
    "SELECT 
        c.id,
        c.file_path,
        c.file_type,
        c.title,
        c.duration,
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
    <title><?php echo htmlspecialchars($playlist['name']); ?> - Digital Signage</title>
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
        
        /* Fade transition */
        .transition-fade .content-item.active {
            animation: fadeIn 0.8s ease-in-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        /* Slide transition */
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
        
        /* Zoom transition */
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
        
        /* None transition */
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
        
        .loading {
            color: #fff;
            font-size: 24px;
            text-align: center;
        }
        
        .branding {
            position: fixed;
            top: 10px;
            left: 10px;
            background: rgba(0, 128, 128, 0.9);
            color: #fff;
            padding: 8px 12px;
            border-radius: 5px;
            font-size: 14px;
            font-weight: bold;
            z-index: 1000;
        }
    </style>
</head>
<body>
    <div class="branding">Digital Signage Platform</div>
    
    <div id="viewer" class="transition-<?php echo htmlspecialchars($transition); ?>">
        <div class="loading">Loading content...</div>
    </div>
    
    <div id="info">
        <div><strong><?php echo htmlspecialchars($playlist['name']); ?></strong></div>
        <div id="currentItem">Item 1 of <?php echo count($items); ?></div>
        <div id="timer">--</div>
        <div style="margin-top: 5px; font-size: 10px; opacity: 0.7;">Press 'i' to toggle info | 'f' for fullscreen</div>
    </div>

    <script>
        const items = <?php echo $itemsJson; ?>;
        const transition = '<?php echo $transition; ?>';
        let currentIndex = 0;
        let timer = null;
        const viewer = document.getElementById('viewer');
        const info = document.getElementById('info');
        const currentItemEl = document.getElementById('currentItem');
        const timerEl = document.getElementById('timer');
        
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
                element.className = 'content-item';
                element.alt = item.title || 'Content';
                
                // Add active class after a brief delay for transition
                setTimeout(() => element.classList.add('active'), 50);
            } else if (item.file_type === 'video') {
                element = document.createElement('video');
                element.src = item.file_path;
                element.className = 'content-item';
                element.autoplay = true;
                element.muted = false;
                element.controls = false;
                
                setTimeout(() => element.classList.add('active'), 50);
                
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
        }
        
        function nextItem() {
            currentIndex = (currentIndex + 1) % items.length;
            showItem(currentIndex);
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
        
        // Start playback
        if (items.length > 0) {
            showItem(0);
        } else {
            viewer.innerHTML = '<div class="loading">No content in playlist</div>';
        }
    </script>
</body>
</html>

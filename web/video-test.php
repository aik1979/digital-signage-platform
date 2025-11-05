<?php
/**
 * Video Playback Diagnostic Tool
 * Tests video playback and shows detailed debug information
 */

// Get a sample video from the database
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/Database.php';

$db = Database::getInstance();

// Get first video from content
$video = $db->fetchOne("SELECT * FROM content WHERE file_type = 'video' LIMIT 1");

if (!$video) {
    die("No video found in database. Please upload a video first.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Video Playback Test</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: #000;
            color: #fff;
            font-family: monospace;
            padding: 20px;
        }
        
        #video-container {
            width: 100%;
            max-width: 800px;
            margin: 0 auto 20px;
            background: #222;
            padding: 20px;
            border: 2px solid #0f0;
        }
        
        video {
            width: 100%;
            height: auto;
            background: #000;
            border: 1px solid #0f0;
        }
        
        #log {
            width: 100%;
            max-width: 800px;
            margin: 0 auto;
            background: #111;
            padding: 20px;
            border: 2px solid #00f;
            max-height: 400px;
            overflow-y: auto;
            font-size: 12px;
        }
        
        .log-entry {
            margin: 5px 0;
            padding: 5px;
            border-left: 3px solid #0f0;
            padding-left: 10px;
        }
        
        .log-error {
            border-left-color: #f00;
            color: #f00;
        }
        
        .log-warning {
            border-left-color: #ff0;
            color: #ff0;
        }
        
        .log-success {
            border-left-color: #0f0;
            color: #0f0;
        }
        
        .controls {
            width: 100%;
            max-width: 800px;
            margin: 20px auto;
            display: flex;
            gap: 10px;
        }
        
        button {
            padding: 10px 20px;
            background: #0f0;
            color: #000;
            border: none;
            cursor: pointer;
            font-weight: bold;
        }
        
        button:hover {
            background: #0c0;
        }
    </style>
</head>
<body>
    <h1 style="text-align: center; margin-bottom: 20px;">🎬 Video Playback Diagnostic</h1>
    
    <div id="video-container">
        <h2>Video: <?php echo htmlspecialchars($video['title']); ?></h2>
        <p>File: <?php echo htmlspecialchars($video['file_path']); ?></p>
        <video id="test-video" muted playsinline></video>
    </div>
    
    <div class="controls">
        <button onclick="loadVideo()">Load Video</button>
        <button onclick="playVideo()">Play</button>
        <button onclick="pauseVideo()">Pause</button>
        <button onclick="testVideo.muted = !testVideo.muted">Toggle Mute (Currently: <span id="mute-status">Muted</span>)</button>
        <button onclick="clearLog()">Clear Log</button>
    </div>
    
    <div id="log">
        <div class="log-entry log-success">Diagnostic tool loaded. Click "Load Video" to begin test.</div>
    </div>

    <script>
        const testVideo = document.getElementById('test-video');
        const logDiv = document.getElementById('log');
        const muteStatus = document.getElementById('mute-status');
        const videoPath = <?php echo json_encode($video['file_path']); ?>;
        
        let logCount = 0;
        
        function log(message, type = 'info') {
            logCount++;
            const entry = document.createElement('div');
            entry.className = 'log-entry log-' + type;
            const timestamp = new Date().toLocaleTimeString();
            entry.textContent = `[${logCount}] ${timestamp} - ${message}`;
            logDiv.appendChild(entry);
            logDiv.scrollTop = logDiv.scrollHeight;
            console.log(`[${type.toUpperCase()}] ${message}`);
        }
        
        function clearLog() {
            logDiv.innerHTML = '<div class="log-entry log-success">Log cleared.</div>';
            logCount = 0;
        }
        
        function loadVideo() {
            log('Loading video: ' + videoPath, 'info');
            testVideo.src = videoPath;
            log('Video src set', 'success');
        }
        
        function playVideo() {
            log('Attempting to play video...', 'info');
            const playPromise = testVideo.play();
            
            if (playPromise !== undefined) {
                playPromise.then(() => {
                    log('✅ Video playing successfully!', 'success');
                }).catch(err => {
                    log('❌ Play failed: ' + err.message, 'error');
                    log('Error name: ' + err.name, 'error');
                });
            } else {
                log('⚠️ play() returned undefined', 'warning');
            }
        }
        
        function pauseVideo() {
            testVideo.pause();
            log('Video paused', 'info');
        }
        
        // Event listeners
        testVideo.addEventListener('loadstart', () => log('Event: loadstart', 'info'));
        testVideo.addEventListener('durationchange', () => log('Event: durationchange - Duration: ' + testVideo.duration + 's', 'info'));
        testVideo.addEventListener('loadedmetadata', () => {
            log('Event: loadedmetadata', 'success');
            log('Video dimensions: ' + testVideo.videoWidth + 'x' + testVideo.videoHeight, 'info');
            log('Duration: ' + testVideo.duration + ' seconds', 'info');
        });
        testVideo.addEventListener('loadeddata', () => log('Event: loadeddata', 'success'));
        testVideo.addEventListener('progress', () => log('Event: progress - Buffered: ' + (testVideo.buffered.length > 0 ? testVideo.buffered.end(0) : 0) + 's', 'info'));
        testVideo.addEventListener('canplay', () => log('Event: canplay - Video can start playing', 'success'));
        testVideo.addEventListener('canplaythrough', () => log('Event: canplaythrough - Video can play without buffering', 'success'));
        testVideo.addEventListener('play', () => log('Event: play - Playback started', 'success'));
        testVideo.addEventListener('playing', () => log('Event: playing - Video is now playing', 'success'));
        testVideo.addEventListener('pause', () => log('Event: pause', 'warning'));
        testVideo.addEventListener('ended', () => log('Event: ended - Video finished', 'success'));
        testVideo.addEventListener('timeupdate', () => {
            if (testVideo.currentTime > 0) {
                log('Event: timeupdate - Current time: ' + testVideo.currentTime.toFixed(2) + 's', 'info');
            }
        });
        testVideo.addEventListener('error', (e) => {
            log('Event: ERROR!', 'error');
            if (testVideo.error) {
                log('Error code: ' + testVideo.error.code, 'error');
                log('Error message: ' + testVideo.error.message, 'error');
                const errorMessages = {
                    1: 'MEDIA_ERR_ABORTED - Fetching process aborted by user',
                    2: 'MEDIA_ERR_NETWORK - Error occurred while downloading',
                    3: 'MEDIA_ERR_DECODE - Error occurred while decoding',
                    4: 'MEDIA_ERR_SRC_NOT_SUPPORTED - Media format not supported'
                };
                log('Error type: ' + (errorMessages[testVideo.error.code] || 'Unknown'), 'error');
            }
        });
        testVideo.addEventListener('stalled', () => log('Event: stalled - Media download has stalled', 'warning'));
        testVideo.addEventListener('suspend', () => log('Event: suspend - Media data loading suspended', 'warning'));
        testVideo.addEventListener('waiting', () => log('Event: waiting - Waiting for data', 'warning'));
        testVideo.addEventListener('seeking', () => log('Event: seeking', 'info'));
        testVideo.addEventListener('seeked', () => log('Event: seeked', 'info'));
        
        // Monitor mute status
        testVideo.addEventListener('volumechange', () => {
            muteStatus.textContent = testVideo.muted ? 'Muted' : 'Unmuted';
            log('Volume changed - Muted: ' + testVideo.muted + ', Volume: ' + testVideo.volume, 'info');
        });
        
        // Log browser info
        log('Browser: ' + navigator.userAgent, 'info');
        log('Video element ready', 'success');
    </script>
</body>
</html>

<?php
/**
 * Video Playback Diagnostic Tool - Auto-running version
 * Tests video playback automatically and shows detailed debug information
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
    <title>Video Playback Auto-Test</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: #000;
            color: #0f0;
            font-family: monospace;
            font-size: 18px;
            line-height: 1.6;
        }
        
        #video-container {
            width: 100vw;
            height: 50vh;
            background: #111;
            border-bottom: 3px solid #0f0;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }
        
        video {
            max-width: 100%;
            max-height: 100%;
            background: #000;
        }
        
        #status {
            position: absolute;
            top: 10px;
            right: 10px;
            background: rgba(0,0,0,0.8);
            padding: 10px;
            border: 2px solid #0f0;
            font-size: 24px;
            font-weight: bold;
        }
        
        #log {
            height: 50vh;
            overflow-y: auto;
            padding: 20px;
            background: #000;
        }
        
        .log-entry {
            margin: 5px 0;
            padding: 8px;
            border-left: 4px solid #0f0;
            padding-left: 15px;
            font-size: 16px;
        }
        
        .log-error {
            border-left-color: #f00;
            color: #f00;
            font-weight: bold;
            font-size: 20px;
        }
        
        .log-warning {
            border-left-color: #ff0;
            color: #ff0;
        }
        
        .log-success {
            border-left-color: #0f0;
            color: #0f0;
            font-weight: bold;
        }
        
        .log-info {
            border-left-color: #0ff;
            color: #0ff;
        }
    </style>
</head>
<body>
    <div id="video-container">
        <video id="test-video" muted playsinline preload="auto"></video>
        <div id="status">INITIALIZING...</div>
    </div>
    
    <div id="log"></div>

    <script>
        const testVideo = document.getElementById('test-video');
        const logDiv = document.getElementById('log');
        const statusDiv = document.getElementById('status');
        const videoPath = <?php echo json_encode($video['file_path']); ?>;
        
        let logCount = 0;
        let playAttempted = false;
        let hasPlayed = false;
        
        function log(message, type = 'info') {
            logCount++;
            const entry = document.createElement('div');
            entry.className = 'log-entry log-' + type;
            const timestamp = new Date().toLocaleTimeString();
            entry.textContent = `[${logCount}] ${timestamp} - ${message}`;
            logDiv.insertBefore(entry, logDiv.firstChild);
            console.log(`[${type.toUpperCase()}] ${message}`);
        }
        
        function updateStatus(text, color = '#0f0') {
            statusDiv.textContent = text;
            statusDiv.style.borderColor = color;
            statusDiv.style.color = color;
        }
        
        function attemptPlay() {
            if (playAttempted) return;
            playAttempted = true;
            
            log('🎬 Attempting to play video...', 'info');
            updateStatus('PLAYING...', '#ff0');
            
            const playPromise = testVideo.play();
            
            if (playPromise !== undefined) {
                playPromise.then(() => {
                    log('✅✅✅ VIDEO PLAYING SUCCESSFULLY! ✅✅✅', 'success');
                    updateStatus('✅ PLAYING', '#0f0');
                }).catch(err => {
                    log('❌❌❌ PLAY FAILED! ❌❌❌', 'error');
                    log('❌ Error: ' + err.message, 'error');
                    log('❌ Error name: ' + err.name, 'error');
                    updateStatus('❌ FAILED', '#f00');
                    
                    // Try again in 2 seconds
                    setTimeout(() => {
                        playAttempted = false;
                        log('Retrying play...', 'warning');
                        attemptPlay();
                    }, 2000);
                });
            } else {
                log('⚠️ play() returned undefined', 'warning');
            }
        }
        
        // Event listeners
        testVideo.addEventListener('loadstart', () => {
            log('📥 loadstart - Starting to load video', 'info');
            updateStatus('LOADING...', '#0ff');
        });
        
        testVideo.addEventListener('loadedmetadata', () => {
            log('📊 loadedmetadata - Metadata loaded', 'success');
            log('   Dimensions: ' + testVideo.videoWidth + 'x' + testVideo.videoHeight, 'info');
            log('   Duration: ' + testVideo.duration + ' seconds', 'info');
        });
        
        testVideo.addEventListener('loadeddata', () => {
            log('📦 loadeddata - First frame loaded', 'success');
        });
        
        testVideo.addEventListener('canplay', () => {
            log('✓ canplay - Video can start playing', 'success');
            if (!playAttempted) {
                setTimeout(attemptPlay, 500);
            }
        });
        
        testVideo.addEventListener('canplaythrough', () => {
            log('✓✓ canplaythrough - Can play without buffering', 'success');
            if (!playAttempted) {
                setTimeout(attemptPlay, 500);
            }
        });
        
        testVideo.addEventListener('play', () => {
            log('▶️ play event - Playback started', 'success');
        });
        
        testVideo.addEventListener('playing', () => {
            hasPlayed = true;
            log('▶️▶️ PLAYING EVENT - Video is now playing!', 'success');
            updateStatus('✅ PLAYING', '#0f0');
        });
        
        testVideo.addEventListener('pause', () => {
            log('⏸️ pause event', 'warning');
            if (hasPlayed) {
                updateStatus('⏸️ PAUSED', '#ff0');
            }
        });
        
        testVideo.addEventListener('ended', () => {
            log('⏹️ ended - Video finished', 'success');
            updateStatus('✅ ENDED', '#0f0');
        });
        
        let lastTime = 0;
        testVideo.addEventListener('timeupdate', () => {
            const currentTime = Math.floor(testVideo.currentTime);
            if (currentTime !== lastTime && currentTime > 0) {
                lastTime = currentTime;
                log('⏱️ Time: ' + currentTime + 's / ' + Math.floor(testVideo.duration) + 's', 'info');
            }
        });
        
        testVideo.addEventListener('error', (e) => {
            log('❌❌❌ ERROR EVENT! ❌❌❌', 'error');
            updateStatus('❌ ERROR', '#f00');
            if (testVideo.error) {
                log('❌ Error code: ' + testVideo.error.code, 'error');
                const errorMessages = {
                    1: 'MEDIA_ERR_ABORTED - Fetching aborted',
                    2: 'MEDIA_ERR_NETWORK - Network error',
                    3: 'MEDIA_ERR_DECODE - Decoding error (codec issue)',
                    4: 'MEDIA_ERR_SRC_NOT_SUPPORTED - Format not supported'
                };
                log('❌ ' + (errorMessages[testVideo.error.code] || 'Unknown error'), 'error');
            }
        });
        
        testVideo.addEventListener('stalled', () => {
            log('⚠️ stalled - Download stalled', 'warning');
        });
        
        testVideo.addEventListener('waiting', () => {
            log('⏳ waiting - Waiting for data', 'warning');
        });
        
        // Log browser info
        log('🌐 Browser: ' + navigator.userAgent, 'info');
        log('📁 Video file: ' + videoPath, 'info');
        
        // Start loading video automatically
        log('🚀 Auto-loading video in 2 seconds...', 'info');
        setTimeout(() => {
            log('📥 Setting video source...', 'info');
            testVideo.src = videoPath;
            log('✓ Video source set', 'success');
        }, 2000);
    </script>
</body>
</html>

<?php
/**
 * Boot Page for Raspberry Pi
 * Checks if device is paired (via localStorage) and redirects accordingly
 */

$deviceId = $_GET['device_id'] ?? '';

if (empty($deviceId)) {
    die('Error: Missing device_id parameter');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DSP - Starting...</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: linear-gradient(135deg, #1a1a1a 0%, #2a2a2a 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            color: #fff;
        }
        
        .container {
            text-align: center;
            padding: 40px;
        }
        
        .logo {
            font-size: 64px;
            margin-bottom: 20px;
        }
        
        .spinner {
            width: 50px;
            height: 50px;
            margin: 30px auto;
            border: 4px solid rgba(255,255,255,0.1);
            border-top: 4px solid #3498DB;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .status {
            font-size: 18px;
            opacity: 0.8;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">📺</div>
        <h1>Digital Signage Platform</h1>
        <div class="spinner"></div>
        <div class="status" id="status">Checking device status...</div>
    </div>

    <script>
        const deviceId = "<?php echo htmlspecialchars($deviceId); ?>";
        const statusEl = document.getElementById('status');
        
        // Check if device is already paired (localStorage)
        const viewerUrl = localStorage.getItem('dsp_viewer_url');
        const savedDeviceId = localStorage.getItem('dsp_device_id');
        
        if (viewerUrl && savedDeviceId === deviceId) {
            // Already paired - show splash screen
            statusEl.textContent = 'Device paired! Loading splash screen...';
            setTimeout(() => {
                window.location.href = '/splash.php?device_id=' + encodeURIComponent(deviceId) + '&viewer_url=' + encodeURIComponent(viewerUrl);
            }, 1000);
        } else {
            // Not paired - show pairing page
            statusEl.textContent = 'Device not paired. Loading pairing page...';
            setTimeout(() => {
                window.location.href = '/pair.php?device_id=' + encodeURIComponent(deviceId);
            }, 1000);
        }
    </script>
</body>
</html>

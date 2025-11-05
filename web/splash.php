<?php
/**
 * Splash Screen
 * Shows pairing QR code for 10 seconds, then loads the viewer
 * Used on Raspberry Pi boot to allow re-pairing if needed
 */

$deviceId = $_GET['device_id'] ?? '';
$viewerUrl = $_GET['viewer_url'] ?? '';

if (empty($deviceId) || empty($viewerUrl)) {
    die('Error: Missing device_id or viewer_url parameter');
}

// Generate pairing URL (use current domain)
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$domain = $_SERVER['HTTP_HOST'] ?? 'dsp.my-toolbox.info';
$baseUrl = $protocol . '://' . $domain;
$pairingUrl = $baseUrl . '/pair.php?device_id=' . urlencode($deviceId);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Digital Signage - Starting...</title>
    <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
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
            max-width: 600px;
            padding: 40px;
        }
        
        .logo {
            font-size: 64px;
            font-weight: bold;
            margin-bottom: 30px;
            background: linear-gradient(90deg, #3498DB, #5CB85C, #E74C3C);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        h1 {
            font-size: 36px;
            margin-bottom: 20px;
        }
        
        .subtitle {
            font-size: 18px;
            opacity: 0.8;
            margin-bottom: 40px;
            color: #aaa;
        }
        
        .qr-container {
            background: white;
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.5);
            margin: 30px auto;
            display: inline-block;
        }
        
        #qrcode {
            margin: 0 auto;
        }
        
        .countdown {
            font-size: 24px;
            margin-top: 30px;
            color: #3498DB;
        }
        
        .info {
            margin-top: 20px;
            font-size: 14px;
            opacity: 0.7;
            color: #aaa;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">📺 DSP</div>
        <h1>Digital Signage Platform</h1>
        <p class="subtitle">Scan to re-pair this device</p>
        
        <div class="qr-container">
            <div id="qrcode"></div>
        </div>
        
        <div class="countdown">
            Starting in <span id="timer">10</span> seconds...
        </div>
        
        <div class="info">
            Press any key to re-pair this device
        </div>
    </div>

    <script>
        // Generate QR code
        new QRCode(document.getElementById("qrcode"), {
            text: "<?php echo htmlspecialchars($pairingUrl); ?>",
            width: 200,
            height: 200,
            colorDark: "#000000",
            colorLight: "#ffffff",
            correctLevel: QRCode.CorrectLevel.H
        });
        
        const viewerUrl = "<?php echo htmlspecialchars($viewerUrl); ?>";
        let countdown = 10;
        const timerEl = document.getElementById('timer');
        
        // Countdown timer
        const interval = setInterval(() => {
            countdown--;
            timerEl.textContent = countdown;
            
            if (countdown <= 0) {
                clearInterval(interval);
                window.location.href = viewerUrl;
            }
        }, 1000);
        
        // Allow user to cancel auto-redirect by pressing any key
        document.addEventListener('keydown', (e) => {
            clearInterval(interval);
            window.location.href = "<?php echo htmlspecialchars($pairingUrl); ?>";
        });
    </script>
</body>
</html>

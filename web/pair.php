<?php
/**
 * Device Pairing Page
 * Displays QR code and pairing code for device setup
 * This page is shown on the Raspberry Pi at first boot
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/Database.php';
require_once __DIR__ . '/includes/functions.php';

$db = Database::getInstance();

// Get or generate device ID
$deviceId = $_GET['device_id'] ?? null;

if (!$deviceId) {
    // Generate a unique device ID if not provided
    $deviceId = 'dsp_' . bin2hex(random_bytes(8));
}

// Generate a 6-character pairing code
function generatePairingCode() {
    $characters = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // Removed confusing chars
    $code = '';
    for ($i = 0; $i < 6; $i++) {
        $code .= $characters[random_int(0, strlen($characters) - 1)];
    }
    return $code;
}

// Check if device already has a pairing code
$existing = $db->fetchOne(
    "SELECT * FROM device_pairing WHERE device_id = ? AND status = 'pending' AND expires_at > NOW()",
    [$deviceId]
);

if ($existing) {
    $pairingCode = $existing['pairing_code'];
} else {
    // Generate new pairing code
    $pairingCode = generatePairingCode();
    
    // Ensure code is unique
    while ($db->fetchOne("SELECT id FROM device_pairing WHERE pairing_code = ?", [$pairingCode])) {
        $pairingCode = generatePairingCode();
    }
    
    // Store pairing code (expires in 1 hour)
    $db->insert('device_pairing', [
        'pairing_code' => $pairingCode,
        'device_id' => $deviceId,
        'status' => 'pending',
        'expires_at' => date('Y-m-d H:i:s', strtotime('+1 hour'))
    ]);
}

// Generate QR code URL for mobile pairing
$pairingUrl = rtrim(APP_URL, '/') . '/pair-device.php?code=' . $pairingCode;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pair Your Device - Digital Signage</title>
    <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
            font-size: 48px;
            font-weight: bold;
            margin-bottom: 20px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        
        h1 {
            font-size: 32px;
            margin-bottom: 10px;
        }
        
        .subtitle {
            font-size: 18px;
            opacity: 0.9;
            margin-bottom: 40px;
        }
        
        .qr-container {
            background: white;
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
            margin-bottom: 30px;
            display: inline-block;
        }
        
        #qrcode {
            margin: 0 auto;
        }
        
        .pairing-code {
            background: rgba(255,255,255,0.2);
            backdrop-filter: blur(10px);
            padding: 20px 40px;
            border-radius: 15px;
            margin: 30px 0;
            border: 2px solid rgba(255,255,255,0.3);
        }
        
        .code-label {
            font-size: 14px;
            opacity: 0.8;
            margin-bottom: 10px;
        }
        
        .code {
            font-size: 48px;
            font-weight: bold;
            letter-spacing: 8px;
            font-family: 'Courier New', monospace;
        }
        
        .instructions {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            padding: 25px;
            border-radius: 15px;
            text-align: left;
            margin-top: 30px;
        }
        
        .instructions h3 {
            margin-bottom: 15px;
            font-size: 20px;
        }
        
        .instructions ol {
            margin-left: 20px;
            line-height: 1.8;
        }
        
        .instructions li {
            margin-bottom: 10px;
        }
        
        .status {
            margin-top: 30px;
            padding: 15px;
            background: rgba(255,255,255,0.1);
            border-radius: 10px;
            font-size: 14px;
        }
        
        .status.checking {
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 0.6; }
            50% { opacity: 1; }
        }
        
        .device-info {
            margin-top: 20px;
            font-size: 12px;
            opacity: 0.6;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">📺 DSP</div>
        <h1>Pair Your Device</h1>
        <p class="subtitle">Scan the QR code with your smartphone to set up this screen</p>
        
        <div class="qr-container">
            <div id="qrcode"></div>
        </div>
        
        <div class="pairing-code">
            <div class="code-label">Or enter this code manually:</div>
            <div class="code"><?php echo $pairingCode; ?></div>
        </div>
        
        <div class="instructions">
            <h3>Setup Instructions:</h3>
            <ol>
                <li>Open your smartphone camera or QR code scanner</li>
                <li>Scan the QR code above</li>
                <li>Follow the setup wizard on your phone</li>
                <li>This screen will automatically start displaying content</li>
            </ol>
            <p style="margin-top: 15px; font-size: 14px; opacity: 0.8;">
                <strong>Can't scan?</strong> Visit <strong><?php echo APP_URL; ?></strong> and enter code: <strong><?php echo $pairingCode; ?></strong>
            </p>
        </div>
        
        <div class="status checking" id="status">
            <div>⏳ Waiting for pairing...</div>
            <div style="font-size: 12px; margin-top: 5px; opacity: 0.7;">Code expires in <span id="countdown">60:00</span></div>
        </div>
        
        <div class="device-info">
            Device ID: <?php echo htmlspecialchars($deviceId); ?>
        </div>
    </div>

    <script>
        // Generate QR code
        new QRCode(document.getElementById("qrcode"), {
            text: "<?php echo $pairingUrl; ?>",
            width: 256,
            height: 256,
            colorDark: "#000000",
            colorLight: "#ffffff",
            correctLevel: QRCode.CorrectLevel.H
        });
        
        // Check pairing status every 3 seconds
        const deviceId = "<?php echo $deviceId; ?>";
        const statusEl = document.getElementById('status');
        
        async function checkPairingStatus() {
            try {
                const response = await fetch(`api/check-pairing.php?device_id=${deviceId}`);
                const data = await response.json();
                
                if (data.paired) {
                    statusEl.innerHTML = '<div>✅ Paired successfully!</div><div style="font-size: 12px; margin-top: 5px;">Redirecting to viewer...</div>';
                    statusEl.classList.remove('checking');
                    statusEl.style.background = 'rgba(76, 175, 80, 0.3)';
                    
                    // Redirect to viewer
                    setTimeout(() => {
                        window.location.href = data.viewer_url;
                    }, 2000);
                } else if (data.expired) {
                    statusEl.innerHTML = '<div>⚠️ Pairing code expired</div><div style="font-size: 12px; margin-top: 5px;">Refreshing...</div>';
                    statusEl.style.background = 'rgba(255, 152, 0, 0.3)';
                    setTimeout(() => window.location.reload(), 3000);
                }
            } catch (error) {
                console.error('Failed to check pairing status:', error);
            }
        }
        
        // Check every 3 seconds
        setInterval(checkPairingStatus, 3000);
        
        // Countdown timer
        let expiresAt = new Date(Date.now() + 3600000); // 1 hour from now
        const countdownEl = document.getElementById('countdown');
        
        function updateCountdown() {
            const now = new Date();
            const remaining = Math.max(0, expiresAt - now);
            const minutes = Math.floor(remaining / 60000);
            const seconds = Math.floor((remaining % 60000) / 1000);
            countdownEl.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;
            
            if (remaining <= 0) {
                window.location.reload();
            }
        }
        
        setInterval(updateCountdown, 1000);
        updateCountdown();
    </script>
</body>
</html>

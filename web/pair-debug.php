<?php
/**
 * Debug version of pair.php to show errors
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!-- Debug: Starting pair-debug.php -->\n";

try {
    echo "<!-- Debug: Loading config -->\n";
    require_once __DIR__ . '/config/config.php';
    echo "<!-- Debug: Config loaded -->\n";
    
    echo "<!-- Debug: Loading Database -->\n";
    require_once __DIR__ . '/includes/Database.php';
    echo "<!-- Debug: Database loaded -->\n";
    
    echo "<!-- Debug: Loading functions -->\n";
    require_once __DIR__ . '/includes/functions.php';
    echo "<!-- Debug: Functions loaded -->\n";
    
    echo "<!-- Debug: Getting database instance -->\n";
    $db = Database::getInstance();
    echo "<!-- Debug: Database instance created -->\n";
    
    // Get or generate device ID
    $deviceId = $_GET['device_id'] ?? null;
    echo "<!-- Debug: Device ID: " . htmlspecialchars($deviceId ?? 'null') . " -->\n";
    
    if (!$deviceId) {
        $deviceId = 'dsp_' . bin2hex(random_bytes(8));
        echo "<!-- Debug: Generated new device ID: " . htmlspecialchars($deviceId) . " -->\n";
    }
    
    // Generate a 6-character pairing code
    function generatePairingCode() {
        $characters = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $code = '';
        for ($i = 0; $i < 6; $i++) {
            $code .= $characters[random_int(0, strlen($characters) - 1)];
        }
        return $code;
    }
    
    echo "<!-- Debug: Checking for existing pairing -->\n";
    // Check if device already has a pairing code
    $existing = $db->fetchOne(
        "SELECT * FROM device_pairing WHERE device_id = ? AND status = 'pending' AND expires_at > NOW()",
        [$deviceId]
    );
    echo "<!-- Debug: Existing pairing: " . ($existing ? 'found' : 'not found') . " -->\n";
    
    if ($existing) {
        $pairingCode = $existing['pairing_code'];
        echo "<!-- Debug: Using existing code: " . htmlspecialchars($pairingCode) . " -->\n";
    } else {
        echo "<!-- Debug: Generating new pairing code -->\n";
        // Generate new pairing code
        $pairingCode = generatePairingCode();
        
        // Ensure code is unique
        while ($db->fetchOne("SELECT id FROM device_pairing WHERE pairing_code = ?", [$pairingCode])) {
            $pairingCode = generatePairingCode();
        }
        echo "<!-- Debug: Generated code: " . htmlspecialchars($pairingCode) . " -->\n";
        
        echo "<!-- Debug: Inserting into database -->\n";
        // Store pairing code (expires in 1 hour)
        $db->insert('device_pairing', [
            'pairing_code' => $pairingCode,
            'device_id' => $deviceId,
            'status' => 'pending',
            'expires_at' => date('Y-m-d H:i:s', strtotime('+1 hour'))
        ]);
        echo "<!-- Debug: Inserted successfully -->\n";
    }
    
    // Generate QR code URL for mobile pairing
    $pairingUrl = rtrim(APP_URL, '/') . '/pair-device.php?code=' . $pairingCode;
    echo "<!-- Debug: Pairing URL: " . htmlspecialchars($pairingUrl) . " -->\n";
    
    echo "<!-- Debug: All processing complete, rendering page -->\n";
    
} catch (Exception $e) {
    echo "<!-- DEBUG ERROR: " . htmlspecialchars($e->getMessage()) . " -->\n";
    echo "<!-- DEBUG TRACE: " . htmlspecialchars($e->getTraceAsString()) . " -->\n";
    die("<h1>Error</h1><p>" . htmlspecialchars($e->getMessage()) . "</p><pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>");
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DEBUG - Pair Your Device</title>
</head>
<body>
    <h1>DEBUG MODE - Pairing Page</h1>
    <p>Device ID: <?php echo htmlspecialchars($deviceId); ?></p>
    <p>Pairing Code: <?php echo htmlspecialchars($pairingCode); ?></p>
    <p>Pairing URL: <?php echo htmlspecialchars($pairingUrl); ?></p>
    <p><a href="<?php echo htmlspecialchars($pairingUrl); ?>">Click to pair</a></p>
    <p style="margin-top: 20px; color: green;">✅ Page loaded successfully! Check HTML source for debug comments.</p>
</body>
</html>

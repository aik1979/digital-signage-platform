<?php
/**
 * Mobile Pairing Interface
 * Allows users to pair a device using their smartphone
 */

session_start();
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/Database.php';
require_once __DIR__ . '/includes/Auth.php';
require_once __DIR__ . '/includes/functions.php';

$db = Database::getInstance();
$auth = new Auth($db);

// Get pairing code from URL or form
$pairingCode = $_GET['code'] ?? $_POST['pairing_code'] ?? '';
$pairingCode = strtoupper(trim($pairingCode));

$error = '';
$success = '';
$pairingInfo = null;

// Check if user is logged in
if (!$auth->isLoggedIn()) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header('Location: /index.php?page=login');
    exit;
}

$userId = $auth->getUserId();

// Validate pairing code
if ($pairingCode) {
    $pairingInfo = $db->fetchOne(
        "SELECT * FROM device_pairing WHERE pairing_code = ? AND status = 'pending' AND expires_at > NOW()",
        [$pairingCode]
    );
    
    if (!$pairingInfo) {
        $error = 'Invalid or expired pairing code. Please check the code and try again.';
        $pairingCode = '';
    }
}

// Handle pairing form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'pair_device') {
    $screenName = sanitize($_POST['screen_name'] ?? '');
    $playlistId = intval($_POST['playlist_id'] ?? 0);
    $code = $_POST['pairing_code'] ?? '';
    
    if (empty($screenName)) {
        $error = 'Please enter a screen name.';
    } elseif ($playlistId <= 0) {
        $error = 'Please select a playlist.';
    } else {
        // Verify playlist belongs to user
        $playlist = $db->fetchOne("SELECT id FROM playlists WHERE id = ? AND user_id = ?", [$playlistId, $userId]);
        
        if (!$playlist) {
            $error = 'Invalid playlist selected.';
        } else {
            // Get pairing info
            $pairing = $db->fetchOne(
                "SELECT * FROM device_pairing WHERE pairing_code = ? AND status = 'pending'",
                [$code]
            );
            
            if (!$pairing) {
                $error = 'Pairing session expired. Please try again.';
            } else {
                // Generate device key
                $deviceKey = bin2hex(random_bytes(16));
                
                // Create screen
                $screenId = $db->insert('screens', [
                    'user_id' => $userId,
                    'name' => $screenName,
                    'device_key' => $deviceKey,
                    'device_id' => $pairing['device_id'],
                    'pairing_code' => $code,
                    'current_playlist_id' => $playlistId,
                    'is_active' => 1,
                    'is_online' => 0
                ]);
                
                // Update pairing status
                $db->update('device_pairing', [
                    'screen_id' => $screenId,
                    'status' => 'paired',
                    'paired_at' => date('Y-m-d H:i:s')
                ], 'id = :id', ['id' => $pairing['id']]);
                
                // Log activity
                logActivity($db, $userId, 'screen_paired', 'screen', $screenId, "Paired device: $screenName");
                
                $success = true;
                $viewerUrl = rtrim(APP_URL, '/') . '/viewer-v2.php?key=' . $deviceKey;
            }
        }
    }
}

// Get user's playlists
$playlists = $db->fetchAll(
    "SELECT id, name, is_default FROM playlists WHERE user_id = ? AND is_active = 1 ORDER BY is_default DESC, name ASC",
    [$userId]
);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pair Device - Digital Signage Platform</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
    </style>
</head>
<body class="flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-8">
        <?php if ($success): ?>
            <!-- Success State -->
            <div class="text-center">
                <div class="text-6xl mb-4">✅</div>
                <h1 class="text-3xl font-bold text-gray-800 mb-2">Device Paired!</h1>
                <p class="text-gray-600 mb-6">Your screen has been successfully configured.</p>
                
                <div class="bg-gray-50 rounded-lg p-6 mb-6">
                    <p class="text-sm text-gray-600 mb-4">Scan this QR code with the device to complete setup:</p>
                    <div id="viewerQR" class="flex justify-center mb-4"></div>
                    <p class="text-xs text-gray-500">Or manually navigate to:</p>
                    <p class="text-xs font-mono bg-white p-2 rounded mt-2 break-all"><?php echo $viewerUrl; ?></p>
                </div>
                
                <a href="/index.php?page=screens" class="inline-block bg-gradient-to-r from-blue-500 to-purple-600 text-white font-semibold py-3 px-6 rounded-lg hover:from-blue-600 hover:to-purple-700 transition">
                    Go to Screens
                </a>
            </div>
            
            <script>
                new QRCode(document.getElementById("viewerQR"), {
                    text: "<?php echo $viewerUrl; ?>",
                    width: 200,
                    height: 200,
                    correctLevel: QRCode.CorrectLevel.H
                });
            </script>
            
        <?php elseif (!$pairingCode): ?>
            <!-- Enter Code State -->
            <div class="text-center mb-6">
                <div class="text-5xl mb-4">📱</div>
                <h1 class="text-2xl font-bold text-gray-800 mb-2">Pair Your Device</h1>
                <p class="text-gray-600">Enter the pairing code shown on your screen</p>
            </div>
            
            <?php if ($error): ?>
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-4">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="mb-6">
                    <label class="block text-gray-700 font-semibold mb-2">Pairing Code</label>
                    <input type="text" 
                           name="pairing_code" 
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent text-center text-2xl font-mono uppercase tracking-widest" 
                           placeholder="ABC123"
                           maxlength="6"
                           required
                           autofocus>
                    <p class="text-sm text-gray-500 mt-2">Enter the 6-character code from your device</p>
                </div>
                
                <button type="submit" class="w-full bg-gradient-to-r from-blue-500 to-purple-600 text-white font-semibold py-3 px-6 rounded-lg hover:from-blue-600 hover:to-purple-700 transition">
                    Continue
                </button>
            </form>
            
        <?php else: ?>
            <!-- Configure Device State -->
            <div class="text-center mb-6">
                <div class="text-5xl mb-4">⚙️</div>
                <h1 class="text-2xl font-bold text-gray-800 mb-2">Configure Your Screen</h1>
                <p class="text-gray-600">Set up your digital signage display</p>
            </div>
            
            <?php if ($error): ?>
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-4">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <input type="hidden" name="action" value="pair_device">
                <input type="hidden" name="pairing_code" value="<?php echo htmlspecialchars($pairingCode); ?>">
                
                <div class="mb-4">
                    <label class="block text-gray-700 font-semibold mb-2">Screen Name</label>
                    <input type="text" 
                           name="screen_name" 
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent" 
                           placeholder="e.g., Lobby Display, Kitchen Screen"
                           required>
                    <p class="text-sm text-gray-500 mt-1">Give this screen a memorable name</p>
                </div>
                
                <div class="mb-6">
                    <label class="block text-gray-700 font-semibold mb-2">Select Playlist</label>
                    <select name="playlist_id" 
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                            required>
                        <option value="">Choose a playlist...</option>
                        <?php foreach ($playlists as $playlist): ?>
                            <option value="<?php echo $playlist['id']; ?>" <?php echo $playlist['is_default'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($playlist['name']); ?>
                                <?php echo $playlist['is_default'] ? ' (Default)' : ''; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="text-sm text-gray-500 mt-1">Choose what content to display on this screen</p>
                </div>
                
                <button type="submit" class="w-full bg-gradient-to-r from-blue-500 to-purple-600 text-white font-semibold py-3 px-6 rounded-lg hover:from-blue-600 hover:to-purple-700 transition">
                    Pair Device
                </button>
            </form>
        <?php endif; ?>
        
        <div class="mt-6 text-center">
            <a href="/index.php?page=dashboard" class="text-sm text-gray-500 hover:text-gray-700">← Back to Dashboard</a>
        </div>
    </div>
</body>
</html>

<?php
$userId = $auth->getUserId();

// Handle screen actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    // Add new screen
    if ($_POST['action'] === 'add_screen') {
        $name = sanitize($_POST['name'] ?? '');
        $orientation = sanitize($_POST['orientation'] ?? 'landscape');
        $resolution = sanitize($_POST['resolution'] ?? '1920x1080');
        $notes = sanitize($_POST['notes'] ?? '');
        
        if (empty($name)) {
            setFlashMessage('error', 'Screen name is required.');
        } else {
            try {
                $deviceKey = generateDeviceKey();
                
                $screenId = $db->insert('screens', [
                    'user_id' => $userId,
                    'name' => $name,
                    'device_key' => $deviceKey,
                    'orientation' => $orientation,
                    'resolution' => $resolution,
                    'notes' => $notes,
                    'is_active' => 1
                ]);
                
                logActivity($db, $userId, 'screen_created', 'screen', $screenId, 'Created screen: ' . $name);
                setFlashMessage('success', 'Screen added successfully! Device key: ' . $deviceKey);
                redirect('screens');
            } catch (Exception $e) {
                setFlashMessage('error', 'Failed to add screen. Please try again.');
            }
        }
    }
    
    // Edit screen
    if ($_POST['action'] === 'edit_screen') {
        $screenId = intval($_POST['screen_id'] ?? 0);
        $name = sanitize($_POST['name'] ?? '');
        $orientation = sanitize($_POST['orientation'] ?? 'landscape');
        $resolution = sanitize($_POST['resolution'] ?? '1920x1080');
        $notes = sanitize($_POST['notes'] ?? '');
        $currentPlaylistId = !empty($_POST['current_playlist_id']) ? intval($_POST['current_playlist_id']) : null;
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        
        if (empty($name)) {
            setFlashMessage('error', 'Screen name is required.');
        } else {
            // Verify ownership
            $screen = $db->fetchOne("SELECT id FROM screens WHERE id = ? AND user_id = ?", [$screenId, $userId]);
            
            if (!$screen) {
                setFlashMessage('error', 'Screen not found.');
            } else {
                try {
                    $db->update('screens', [
                        'name' => $name,
                        'orientation' => $orientation,
                        'resolution' => $resolution,
                        'notes' => $notes,
                        'current_playlist_id' => $currentPlaylistId,
                        'is_active' => $isActive
                    ], 'id = :id', ['id' => $screenId]);
                    
                    logActivity($db, $userId, 'screen_updated', 'screen', $screenId, 'Updated screen: ' . $name);
                    setFlashMessage('success', 'Screen updated successfully!');
                    redirect('screens');
                } catch (Exception $e) {
                    setFlashMessage('error', 'Failed to update screen. Please try again.');
                }
            }
        }
    }
    
    // Delete screen
    if ($_POST['action'] === 'delete_screen') {
        $screenId = intval($_POST['screen_id'] ?? 0);
        
        // Verify ownership
        $screen = $db->fetchOne("SELECT name FROM screens WHERE id = ? AND user_id = ?", [$screenId, $userId]);
        
        if (!$screen) {
            setFlashMessage('error', 'Screen not found.');
        } else {
            try {
                $db->delete('screens', 'id = :id', ['id' => $screenId]);
                logActivity($db, $userId, 'screen_deleted', 'screen', $screenId, 'Deleted screen: ' . $screen['name']);
                setFlashMessage('success', 'Screen deleted successfully!');
                redirect('screens');
            } catch (Exception $e) {
                setFlashMessage('error', 'Failed to delete screen. Please try again.');
            }
        }
    }
}

// Handle GET actions (view device key)
$showDeviceKey = null;
if (isset($_GET['view_key'])) {
    $screenId = intval($_GET['view_key']);
    $screen = $db->fetchOne("SELECT device_key, name FROM screens WHERE id = ? AND user_id = ?", [$screenId, $userId]);
    if ($screen) {
        $showDeviceKey = $screen;
    }
}

// Get all screens for current user
$screens = $db->fetchAll(
    "SELECT s.*, 
     (SELECT COUNT(*) FROM schedules WHERE screen_id = s.id) as schedule_count
     FROM screens s 
     WHERE s.user_id = ? 
     ORDER BY s.created_at DESC",
    [$userId]
);

// Get screen for editing
$editScreen = null;
if (isset($_GET['edit'])) {
    $screenId = intval($_GET['edit']);
    $editScreen = $db->fetchOne("SELECT * FROM screens WHERE id = ? AND user_id = ?", [$screenId, $userId]);
}
?>

<div class="space-y-8">
    <!-- Page Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-white mb-2">Screens</h1>
            <p class="text-gray-400">Manage your digital signage displays</p>
        </div>
        <button type="button" class="bg-gradient-to-r from-dsp-blue to-blue-600 text-white font-semibold py-2 px-6 rounded-lg hover:from-blue-600 hover:to-blue-700 transition transform hover:scale-105 shadow-lg" onclick="toggleModal('addScreenModal')">
            ➕ Add Screen
        </button>
    </div>

    <?php if (empty($screens)): ?>
    <!-- Empty State -->
    <div class="bg-gray-800 border border-gray-700 rounded-lg p-12 text-center">
        <div class="text-6xl mb-4">📺</div>
        <h2 class="text-2xl font-bold text-white mb-2">No Screens Yet</h2>
        <p class="text-gray-400 mb-6">Add your first screen to start displaying content on your digital signage.</p>
        <button type="button" class="bg-gradient-to-r from-dsp-blue to-blue-600 text-white font-semibold py-2 px-6 rounded-lg hover:from-blue-600 hover:to-blue-700 transition transform hover:scale-105 shadow-lg" onclick="toggleModal('addScreenModal')">
            Add Your First Screen
        </button>
    </div>
    <?php else: ?>
    <!-- Screens Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($screens as $screen): ?>
        <div class="bg-gray-800 border <?php echo isScreenOnline($screen['last_heartbeat']) ? 'border-green-600' : 'border-gray-700'; ?> rounded-lg p-6 shadow-lg hover:shadow-xl transition-shadow">
            <!-- Screen Header -->
            <div class="flex items-start justify-between mb-4">
                <h3 class="text-xl font-bold text-white"><?php echo sanitize($screen['name']); ?></h3>
                <?php if (isScreenOnline($screen['last_heartbeat'])): ?>
                    <span class="inline-block bg-green-600 text-white text-xs px-2 py-1 rounded">● Online</span>
                <?php else: ?>
                    <span class="inline-block bg-red-600 text-white text-xs px-2 py-1 rounded">● Offline</span>
                <?php endif; ?>
            </div>
            
            <!-- Screen Info -->
            <div class="space-y-2 mb-4 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-400">Orientation:</span>
                    <span class="text-white"><?php echo ucfirst($screen['orientation']); ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-400">Resolution:</span>
                    <span class="text-white"><?php echo $screen['resolution']; ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-400">Last Seen:</span>
                    <span class="text-white"><?php echo timeAgo($screen['last_heartbeat']); ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-400">Schedules:</span>
                    <span class="text-white"><?php echo $screen['schedule_count']; ?></span>
                </div>
                <?php if (!empty($screen['notes'])): ?>
                <div class="pt-2 border-t border-gray-700">
                    <span class="text-gray-400 block mb-1">Notes:</span>
                    <span class="text-white text-xs"><?php echo sanitize($screen['notes']); ?></span>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Screen Actions -->
            <div class="grid grid-cols-2 gap-2">
                <a href="viewer.php?key=<?php echo urlencode($screen['device_key']); ?>" target="_blank" class="bg-dsp-blue text-white text-center font-semibold py-2 px-3 text-sm rounded-md hover:bg-blue-600 transition">
                    🖥️ Test
                </a>
                <a href="?page=screens&view_key=<?php echo $screen['id']; ?>" class="bg-gray-700 text-white text-center font-semibold py-2 px-3 text-sm rounded-md hover:bg-gray-600 transition">
                    🔑 Key
                </a>
                <a href="?page=screens&edit=<?php echo $screen['id']; ?>" class="bg-gray-700 text-white text-center font-semibold py-2 px-3 text-sm rounded-md hover:bg-gray-600 transition">
                    ✏️ Edit
                </a>
                <button type="button" class="bg-gradient-to-r from-dsp-red to-red-600 text-white font-semibold py-2 px-3 text-sm rounded-md hover:from-red-600 hover:to-red-700 transition" onclick="confirmDelete(<?php echo $screen['id']; ?>, '<?php echo addslashes($screen['name']); ?>')">
                    🗑️ Delete
                </button>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<!-- Add Screen Modal -->
<div id="addScreenModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" style="display: none;">
    <div class="bg-gray-800 rounded-lg shadow-2xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto border border-gray-700">
        <div class="flex items-center justify-between p-6 border-b border-gray-700">
            <h2 class="text-2xl font-bold text-white">Add New Screen</h2>
            <button type="button" class="text-gray-400 hover:text-white text-3xl leading-none" onclick="toggleModal('addScreenModal')">&times;</button>
        </div>
        <form method="POST" action="?page=screens">
            <div class="p-6 space-y-4">
                <input type="hidden" name="action" value="add_screen">
                
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-300 mb-2">Screen Name *</label>
                    <input type="text" id="name" name="name" required 
                           placeholder="e.g., Main Menu Board, Front Window Display"
                           class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-dsp-blue focus:border-transparent transition">
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="orientation" class="block text-sm font-medium text-gray-300 mb-2">Orientation</label>
                        <select id="orientation" name="orientation" class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-dsp-blue focus:border-transparent transition">
                            <option value="landscape">Landscape</option>
                            <option value="portrait">Portrait</option>
                        </select>
                    </div>
                    
                    <div>
                        <label for="resolution" class="block text-sm font-medium text-gray-300 mb-2">Resolution</label>
                        <select id="resolution" name="resolution" class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-dsp-blue focus:border-transparent transition">
                            <option value="1920x1080">1920x1080 (Full HD)</option>
                            <option value="1280x720">1280x720 (HD)</option>
                            <option value="3840x2160">3840x2160 (4K)</option>
                        </select>
                    </div>
                </div>
                
                <div>
                    <label for="notes" class="block text-sm font-medium text-gray-300 mb-2">Notes (Optional)</label>
                    <textarea id="notes" name="notes" rows="3" 
                              placeholder="Location, purpose, or any other notes..."
                              class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-dsp-blue focus:border-transparent transition"></textarea>
                </div>
            </div>
            
            <div class="flex justify-end space-x-3 p-6 border-t border-gray-700 bg-gray-900">
                <button type="button" class="bg-gray-700 text-white font-semibold py-2 px-6 rounded-lg hover:bg-gray-600 transition" onclick="toggleModal('addScreenModal')">Cancel</button>
                <button type="submit" class="bg-gradient-to-r from-dsp-blue to-blue-600 text-white font-semibold py-2 px-6 rounded-lg hover:from-blue-600 hover:to-blue-700 transition shadow-lg">Add Screen</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Screen Modal -->
<?php if ($editScreen): ?>
<div id="editScreenModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-gray-800 rounded-lg shadow-2xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto border border-gray-700">
        <div class="flex items-center justify-between p-6 border-b border-gray-700">
            <h2 class="text-2xl font-bold text-white">Edit Screen</h2>
            <button type="button" class="text-gray-400 hover:text-white text-3xl leading-none" onclick="window.location.href='?page=screens'">&times;</button>
        </div>
        <form method="POST" action="?page=screens">
            <div class="p-6 space-y-4">
                <input type="hidden" name="action" value="edit_screen">
                <input type="hidden" name="screen_id" value="<?php echo $editScreen['id']; ?>">
                
                <div>
                    <label for="edit_name" class="block text-sm font-medium text-gray-300 mb-2">Screen Name *</label>
                    <input type="text" id="edit_name" name="name" required 
                           value="<?php echo sanitize($editScreen['name']); ?>"
                           class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-dsp-blue focus:border-transparent transition">
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="edit_orientation" class="block text-sm font-medium text-gray-300 mb-2">Orientation</label>
                        <select id="edit_orientation" name="orientation" class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-dsp-blue focus:border-transparent transition">
                            <option value="landscape" <?php echo $editScreen['orientation'] === 'landscape' ? 'selected' : ''; ?>>Landscape</option>
                            <option value="portrait" <?php echo $editScreen['orientation'] === 'portrait' ? 'selected' : ''; ?>>Portrait</option>
                        </select>
                    </div>
                    
                    <div>
                        <label for="edit_resolution" class="block text-sm font-medium text-gray-300 mb-2">Resolution</label>
                        <select id="edit_resolution" name="resolution" class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-dsp-blue focus:border-transparent transition">
                            <option value="1920x1080" <?php echo $editScreen['resolution'] === '1920x1080' ? 'selected' : ''; ?>>1920x1080 (Full HD)</option>
                            <option value="1280x720" <?php echo $editScreen['resolution'] === '1280x720' ? 'selected' : ''; ?>>1280x720 (HD)</option>
                            <option value="3840x2160" <?php echo $editScreen['resolution'] === '3840x2160' ? 'selected' : ''; ?>>3840x2160 (4K)</option>
                        </select>
                    </div>
                </div>
                
                <div>
                    <label for="edit_notes" class="block text-sm font-medium text-gray-300 mb-2">Notes</label>
                    <textarea id="edit_notes" name="notes" rows="3" class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-dsp-blue focus:border-transparent transition"><?php echo sanitize($editScreen['notes'] ?? ''); ?></textarea>
                </div>
                
                <div>
                    <label for="edit_playlist" class="block text-sm font-medium text-gray-300 mb-2">Assigned Playlist</label>
                    <select id="edit_playlist" name="current_playlist_id" class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-dsp-blue focus:border-transparent transition">
                        <option value="">-- Use Default Playlist --</option>
                        <?php
                        $playlists = $db->fetchAll("SELECT id, name, is_default FROM playlists WHERE user_id = ? ORDER BY is_default DESC, name ASC", [$userId]);
                        foreach ($playlists as $pl) {
                            $selected = ($editScreen['current_playlist_id'] == $pl['id']) ? 'selected' : '';
                            $defaultLabel = $pl['is_default'] ? ' (Default)' : '';
                            echo '<option value="' . $pl['id'] . '" ' . $selected . '>' . htmlspecialchars($pl['name']) . $defaultLabel . '</option>';
                        }
                        ?>
                    </select>
                    <p class="text-xs text-gray-400 mt-1">Select which playlist to display on this screen</p>
                </div>
                
                <div class="flex items-center">
                    <input type="checkbox" id="is_active" name="is_active" <?php echo $editScreen['is_active'] ? 'checked' : ''; ?> class="w-4 h-4 text-dsp-blue bg-gray-700 border-gray-600 rounded focus:ring-dsp-blue focus:ring-2">
                    <label for="is_active" class="ml-2 text-sm text-gray-300">Screen is active</label>
                </div>
            </div>
            
            <div class="flex justify-end space-x-3 p-6 border-t border-gray-700 bg-gray-900">
                <button type="button" class="bg-gray-700 text-white font-semibold py-2 px-6 rounded-lg hover:bg-gray-600 transition" onclick="window.location.href='?page=screens'">Cancel</button>
                <button type="submit" class="bg-gradient-to-r from-dsp-blue to-blue-600 text-white font-semibold py-2 px-6 rounded-lg hover:from-blue-600 hover:to-blue-700 transition shadow-lg">Update Screen</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- View Device Key Modal -->
<?php if ($showDeviceKey): ?>
<div id="deviceKeyModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-gray-800 rounded-lg shadow-2xl max-w-2xl w-full mx-4 border border-gray-700">
        <div class="flex items-center justify-between p-6 border-b border-gray-700">
            <h2 class="text-2xl font-bold text-white">Device Key: <?php echo sanitize($showDeviceKey['name']); ?></h2>
            <button type="button" class="text-gray-400 hover:text-white text-3xl leading-none" onclick="window.location.href='?page=screens'">&times;</button>
        </div>
        <div class="p-6 space-y-4">
            <p class="text-white font-semibold">Use this key to configure your Raspberry Pi:</p>
            <div class="flex items-center space-x-3 bg-gray-900 p-4 rounded-lg border border-gray-700">
                <code id="deviceKey" class="flex-1 text-dsp-blue font-mono text-lg"><?php echo $showDeviceKey['device_key']; ?></code>
                <button type="button" class="bg-gray-700 text-white font-semibold py-2 px-4 text-sm rounded-md hover:bg-gray-600 transition" onclick="copyDeviceKey()">
                    📋 Copy
                </button>
            </div>
            <div class="bg-yellow-900 bg-opacity-20 border border-yellow-700 rounded-lg p-4">
                <p class="text-yellow-400 text-sm">⚠️ <strong>Keep this key secure!</strong> Anyone with this key can connect a device to your account.</p>
            </div>
            <p class="text-gray-400 text-sm">
                📖 See the <a href="#" target="_blank" class="text-dsp-blue hover:text-blue-400">Raspberry Pi Setup Guide</a> for installation instructions.
            </p>
        </div>
        <div class="flex justify-end p-6 border-t border-gray-700 bg-gray-900">
            <button type="button" class="bg-gradient-to-r from-dsp-blue to-blue-600 text-white font-semibold py-2 px-6 rounded-lg hover:from-blue-600 hover:to-blue-700 transition shadow-lg" onclick="window.location.href='?page=screens'">Close</button>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Delete Confirmation Form -->
<form id="deleteForm" method="POST" action="?page=screens" style="display: none;">
    <input type="hidden" name="action" value="delete_screen">
    <input type="hidden" name="screen_id" id="deleteScreenId">
</form>

<script>
function toggleModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal.style.display === 'none') {
        modal.style.display = 'flex';
    } else {
        modal.style.display = 'none';
    }
}

function confirmDelete(screenId, screenName) {
    if (confirm('Are you sure you want to delete "' + screenName + '"?\n\nThis action cannot be undone.')) {
        document.getElementById('deleteScreenId').value = screenId;
        document.getElementById('deleteForm').submit();
    }
}

function copyDeviceKey() {
    const keyElement = document.getElementById('deviceKey');
    const textArea = document.createElement('textarea');
    textArea.value = keyElement.textContent;
    document.body.appendChild(textArea);
    textArea.select();
    document.execCommand('copy');
    document.body.removeChild(textArea);
    alert('Device key copied to clipboard!');
}

// Close modal when clicking outside
window.onclick = function(event) {
    if (event.target.classList.contains('fixed')) {
        const modals = document.querySelectorAll('.fixed');
        modals.forEach(modal => {
            if (modal.contains(event.target) && event.target === modal) {
                if (window.location.search.includes('edit') || window.location.search.includes('view_key')) {
                    window.location.href = '?page=screens';
                } else {
                    modal.style.display = 'none';
                }
            }
        });
    }
}
</script>

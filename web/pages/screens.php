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

<div class="page-header">
    <div class="header-content">
        <div>
            <h1>Screens</h1>
            <p>Manage your digital signage displays</p>
        </div>
        <button type="button" class="btn btn-primary" onclick="toggleModal('addScreenModal')">
            ➕ Add Screen
        </button>
    </div>
</div>

<?php if (empty($screens)): ?>
<div class="empty-state">
    <h2>📺 No Screens Yet</h2>
    <p>Add your first screen to start displaying content on your digital signage.</p>
    <button type="button" class="btn btn-primary" onclick="toggleModal('addScreenModal')">
        Add Your First Screen
    </button>
</div>
<?php else: ?>
<div class="screens-grid">
    <?php foreach ($screens as $screen): ?>
    <div class="screen-card <?php echo isScreenOnline($screen['last_heartbeat']) ? 'online' : 'offline'; ?>">
        <div class="screen-header">
            <h3><?php echo sanitize($screen['name']); ?></h3>
            <div class="screen-status">
                <?php if (isScreenOnline($screen['last_heartbeat'])): ?>
                    <span class="badge badge-success">● Online</span>
                <?php else: ?>
                    <span class="badge badge-error">● Offline</span>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="screen-info">
            <div class="info-row">
                <span class="label">Orientation:</span>
                <span class="value"><?php echo ucfirst($screen['orientation']); ?></span>
            </div>
            <div class="info-row">
                <span class="label">Resolution:</span>
                <span class="value"><?php echo $screen['resolution']; ?></span>
            </div>
            <div class="info-row">
                <span class="label">Last Seen:</span>
                <span class="value"><?php echo timeAgo($screen['last_heartbeat']); ?></span>
            </div>
            <div class="info-row">
                <span class="label">Schedules:</span>
                <span class="value"><?php echo $screen['schedule_count']; ?></span>
            </div>
            <?php if (!empty($screen['notes'])): ?>
            <div class="info-row full-width">
                <span class="label">Notes:</span>
                <span class="value"><?php echo sanitize($screen['notes']); ?></span>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="screen-actions">
            <a href="?page=screens&view_key=<?php echo $screen['id']; ?>" class="btn btn-secondary btn-sm">
                🔑 View Key
            </a>
            <a href="?page=screens&edit=<?php echo $screen['id']; ?>" class="btn btn-secondary btn-sm">
                ✏️ Edit
            </a>
            <button type="button" class="btn btn-danger btn-sm" onclick="confirmDelete(<?php echo $screen['id']; ?>, '<?php echo addslashes($screen['name']); ?>')">
                🗑️ Delete
            </button>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Add Screen Modal -->
<div id="addScreenModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Add New Screen</h2>
            <button type="button" class="close-btn" onclick="toggleModal('addScreenModal')">&times;</button>
        </div>
        <form method="POST" action="?page=screens">
            <input type="hidden" name="action" value="add_screen">
            
            <div class="form-group">
                <label for="name">Screen Name *</label>
                <input type="text" id="name" name="name" required 
                       placeholder="e.g., Main Menu Board, Front Window Display">
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="orientation">Orientation</label>
                    <select id="orientation" name="orientation">
                        <option value="landscape">Landscape</option>
                        <option value="portrait">Portrait</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="resolution">Resolution</label>
                    <select id="resolution" name="resolution">
                        <option value="1920x1080">1920x1080 (Full HD)</option>
                        <option value="1280x720">1280x720 (HD)</option>
                        <option value="3840x2160">3840x2160 (4K)</option>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label for="notes">Notes (Optional)</label>
                <textarea id="notes" name="notes" rows="3" 
                          placeholder="Location, purpose, or any other notes..."></textarea>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="toggleModal('addScreenModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Add Screen</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Screen Modal -->
<?php if ($editScreen): ?>
<div id="editScreenModal" class="modal" style="display: block;">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Edit Screen</h2>
            <button type="button" class="close-btn" onclick="window.location.href='?page=screens'">&times;</button>
        </div>
        <form method="POST" action="?page=screens">
            <input type="hidden" name="action" value="edit_screen">
            <input type="hidden" name="screen_id" value="<?php echo $editScreen['id']; ?>">
            
            <div class="form-group">
                <label for="edit_name">Screen Name *</label>
                <input type="text" id="edit_name" name="name" required 
                       value="<?php echo sanitize($editScreen['name']); ?>">
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="edit_orientation">Orientation</label>
                    <select id="edit_orientation" name="orientation">
                        <option value="landscape" <?php echo $editScreen['orientation'] === 'landscape' ? 'selected' : ''; ?>>Landscape</option>
                        <option value="portrait" <?php echo $editScreen['orientation'] === 'portrait' ? 'selected' : ''; ?>>Portrait</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="edit_resolution">Resolution</label>
                    <select id="edit_resolution" name="resolution">
                        <option value="1920x1080" <?php echo $editScreen['resolution'] === '1920x1080' ? 'selected' : ''; ?>>1920x1080 (Full HD)</option>
                        <option value="1280x720" <?php echo $editScreen['resolution'] === '1280x720' ? 'selected' : ''; ?>>1280x720 (HD)</option>
                        <option value="3840x2160" <?php echo $editScreen['resolution'] === '3840x2160' ? 'selected' : ''; ?>>3840x2160 (4K)</option>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label for="edit_notes">Notes</label>
                <textarea id="edit_notes" name="notes" rows="3"><?php echo sanitize($editScreen['notes'] ?? ''); ?></textarea>
            </div>
            
            <div class="form-group">
                <label>
                    <input type="checkbox" name="is_active" <?php echo $editScreen['is_active'] ? 'checked' : ''; ?>>
                    Screen is active
                </label>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="window.location.href='?page=screens'">Cancel</button>
                <button type="submit" class="btn btn-primary">Update Screen</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- View Device Key Modal -->
<?php if ($showDeviceKey): ?>
<div id="deviceKeyModal" class="modal" style="display: block;">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Device Key: <?php echo sanitize($showDeviceKey['name']); ?></h2>
            <button type="button" class="close-btn" onclick="window.location.href='?page=screens'">&times;</button>
        </div>
        <div class="device-key-display">
            <p><strong>Use this key to configure your Raspberry Pi:</strong></p>
            <div class="key-box">
                <code id="deviceKey"><?php echo $showDeviceKey['device_key']; ?></code>
                <button type="button" class="btn btn-secondary btn-sm" onclick="copyDeviceKey()">
                    📋 Copy
                </button>
            </div>
            <p class="help-text">
                ⚠️ Keep this key secure! Anyone with this key can connect a device to your account.
            </p>
            <p class="help-text">
                📖 See the <a href="#" target="_blank">Raspberry Pi Setup Guide</a> for installation instructions.
            </p>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-primary" onclick="window.location.href='?page=screens'">Close</button>
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
        modal.style.display = 'block';
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
    if (event.target.classList.contains('modal')) {
        event.target.style.display = 'none';
        if (window.location.search.includes('edit') || window.location.search.includes('view_key')) {
            window.location.href = '?page=screens';
        }
    }
}
</script>

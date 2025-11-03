<?php
$userId = $auth->getUserId();

// Handle playlist actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    // Create playlist
    if ($_POST['action'] === 'create_playlist') {
        $name = sanitize($_POST['name'] ?? '');
        $description = sanitize($_POST['description'] ?? '');
        $transition = sanitize($_POST['transition'] ?? 'fade');
        $isDefault = isset($_POST['is_default']) ? 1 : 0;
        
        if (empty($name)) {
            setFlashMessage('error', 'Playlist name is required.');
        } else {
            try {
                // If setting as default, unset other defaults
                if ($isDefault) {
                    $db->query("UPDATE playlists SET is_default = 0 WHERE user_id = ?", [$userId]);
                }
                
                $playlistId = $db->insert('playlists', [
                    'user_id' => $userId,
                    'name' => $name,
                    'description' => $description,
                    'transition' => $transition,
                    'is_default' => $isDefault,
                    'is_active' => 1
                ]);
                
                logActivity($db, $userId, 'playlist_created', 'playlist', $playlistId, 'Created playlist: ' . $name);
                setFlashMessage('success', 'Playlist created successfully!');
                redirect('playlists', ['edit' => $playlistId]);
            } catch (Exception $e) {
                setFlashMessage('error', 'Failed to create playlist.');
            }
        }
    }
    
    // Update playlist
    if ($_POST['action'] === 'update_playlist') {
        $playlistId = intval($_POST['playlist_id'] ?? 0);
        $name = sanitize($_POST['name'] ?? '');
        $description = sanitize($_POST['description'] ?? '');
        $transition = sanitize($_POST['transition'] ?? 'fade');
        $isDefault = isset($_POST['is_default']) ? 1 : 0;
        
        $playlist = $db->fetchOne("SELECT id FROM playlists WHERE id = ? AND user_id = ?", [$playlistId, $userId]);
        
        if (!$playlist) {
            setFlashMessage('error', 'Playlist not found.');
        } else {
            try {
                if ($isDefault) {
                    $db->query("UPDATE playlists SET is_default = 0 WHERE user_id = ?", [$userId]);
                }
                
                $db->update('playlists', [
                    'name' => $name,
                    'description' => $description,
                    'transition' => $transition,
                    'is_default' => $isDefault
                ], 'id = :id', ['id' => $playlistId]);
                
                logActivity($db, $userId, 'playlist_updated', 'playlist', $playlistId, 'Updated playlist: ' . $name);
                setFlashMessage('success', 'Playlist updated successfully!');
                redirect('playlists', ['edit' => $playlistId]);
            } catch (Exception $e) {
                setFlashMessage('error', 'Failed to update playlist.');
            }
        }
    }
    
    // Save playlist items (AJAX)
    if ($_POST['action'] === 'save_playlist_items') {
        $playlistId = intval($_POST['playlist_id'] ?? 0);
        $items = json_decode($_POST['items'] ?? '[]', true);
        
        $playlist = $db->fetchOne("SELECT id FROM playlists WHERE id = ? AND user_id = ?", [$playlistId, $userId]);
        
        if (!$playlist) {
            jsonResponse(['success' => false, 'message' => 'Playlist not found'], 404);
        }
        
        try {
            // Delete existing items
            $db->delete('playlist_items', 'playlist_id = :id', ['id' => $playlistId]);
            
            // Insert new items
            foreach ($items as $index => $item) {
                $db->insert('playlist_items', [
                    'playlist_id' => $playlistId,
                    'content_id' => intval($item['content_id']),
                    'sort_order' => $index,
                    'duration_override' => !empty($item['duration']) ? intval($item['duration']) : null
                ]);
            }
            
            logActivity($db, $userId, 'playlist_items_updated', 'playlist', $playlistId, 'Updated playlist items');
            jsonResponse(['success' => true, 'message' => 'Playlist saved successfully']);
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'message' => 'Failed to save playlist: ' . $e->getMessage()], 500);
        }
    }
    
    // Delete playlist
    if ($_POST['action'] === 'delete_playlist') {
        $playlistId = intval($_POST['playlist_id'] ?? 0);
        
        $playlist = $db->fetchOne("SELECT name FROM playlists WHERE id = ? AND user_id = ?", [$playlistId, $userId]);
        
        if (!$playlist) {
            setFlashMessage('error', 'Playlist not found.');
        } else {
            try {
                $db->delete('playlists', 'id = :id', ['id' => $playlistId]);
                logActivity($db, $userId, 'playlist_deleted', 'playlist', $playlistId, 'Deleted playlist: ' . $playlist['name']);
                setFlashMessage('success', 'Playlist deleted successfully!');
                redirect('playlists');
            } catch (Exception $e) {
                setFlashMessage('error', 'Failed to delete playlist.');
            }
        }
    }
}

// Get all playlists
$playlists = $db->fetchAll(
    "SELECT p.*, 
     (SELECT COUNT(*) FROM playlist_items WHERE playlist_id = p.id) as item_count,
     (SELECT COUNT(*) FROM screens WHERE current_playlist_id = p.id) as screen_count
     FROM playlists p 
     WHERE p.user_id = ? 
     ORDER BY p.is_default DESC, p.created_at DESC",
    [$userId]
);

// Get playlist for editing
$editPlaylist = null;
$playlistItems = [];
$availableContent = [];

if (isset($_GET['edit'])) {
    $playlistId = intval($_GET['edit']);
    $editPlaylist = $db->fetchOne("SELECT * FROM playlists WHERE id = ? AND user_id = ?", [$playlistId, $userId]);
    
    if ($editPlaylist) {
        // Get playlist items with content details
        $playlistItems = $db->fetchAll(
            "SELECT pi.*, c.title, c.file_type, c.thumbnail_path, c.file_path, c.duration as default_duration
             FROM playlist_items pi
             JOIN content c ON pi.content_id = c.id
             WHERE pi.playlist_id = ?
             ORDER BY pi.sort_order ASC",
            [$playlistId]
        );
        
        // Get available content not in playlist
        $usedContentIds = array_column($playlistItems, 'content_id');
        if (!empty($usedContentIds)) {
            $placeholders = implode(',', array_fill(0, count($usedContentIds), '?'));
            $availableContent = $db->fetchAll(
                "SELECT * FROM content WHERE user_id = ? AND id NOT IN ($placeholders) AND is_active = 1 ORDER BY created_at DESC",
                array_merge([$userId], $usedContentIds)
            );
        } else {
            $availableContent = $db->fetchAll(
                "SELECT * FROM content WHERE user_id = ? AND is_active = 1 ORDER BY created_at DESC",
                [$userId]
            );
        }
    }
}
?>

<div class="page-header">
    <div class="header-content">
        <div>
            <h1>Playlists</h1>
            <p>Create and manage content playlists</p>
        </div>
        <button type="button" class="btn btn-primary" onclick="toggleModal('createPlaylistModal')">
            ➕ Create Playlist
        </button>
    </div>
</div>

<?php if (!$editPlaylist): ?>
    <?php if (empty($playlists)): ?>
    <div class="empty-state">
        <h2>📋 No Playlists Yet</h2>
        <p>Create a playlist to organize your content for display on screens.</p>
        <button type="button" class="btn btn-primary" onclick="toggleModal('createPlaylistModal')">
            Create Your First Playlist
        </button>
    </div>
    <?php else: ?>
    <div class="playlists-grid">
        <?php foreach ($playlists as $playlist): ?>
        <div class="playlist-card">
            <div class="playlist-header">
                <h3><?php echo sanitize($playlist['name']); ?></h3>
                <?php if ($playlist['is_default']): ?>
                    <span class="badge badge-success">Default</span>
                <?php endif; ?>
            </div>
            
            <div class="playlist-info">
                <?php if ($playlist['description']): ?>
                <p class="playlist-description"><?php echo sanitize($playlist['description']); ?></p>
                <?php endif; ?>
                
                <div class="playlist-stats">
                    <span>📦 <?php echo $playlist['item_count']; ?> items</span>
                    <span>📺 <?php echo $playlist['screen_count']; ?> screens</span>
                </div>
            </div>
            
            <div class="playlist-actions">
                <a href="public_viewer.php?id=<?php echo $playlist['id']; ?>" target="_blank" class="btn btn-secondary btn-sm" title="View in browser">
                    🌐 View
                </a>
                <a href="?page=playlists&edit=<?php echo $playlist['id']; ?>" class="btn btn-primary btn-sm">
                    ✏️ Edit
                </a>
                <button type="button" class="btn btn-danger btn-sm" 
                        onclick="confirmDelete(<?php echo $playlist['id']; ?>, '<?php echo addslashes($playlist['name']); ?>')">
                    🗑️ Delete
                </button>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

<?php else: ?>
    <!-- Playlist Editor -->
    <div class="playlist-editor">
        <div class="editor-header">
            <div>
                <h2><?php echo sanitize($editPlaylist['name']); ?></h2>
                <p><?php echo sanitize($editPlaylist['description'] ?: 'No description'); ?></p>
            </div>
            <div class="editor-actions">
                <button type="button" class="btn btn-secondary" onclick="toggleModal('editPlaylistModal')">
                    ⚙️ Settings
                </button>
                <a href="?page=playlists" class="btn btn-secondary">← Back to Playlists</a>
            </div>
        </div>
        
        <div class="editor-content">
            <!-- Available Content -->
            <div class="content-library-panel">
                <h3>Available Content</h3>
                <div id="availableContent" class="content-list">
                    <?php if (empty($availableContent)): ?>
                        <p class="empty-message">All content has been added to this playlist.</p>
                    <?php else: ?>
                        <?php foreach ($availableContent as $content): ?>
                        <div class="content-item" data-content-id="<?php echo $content['id']; ?>" 
                             data-title="<?php echo htmlspecialchars($content['title']); ?>"
                             data-duration="<?php echo $content['duration']; ?>"
                             data-type="<?php echo $content['file_type']; ?>"
                             data-thumbnail="<?php echo $content['thumbnail_path'] ?: $content['file_path']; ?>">
                            <div class="content-thumbnail">
                                <?php if ($content['file_type'] === 'image'): ?>
                                    <img src="<?php echo $content['thumbnail_path'] ?: $content['file_path']; ?>" 
                                         alt="<?php echo sanitize($content['title']); ?>">
                                <?php else: ?>
                                    <div class="video-thumb">🎥</div>
                                <?php endif; ?>
                            </div>
                            <div class="content-details">
                                <strong><?php echo sanitize($content['title']); ?></strong>
                                <span><?php echo $content['duration']; ?>s</span>
                            </div>
                            <button type="button" class="btn-add" onclick="addToPlaylist(this.parentElement)">+</button>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Playlist Items -->
            <div class="playlist-panel">
                <h3>Playlist Items (Drag to Reorder)</h3>
                <div id="playlistItems" class="playlist-items">
                    <?php if (empty($playlistItems)): ?>
                        <p class="empty-message">No items in this playlist. Add content from the left.</p>
                    <?php else: ?>
                        <?php foreach ($playlistItems as $item): ?>
                        <div class="playlist-item" draggable="true" 
                             data-content-id="<?php echo $item['content_id']; ?>"
                             data-type="<?php echo $item['file_type']; ?>"
                             data-duration="<?php echo $item['duration_override'] ?: $item['default_duration']; ?>">
                            <div class="drag-handle">⋮⋮</div>
                            <div class="item-thumbnail">
                                <?php if ($item['file_type'] === 'image'): ?>
                                    <img src="<?php echo $item['thumbnail_path'] ?: $item['file_path']; ?>" 
                                         alt="<?php echo sanitize($item['title']); ?>">
                                <?php else: ?>
                                    <div class="video-thumb">🎥</div>
                                <?php endif; ?>
                            </div>
                            <div class="item-details">
                                <strong><?php echo sanitize($item['title']); ?></strong>
                                <?php if ($item['file_type'] === 'image'): ?>
                                    <input type="number" class="duration-input" 
                                           value="<?php echo $item['duration_override'] ?: $item['default_duration']; ?>" 
                                           min="1" max="300" placeholder="Duration (s)">
                                <?php else: ?>
                                    <span class="video-duration">⏱️ <?php echo $item['default_duration']; ?>s (auto)</span>
                                <?php endif; ?>
                            </div>
                            <button type="button" class="btn-remove" onclick="removeFromPlaylist(this.parentElement)">×</button>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <div class="playlist-save">
                    <button type="button" class="btn btn-primary btn-block" onclick="savePlaylist()">
                        💾 Save Playlist
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Edit Playlist Settings Modal -->
    <div id="editPlaylistModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Playlist Settings</h2>
                <button type="button" class="close-btn" onclick="toggleModal('editPlaylistModal')">&times;</button>
            </div>
            <form method="POST" action="?page=playlists">
                <input type="hidden" name="action" value="update_playlist">
                <input type="hidden" name="playlist_id" value="<?php echo $editPlaylist['id']; ?>">
                
                <div class="form-group">
                    <label for="edit_name">Playlist Name *</label>
                    <input type="text" id="edit_name" name="name" required 
                           value="<?php echo sanitize($editPlaylist['name']); ?>">
                </div>
                
                <div class="form-group">
                    <label for="edit_description">Description</label>
                    <textarea id="edit_description" name="description" rows="3"><?php echo sanitize($editPlaylist['description'] ?? ''); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="edit_transition">Transition Effect</label>
                    <select id="edit_transition" name="transition">
                        <option value="fade" <?php echo ($editPlaylist['transition'] ?? 'fade') === 'fade' ? 'selected' : ''; ?>>Fade</option>
                        <option value="slide" <?php echo ($editPlaylist['transition'] ?? '') === 'slide' ? 'selected' : ''; ?>>Slide</option>
                        <option value="zoom" <?php echo ($editPlaylist['transition'] ?? '') === 'zoom' ? 'selected' : ''; ?>>Zoom</option>
                        <option value="none" <?php echo ($editPlaylist['transition'] ?? '') === 'none' ? 'selected' : ''; ?>>None (Instant)</option>
                    </select>
                    <small>Animation between content items</small>
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="is_default" <?php echo $editPlaylist['is_default'] ? 'checked' : ''; ?>>
                        Set as default playlist (fallback for screens)
                    </label>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="toggleModal('editPlaylistModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Playlist</button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<!-- Create Playlist Modal -->
<div id="createPlaylistModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Create New Playlist</h2>
            <button type="button" class="close-btn" onclick="toggleModal('createPlaylistModal')">&times;</button>
        </div>
        <form method="POST" action="?page=playlists">
            <input type="hidden" name="action" value="create_playlist">
            
            <div class="form-group">
                <label for="name">Playlist Name *</label>
                <input type="text" id="name" name="name" required 
                       placeholder="e.g., Breakfast Menu, Lunch Specials">
            </div>
            
            <div class="form-group">
                <label for="description">Description (Optional)</label>
                <textarea id="description" name="description" rows="3" 
                          placeholder="Brief description of this playlist..."></textarea>
            </div>
            
            <div class="form-group">
                <label for="transition">Transition Effect</label>
                <select id="transition" name="transition">
                    <option value="fade">Fade</option>
                    <option value="slide">Slide</option>
                    <option value="zoom">Zoom</option>
                    <option value="none">None (Instant)</option>
                </select>
                <small>Animation between content items</small>
            </div>
            
            <div class="form-group">
                <label>
                    <input type="checkbox" name="is_default">
                    Set as default playlist
                </label>
                <small>Default playlist is used when no schedule is active</small>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="toggleModal('createPlaylistModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Create Playlist</button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Form -->
<form id="deleteForm" method="POST" action="?page=playlists" style="display: none;">
    <input type="hidden" name="action" value="delete_playlist">
    <input type="hidden" name="playlist_id" id="deletePlaylistId">
</form>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
// Initialize drag and drop
<?php if ($editPlaylist): ?>
const playlistId = <?php echo $editPlaylist['id']; ?>;
const playlistItems = document.getElementById('playlistItems');

// Make playlist items sortable
new Sortable(playlistItems, {
    animation: 150,
    handle: '.drag-handle',
    ghostClass: 'sortable-ghost',
    dragClass: 'sortable-drag'
});
<?php endif; ?>

function addToPlaylist(element) {
    const contentId = element.dataset.contentId;
    const title = element.dataset.title;
    const duration = element.dataset.duration;
    const type = element.dataset.type;
    const thumbnail = element.dataset.thumbnail;
    
    const playlistItems = document.getElementById('playlistItems');
    
    // Remove empty message if exists
    const emptyMsg = playlistItems.querySelector('.empty-message');
    if (emptyMsg) emptyMsg.remove();
    
    // Create playlist item
    const item = document.createElement('div');
    item.className = 'playlist-item';
    item.draggable = true;
    item.dataset.contentId = contentId;
    item.dataset.duration = duration;
    
    const thumbHtml = type === 'image' 
        ? `<img src="${thumbnail}" alt="${title}">`
        : '<div class="video-thumb">🎥</div>';
    
    const durationHtml = type === 'image'
        ? `<input type="number" class="duration-input" value="${duration}" min="1" max="300" placeholder="Duration (s)">`
        : `<span class="video-duration">⏱️ ${duration}s (auto)</span>`;
    
    item.innerHTML = `
        <div class="drag-handle">⋮⋮</div>
        <div class="item-thumbnail">${thumbHtml}</div>
        <div class="item-details">
            <strong>${title}</strong>
            ${durationHtml}
        </div>
        <button type="button" class="btn-remove" onclick="removeFromPlaylist(this.parentElement)">×</button>
    `;
    
    playlistItems.appendChild(item);
    
    // Remove from available content
    element.remove();
    
    // Check if available content is empty
    const availableContent = document.getElementById('availableContent');
    if (availableContent.children.length === 0) {
        availableContent.innerHTML = '<p class="empty-message">All content has been added to this playlist.</p>';
    }
}

function removeFromPlaylist(element) {
    const contentId = element.dataset.contentId;
    const type = element.dataset.type;
    const duration = element.dataset.duration;
    
    // Get title and thumbnail from the element
    const title = element.querySelector('.item-details strong').textContent;
    const thumbnailElement = element.querySelector('.item-thumbnail img, .item-thumbnail .video-thumb');
    let thumbnail = '';
    
    if (thumbnailElement.tagName === 'IMG') {
        thumbnail = thumbnailElement.src;
    }
    
    element.remove();
    
    // Check if playlist is empty
    const playlistItems = document.getElementById('playlistItems');
    if (playlistItems.children.length === 0) {
        playlistItems.innerHTML = '<p class="empty-message">No items in this playlist. Add content from the left.</p>';
    }
    
    // Add back to available content
    const availableContent = document.getElementById('availableContent');
    
    // Remove empty message if exists
    const emptyMsg = availableContent.querySelector('.empty-message');
    if (emptyMsg) emptyMsg.remove();
    
    // Create content item
    const item = document.createElement('div');
    item.className = 'content-item';
    item.dataset.contentId = contentId;
    item.dataset.title = title;
    item.dataset.duration = duration;
    item.dataset.type = type;
    item.dataset.thumbnail = thumbnail;
    
    const thumbHtml = type === 'image' && thumbnail
        ? `<img src="${thumbnail}" alt="${title}">`
        : '<div class="video-thumb">🎥</div>';
    
    item.innerHTML = `
        <div class="content-thumbnail">${thumbHtml}</div>
        <div class="content-details">
            <strong>${title}</strong>
            <span>${duration}s</span>
        </div>
        <button type="button" class="btn-add" onclick="addToPlaylist(this.parentElement)">+</button>
    `;
    
    availableContent.appendChild(item);
}

function savePlaylist() {
    const items = [];
    const playlistItemElements = document.querySelectorAll('.playlist-item');
    
    playlistItemElements.forEach((item, index) => {
        const durationInput = item.querySelector('.duration-input');
        const itemData = {
            content_id: item.dataset.contentId
        };
        
        // Only include duration for images (videos use their actual duration)
        if (item.dataset.type === 'image' && durationInput) {
            itemData.duration = durationInput.value;
        }
        
        items.push(itemData);
    });
    
    // Send via AJAX
    fetch('?page=playlists', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=save_playlist_items&playlist_id=${playlistId}&items=${encodeURIComponent(JSON.stringify(items))}`
    })
    .then(response => {
        console.log('Response status:', response.status);
        console.log('Response headers:', response.headers);
        return response.text();
    })
    .then(text => {
        console.log('Raw response:', text);
        try {
            const data = JSON.parse(text);
            if (data.success) {
                alert('✅ Playlist saved successfully!');
            } else {
                alert('❌ Error: ' + data.message);
            }
        } catch (e) {
            console.error('JSON parse error:', e);
            console.error('Response text:', text);
            alert('❌ Error: Invalid response from server. Check console for details.');
        }
    })
    .catch(error => {
        console.error('Fetch error:', error);
        alert('❌ Error saving playlist: ' + error.message);
    });
}

function toggleModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal.style.display === 'none') {
        modal.style.display = 'block';
    } else {
        modal.style.display = 'none';
    }
}

function confirmDelete(playlistId, playlistName) {
    if (confirm('Are you sure you want to delete "' + playlistName + '"?\n\nThis action cannot be undone.')) {
        document.getElementById('deletePlaylistId').value = playlistId;
        document.getElementById('deleteForm').submit();
    }
}

// Close modal when clicking outside
window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.style.display = 'none';
    }
}
</script>

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
                
                // Generate unique share token
                $shareToken = 'pl_' . bin2hex(random_bytes(16));
                
                $playlistId = $db->insert('playlists', [
                    'user_id' => $userId,
                    'name' => $name,
                    'description' => $description,
                    'transition' => $transition,
                    'share_token' => $shareToken,
                    'share_enabled' => 0,
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
        $shareEnabled = isset($_POST['share_enabled']) ? 1 : 0;
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
                    'share_enabled' => $shareEnabled,
                    'is_default' => $isDefault
                ], 'id = :id', ['id' => $playlistId]);
                
                // Generate short URL if sharing is enabled and doesn't exist
                if ($shareEnabled) {
                    $existingShort = $db->fetchOne(
                        "SELECT short_code FROM short_urls WHERE playlist_id = ? AND user_id = ?",
                        [$playlistId, $userId]
                    );
                    
                    if (!$existingShort) {
                        // Get playlist share token
                        $playlist = $db->fetchOne("SELECT share_token FROM playlists WHERE id = ?", [$playlistId]);
                        
                        // Generate short code (6 characters)
                        $shortCode = substr(str_shuffle('abcdefghijklmnopqrstuvwxyz0123456789'), 0, 6);
                        
                        // Ensure uniqueness
                        while ($db->fetchOne("SELECT id FROM short_urls WHERE short_code = ?", [$shortCode])) {
                            $shortCode = substr(str_shuffle('abcdefghijklmnopqrstuvwxyz0123456789'), 0, 6);
                        }
                        
                        $originalUrl = rtrim(APP_URL, '/') . '/view/' . $playlist['share_token'];
                        
                        $db->insert('short_urls', [
                            'short_code' => $shortCode,
                            'original_url' => $originalUrl,
                            'playlist_id' => $playlistId,
                            'user_id' => $userId,
                            'is_active' => 1
                        ]);
                    }
                }
                
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


<div class="space-y-8">
    <!-- Page Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-white mb-2">Playlists</h1>
            <p class="text-gray-400">Create and manage content playlists</p>
        </div>
        <button type="button" class="bg-gradient-to-r from-dsp-blue to-blue-600 text-white font-semibold py-2 px-6 rounded-lg hover:from-blue-600 hover:to-blue-700 transition transform hover:scale-105 shadow-lg" onclick="toggleModal('createPlaylistModal')">
            ➕ Create Playlist
        </button>
    </div>

    <?php if (empty($playlists)): ?>
    <!-- Empty State -->
    <div class="bg-gray-800 border border-gray-700 rounded-lg p-12 text-center">
        <div class="text-6xl mb-4">📋</div>
        <h2 class="text-2xl font-bold text-white mb-2">No Playlists Yet</h2>
        <p class="text-gray-400 mb-6">Create your first playlist to organize content for your screens.</p>
        <button type="button" class="bg-gradient-to-r from-dsp-blue to-blue-600 text-white font-semibold py-2 px-6 rounded-lg hover:from-blue-600 hover:to-blue-700 transition transform hover:scale-105 shadow-lg" onclick="toggleModal('createPlaylistModal')">
            Create Your First Playlist
        </button>
    </div>
    <?php else: ?>
    <!-- Playlists Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($playlists as $playlist): ?>
        <div class="bg-gray-800 border border-gray-700 rounded-lg p-6 shadow-lg hover:shadow-xl transition-shadow">
            <div class="flex items-start justify-between mb-4">
                <div class="flex-1">
                    <h3 class="text-xl font-bold text-white mb-1"><?php echo sanitize($playlist['name']); ?></h3>
                    <?php if ($playlist['description']): ?>
                    <p class="text-gray-400 text-sm"><?php echo sanitize($playlist['description']); ?></p>
                    <?php endif; ?>
                </div>
                <?php if ($playlist['is_default']): ?>
                <span class="inline-block bg-green-600 text-white text-xs px-2 py-1 rounded ml-2">Default</span>
                <?php endif; ?>
            </div>
            
            <div class="space-y-2 mb-4 text-sm">
                <div class="flex justify-between text-gray-400">
                    <span>Items:</span>
                    <span class="text-white"><?php echo $playlist['item_count']; ?></span>
                </div>
                <div class="flex justify-between text-gray-400">
                    <span>Transition:</span>
                    <span class="text-white"><?php echo ucfirst($playlist['transition']); ?></span>
                </div>
                <?php if ($playlist['share_enabled']): ?>
                <div class="flex justify-between text-gray-400">
                    <span>Sharing:</span>
                    <span class="text-green-400">✓ Enabled</span>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="grid grid-cols-2 gap-2">
                <a href="?page=playlists&edit=<?php echo $playlist['id']; ?>" class="bg-dsp-blue text-white text-center font-semibold py-2 px-3 text-sm rounded-md hover:bg-blue-600 transition">
                    ✏️ Edit
                </a>
                <?php if ($playlist['share_enabled']): ?>
                <button type="button" class="bg-gray-700 text-white font-semibold py-2 px-3 text-sm rounded-md hover:bg-gray-600 transition" onclick="showShareLink(<?php echo $playlist['id']; ?>)">
                    🔗 Share
                </button>
                <?php else: ?>
                <button type="button" class="bg-gradient-to-r from-dsp-red to-red-600 text-white font-semibold py-2 px-3 text-sm rounded-md hover:from-red-600 hover:to-red-700 transition" onclick="confirmDelete(<?php echo $playlist['id']; ?>, '<?php echo addslashes($playlist['name']); ?>')">
                    🗑️ Delete
                </button>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<?php if ($editPlaylist): ?>
<!-- Playlist Editor -->
<div class="mt-8 space-y-6">
    <div class="bg-gray-800 border border-gray-700 rounded-lg p-6">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-2xl font-bold text-white">Edit: <?php echo sanitize($editPlaylist['name']); ?></h2>
            <a href="?page=playlists" class="text-gray-400 hover:text-white">✕ Close Editor</a>
        </div>
        
        <form method="POST" action="?page=playlists" class="space-y-4">
            <input type="hidden" name="action" value="update_playlist">
            <input type="hidden" name="playlist_id" value="<?php echo $editPlaylist['id']; ?>">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-300 mb-2">Playlist Name *</label>
                    <input type="text" id="name" name="name" required 
                           value="<?php echo sanitize($editPlaylist['name']); ?>"
                           class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-dsp-blue focus:border-transparent transition">
                </div>
                
                <div>
                    <label for="transition" class="block text-sm font-medium text-gray-300 mb-2">Transition Effect</label>
                    <select id="transition" name="transition" class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-dsp-blue focus:border-transparent transition">
                        <option value="fade" <?php echo $editPlaylist['transition'] === 'fade' ? 'selected' : ''; ?>>Fade</option>
                        <option value="slide" <?php echo $editPlaylist['transition'] === 'slide' ? 'selected' : ''; ?>>Slide</option>
                        <option value="zoom" <?php echo $editPlaylist['transition'] === 'zoom' ? 'selected' : ''; ?>>Zoom</option>
                        <option value="none" <?php echo $editPlaylist['transition'] === 'none' ? 'selected' : ''; ?>>None</option>
                    </select>
                </div>
            </div>
            
            <div>
                <label for="description" class="block text-sm font-medium text-gray-300 mb-2">Description</label>
                <textarea id="description" name="description" rows="2"
                          class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-dsp-blue focus:border-transparent transition"><?php echo sanitize($editPlaylist['description'] ?? ''); ?></textarea>
            </div>
            
            <div class="flex items-center space-x-6">
                <label class="flex items-center">
                    <input type="checkbox" name="is_default" <?php echo $editPlaylist['is_default'] ? 'checked' : ''; ?> class="w-4 h-4 text-dsp-blue bg-gray-700 border-gray-600 rounded focus:ring-dsp-blue focus:ring-2">
                    <span class="ml-2 text-sm text-gray-300">Set as default playlist</span>
                </label>
                
                <label class="flex items-center">
                    <input type="checkbox" name="share_enabled" <?php echo $editPlaylist['share_enabled'] ? 'checked' : ''; ?> class="w-4 h-4 text-dsp-blue bg-gray-700 border-gray-600 rounded focus:ring-dsp-blue focus:ring-2">
                    <span class="ml-2 text-sm text-gray-300">Enable public sharing</span>
                </label>
            </div>
            
            <div class="flex justify-end">
                <button type="submit" class="bg-gradient-to-r from-dsp-blue to-blue-600 text-white font-semibold py-2 px-6 rounded-lg hover:from-blue-600 hover:to-blue-700 transition shadow-lg">
                    Save Changes
                </button>
            </div>
        </form>
    </div>

    <!-- Playlist Content Editor -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Current Playlist Items -->
        <div class="bg-gray-800 border border-gray-700 rounded-lg p-6">
            <h3 class="text-xl font-bold text-white mb-4">Playlist Items (<?php echo count($playlistItems); ?>)</h3>
            
            <?php if (empty($playlistItems)): ?>
            <div class="text-center py-8 text-gray-400">
                <p>No items in this playlist yet.</p>
                <p class="text-sm mt-2">Drag content from the right to add items.</p>
            </div>
            <?php else: ?>
            <div id="playlistItems" class="space-y-2">
                <?php foreach ($playlistItems as $item): ?>
                <div class="bg-gray-700 border border-gray-600 rounded-lg p-3 flex items-center space-x-3 cursor-move hover:bg-gray-600 transition" data-id="<?php echo $item['id']; ?>">
                    <div class="text-gray-400">☰</div>
                    <div class="w-12 h-12 bg-gray-900 rounded overflow-hidden flex-shrink-0">
                        <?php if ($item['file_type'] === 'image'): ?>
                            <img src="<?php echo $item['thumbnail_path'] ?: $item['file_path']; ?>" alt="" class="w-full h-full object-cover">
                        <?php else: ?>
                            <div class="w-full h-full flex items-center justify-center text-xl">🎥</div>
                        <?php endif; ?>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-white font-medium truncate"><?php echo sanitize($item['title']); ?></p>
                        <p class="text-xs text-gray-400"><?php echo $item['duration']; ?>s • <?php echo ucfirst($item['file_type']); ?></p>
                    </div>
                    <button type="button" class="text-red-400 hover:text-red-300" onclick="removeFromPlaylist(<?php echo $item['playlist_item_id']; ?>)">
                        ✕
                    </button>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Available Content -->
        <div class="bg-gray-800 border border-gray-700 rounded-lg p-6">
            <h3 class="text-xl font-bold text-white mb-4">Available Content</h3>
            
            <?php if (empty($availableContent)): ?>
            <div class="text-center py-8 text-gray-400">
                <p>No available content.</p>
                <p class="text-sm mt-2">Upload content first to add to playlists.</p>
            </div>
            <?php else: ?>
            <div class="space-y-2 max-h-96 overflow-y-auto">
                <?php foreach ($availableContent as $content): ?>
                <div class="bg-gray-700 border border-gray-600 rounded-lg p-3 flex items-center space-x-3 hover:bg-gray-600 transition cursor-pointer" onclick="addToPlaylist(<?php echo $content['id']; ?>)">
                    <div class="w-12 h-12 bg-gray-900 rounded overflow-hidden flex-shrink-0">
                        <?php if ($content['file_type'] === 'image'): ?>
                            <img src="<?php echo $content['thumbnail_path'] ?: $content['file_path']; ?>" alt="" class="w-full h-full object-cover">
                        <?php else: ?>
                            <div class="w-full h-full flex items-center justify-center text-xl">🎥</div>
                        <?php endif; ?>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-white font-medium truncate"><?php echo sanitize($content['title']); ?></p>
                        <p class="text-xs text-gray-400"><?php echo $content['duration']; ?>s • <?php echo ucfirst($content['file_type']); ?></p>
                    </div>
                    <div class="text-dsp-blue">+</div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endif; ?>


<!-- Create Playlist Modal -->
<div id="createPlaylistModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" style="display: none;">
    <div class="bg-gray-800 rounded-lg shadow-2xl max-w-2xl w-full mx-4 border border-gray-700">
        <div class="flex items-center justify-between p-6 border-b border-gray-700">
            <h2 class="text-2xl font-bold text-white">Create New Playlist</h2>
            <button type="button" class="text-gray-400 hover:text-white text-3xl leading-none" onclick="toggleModal('createPlaylistModal')">&times;</button>
        </div>
        <form method="POST" action="?page=playlists">
            <div class="p-6 space-y-4">
                <input type="hidden" name="action" value="create_playlist">
                
                <div>
                    <label for="create_name" class="block text-sm font-medium text-gray-300 mb-2">Playlist Name *</label>
                    <input type="text" id="create_name" name="name" required 
                           placeholder="e.g., Main Menu, Breakfast Specials"
                           class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-dsp-blue focus:border-transparent transition">
                </div>
                
                <div>
                    <label for="create_description" class="block text-sm font-medium text-gray-300 mb-2">Description</label>
                    <textarea id="create_description" name="description" rows="2"
                              placeholder="Optional description..."
                              class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-dsp-blue focus:border-transparent transition"></textarea>
                </div>
                
                <div>
                    <label for="create_transition" class="block text-sm font-medium text-gray-300 mb-2">Transition Effect</label>
                    <select id="create_transition" name="transition" class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-dsp-blue focus:border-transparent transition">
                        <option value="fade">Fade</option>
                        <option value="slide">Slide</option>
                        <option value="zoom">Zoom</option>
                        <option value="none">None</option>
                    </select>
                </div>
                
                <div>
                    <label class="flex items-center">
                        <input type="checkbox" name="is_default" class="w-4 h-4 text-dsp-blue bg-gray-700 border-gray-600 rounded focus:ring-dsp-blue focus:ring-2">
                        <span class="ml-2 text-sm text-gray-300">Set as default playlist</span>
                    </label>
                </div>
            </div>
            
            <div class="flex justify-end space-x-3 p-6 border-t border-gray-700 bg-gray-900">
                <button type="button" class="bg-gray-700 text-white font-semibold py-2 px-6 rounded-lg hover:bg-gray-600 transition" onclick="toggleModal('createPlaylistModal')">Cancel</button>
                <button type="submit" class="bg-gradient-to-r from-dsp-blue to-blue-600 text-white font-semibold py-2 px-6 rounded-lg hover:from-blue-600 hover:to-blue-700 transition shadow-lg">Create Playlist</button>
            </div>
        </form>
    </div>
</div>

<!-- Share Link Modal -->
<div id="shareLinkModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" style="display: none;">
    <div class="bg-gray-800 rounded-lg shadow-2xl max-w-2xl w-full mx-4 border border-gray-700">
        <div class="flex items-center justify-between p-6 border-b border-gray-700">
            <h2 class="text-2xl font-bold text-white">Share Playlist</h2>
            <button type="button" class="text-gray-400 hover:text-white text-3xl leading-none" onclick="toggleModal('shareLinkModal')">&times;</button>
        </div>
        <div class="p-6 space-y-4">
            <p class="text-gray-300">Share this public link to display the playlist:</p>
            <div class="flex items-center space-x-3 bg-gray-900 p-4 rounded-lg border border-gray-700">
                <code id="shareUrl" class="flex-1 text-dsp-blue font-mono text-sm break-all"></code>
                <button type="button" class="bg-gray-700 text-white font-semibold py-2 px-4 text-sm rounded-md hover:bg-gray-600 transition whitespace-nowrap" onclick="copyShareLink()">
                    📋 Copy
                </button>
            </div>
            <p class="text-xs text-gray-400">Anyone with this link can view the playlist. Disable sharing in playlist settings to revoke access.</p>
        </div>
        <div class="flex justify-end p-6 border-t border-gray-700 bg-gray-900">
            <button type="button" class="bg-gradient-to-r from-dsp-blue to-blue-600 text-white font-semibold py-2 px-6 rounded-lg hover:from-blue-600 hover:to-blue-700 transition shadow-lg" onclick="toggleModal('shareLinkModal')">Close</button>
        </div>
    </div>
</div>

<!-- Delete Confirmation Form -->
<form id="deleteForm" method="POST" action="?page=playlists" style="display: none;">
    <input type="hidden" name="action" value="delete_playlist">
    <input type="hidden" name="playlist_id" id="deletePlaylistId">
</form>

<!-- Hidden forms for playlist operations -->


<form id="removeFromPlaylistForm" method="POST" action="?page=playlists" style="display: none;">
    <input type="hidden" name="action" value="remove_from_playlist">
    <input type="hidden" name="playlist_item_id" id="removeItemId">
</form>

<form id="reorderForm" method="POST" action="?page=playlists" style="display: none;">
    <input type="hidden" name="action" value="reorder_playlist">
    <input type="hidden" name="playlist_id" value="<?php echo $editPlaylist['id'] ?? ''; ?>">
    <input type="hidden" name="order" id="newOrder">
</form>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
function toggleModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal.style.display === 'none') {
        modal.style.display = 'flex';
    } else {
        modal.style.display = 'none';
    }
}

function confirmDelete(playlistId, playlistName) {
    if (confirm('Are you sure you want to delete "' + playlistName + '"?\\n\\nThis action cannot be undone.')) {
        document.getElementById('deletePlaylistId').value = playlistId;
        document.getElementById('deleteForm').submit();
    }
}

function showShareLink(playlistId) {
    fetch('?page=playlists&action=get_share_link&id=' + playlistId)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('shareUrl').textContent = data.url;
                toggleModal('shareLinkModal');
            } else {
                alert('Failed to get share link');
            }
        });
}

function copyShareLink() {
    const url = document.getElementById('shareUrl').textContent;
    const textArea = document.createElement('textarea');
    textArea.value = url;
    document.body.appendChild(textArea);
    textArea.select();
    document.execCommand('copy');
    document.body.removeChild(textArea);
    alert('Link copied to clipboard!');
}

function addToPlaylist(contentId) {
    const urlParams = new URLSearchParams(window.location.search);
    const playlistId = urlParams.get('edit');

    const playlistItemsEl = document.getElementById('playlistItems');
    const existingItems = Array.from(playlistItemsEl.querySelectorAll('[data-id]')).map(item => ({
        content_id: item.getAttribute('data-id'),
        duration: item.querySelector('input[name="duration"]').value
    }));

    const newItems = [...existingItems, { content_id: contentId, duration: 10 }];

    const formData = new FormData();
    formData.append('action', 'save_playlist_items');
    formData.append('playlist_id', playlistId);
    formData.append('items', JSON.stringify(newItems));

    fetch('?page=playlists', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Failed to add item to playlist.');
        }
    });
}

function removeFromPlaylist(itemId) {
    if (confirm('Remove this item from the playlist?')) {
        document.getElementById('removeItemId').value = itemId;
        document.getElementById('removeFromPlaylistForm').submit();
    }
}

// Initialize SortableJS for drag-and-drop
<?php if ($editPlaylist && !empty($playlistItems)): ?>
const playlistEl = document.getElementById('playlistItems');
if (playlistEl) {
    Sortable.create(playlistEl, {
        animation: 150,
        handle: '.cursor-move',
        onEnd: function(evt) {
            // Get new order
            const items = playlistEl.querySelectorAll('[data-id]');
            const order = Array.from(items).map(item => item.getAttribute('data-id'));
            
            // Submit reorder
            document.getElementById('newOrder').value = JSON.stringify(order);
            document.getElementById('reorderForm').submit();
        }
    });
}
<?php endif; ?>

// Close modal when clicking outside
window.onclick = function(event) {
    if (event.target.classList.contains('fixed')) {
        const modals = document.querySelectorAll('.fixed');
        modals.forEach(modal => {
            if (modal.contains(event.target) && event.target === modal) {
                modal.style.display = 'none';
            }
        });
    }
}
</script>

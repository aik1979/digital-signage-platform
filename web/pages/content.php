<?php
require_once __DIR__ . '/../includes/ContentUploader.php';

$userId = $auth->getUserId();
$uploader = new ContentUploader($db, $userId);

// Handle content actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    // Upload content
    if ($_POST['action'] === 'upload_content') {
        if (!isset($_FILES['content_file']) || $_FILES['content_file']['error'] !== UPLOAD_ERR_OK) {
            setFlashMessage('error', 'Please select a file to upload.');
        } else {
            $title = sanitize($_POST['title'] ?? '');
            $duration = intval($_POST['duration'] ?? 10);
            
            $result = $uploader->upload($_FILES['content_file'], $title, $duration);
            
            if ($result['success']) {
                logActivity($db, $userId, 'content_uploaded', 'content', $result['content_id'], 'Uploaded: ' . $result['filename']);
                setFlashMessage('success', 'Content uploaded successfully!');
                redirect('content');
            } else {
                setFlashMessage('error', $result['message']);
            }
        }
    }
    
    // Edit content
    if ($_POST['action'] === 'edit_content') {
        $contentId = intval($_POST['content_id'] ?? 0);
        $title = sanitize($_POST['title'] ?? '');
        $duration = intval($_POST['duration'] ?? 10);
        $description = sanitize($_POST['description'] ?? '');
        $tags = sanitize($_POST['tags'] ?? '');
        
        // Verify ownership
        $content = $db->fetchOne("SELECT id FROM content WHERE id = ? AND user_id = ?", [$contentId, $userId]);
        
        if (!$content) {
            setFlashMessage('error', 'Content not found.');
        } else {
            try {
                $db->update('content', [
                    'title' => $title,
                    'duration' => $duration,
                    'description' => $description,
                    'tags' => $tags
                ], 'id = :id', ['id' => $contentId]);
                
                logActivity($db, $userId, 'content_updated', 'content', $contentId, 'Updated content: ' . $title);
                setFlashMessage('success', 'Content updated successfully!');
                redirect('content');
            } catch (Exception $e) {
                setFlashMessage('error', 'Failed to update content.');
            }
        }
    }
    
    // Delete content
    if ($_POST['action'] === 'delete_content') {
        $contentId = intval($_POST['content_id'] ?? 0);
        
        $result = $uploader->delete($contentId);
        
        if ($result['success']) {
            logActivity($db, $userId, 'content_deleted', 'content', $contentId, 'Deleted content');
            setFlashMessage('success', 'Content deleted successfully!');
        } else {
            setFlashMessage('error', $result['message']);
        }
        redirect('content');
    }
}

// Get filter and view mode
$filterType = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$viewMode = isset($_GET['view']) ? $_GET['view'] : 'grid';

// Build query based on filter
$whereClause = "user_id = ?";
$params = [$userId];

if ($filterType === 'images') {
    $whereClause .= " AND file_type = 'image'";
} elseif ($filterType === 'videos') {
    $whereClause .= " AND file_type = 'video'";
}

// Get all content for current user
$content = $db->fetchAll(
    "SELECT * FROM content WHERE {$whereClause} ORDER BY created_at DESC",
    $params
);

// Get content for editing
$editContent = null;
if (isset($_GET['edit'])) {
    $contentId = intval($_GET['edit']);
    $editContent = $db->fetchOne("SELECT * FROM content WHERE id = ? AND user_id = ?", [$contentId, $userId]);
}

// Get content statistics
$totalImages = $db->fetchOne("SELECT COUNT(*) as count FROM content WHERE user_id = ? AND file_type = 'image'", [$userId])['count'];
$totalVideos = $db->fetchOne("SELECT COUNT(*) as count FROM content WHERE user_id = ? AND file_type = 'video'", [$userId])['count'];
$totalSize = $db->fetchOne("SELECT SUM(file_size) as total FROM content WHERE user_id = ?", [$userId])['total'] ?? 0;
?>

<div class="page-header">
    <div class="header-content">
        <div>
            <h1>Content Library</h1>
            <p>Manage your images and videos</p>
        </div>
        <button type="button" class="btn btn-primary" onclick="toggleModal('uploadModal')">
            ⬆️ Upload Content
        </button>
    </div>
</div>

<!-- Content Stats -->
<div class="content-stats">
    <div class="stat-item">
        <span class="stat-icon">🖼️</span>
        <span class="stat-value"><?php echo $totalImages; ?></span>
        <span class="stat-label">Images</span>
    </div>
    <div class="stat-item">
        <span class="stat-icon">🎥</span>
        <span class="stat-value"><?php echo $totalVideos; ?></span>
        <span class="stat-label">Videos</span>
    </div>
    <div class="stat-item">
        <span class="stat-icon">💾</span>
        <span class="stat-value"><?php echo formatFileSize($totalSize); ?></span>
        <span class="stat-label">Total Size</span>
    </div>
</div>

<!-- Filter and View Controls -->
<div class="content-controls">
    <div class="filter-buttons">
        <a href="?page=content&filter=all&view=<?php echo $viewMode; ?>" 
           class="filter-btn <?php echo $filterType === 'all' ? 'active' : ''; ?>">
            All (<?php echo $totalImages + $totalVideos; ?>)
        </a>
        <a href="?page=content&filter=images&view=<?php echo $viewMode; ?>" 
           class="filter-btn <?php echo $filterType === 'images' ? 'active' : ''; ?>">
            Images (<?php echo $totalImages; ?>)
        </a>
        <a href="?page=content&filter=videos&view=<?php echo $viewMode; ?>" 
           class="filter-btn <?php echo $filterType === 'videos' ? 'active' : ''; ?>">
            Videos (<?php echo $totalVideos; ?>)
        </a>
    </div>
    
    <div class="view-toggle">
        <a href="?page=content&filter=<?php echo $filterType; ?>&view=grid" 
           class="view-btn <?php echo $viewMode === 'grid' ? 'active' : ''; ?>" title="Grid View">
            ▦
        </a>
        <a href="?page=content&filter=<?php echo $filterType; ?>&view=list" 
           class="view-btn <?php echo $viewMode === 'list' ? 'active' : ''; ?>" title="List View">
            ☰
        </a>
    </div>
</div>

<?php if (empty($content)): ?>
<div class="empty-state">
    <h2>📁 No Content Yet</h2>
    <p>Upload images and videos to display on your screens.</p>
    <button type="button" class="btn btn-primary" onclick="toggleModal('uploadModal')">
        Upload Your First Content
    </button>
</div>
<?php else: ?>

<?php if ($viewMode === 'grid'): ?>
<!-- Grid View -->
<div class="content-grid">
    <?php foreach ($content as $item): ?>
    <div class="content-card">
        <div class="content-preview">
            <?php if ($item['file_type'] === 'image'): ?>
                <img src="<?php echo $item['thumbnail_path'] ?: $item['file_path']; ?>" 
                     alt="<?php echo sanitize($item['title']); ?>">
            <?php else: ?>
                <div class="video-placeholder">
                    <span class="video-icon">🎥</span>
                    <span class="video-label">VIDEO</span>
                </div>
            <?php endif; ?>
            <span class="content-type-badge"><?php echo strtoupper($item['file_type']); ?></span>
        </div>
        
        <div class="content-info">
            <h4><?php echo sanitize($item['title']); ?></h4>
            <div class="content-meta">
                <span>⏱️ <?php echo $item['duration']; ?>s</span>
                <span>📦 <?php echo formatFileSize($item['file_size']); ?></span>
                <?php if ($item['width'] && $item['height']): ?>
                <span>📐 <?php echo $item['width']; ?>×<?php echo $item['height']; ?></span>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="content-actions">
            <button type="button" class="btn btn-secondary btn-sm" onclick="viewContent(<?php echo $item['id']; ?>, '<?php echo addslashes($item['title']); ?>', '<?php echo $item['file_path']; ?>', '<?php echo $item['file_type']; ?>')">👁️ View</button>
            <a href="?page=content&edit=<?php echo $item['id']; ?>" class="btn btn-secondary btn-sm">✏️ Edit</a>
            <button type="button" class="btn btn-danger btn-sm" 
                    onclick="confirmDelete(<?php echo $item['id']; ?>, '<?php echo addslashes($item['title']); ?>')">
                🗑️ Delete
            </button>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php else: ?>
<!-- List View -->
<table class="table content-table">
    <thead>
        <tr>
            <th>Preview</th>
            <th>Title</th>
            <th>Type</th>
            <th>Duration</th>
            <th>Size</th>
            <th>Uploaded</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($content as $item): ?>
        <tr>
            <td>
                <?php if ($item['file_type'] === 'image'): ?>
                    <img src="<?php echo $item['thumbnail_path'] ?: $item['file_path']; ?>" 
                         alt="<?php echo sanitize($item['title']); ?>" 
                         class="table-thumbnail">
                <?php else: ?>
                    <span class="video-icon-small">🎥</span>
                <?php endif; ?>
            </td>
            <td><strong><?php echo sanitize($item['title']); ?></strong></td>
            <td><span class="badge badge-info"><?php echo ucfirst($item['file_type']); ?></span></td>
            <td><?php echo $item['duration']; ?>s</td>
            <td><?php echo formatFileSize($item['file_size']); ?></td>
            <td><?php echo timeAgo($item['created_at']); ?></td>
            <td>
                <button type="button" class="btn btn-secondary btn-sm" onclick="viewContent(<?php echo $item['id']; ?>, '<?php echo addslashes($item['title']); ?>', '<?php echo $item['file_path']; ?>', '<?php echo $item['file_type']; ?>')">View</button>
                <a href="?page=content&edit=<?php echo $item['id']; ?>" class="btn btn-secondary btn-sm">Edit</a>
                <button type="button" class="btn btn-danger btn-sm" 
                        onclick="confirmDelete(<?php echo $item['id']; ?>, '<?php echo addslashes($item['title']); ?>')">
                    Delete
                </button>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>

<?php endif; ?>

<!-- Upload Modal -->
<div id="uploadModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Upload Content</h2>
            <button type="button" class="close-btn" onclick="toggleModal('uploadModal')">&times;</button>
        </div>
        <form method="POST" action="?page=content" enctype="multipart/form-data">
            <input type="hidden" name="action" value="upload_content">
            
            <div class="form-group">
                <label for="content_file">Select File *</label>
                <input type="file" id="content_file" name="content_file" required 
                       accept="image/jpeg,image/png,video/mp4">
                <small>Supported: JPG, PNG (max 10MB), MP4 (max 50MB)</small>
            </div>
            
            <div class="form-group">
                <label for="title">Title (Optional)</label>
                <input type="text" id="title" name="title" 
                       placeholder="Leave blank to use filename">
            </div>
            
            <div class="form-group">
                <label for="duration">Display Duration (seconds)</label>
                <input type="number" id="duration" name="duration" value="10" min="1" max="300">
                <small>How long to display this content (images only)</small>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="toggleModal('uploadModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Upload</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Content Modal -->
<?php if ($editContent): ?>
<div id="editModal" class="modal" style="display: block;">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Edit Content</h2>
            <button type="button" class="close-btn" onclick="window.location.href='?page=content'">&times;</button>
        </div>
        <form method="POST" action="?page=content">
            <input type="hidden" name="action" value="edit_content">
            <input type="hidden" name="content_id" value="<?php echo $editContent['id']; ?>">
            
            <div class="form-group">
                <label for="edit_title">Title *</label>
                <input type="text" id="edit_title" name="title" required 
                       value="<?php echo sanitize($editContent['title']); ?>">
            </div>
            
            <div class="form-group">
                <label for="edit_duration">Display Duration (seconds)</label>
                <input type="number" id="edit_duration" name="duration" 
                       value="<?php echo $editContent['duration']; ?>" min="1" max="300">
            </div>
            
            <div class="form-group">
                <label for="edit_description">Description</label>
                <textarea id="edit_description" name="description" rows="3"><?php echo sanitize($editContent['description'] ?? ''); ?></textarea>
            </div>
            
            <div class="form-group">
                <label for="edit_tags">Tags (comma-separated)</label>
                <input type="text" id="edit_tags" name="tags" 
                       value="<?php echo sanitize($editContent['tags'] ?? ''); ?>"
                       placeholder="menu, special, breakfast">
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="window.location.href='?page=content'">Cancel</button>
                <button type="submit" class="btn btn-primary">Update Content</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- View Content Lightbox -->
<div id="viewLightbox" class="lightbox" style="display: none;" onclick="closeLightbox(event)">
    <div class="lightbox-content">
        <div class="lightbox-header">
            <h3 id="lightboxTitle"></h3>
            <button type="button" class="close-btn" onclick="closeLightbox()">&times;</button>
        </div>
        <div class="lightbox-body" id="lightboxBody">
            <!-- Content will be loaded here -->
        </div>
    </div>
</div>

<!-- Delete Form -->
<form id="deleteForm" method="POST" action="?page=content" style="display: none;">
    <input type="hidden" name="action" value="delete_content">
    <input type="hidden" name="content_id" id="deleteContentId">
</form>

<script>
function viewContent(id, title, path, type) {
    const lightbox = document.getElementById('viewLightbox');
    const lightboxTitle = document.getElementById('lightboxTitle');
    const lightboxBody = document.getElementById('lightboxBody');
    
    lightboxTitle.textContent = title;
    
    if (type === 'image') {
        lightboxBody.innerHTML = '<img src="' + path + '" alt="' + title + '" style="max-width: 100%; max-height: 80vh; display: block; margin: 0 auto;">';
    } else if (type === 'video') {
        lightboxBody.innerHTML = '<video controls autoplay style="max-width: 100%; max-height: 80vh; display: block; margin: 0 auto;"><source src="' + path + '" type="video/mp4">Your browser does not support the video tag.</video>';
    }
    
    lightbox.style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function closeLightbox(event) {
    // Only close if clicking the backdrop or close button
    if (!event || event.target.id === 'viewLightbox' || event.target.classList.contains('close-btn')) {
        const lightbox = document.getElementById('viewLightbox');
        const lightboxBody = document.getElementById('lightboxBody');
        
        // Stop any playing videos
        const videos = lightboxBody.getElementsByTagName('video');
        for (let video of videos) {
            video.pause();
        }
        
        lightbox.style.display = 'none';
        lightboxBody.innerHTML = '';
        document.body.style.overflow = 'auto';
    }
}

// Close lightbox with Escape key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeLightbox();
    }
});

function toggleModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal.style.display === 'none') {
        modal.style.display = 'block';
    } else {
        modal.style.display = 'none';
    }
}

function confirmDelete(contentId, contentTitle) {
    if (confirm('Are you sure you want to delete "' + contentTitle + '"?\n\nThis action cannot be undone.')) {
        document.getElementById('deleteContentId').value = contentId;
        document.getElementById('deleteForm').submit();
    }
}

// Close modal when clicking outside
window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.style.display = 'none';
        if (window.location.search.includes('edit')) {
            window.location.href = '?page=content';
        }
    }
}
</script>

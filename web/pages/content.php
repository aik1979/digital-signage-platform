<?php
require_once __DIR__ . '/../includes/ContentUploader.php';

$userId = $auth->getUserId();
$uploader = new ContentUploader($db, $userId);

// Handle content actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    // Upload content (supports multiple files)
    if ($_POST['action'] === 'upload_content') {
        if (!isset($_FILES['content_file']) || empty($_FILES['content_file']['name'][0])) {
            setFlashMessage('error', 'Please select at least one file to upload.');
        } else {
            $duration = intval($_POST['duration'] ?? 10);
            $uploadedCount = 0;
            $failedCount = 0;
            $errors = [];
            
            // Handle multiple files
            $fileCount = count($_FILES['content_file']['name']);
            
            for ($i = 0; $i < $fileCount; $i++) {
                // Skip if file has error
                if ($_FILES['content_file']['error'][$i] !== UPLOAD_ERR_OK) {
                    $failedCount++;
                    continue;
                }
                
                // Create single file array for uploader
                $file = [
                    'name' => $_FILES['content_file']['name'][$i],
                    'type' => $_FILES['content_file']['type'][$i],
                    'tmp_name' => $_FILES['content_file']['tmp_name'][$i],
                    'error' => $_FILES['content_file']['error'][$i],
                    'size' => $_FILES['content_file']['size'][$i]
                ];
                
                // Use filename as title if not provided
                $title = pathinfo($file['name'], PATHINFO_FILENAME);
                
                $result = $uploader->upload($file, $title, $duration);
                
                if ($result['success']) {
                    logActivity($db, $userId, 'content_uploaded', 'content', $result['content_id'], 'Uploaded: ' . $result['filename']);
                    $uploadedCount++;
                } else {
                    $failedCount++;
                    $errors[] = $file['name'] . ': ' . $result['message'];
                }
            }
            
            // Set appropriate message
            if ($uploadedCount > 0 && $failedCount === 0) {
                setFlashMessage('success', "Successfully uploaded {$uploadedCount} file(s)!");
            } elseif ($uploadedCount > 0 && $failedCount > 0) {
                setFlashMessage('warning', "Uploaded {$uploadedCount} file(s), {$failedCount} failed.");
            } else {
                setFlashMessage('error', 'All uploads failed: ' . implode(', ', $errors));
            }
            
            redirect('content');
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

<div class="space-y-8">
    <!-- Page Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-white mb-2">Content Library</h1>
            <p class="text-gray-400">Manage your images and videos</p>
        </div>
        <button type="button" class="bg-gradient-to-r from-dsp-blue to-blue-600 text-white font-semibold py-2 px-6 rounded-lg hover:from-blue-600 hover:to-blue-700 transition transform hover:scale-105 shadow-lg" onclick="toggleModal('uploadModal')">
            ⬆️ Upload Content
        </button>
    </div>

    <!-- Content Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-gradient-to-br from-gray-800 to-gray-900 border border-gray-700 rounded-lg p-6 shadow-lg">
            <div class="flex items-center space-x-4">
                <div class="text-4xl">🖼️</div>
                <div>
                    <p class="text-3xl font-bold text-white"><?php echo $totalImages; ?></p>
                    <p class="text-gray-400 text-sm">Images</p>
                </div>
            </div>
        </div>
        <div class="bg-gradient-to-br from-gray-800 to-gray-900 border border-gray-700 rounded-lg p-6 shadow-lg">
            <div class="flex items-center space-x-4">
                <div class="text-4xl">🎥</div>
                <div>
                    <p class="text-3xl font-bold text-white"><?php echo $totalVideos; ?></p>
                    <p class="text-gray-400 text-sm">Videos</p>
                </div>
            </div>
        </div>
        <div class="bg-gradient-to-br from-gray-800 to-gray-900 border border-gray-700 rounded-lg p-6 shadow-lg">
            <div class="flex items-center space-x-4">
                <div class="text-4xl">💾</div>
                <div>
                    <p class="text-3xl font-bold text-white"><?php echo formatFileSize($totalSize); ?></p>
                    <p class="text-gray-400 text-sm">Total Size</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter and View Controls -->
    <div class="flex items-center justify-between bg-gray-800 border border-gray-700 rounded-lg p-4">
        <div class="flex space-x-2">
            <a href="?page=content&filter=all&view=<?php echo $viewMode; ?>" 
               class="px-4 py-2 rounded-md text-sm font-medium transition-colors <?php echo $filterType === 'all' ? 'bg-dsp-blue text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?>">
                All (<?php echo $totalImages + $totalVideos; ?>)
            </a>
            <a href="?page=content&filter=images&view=<?php echo $viewMode; ?>" 
               class="px-4 py-2 rounded-md text-sm font-medium transition-colors <?php echo $filterType === 'images' ? 'bg-dsp-blue text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?>">
                Images (<?php echo $totalImages; ?>)
            </a>
            <a href="?page=content&filter=videos&view=<?php echo $viewMode; ?>" 
               class="px-4 py-2 rounded-md text-sm font-medium transition-colors <?php echo $filterType === 'videos' ? 'bg-dsp-blue text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?>">
                Videos (<?php echo $totalVideos; ?>)
            </a>
        </div>
        
        <div class="flex space-x-2">
            <a href="?page=content&filter=<?php echo $filterType; ?>&view=grid" 
               class="px-3 py-2 rounded-md text-sm font-medium transition-colors <?php echo $viewMode === 'grid' ? 'bg-dsp-blue text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?>" title="Grid View">
                ▦
            </a>
            <a href="?page=content&filter=<?php echo $filterType; ?>&view=list" 
               class="px-3 py-2 rounded-md text-sm font-medium transition-colors <?php echo $viewMode === 'list' ? 'bg-dsp-blue text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?>" title="List View">
                ☰
            </a>
        </div>
    </div>

    <?php if (empty($content)): ?>
    <!-- Empty State -->
    <div class="bg-gray-800 border border-gray-700 rounded-lg p-12 text-center">
        <div class="text-6xl mb-4">📁</div>
        <h2 class="text-2xl font-bold text-white mb-2">No Content Yet</h2>
        <p class="text-gray-400 mb-6">Upload images and videos to display on your screens.</p>
        <button type="button" class="bg-gradient-to-r from-dsp-blue to-blue-600 text-white font-semibold py-2 px-6 rounded-lg hover:from-blue-600 hover:to-blue-700 transition transform hover:scale-105 shadow-lg" onclick="toggleModal('uploadModal')">
            Upload Your First Content
        </button>
    </div>
    <?php else: ?>

    <?php if ($viewMode === 'grid'): ?>
    <!-- Grid View -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        <?php foreach ($content as $item): ?>
        <div class="bg-gray-800 border border-gray-700 rounded-lg overflow-hidden shadow-lg hover:shadow-xl transition-shadow">
            <div class="relative aspect-video bg-gray-900">
                <?php if ($item['file_type'] === 'image'): ?>
                    <img src="<?php echo $item['thumbnail_path'] ?: $item['file_path']; ?>" 
                         alt="<?php echo sanitize($item['title']); ?>"
                         class="w-full h-full object-cover">
                <?php else: ?>
                    <div class="w-full h-full flex flex-col items-center justify-center">
                        <span class="text-6xl mb-2">🎥</span>
                        <span class="text-gray-400 text-sm">VIDEO</span>
                    </div>
                <?php endif; ?>
                <span class="absolute top-2 right-2 bg-black bg-opacity-75 text-white text-xs px-2 py-1 rounded">
                    <?php echo strtoupper($item['file_type']); ?>
                </span>
            </div>
            
            <div class="p-4">
                <h4 class="text-white font-semibold mb-2 truncate"><?php echo sanitize($item['title']); ?></h4>
                <div class="flex flex-wrap gap-2 text-xs text-gray-400 mb-4">
                    <span>⏱️ <?php echo $item['duration']; ?>s</span>
                    <span>📦 <?php echo formatFileSize($item['file_size']); ?></span>
                    <?php if ($item['width'] && $item['height']): ?>
                    <span>📐 <?php echo $item['width']; ?>×<?php echo $item['height']; ?></span>
                    <?php endif; ?>
                </div>
                
                <div class="grid grid-cols-3 gap-2">
                    <button type="button" class="bg-dsp-blue text-white text-center font-semibold py-1.5 px-2 text-xs rounded-md hover:bg-blue-600 transition" onclick="viewContent(<?php echo $item['id']; ?>, '<?php echo addslashes($item['title']); ?>', '<?php echo $item['file_path']; ?>', '<?php echo $item['file_type']; ?>')">
                        👁️ View
                    </button>
                    <a href="?page=content&edit=<?php echo $item['id']; ?>" class="bg-gray-700 text-white text-center font-semibold py-1.5 px-2 text-xs rounded-md hover:bg-gray-600 transition">
                        ✏️ Edit
                    </a>
                    <button type="button" class="bg-gradient-to-r from-dsp-red to-red-600 text-white font-semibold py-1.5 px-2 text-xs rounded-md hover:from-red-600 hover:to-red-700 transition" onclick="confirmDelete(<?php echo $item['id']; ?>, '<?php echo addslashes($item['title']); ?>')">
                        🗑️
                    </button>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <!-- List View -->
    <div class="bg-gray-800 border border-gray-700 rounded-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-gray-700 bg-gray-900">
                        <th class="text-left py-3 px-4 text-gray-300 font-semibold">Preview</th>
                        <th class="text-left py-3 px-4 text-gray-300 font-semibold">Title</th>
                        <th class="text-left py-3 px-4 text-gray-300 font-semibold">Type</th>
                        <th class="text-left py-3 px-4 text-gray-300 font-semibold">Duration</th>
                        <th class="text-left py-3 px-4 text-gray-300 font-semibold">Size</th>
                        <th class="text-left py-3 px-4 text-gray-300 font-semibold">Dimensions</th>
                        <th class="text-left py-3 px-4 text-gray-300 font-semibold">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($content as $item): ?>
                    <tr class="border-b border-gray-700 hover:bg-gray-700 transition">
                        <td class="py-3 px-4">
                            <div class="w-16 h-16 bg-gray-900 rounded overflow-hidden">
                                <?php if ($item['file_type'] === 'image'): ?>
                                    <img src="<?php echo $item['thumbnail_path'] ?: $item['file_path']; ?>" 
                                         alt="<?php echo sanitize($item['title']); ?>"
                                         class="w-full h-full object-cover">
                                <?php else: ?>
                                    <div class="w-full h-full flex items-center justify-center text-2xl">🎥</div>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="py-3 px-4 text-white"><?php echo sanitize($item['title']); ?></td>
                        <td class="py-3 px-4">
                            <span class="inline-block bg-blue-600 text-white text-xs px-2 py-1 rounded">
                                <?php echo ucfirst($item['file_type']); ?>
                            </span>
                        </td>
                        <td class="py-3 px-4 text-gray-400"><?php echo $item['duration']; ?>s</td>
                        <td class="py-3 px-4 text-gray-400"><?php echo formatFileSize($item['file_size']); ?></td>
                        <td class="py-3 px-4 text-gray-400">
                            <?php if ($item['width'] && $item['height']): ?>
                                <?php echo $item['width']; ?>×<?php echo $item['height']; ?>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td class="py-3 px-4">
                            <div class="flex space-x-2">
                                <button type="button" class="bg-dsp-blue text-white font-semibold py-1 px-3 text-xs rounded-md hover:bg-blue-600 transition" onclick="viewContent(<?php echo $item['id']; ?>, '<?php echo addslashes($item['title']); ?>', '<?php echo $item['file_path']; ?>', '<?php echo $item['file_type']; ?>')">
                                    👁️
                                </button>
                                <a href="?page=content&edit=<?php echo $item['id']; ?>" class="bg-gray-700 text-white font-semibold py-1 px-3 text-xs rounded-md hover:bg-gray-600 transition">
                                    ✏️
                                </a>
                                <button type="button" class="bg-gradient-to-r from-dsp-red to-red-600 text-white font-semibold py-1 px-3 text-xs rounded-md hover:from-red-600 hover:to-red-700 transition" onclick="confirmDelete(<?php echo $item['id']; ?>, '<?php echo addslashes($item['title']); ?>')">
                                    🗑️
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</div>


<!-- Upload Modal -->
<div id="uploadModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" style="display: none;">
    <div class="bg-gray-800 rounded-lg shadow-2xl max-w-2xl w-full mx-4 border border-gray-700">
        <div class="flex items-center justify-between p-6 border-b border-gray-700">
            <h2 class="text-2xl font-bold text-white">Upload Content</h2>
            <button type="button" class="text-gray-400 hover:text-white text-3xl leading-none" onclick="toggleModal('uploadModal')">&times;</button>
        </div>
        <form method="POST" action="?page=content" enctype="multipart/form-data">
            <div class="p-6 space-y-4">
                <input type="hidden" name="action" value="upload_content">
                
                <div>
                    <label for="content_file" class="block text-sm font-medium text-gray-300 mb-2">Select Files *</label>
                    <input type="file" id="content_file" name="content_file[]" required multiple
                           accept="image/jpeg,image/png,video/mp4"
                           class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-dsp-blue file:text-white hover:file:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-dsp-blue focus:border-transparent transition">
                    <p class="text-xs text-gray-400 mt-1">Supported: JPG, PNG (max 10MB), MP4 (max 50MB). Select multiple files to upload at once.</p>
                    <div id="fileList" class="mt-2 text-sm text-gray-300"></div>
                </div>
                
                <div>
                    <label for="duration" class="block text-sm font-medium text-gray-300 mb-2">Default Display Duration (seconds)</label>
                    <input type="number" id="duration" name="duration" value="10" min="1" max="300"
                           class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-dsp-blue focus:border-transparent transition">
                    <p class="text-xs text-gray-400 mt-1">Default duration for images (videos use their actual length)</p>
                </div>
            </div>
            
            <div class="flex justify-end space-x-3 p-6 border-t border-gray-700 bg-gray-900">
                <button type="button" class="bg-gray-700 text-white font-semibold py-2 px-6 rounded-lg hover:bg-gray-600 transition" onclick="toggleModal('uploadModal')">Cancel</button>
                <button type="submit" class="bg-gradient-to-r from-dsp-blue to-blue-600 text-white font-semibold py-2 px-6 rounded-lg hover:from-blue-600 hover:to-blue-700 transition shadow-lg">Upload</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Content Modal -->
<?php if ($editContent): ?>
<div id="editModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-gray-800 rounded-lg shadow-2xl max-w-2xl w-full mx-4 border border-gray-700">
        <div class="flex items-center justify-between p-6 border-b border-gray-700">
            <h2 class="text-2xl font-bold text-white">Edit Content</h2>
            <button type="button" class="text-gray-400 hover:text-white text-3xl leading-none" onclick="window.location.href='?page=content'">&times;</button>
        </div>
        <form method="POST" action="?page=content">
            <div class="p-6 space-y-4">
                <input type="hidden" name="action" value="edit_content">
                <input type="hidden" name="content_id" value="<?php echo $editContent['id']; ?>">
                
                <div>
                    <label for="edit_title" class="block text-sm font-medium text-gray-300 mb-2">Title *</label>
                    <input type="text" id="edit_title" name="title" required 
                           value="<?php echo sanitize($editContent['title']); ?>"
                           class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-dsp-blue focus:border-transparent transition">
                </div>
                
                <div>
                    <label for="edit_duration" class="block text-sm font-medium text-gray-300 mb-2">Display Duration (seconds)</label>
                    <input type="number" id="edit_duration" name="duration" 
                           value="<?php echo $editContent['duration']; ?>" min="1" max="300"
                           class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-dsp-blue focus:border-transparent transition">
                </div>
                
                <div>
                    <label for="edit_description" class="block text-sm font-medium text-gray-300 mb-2">Description</label>
                    <textarea id="edit_description" name="description" rows="3"
                              class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-dsp-blue focus:border-transparent transition"><?php echo sanitize($editContent['description'] ?? ''); ?></textarea>
                </div>
                
                <div>
                    <label for="edit_tags" class="block text-sm font-medium text-gray-300 mb-2">Tags (comma-separated)</label>
                    <input type="text" id="edit_tags" name="tags" 
                           value="<?php echo sanitize($editContent['tags'] ?? ''); ?>"
                           placeholder="menu, special, breakfast"
                           class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-dsp-blue focus:border-transparent transition">
                </div>
            </div>
            
            <div class="flex justify-end space-x-3 p-6 border-t border-gray-700 bg-gray-900">
                <button type="button" class="bg-gray-700 text-white font-semibold py-2 px-6 rounded-lg hover:bg-gray-600 transition" onclick="window.location.href='?page=content'">Cancel</button>
                <button type="submit" class="bg-gradient-to-r from-dsp-blue to-blue-600 text-white font-semibold py-2 px-6 rounded-lg hover:from-blue-600 hover:to-blue-700 transition shadow-lg">Update Content</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- View Content Lightbox -->
<div id="viewLightbox" class="fixed inset-0 bg-black bg-opacity-90 flex items-center justify-center z-50" style="display: none;" onclick="closeLightbox(event)">
    <div class="max-w-6xl w-full mx-4" onclick="event.stopPropagation()">
        <div class="flex items-center justify-between mb-4">
            <h3 id="lightboxTitle" class="text-2xl font-bold text-white"></h3>
            <button type="button" class="text-white hover:text-gray-300 text-4xl leading-none" onclick="closeLightbox()">&times;</button>
        </div>
        <div id="lightboxBody" class="bg-black rounded-lg overflow-hidden flex items-center justify-center" style="max-height: 80vh;">
            <!-- Content will be inserted here -->
        </div>
    </div>
</div>

<!-- Delete Confirmation Form -->
<form id="deleteForm" method="POST" action="?page=content" style="display: none;">
    <input type="hidden" name="action" value="delete_content">
    <input type="hidden" name="content_id" id="deleteContentId">
</form>

<script>
function viewContent(id, title, path, type) {
    document.getElementById('lightboxTitle').textContent = title;
    const body = document.getElementById('lightboxBody');
    
    if (type === 'image') {
        body.innerHTML = '<img src="' + path + '" alt="' + title + '" class="max-w-full max-h-full object-contain">';
    } else {
        body.innerHTML = '<video src="' + path + '" controls autoplay class="max-w-full max-h-full"></video>';
    }
    
    document.getElementById('viewLightbox').style.display = 'flex';
}

function closeLightbox(event) {
    if (!event || event.target.id === 'viewLightbox') {
        document.getElementById('viewLightbox').style.display = 'none';
        const body = document.getElementById('lightboxBody');
        body.innerHTML = '';
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
        modal.style.display = 'flex';
    } else {
        modal.style.display = 'none';
    }
}

function confirmDelete(contentId, contentTitle) {
    if (confirm('Are you sure you want to delete "' + contentTitle + '"?\\n\\nThis action cannot be undone.')) {
        document.getElementById('deleteContentId').value = contentId;
        document.getElementById('deleteForm').submit();
    }
}

// Show selected files
document.getElementById('content_file').addEventListener('change', function(e) {
    const fileList = document.getElementById('fileList');
    const files = e.target.files;
    
    if (files.length === 0) {
        fileList.innerHTML = '';
        return;
    }
    
    let html = '<strong>Selected files (' + files.length + '):</strong><ul class="mt-2 space-y-1">';
    for (let i = 0; i < files.length; i++) {
        const size = (files[i].size / 1024 / 1024).toFixed(2);
        html += '<li class="text-xs">• ' + files[i].name + ' (' + size + ' MB)</li>';
    }
    html += '</ul>';
    fileList.innerHTML = html;
});

// Close modal when clicking outside
window.onclick = function(event) {
    if (event.target.classList.contains('fixed')) {
        const modals = document.querySelectorAll('.fixed');
        modals.forEach(modal => {
            if (modal.contains(event.target) && event.target === modal) {
                if (window.location.search.includes('edit')) {
                    window.location.href = '?page=content';
                } else {
                    modal.style.display = 'none';
                }
            }
        });
    }
}
</script>

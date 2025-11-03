<?php
$userId = $auth->getUserId();

// Get statistics
$totalScreens = $db->fetchOne("SELECT COUNT(*) as count FROM screens WHERE user_id = ?", [$userId])['count'];
$onlineScreens = $db->fetchOne("SELECT COUNT(*) as count FROM screens WHERE user_id = ? AND is_online = 1", [$userId])['count'];
$totalContent = $db->fetchOne("SELECT COUNT(*) as count FROM content WHERE user_id = ?", [$userId])['count'];
$totalPlaylists = $db->fetchOne("SELECT COUNT(*) as count FROM playlists WHERE user_id = ?", [$userId])['count'];

// Get recent screens
$recentScreens = $db->fetchAll(
    "SELECT * FROM screens WHERE user_id = ? ORDER BY last_heartbeat DESC LIMIT 5",
    [$userId]
);

// Get recent content
$recentContent = $db->fetchAll(
    "SELECT * FROM content WHERE user_id = ? ORDER BY created_at DESC LIMIT 5",
    [$userId]
);
?>

<div class="dashboard">
    <h1>Dashboard</h1>
    <p class="subtitle">Welcome back, <?php echo sanitize($_SESSION['user_name']); ?>!</p>
    
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">📺</div>
            <div class="stat-content">
                <h3><?php echo $totalScreens; ?></h3>
                <p>Total Screens</p>
            </div>
        </div>
        
        <div class="stat-card stat-success">
            <div class="stat-icon">✓</div>
            <div class="stat-content">
                <h3><?php echo $onlineScreens; ?></h3>
                <p>Online Screens</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">🖼️</div>
            <div class="stat-content">
                <h3><?php echo $totalContent; ?></h3>
                <p>Content Items</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">📋</div>
            <div class="stat-content">
                <h3><?php echo $totalPlaylists; ?></h3>
                <p>Playlists</p>
            </div>
        </div>
    </div>
    
    <div class="dashboard-grid">
        <div class="dashboard-section">
            <h2>Recent Screens</h2>
            <?php if (empty($recentScreens)): ?>
                <div class="empty-state">
                    <p>No screens added yet.</p>
                    <a href="?page=screens" class="btn btn-primary">Add Your First Screen</a>
                </div>
            <?php else: ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Status</th>
                            <th>Last Seen</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentScreens as $screen): ?>
                        <tr>
                            <td><?php echo sanitize($screen['name']); ?></td>
                            <td>
                                <?php if (isScreenOnline($screen['last_heartbeat'])): ?>
                                    <span class="badge badge-success">Online</span>
                                <?php else: ?>
                                    <span class="badge badge-error">Offline</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo timeAgo($screen['last_heartbeat']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <a href="?page=screens" class="btn btn-secondary">View All Screens</a>
            <?php endif; ?>
        </div>
        
        <div class="dashboard-section">
            <h2>Recent Content</h2>
            <?php if (empty($recentContent)): ?>
                <div class="empty-state">
                    <p>No content uploaded yet.</p>
                    <a href="?page=content" class="btn btn-primary">Upload Content</a>
                </div>
            <?php else: ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Type</th>
                            <th>Uploaded</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentContent as $content): ?>
                        <tr>
                            <td><?php echo sanitize($content['title'] ?: $content['original_filename']); ?></td>
                            <td>
                                <span class="badge badge-info">
                                    <?php echo ucfirst($content['file_type']); ?>
                                </span>
                            </td>
                            <td><?php echo timeAgo($content['created_at']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <a href="?page=content" class="btn btn-secondary">View All Content</a>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="quick-actions">
        <h2>Quick Actions</h2>
        <div class="action-buttons">
            <a href="?page=screens" class="btn btn-primary">➕ Add Screen</a>
            <a href="?page=content" class="btn btn-primary">⬆️ Upload Content</a>
            <a href="?page=playlists" class="btn btn-primary">📋 Create Playlist</a>
        </div>
    </div>
</div>

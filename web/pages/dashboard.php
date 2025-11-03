<?php
$userId = $auth->getUserId();

// Get statistics
$totalScreens = $db->fetchOne("SELECT COUNT(*) as count FROM screens WHERE user_id = ?", [$userId])['count'];
$onlineScreens = $db->fetchOne("SELECT COUNT(*) as count FROM screens WHERE user_id = ? AND is_online = 1", [$userId])['count'];
$totalContent = $db->fetchOne("SELECT COUNT(*) as count FROM content WHERE user_id = ?", [$userId])['count'];
$totalPlaylists = $db->fetchOne("SELECT COUNT(*) as count FROM playlists WHERE user_id = ?", [$userId])['count'];

// Get recent screens with playlist names
$recentScreens = $db->fetchAll(
    "SELECT s.*, p.name as playlist_name 
     FROM screens s 
     LEFT JOIN playlists p ON s.current_playlist_id = p.id
     WHERE s.user_id = ? 
     ORDER BY s.last_heartbeat DESC LIMIT 5",
    [$userId]
);

// Get recent content
$recentContent = $db->fetchAll(
    "SELECT * FROM content WHERE user_id = ? ORDER BY created_at DESC LIMIT 5",
    [$userId]
);
?>

<div class="space-y-8">
    <div>
        <h1 class="text-3xl font-bold text-white mb-2">Dashboard</h1>
        <p class="text-gray-400">Welcome back, <?php echo sanitize($_SESSION['user_name']); ?>!</p>
    </div>
    
    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bg-gradient-to-br from-gray-800 to-gray-900 border border-gray-700 rounded-lg p-6 shadow-lg hover:shadow-xl transition-shadow">
            <div class="flex items-center space-x-4">
                <div class="text-4xl">📺</div>
                <div>
                    <h3 class="text-3xl font-bold text-white"><?php echo $totalScreens; ?></h3>
                    <p class="text-gray-400 text-sm">Total Screens</p>
                </div>
            </div>
        </div>
        
        <div class="bg-gradient-to-br from-green-900 to-green-800 border border-green-700 rounded-lg p-6 shadow-lg hover:shadow-xl transition-shadow">
            <div class="flex items-center space-x-4">
                <div class="text-4xl">✓</div>
                <div>
                    <h3 class="text-3xl font-bold text-white"><?php echo $onlineScreens; ?></h3>
                    <p class="text-green-200 text-sm">Online Screens</p>
                </div>
            </div>
        </div>
        
        <div class="bg-gradient-to-br from-gray-800 to-gray-900 border border-gray-700 rounded-lg p-6 shadow-lg hover:shadow-xl transition-shadow">
            <div class="flex items-center space-x-4">
                <div class="text-4xl">🖼️</div>
                <div>
                    <h3 class="text-3xl font-bold text-white"><?php echo $totalContent; ?></h3>
                    <p class="text-gray-400 text-sm">Content Items</p>
                </div>
            </div>
        </div>
        
        <div class="bg-gradient-to-br from-gray-800 to-gray-900 border border-gray-700 rounded-lg p-6 shadow-lg hover:shadow-xl transition-shadow">
            <div class="flex items-center space-x-4">
                <div class="text-4xl">📋</div>
                <div>
                    <h3 class="text-3xl font-bold text-white"><?php echo $totalPlaylists; ?></h3>
                    <p class="text-gray-400 text-sm">Playlists</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Recent Screens & Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Recent Screens -->
        <div class="bg-gray-800 border border-gray-700 rounded-lg p-6 shadow-lg">
            <h2 class="text-2xl font-bold text-white mb-4">Recent Screens</h2>
            <?php if (empty($recentScreens)): ?>
                <div class="text-center py-8">
                    <p class="text-gray-400 mb-4">No screens added yet.</p>
                    <a href="?page=screens" class="inline-block bg-gradient-to-r from-dsp-blue to-blue-600 text-white font-semibold py-2 px-6 rounded-lg hover:from-blue-600 hover:to-blue-700 transition transform hover:scale-105 shadow-lg">
                        Add Your First Screen
                    </a>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-gray-700">
                                <th class="text-left py-3 px-4 text-gray-300 font-semibold">Name</th>
                                <th class="text-left py-3 px-4 text-gray-300 font-semibold">Playlist</th>
                                <th class="text-left py-3 px-4 text-gray-300 font-semibold">Status</th>
                                <th class="text-left py-3 px-4 text-gray-300 font-semibold">Last Seen</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentScreens as $screen): ?>
                            <tr class="border-b border-gray-700 hover:bg-gray-700 transition">
                                <td class="py-3 px-4 text-white"><?php echo sanitize($screen['name']); ?></td>
                                <td class="py-3 px-4">
                                    <?php if ($screen['playlist_name']): ?>
                                        <span class="inline-block bg-blue-600 text-white text-xs px-2 py-1 rounded"><?php echo sanitize($screen['playlist_name']); ?></span>
                                    <?php else: ?>
                                        <span class="text-gray-500">Default</span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3 px-4">
                                    <?php if (isScreenOnline($screen['last_heartbeat'])): ?>
                                        <span class="inline-block bg-green-600 text-white text-xs px-2 py-1 rounded">Online</span>
                                    <?php else: ?>
                                        <span class="inline-block bg-red-600 text-white text-xs px-2 py-1 rounded">Offline</span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3 px-4 text-gray-400"><?php echo timeAgo($screen['last_heartbeat']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="mt-4">
                    <a href="?page=screens" class="inline-block bg-gray-700 text-white font-semibold py-2 px-6 rounded-lg hover:bg-gray-600 transition">
                        View All Screens
                    </a>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Recent Content -->
        <div class="bg-gray-800 border border-gray-700 rounded-lg p-6 shadow-lg">
            <h2 class="text-2xl font-bold text-white mb-4">Recent Content</h2>
            <?php if (empty($recentContent)): ?>
                <div class="text-center py-8">
                    <p class="text-gray-400 mb-4">No content uploaded yet.</p>
                    <a href="?page=content" class="inline-block bg-gradient-to-r from-dsp-blue to-blue-600 text-white font-semibold py-2 px-6 rounded-lg hover:from-blue-600 hover:to-blue-700 transition transform hover:scale-105 shadow-lg">
                        Upload Content
                    </a>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-gray-700">
                                <th class="text-left py-3 px-4 text-gray-300 font-semibold">Title</th>
                                <th class="text-left py-3 px-4 text-gray-300 font-semibold">Type</th>
                                <th class="text-left py-3 px-4 text-gray-300 font-semibold">Uploaded</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentContent as $content): ?>
                            <tr class="border-b border-gray-700 hover:bg-gray-700 transition">
                                <td class="py-3 px-4 text-white"><?php echo sanitize($content['title'] ?: $content['original_filename']); ?></td>
                                <td class="py-3 px-4">
                                    <span class="inline-block bg-blue-600 text-white text-xs px-2 py-1 rounded">
                                        <?php echo ucfirst($content['file_type']); ?>
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-gray-400"><?php echo timeAgo($content['created_at']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="mt-4">
                    <a href="?page=content" class="inline-block bg-gray-700 text-white font-semibold py-2 px-6 rounded-lg hover:bg-gray-600 transition">
                        View All Content
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="bg-gray-800 border border-gray-700 rounded-lg p-6 shadow-lg">
        <h2 class="text-2xl font-bold text-white mb-4">Quick Actions</h2>
        <div class="flex flex-wrap gap-4">
            <a href="?page=screens" class="bg-gradient-to-r from-dsp-blue to-blue-600 text-white font-semibold py-3 px-6 rounded-lg hover:from-blue-600 hover:to-blue-700 transition transform hover:scale-105 shadow-lg">
                ➕ Add Screen
            </a>
            <a href="?page=content" class="bg-gradient-to-r from-dsp-green to-green-600 text-white font-semibold py-3 px-6 rounded-lg hover:from-green-600 hover:to-green-700 transition transform hover:scale-105 shadow-lg">
                ⬆️ Upload Content
            </a>
            <a href="?page=playlists" class="bg-gradient-to-r from-dsp-red to-red-600 text-white font-semibold py-3 px-6 rounded-lg hover:from-red-600 hover:to-red-700 transition transform hover:scale-105 shadow-lg">
                📋 Create Playlist
            </a>
        </div>
    </div>
</div>

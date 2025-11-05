<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!-- Starting admin page -->\n";

// Admin Panel - User Management
// Only accessible to admin users

try {
    echo "<!-- Getting user ID -->\n";
    $userId = $auth->getUserId();
    echo "<!-- User ID: $userId -->\n";
    
    echo "<!-- Getting user -->\n";
    $user = $auth->getUser();
    echo "<!-- User email: " . $user['email'] . " -->\n";
    
    // Check if user is admin
    $isAdmin = ($user['email'] === 'aik1979@gmail.com') || (isset($user['is_admin']) && $user['is_admin'] == 1);
    echo "<!-- Is admin: " . ($isAdmin ? 'YES' : 'NO') . " -->\n";
    
    if (!$isAdmin) {
        setFlashMessage('error', 'Access denied. Admin privileges required.');
        redirect('dashboard');
    }
    
    echo "<!-- Getting users -->\n";
    // Get all users with statistics
    $users = $db->fetchAll("
        SELECT 
            u.id,
            u.first_name,
            u.last_name,
            u.email,
            u.business_name,
            u.is_active,
            COALESCE(u.is_admin, 0) as is_admin,
            u.created_at,
            u.last_login,
            (SELECT COUNT(*) FROM screens WHERE user_id = u.id) as screen_count,
            (SELECT COUNT(*) FROM content WHERE user_id = u.id) as content_count,
            (SELECT COUNT(*) FROM playlists WHERE user_id = u.id) as playlist_count
        FROM users u
        ORDER BY u.created_at DESC
    ");
    echo "<!-- Found " . count($users) . " users -->\n";
    
    echo "<!-- Getting stats -->\n";
    // Get platform statistics
    $stats = [
        'total_users' => $db->fetchOne("SELECT COUNT(*) as count FROM users")['count'],
        'active_users' => $db->fetchOne("SELECT COUNT(*) as count FROM users WHERE is_active = 1")['count'],
        'total_screens' => $db->fetchOne("SELECT COUNT(*) as count FROM screens")['count'],
        'total_content' => $db->fetchOne("SELECT COUNT(*) as count FROM content")['count'],
        'total_playlists' => $db->fetchOne("SELECT COUNT(*) as count FROM playlists")['count'],
    ];
    echo "<!-- Stats retrieved -->\n";
    
} catch (Exception $e) {
    echo "<!-- ERROR: " . $e->getMessage() . " -->\n";
    echo "<!-- Stack trace: " . $e->getTraceAsString() . " -->\n";
    die("<h1>Error loading admin page</h1><pre>" . $e->getMessage() . "\n\n" . $e->getTraceAsString() . "</pre>");
}
?>

<div class="space-y-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-white mb-2">🛡️ Admin Panel (Debug Version)</h1>
        <p class="text-gray-400">Manage users and monitor platform activity</p>
    </div>

    <!-- Platform Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-5 gap-6 mb-8">
        <div class="bg-gradient-to-br from-dsp-blue to-blue-600 rounded-lg p-6 shadow-lg">
            <div class="text-white text-opacity-80 text-sm font-medium mb-2">Total Users</div>
            <div class="text-white text-3xl font-black"><?php echo $stats['total_users']; ?></div>
        </div>
        <div class="bg-gradient-to-br from-dsp-green to-green-600 rounded-lg p-6 shadow-lg">
            <div class="text-white text-opacity-80 text-sm font-medium mb-2">Active Users</div>
            <div class="text-white text-3xl font-black"><?php echo $stats['active_users']; ?></div>
        </div>
        <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-lg p-6 shadow-lg">
            <div class="text-white text-opacity-80 text-sm font-medium mb-2">Total Screens</div>
            <div class="text-white text-3xl font-black"><?php echo $stats['total_screens']; ?></div>
        </div>
        <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-lg p-6 shadow-lg">
            <div class="text-white text-opacity-80 text-sm font-medium mb-2">Total Content</div>
            <div class="text-white text-3xl font-black"><?php echo $stats['total_content']; ?></div>
        </div>
        <div class="bg-gradient-to-br from-pink-500 to-pink-600 rounded-lg p-6 shadow-lg">
            <div class="text-white text-opacity-80 text-sm font-medium mb-2">Total Playlists</div>
            <div class="text-white text-3xl font-black"><?php echo $stats['total_playlists']; ?></div>
        </div>
    </div>

    <!-- Users Table -->
    <div class="bg-gray-800 rounded-lg shadow-lg border border-gray-700 overflow-hidden">
        <div class="p-6 border-b border-gray-700">
            <h2 class="text-xl font-bold text-white">User Management</h2>
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-900">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">User</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Email</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Business</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Resources</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Role</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Joined</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Last Login</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-700">
                    <?php foreach ($users as $u): ?>
                    <tr class="hover:bg-gray-750 transition">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-white"><?php echo sanitize($u['first_name'] . ' ' . $u['last_name']); ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-300"><?php echo sanitize($u['email']); ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-300"><?php echo sanitize($u['business_name'] ?: '-'); ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-xs text-gray-400">
                                📺 <?php echo $u['screen_count']; ?> screens<br>
                                🎨 <?php echo $u['content_count']; ?> content<br>
                                📋 <?php echo $u['playlist_count']; ?> playlists
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php if ($u['is_active']): ?>
                                <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-900 text-green-300">Active</span>
                            <?php else: ?>
                                <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-900 text-red-300">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php if ($u['is_admin']): ?>
                                <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-purple-900 text-purple-300">Admin</span>
                            <?php else: ?>
                                <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-700 text-gray-300">User</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-400">
                            <?php echo date('M d, Y', strtotime($u['created_at'])); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-400">
                            <?php echo $u['last_login'] ? date('M d, Y', strtotime($u['last_login'])) : 'Never'; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <p class="text-gray-400 text-sm">This is a debug version without action buttons. Go to <a href="/?page=dashboard" class="text-blue-400 hover:underline">Dashboard</a></p>
</div>

<?php
// Admin Panel - User Management
// Only accessible to admin users

$userId = $auth->getUserId();
$user = $auth->getUser();

// Check if user is admin
$isAdmin = ($user['email'] === 'aik_1979@hotmail.com') || (isset($user['is_admin']) && $user['is_admin'] == 1);

if (!$isAdmin) {
    setFlashMessage('error', 'Access denied. Admin privileges required.');
    redirect('dashboard');
}

// Handle user actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    if ($_POST['action'] === 'toggle_status') {
        $targetUserId = intval($_POST['user_id']);
        $currentStatus = intval($_POST['current_status']);
        $newStatus = $currentStatus ? 0 : 1;
        
        try {
            $db->update('users', ['is_active' => $newStatus], 'id = :id', ['id' => $targetUserId]);
            $statusText = $newStatus ? 'activated' : 'deactivated';
            setFlashMessage('success', "User account {$statusText} successfully.");
        } catch (Exception $e) {
            setFlashMessage('error', 'Failed to update user status.');
        }
        redirect('admin');
    }
    
    if ($_POST['action'] === 'delete_user') {
        $targetUserId = intval($_POST['user_id']);
        
        // Prevent self-deletion
        if ($targetUserId === $userId) {
            setFlashMessage('error', 'You cannot delete your own account from the admin panel.');
            redirect('admin');
        }
        
        try {
            // Delete all user data (same as account deletion)
            $db->query("DELETE pi FROM playlist_items pi INNER JOIN playlists p ON pi.playlist_id = p.id WHERE p.user_id = ?", [$targetUserId]);
            $db->query("DELETE FROM playlists WHERE user_id = ?", [$targetUserId]);
            $db->query("DELETE FROM schedules WHERE user_id = ?", [$targetUserId]);
            
            // Delete content files
            $contentFiles = $db->fetchAll("SELECT file_path FROM content WHERE user_id = ?", [$targetUserId]);
            foreach ($contentFiles as $file) {
                $filePath = __DIR__ . '/../' . $file['file_path'];
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }
            
            $db->query("DELETE FROM content WHERE user_id = ?", [$targetUserId]);
            $db->query("DELETE FROM screens WHERE user_id = ?", [$targetUserId]);
            $db->query("DELETE FROM short_urls WHERE user_id = ?", [$targetUserId]);
            $db->query("DELETE FROM activity_log WHERE user_id = ?", [$targetUserId]);
            $db->query("DELETE FROM users WHERE id = ?", [$targetUserId]);
            
            logActivity($db, $userId, 'user_deleted', 'user', $targetUserId, 'Admin deleted user account');
            setFlashMessage('success', 'User account and all associated data deleted successfully.');
        } catch (Exception $e) {
            setFlashMessage('error', 'Failed to delete user account.');
        }
        redirect('admin');
    }
    
    if ($_POST['action'] === 'toggle_admin') {
        $targetUserId = intval($_POST['user_id']);
        $currentAdmin = intval($_POST['current_admin']);
        $newAdmin = $currentAdmin ? 0 : 1;
        
        try {
            $db->update('users', ['is_admin' => $newAdmin], 'id = :id', ['id' => $targetUserId]);
            $adminText = $newAdmin ? 'granted' : 'revoked';
            setFlashMessage('success', "Admin privileges {$adminText} successfully.");
            logActivity($db, $userId, 'admin_privileges_changed', 'user', $targetUserId, "Admin privileges {$adminText}");
        } catch (Exception $e) {
            setFlashMessage('error', 'Failed to update admin privileges.');
        }
        redirect('admin');
    }
}

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

// Get platform statistics
$stats = [
    'total_users' => $db->fetchOne("SELECT COUNT(*) as count FROM users")['count'],
    'active_users' => $db->fetchOne("SELECT COUNT(*) as count FROM users WHERE is_active = 1")['count'],
    'total_screens' => $db->fetchOne("SELECT COUNT(*) as count FROM screens")['count'],
    'total_content' => $db->fetchOne("SELECT COUNT(*) as count FROM content")['count'],
    'total_playlists' => $db->fetchOne("SELECT COUNT(*) as count FROM playlists")['count'],
];
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-white mb-2">🛡️ Admin Panel</h1>
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
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-400 uppercase tracking-wider">Actions</th>
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
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <div class="flex justify-end gap-2">
                                <!-- Toggle Status -->
                                <form method="POST" class="inline">
                                    <input type="hidden" name="action" value="toggle_status">
                                    <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                    <input type="hidden" name="current_status" value="<?php echo $u['is_active']; ?>">
                                    <button type="submit" class="text-<?php echo $u['is_active'] ? 'red' : 'green'; ?>-400 hover:text-<?php echo $u['is_active'] ? 'red' : 'green'; ?>-300 transition" title="<?php echo $u['is_active'] ? 'Deactivate' : 'Activate'; ?>">
                                        <?php echo $u['is_active'] ? '🔴' : '🟢'; ?>
                                    </button>
                                </form>
                                
                                <!-- Toggle Admin -->
                                <?php if ($u['id'] !== $userId): ?>
                                <form method="POST" class="inline">
                                    <input type="hidden" name="action" value="toggle_admin">
                                    <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                    <input type="hidden" name="current_admin" value="<?php echo $u['is_admin']; ?>">
                                    <button type="submit" class="text-purple-400 hover:text-purple-300 transition" title="<?php echo $u['is_admin'] ? 'Revoke Admin' : 'Grant Admin'; ?>">
                                        <?php echo $u['is_admin'] ? '👑' : '👤'; ?>
                                    </button>
                                </form>
                                <?php endif; ?>
                                
                                <!-- Delete User -->
                                <?php if ($u['id'] !== $userId): ?>
                                <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this user and all their data? This action cannot be undone!');">
                                    <input type="hidden" name="action" value="delete_user">
                                    <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                    <button type="submit" class="text-red-400 hover:text-red-300 transition" title="Delete User">
                                        🗑️
                                    </button>
                                </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

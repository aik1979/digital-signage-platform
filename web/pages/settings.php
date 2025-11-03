<?php
$userId = $auth->getUserId();
$user = $auth->getUser();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    if ($_POST['action'] === 'update_profile') {
        $firstName = sanitize($_POST['first_name'] ?? '');
        $lastName = sanitize($_POST['last_name'] ?? '');
        $businessName = sanitize($_POST['business_name'] ?? '');
        $phone = sanitize($_POST['phone'] ?? '');
        
        if (empty($firstName) || empty($lastName)) {
            setFlashMessage('error', 'First name and last name are required.');
        } else {
            try {
                $db->update('users', [
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'business_name' => $businessName,
                    'phone' => $phone
                ], 'id = :id', ['id' => $userId]);
                
                // Update session name
                $_SESSION['user_name'] = $firstName . ' ' . $lastName;
                
                logActivity($db, $userId, 'profile_updated', 'user', $userId, 'Profile information updated');
                setFlashMessage('success', 'Profile updated successfully!');
                redirect('settings');
            } catch (Exception $e) {
                setFlashMessage('error', 'Failed to update profile. Please try again.');
            }
        }
    }
    
    if ($_POST['action'] === 'delete_account') {
        $password = $_POST['password'] ?? '';
        
        if (empty($password)) {
            setFlashMessage('error', 'Password is required to delete your account.');
        } else {
            // Verify password
            if (!$auth->verifyPassword($user['email'], $password)) {
                setFlashMessage('error', 'Incorrect password.');
            } else {
                try {
                    // Delete all user data
                    // 1. Delete playlist items
                    $db->query("DELETE pi FROM playlist_items pi INNER JOIN playlists p ON pi.playlist_id = p.id WHERE p.user_id = ?", [$userId]);
                    
                    // 2. Delete playlists
                    $db->query("DELETE FROM playlists WHERE user_id = ?", [$userId]);
                    
                    // 3. Delete schedules
                    $db->query("DELETE FROM schedules WHERE user_id = ?", [$userId]);
                    
                    // 4. Delete content files (get file paths first)
                    $contentFiles = $db->fetchAll("SELECT file_path FROM content WHERE user_id = ?", [$userId]);
                    foreach ($contentFiles as $file) {
                        $filePath = __DIR__ . '/../' . $file['file_path'];
                        if (file_exists($filePath)) {
                            unlink($filePath);
                        }
                    }
                    
                    // 5. Delete content records
                    $db->query("DELETE FROM content WHERE user_id = ?", [$userId]);
                    
                    // 6. Delete screens
                    $db->query("DELETE FROM screens WHERE user_id = ?", [$userId]);
                    
                    // 7. Delete short URLs
                    $db->query("DELETE FROM short_urls WHERE user_id = ?", [$userId]);
                    
                    // 8. Delete activity logs
                    $db->query("DELETE FROM activity_log WHERE user_id = ?", [$userId]);
                    
                    // 9. Finally, delete the user
                    $db->query("DELETE FROM users WHERE id = ?", [$userId]);
                    
                    // Log out and redirect
                    session_destroy();
                    header('Location: ?page=login&deleted=1');
                    exit;
                } catch (Exception $e) {
                    setFlashMessage('error', 'Failed to delete account. Please contact support.');
                }
            }
        }
    }
    
    if ($_POST['action'] === 'change_password') {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            setFlashMessage('error', 'All password fields are required.');
        } elseif ($newPassword !== $confirmPassword) {
            setFlashMessage('error', 'New passwords do not match.');
        } elseif (strlen($newPassword) < PASSWORD_MIN_LENGTH) {
            setFlashMessage('error', 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters.');
        } else {
            $result = $auth->changePassword($userId, $currentPassword, $newPassword);
            
            if ($result['success']) {
                logActivity($db, $userId, 'password_changed', 'user', $userId, 'Password changed successfully');
                setFlashMessage('success', 'Password changed successfully!');
                redirect('settings');
            } else {
                setFlashMessage('error', $result['message']);
            }
        }
    }
    
    if ($_POST['action'] === 'change_email') {
        $currentPassword = $_POST['email_password'] ?? '';
        $newEmail = sanitize($_POST['new_email'] ?? '');
        
        if (empty($currentPassword) || empty($newEmail)) {
            setFlashMessage('error', 'Password and new email are required.');
        } elseif (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
            setFlashMessage('error', 'Invalid email address.');
        } else {
            // Verify current password
            $userCheck = $db->fetchOne("SELECT password_hash FROM users WHERE id = ?", [$userId]);
            
            if (!password_verify($currentPassword, $userCheck['password_hash'])) {
                setFlashMessage('error', 'Current password is incorrect.');
            } else {
                // Check if email already exists
                $emailExists = $db->fetchOne("SELECT id FROM users WHERE email = ? AND id != ?", [$newEmail, $userId]);
                
                if ($emailExists) {
                    setFlashMessage('error', 'This email is already in use.');
                } else {
                    try {
                        $db->update('users', [
                            'email' => $newEmail
                        ], 'id = :id', ['id' => $userId]);
                        
                        $_SESSION['user_email'] = $newEmail;
                        
                        logActivity($db, $userId, 'email_changed', 'user', $userId, 'Email address changed to ' . $newEmail);
                        setFlashMessage('success', 'Email address changed successfully!');
                        redirect('settings');
                    } catch (Exception $e) {
                        setFlashMessage('error', 'Failed to update email. Please try again.');
                    }
                }
            }
        }
    }
}

// Refresh user data after updates
$user = $auth->getUser();

// Get account statistics
$totalScreens = $db->fetchOne("SELECT COUNT(*) as count FROM screens WHERE user_id = ?", [$userId])['count'];
$totalContent = $db->fetchOne("SELECT COUNT(*) as count FROM content WHERE user_id = ?", [$userId])['count'];
$totalPlaylists = $db->fetchOne("SELECT COUNT(*) as count FROM playlists WHERE user_id = ?", [$userId])['count'];
$accountAge = floor((time() - strtotime($user['created_at'])) / 86400);
?>

<div class="space-y-8">
    <div>
        <h1 class="text-3xl font-bold text-white mb-2">Settings</h1>
        <p class="text-gray-400">Manage your account and preferences</p>
    </div>

    <!-- Account Overview -->
    <div class="bg-gray-800 border border-gray-700 rounded-lg p-6 shadow-lg">
        <h2 class="text-2xl font-bold text-white mb-6">Account Overview</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <div class="text-center">
                <p class="text-gray-400 text-sm mb-1">Member Since</p>
                <p class="text-2xl font-bold text-white"><?php echo date('M j, Y', strtotime($user['created_at'])); ?></p>
                <p class="text-gray-500 text-xs mt-1"><?php echo $accountAge; ?> days ago</p>
            </div>
            <div class="text-center">
                <p class="text-gray-400 text-sm mb-1">Total Screens</p>
                <p class="text-2xl font-bold text-white"><?php echo $totalScreens; ?></p>
            </div>
            <div class="text-center">
                <p class="text-gray-400 text-sm mb-1">Content Items</p>
                <p class="text-2xl font-bold text-white"><?php echo $totalContent; ?></p>
            </div>
            <div class="text-center">
                <p class="text-gray-400 text-sm mb-1">Playlists</p>
                <p class="text-2xl font-bold text-white"><?php echo $totalPlaylists; ?></p>
            </div>
        </div>
    </div>

    <!-- Profile Information -->
    <div class="bg-gray-800 border border-gray-700 rounded-lg p-6 shadow-lg">
        <h2 class="text-2xl font-bold text-white mb-6">Profile Information</h2>
        <form method="POST" action="?page=settings" class="space-y-4">
            <input type="hidden" name="action" value="update_profile">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="first_name" class="block text-sm font-medium text-gray-300 mb-2">First Name *</label>
                    <input type="text" id="first_name" name="first_name" required 
                           value="<?php echo sanitize($user['first_name']); ?>"
                           class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-dsp-blue focus:border-transparent transition">
                </div>
                
                <div>
                    <label for="last_name" class="block text-sm font-medium text-gray-300 mb-2">Last Name *</label>
                    <input type="text" id="last_name" name="last_name" required 
                           value="<?php echo sanitize($user['last_name']); ?>"
                           class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-dsp-blue focus:border-transparent transition">
                </div>
            </div>
            
            <div>
                <label for="business_name" class="block text-sm font-medium text-gray-300 mb-2">Business Name</label>
                <input type="text" id="business_name" name="business_name" 
                       value="<?php echo sanitize($user['business_name'] ?? ''); ?>"
                       placeholder="Your business or company name"
                       class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-dsp-blue focus:border-transparent transition">
            </div>
            
            <div>
                <label for="phone" class="block text-sm font-medium text-gray-300 mb-2">Phone Number</label>
                <input type="tel" id="phone" name="phone" 
                       value="<?php echo sanitize($user['phone'] ?? ''); ?>"
                       placeholder="+44 1234 567890"
                       class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-dsp-blue focus:border-transparent transition">
            </div>
            
            <button type="submit" class="bg-gradient-to-r from-dsp-blue to-blue-600 text-white font-semibold py-2 px-6 rounded-lg hover:from-blue-600 hover:to-blue-700 transition transform hover:scale-105 shadow-lg">
                Update Profile
            </button>
        </form>
    </div>

    <!-- Email Address -->
    <div class="bg-gray-800 border border-gray-700 rounded-lg p-6 shadow-lg">
        <h2 class="text-2xl font-bold text-white mb-4">Email Address</h2>
        <div class="mb-4">
            <p class="text-gray-400"><strong class="text-white">Current Email:</strong> <?php echo sanitize($user['email']); ?></p>
        </div>
        
        <button type="button" class="bg-gray-700 text-white font-semibold py-2 px-6 rounded-lg hover:bg-gray-600 transition" onclick="toggleSection('email-change')">
            Change Email Address
        </button>
        
        <div id="email-change" class="mt-6 space-y-4" style="display: none;">
            <form method="POST" action="?page=settings" class="space-y-4">
                <input type="hidden" name="action" value="change_email">
                
                <div>
                    <label for="new_email" class="block text-sm font-medium text-gray-300 mb-2">New Email Address *</label>
                    <input type="email" id="new_email" name="new_email" required 
                           placeholder="new@example.com"
                           class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-dsp-blue focus:border-transparent transition">
                </div>
                
                <div>
                    <label for="email_password" class="block text-sm font-medium text-gray-300 mb-2">Current Password (for verification) *</label>
                    <input type="password" id="email_password" name="email_password" required 
                           placeholder="Enter your current password"
                           class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-dsp-blue focus:border-transparent transition">
                </div>
                
                <div class="flex space-x-3">
                    <button type="submit" class="bg-gradient-to-r from-dsp-blue to-blue-600 text-white font-semibold py-2 px-6 rounded-lg hover:from-blue-600 hover:to-blue-700 transition shadow-lg">
                        Change Email
                    </button>
                    <button type="button" class="bg-gray-700 text-white font-semibold py-2 px-6 rounded-lg hover:bg-gray-600 transition" onclick="toggleSection('email-change')">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Change Password -->
    <div class="bg-gray-800 border border-gray-700 rounded-lg p-6 shadow-lg">
        <h2 class="text-2xl font-bold text-white mb-4">Change Password</h2>
        <p class="text-gray-400 mb-4">Update your password to keep your account secure.</p>
        
        <button type="button" class="bg-gray-700 text-white font-semibold py-2 px-6 rounded-lg hover:bg-gray-600 transition" onclick="toggleSection('password-change')">
            Change Password
        </button>
        
        <div id="password-change" class="mt-6 space-y-4" style="display: none;">
            <form method="POST" action="?page=settings" class="space-y-4">
                <input type="hidden" name="action" value="change_password">
                
                <div>
                    <label for="current_password" class="block text-sm font-medium text-gray-300 mb-2">Current Password *</label>
                    <input type="password" id="current_password" name="current_password" required 
                           placeholder="Enter your current password"
                           class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-dsp-blue focus:border-transparent transition">
                </div>
                
                <div>
                    <label for="new_password" class="block text-sm font-medium text-gray-300 mb-2">New Password *</label>
                    <input type="password" id="new_password" name="new_password" required 
                           placeholder="Minimum <?php echo PASSWORD_MIN_LENGTH; ?> characters"
                           class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-dsp-blue focus:border-transparent transition">
                    <p class="text-xs text-gray-400 mt-1">Minimum <?php echo PASSWORD_MIN_LENGTH; ?> characters</p>
                </div>
                
                <div>
                    <label for="confirm_password" class="block text-sm font-medium text-gray-300 mb-2">Confirm New Password *</label>
                    <input type="password" id="confirm_password" name="confirm_password" required 
                           placeholder="Re-enter your new password"
                           class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-dsp-blue focus:border-transparent transition">
                </div>
                
                <div class="flex space-x-3">
                    <button type="submit" class="bg-gradient-to-r from-dsp-blue to-blue-600 text-white font-semibold py-2 px-6 rounded-lg hover:from-blue-600 hover:to-blue-700 transition shadow-lg">
                        Change Password
                    </button>
                    <button type="button" class="bg-gray-700 text-white font-semibold py-2 px-6 rounded-lg hover:bg-gray-600 transition" onclick="toggleSection('password-change')">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Tour Preferences -->
    <div class="bg-gray-800 border border-gray-700 rounded-lg p-6 shadow-lg">
        <h2 class="text-2xl font-bold text-white mb-4">🎯 Guided Tour Preferences</h2>
        <p class="text-gray-400 mb-6">Control when and how the interactive guided tours appear.</p>
        
        <div class="space-y-4">
            <div class="flex items-center justify-between p-4 bg-gray-700 rounded-lg">
                <div>
                    <p class="font-bold text-white mb-1">Auto-start Tours</p>
                    <p class="text-sm text-gray-400">Automatically show guided tours when visiting pages for the first time.</p>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" id="tourEnabled" class="sr-only peer" checked onchange="toggleTourPreference()">
                    <div class="w-14 h-7 bg-gray-600 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-800 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[4px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-dsp-blue"></div>
                </label>
            </div>
            
            <div class="flex items-center justify-between p-4 bg-gray-700 rounded-lg">
                <div>
                    <p class="font-bold text-white mb-1">Restart Tour</p>
                    <p class="text-sm text-gray-400">Manually start the guided tour for the current page.</p>
                </div>
                <button onclick="startTour()" class="bg-gradient-to-r from-yellow-500 to-yellow-600 text-white font-semibold py-2 px-6 rounded-lg hover:from-yellow-600 hover:to-yellow-700 transition shadow-lg">
                    🎯 Start Tour
                </button>
            </div>
            
            <div class="bg-blue-900 bg-opacity-30 border border-blue-700 rounded-lg p-4">
                <p class="text-blue-300 text-sm">
                    <strong>💡 Tip:</strong> Tours are helpful for learning the platform. They automatically appear once per page per session. You can always restart them using the 🎯 Tour button in the navigation bar.
                </p>
            </div>
        </div>
    </div>

    <!-- Danger Zone -->
    <div class="bg-red-900 bg-opacity-20 border-2 border-red-700 rounded-lg p-6 shadow-lg">
        <h2 class="text-2xl font-bold text-red-400 mb-4">Danger Zone</h2>
        <p class="text-gray-400 mb-6">Irreversible and destructive actions.</p>
        
        <div class="flex items-center justify-between p-4 bg-gray-800 rounded-lg border border-red-700">
            <div>
                <p class="font-bold text-white mb-1">Delete Account</p>
                <p class="text-sm text-gray-400">Permanently delete your account and all associated data.</p>
            </div>
            <button type="button" class="bg-gradient-to-r from-dsp-red to-red-600 text-white font-semibold py-2 px-6 rounded-lg hover:from-red-600 hover:to-red-700 transition shadow-lg" onclick="toggleModal('deleteAccountModal')">
                Delete Account
            </button>
        </div>
    </div>
</div>

<!-- Delete Account Modal -->
<div id="deleteAccountModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" style="display: none;">
    <div class="bg-gray-800 rounded-lg shadow-2xl max-w-md w-full mx-4 border-2 border-red-700">
        <div class="flex items-center justify-between p-6 border-b border-red-700 bg-red-900 bg-opacity-20">
            <h2 class="text-2xl font-bold text-red-400">⚠️ Delete Account</h2>
            <button type="button" class="text-gray-400 hover:text-white text-3xl leading-none" onclick="toggleModal('deleteAccountModal')">&times;</button>
        </div>
        <form method="POST" action="?page=settings" onsubmit="return confirmDelete()">
            <input type="hidden" name="action" value="delete_account">
            <div class="p-6 space-y-4">
                <div class="bg-red-900 bg-opacity-20 border border-red-700 rounded-lg p-4">
                    <p class="text-red-400 font-bold mb-2">⚠️ Warning: This action is irreversible!</p>
                    <p class="text-gray-300 text-sm">This will permanently delete:</p>
                    <ul class="text-gray-400 text-sm mt-2 space-y-1 list-disc list-inside">
                        <li>Your account and profile</li>
                        <li>All screens and device keys</li>
                        <li>All uploaded content files</li>
                        <li>All playlists and schedules</li>
                        <li>All activity logs</li>
                    </ul>
                </div>
                
                <div>
                    <label for="delete_password" class="block text-sm font-medium text-gray-300 mb-2">
                        Enter your password to confirm:
                    </label>
                    <input type="password" id="delete_password" name="password" required
                           class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent"
                           placeholder="Your password">
                </div>
                
                <div class="bg-gray-900 border border-gray-700 rounded-lg p-4">
                    <label class="flex items-start">
                        <input type="checkbox" id="confirmDelete" required
                               class="mt-1 mr-3 w-5 h-5 text-red-600 bg-gray-700 border-gray-600 rounded focus:ring-red-500">
                        <span class="text-sm text-gray-300">
                            I understand this action cannot be undone and all my data will be permanently deleted.
                        </span>
                    </label>
                </div>
            </div>
            <div class="flex justify-end gap-3 p-6 border-t border-gray-700 bg-gray-900">
                <button type="button" class="bg-gray-700 text-white font-semibold py-2 px-6 rounded-lg hover:bg-gray-600 transition" onclick="toggleModal('deleteAccountModal')">
                    Cancel
                </button>
                <button type="submit" class="bg-gradient-to-r from-dsp-red to-red-600 text-white font-semibold py-2 px-6 rounded-lg hover:from-red-600 hover:to-red-700 transition shadow-lg">
                    Delete My Account
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleSection(sectionId) {
    const section = document.getElementById(sectionId);
    if (section.style.display === 'none') {
        section.style.display = 'block';
    } else {
        section.style.display = 'none';
    }
}

function toggleTourPreference() {
    const checkbox = document.getElementById('tourEnabled');
    if (checkbox.checked) {
        enableTour();
        alert('✅ Guided tours enabled! Tours will automatically appear when you visit pages.');
    } else {
        disableTour();
        alert('❌ Guided tours disabled. You can still manually start tours using the 🎯 Tour button.');
    }
}

function confirmDelete() {
    const checkbox = document.getElementById('confirmDelete');
    if (!checkbox.checked) {
        alert('Please confirm that you understand this action cannot be undone.');
        return false;
    }
    return confirm('Are you absolutely sure? This will permanently delete your account and all data. This action CANNOT be undone!');
}

// Initialize tour toggle state on page load
window.addEventListener('DOMContentLoaded', function() {
    const checkbox = document.getElementById('tourEnabled');
    if (checkbox) {
        checkbox.checked = !isTourDisabled();
    }
});
</script>

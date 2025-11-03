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

<div class="page-header">
    <h1>Settings</h1>
    <p>Manage your account and preferences</p>
</div>

<div class="settings-layout">
    <!-- Account Overview -->
    <div class="settings-card">
        <h2>Account Overview</h2>
        <div class="account-stats">
            <div class="stat-item">
                <span class="stat-label">Member Since</span>
                <span class="stat-value"><?php echo date('F j, Y', strtotime($user['created_at'])); ?></span>
                <span class="stat-detail"><?php echo $accountAge; ?> days ago</span>
            </div>
            <div class="stat-item">
                <span class="stat-label">Total Screens</span>
                <span class="stat-value"><?php echo $totalScreens; ?></span>
            </div>
            <div class="stat-item">
                <span class="stat-label">Content Items</span>
                <span class="stat-value"><?php echo $totalContent; ?></span>
            </div>
            <div class="stat-item">
                <span class="stat-label">Playlists</span>
                <span class="stat-value"><?php echo $totalPlaylists; ?></span>
            </div>
        </div>
    </div>

    <!-- Profile Information -->
    <div class="settings-card">
        <h2>Profile Information</h2>
        <form method="POST" action="?page=settings" class="settings-form">
            <input type="hidden" name="action" value="update_profile">
            
            <div class="form-row">
                <div class="form-group">
                    <label for="first_name">First Name *</label>
                    <input type="text" id="first_name" name="first_name" required 
                           value="<?php echo sanitize($user['first_name']); ?>">
                </div>
                
                <div class="form-group">
                    <label for="last_name">Last Name *</label>
                    <input type="text" id="last_name" name="last_name" required 
                           value="<?php echo sanitize($user['last_name']); ?>">
                </div>
            </div>
            
            <div class="form-group">
                <label for="business_name">Business Name</label>
                <input type="text" id="business_name" name="business_name" 
                       value="<?php echo sanitize($user['business_name'] ?? ''); ?>"
                       placeholder="Your business or company name">
            </div>
            
            <div class="form-group">
                <label for="phone">Phone Number</label>
                <input type="tel" id="phone" name="phone" 
                       value="<?php echo sanitize($user['phone'] ?? ''); ?>"
                       placeholder="+44 1234 567890">
            </div>
            
            <button type="submit" class="btn btn-primary">Update Profile</button>
        </form>
    </div>

    <!-- Email Address -->
    <div class="settings-card">
        <h2>Email Address</h2>
        <div class="current-info">
            <p><strong>Current Email:</strong> <?php echo sanitize($user['email']); ?></p>
        </div>
        
        <button type="button" class="btn btn-secondary" onclick="toggleSection('email-change')">
            Change Email Address
        </button>
        
        <div id="email-change" class="collapsible-section" style="display: none;">
            <form method="POST" action="?page=settings" class="settings-form">
                <input type="hidden" name="action" value="change_email">
                
                <div class="form-group">
                    <label for="new_email">New Email Address *</label>
                    <input type="email" id="new_email" name="new_email" required 
                           placeholder="new@example.com">
                </div>
                
                <div class="form-group">
                    <label for="email_password">Current Password (for verification) *</label>
                    <input type="password" id="email_password" name="email_password" required 
                           placeholder="Enter your current password">
                </div>
                
                <button type="submit" class="btn btn-primary">Change Email</button>
                <button type="button" class="btn btn-secondary" onclick="toggleSection('email-change')">Cancel</button>
            </form>
        </div>
    </div>

    <!-- Change Password -->
    <div class="settings-card">
        <h2>Change Password</h2>
        <p class="card-description">Update your password to keep your account secure.</p>
        
        <button type="button" class="btn btn-secondary" onclick="toggleSection('password-change')">
            Change Password
        </button>
        
        <div id="password-change" class="collapsible-section" style="display: none;">
            <form method="POST" action="?page=settings" class="settings-form">
                <input type="hidden" name="action" value="change_password">
                
                <div class="form-group">
                    <label for="current_password">Current Password *</label>
                    <input type="password" id="current_password" name="current_password" required 
                           placeholder="Enter your current password">
                </div>
                
                <div class="form-group">
                    <label for="new_password">New Password *</label>
                    <input type="password" id="new_password" name="new_password" required 
                           placeholder="Minimum <?php echo PASSWORD_MIN_LENGTH; ?> characters">
                    <small>Minimum <?php echo PASSWORD_MIN_LENGTH; ?> characters</small>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm New Password *</label>
                    <input type="password" id="confirm_password" name="confirm_password" required 
                           placeholder="Re-enter your new password">
                </div>
                
                <button type="submit" class="btn btn-primary">Change Password</button>
                <button type="button" class="btn btn-secondary" onclick="toggleSection('password-change')">Cancel</button>
            </form>
        </div>
    </div>

    <!-- Danger Zone -->
    <div class="settings-card danger-zone">
        <h2>Danger Zone</h2>
        <p class="card-description">Irreversible and destructive actions.</p>
        
        <div class="danger-actions">
            <div class="danger-item">
                <div>
                    <strong>Delete Account</strong>
                    <p>Permanently delete your account and all associated data.</p>
                </div>
                <button type="button" class="btn btn-danger" onclick="alert('Account deletion feature coming soon. Please contact support.')">
                    Delete Account
                </button>
            </div>
        </div>
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
</script>

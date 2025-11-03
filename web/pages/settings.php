<?php
// Settings page - Coming soon
$user = $auth->getUser();
?>
<div class="page-header">
    <h1>Settings</h1>
    <p>Manage your account settings</p>
</div>

<div class="settings-container">
    <h2>Account Information</h2>
    <div class="info-grid">
        <div class="info-item">
            <strong>Name:</strong>
            <span><?php echo sanitize($user['first_name'] . ' ' . $user['last_name']); ?></span>
        </div>
        <div class="info-item">
            <strong>Email:</strong>
            <span><?php echo sanitize($user['email']); ?></span>
        </div>
        <div class="info-item">
            <strong>Business:</strong>
            <span><?php echo sanitize($user['business_name'] ?: 'Not set'); ?></span>
        </div>
        <div class="info-item">
            <strong>Member Since:</strong>
            <span><?php echo date('F j, Y', strtotime($user['created_at'])); ?></span>
        </div>
    </div>
    
    <div class="empty-state">
        <h3>🚧 Additional Settings Coming Soon</h3>
        <p>Password change, profile editing, and more settings will be available soon.</p>
    </div>
</div>

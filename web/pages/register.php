<?php
// Handle registration form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $firstName = sanitize($_POST['first_name'] ?? '');
    $lastName = sanitize($_POST['last_name'] ?? '');
    $businessName = sanitize($_POST['business_name'] ?? '');
    
    // Validation
    if (empty($email) || empty($password) || empty($firstName) || empty($lastName)) {
        $error = 'Please fill in all required fields';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match';
    } else {
        $result = $auth->register($email, $password, $firstName, $lastName, $businessName);
        
        if ($result['success']) {
            setFlashMessage('success', 'Registration successful! Please login.');
            redirect('login');
        } else {
            $error = $result['message'];
        }
    }
}
?>

<div class="auth-container">
    <div class="auth-box">
        <h1>Register</h1>
        <p class="subtitle">Create your <?php echo APP_NAME; ?> account</p>
        
        <?php if (isset($error)): ?>
        <div class="alert alert-error"><?php echo sanitize($error); ?></div>
        <?php endif; ?>
        
        <form method="POST" action="?page=register">
            <div class="form-row">
                <div class="form-group">
                    <label for="first_name">First Name *</label>
                    <input type="text" id="first_name" name="first_name" required 
                           value="<?php echo isset($firstName) ? sanitize($firstName) : ''; ?>" 
                           placeholder="John">
                </div>
                
                <div class="form-group">
                    <label for="last_name">Last Name *</label>
                    <input type="text" id="last_name" name="last_name" required 
                           value="<?php echo isset($lastName) ? sanitize($lastName) : ''; ?>" 
                           placeholder="Doe">
                </div>
            </div>
            
            <div class="form-group">
                <label for="business_name">Business Name (Optional)</label>
                <input type="text" id="business_name" name="business_name" 
                       value="<?php echo isset($businessName) ? sanitize($businessName) : ''; ?>" 
                       placeholder="Your Business Name">
            </div>
            
            <div class="form-group">
                <label for="email">Email Address *</label>
                <input type="email" id="email" name="email" required 
                       value="<?php echo isset($email) ? sanitize($email) : ''; ?>" 
                       placeholder="you@example.com">
            </div>
            
            <div class="form-group">
                <label for="password">Password *</label>
                <input type="password" id="password" name="password" required 
                       placeholder="Minimum 8 characters">
                <small>Minimum <?php echo PASSWORD_MIN_LENGTH; ?> characters</small>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirm Password *</label>
                <input type="password" id="confirm_password" name="confirm_password" required 
                       placeholder="Re-enter your password">
            </div>
            
            <button type="submit" class="btn btn-primary btn-block">Create Account</button>
        </form>
        
        <div class="auth-links">
            <p>Already have an account? <a href="?page=login">Login here</a></p>
        </div>
    </div>
</div>

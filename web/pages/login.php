<?php
// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password';
    } else {
        $result = $auth->login($email, $password);
        
        if ($result['success']) {
            logActivity($db, $auth->getUserId(), 'login', 'user', $auth->getUserId(), 'User logged in');
            redirect('dashboard');
        } else {
            $error = $result['message'];
        }
    }
}
?>

<div class="auth-container">
    <div class="auth-box">
        <h1>Login</h1>
        <p class="subtitle">Welcome back to <?php echo APP_NAME; ?></p>
        
        <?php if (isset($error)): ?>
        <div class="alert alert-error"><?php echo sanitize($error); ?></div>
        <?php endif; ?>
        
        <form method="POST" action="?page=login">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" required 
                       value="<?php echo isset($email) ? sanitize($email) : ''; ?>" 
                       placeholder="you@example.com">
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required 
                       placeholder="Enter your password">
            </div>
            
            <button type="submit" class="btn btn-primary btn-block">Login</button>
        </form>
        
        <div class="auth-links">
            <p>Don't have an account? <a href="?page=register">Register here</a></p>
            <p><a href="?page=forgot-password">Forgot your password?</a></p>
        </div>
    </div>
</div>

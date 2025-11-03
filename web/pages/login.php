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

<div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-gray-900 via-gray-800 to-black py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        <div class="bg-gray-800 shadow-2xl rounded-lg p-8 border border-gray-700">
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-white mb-2">Login</h1>
                <p class="text-gray-400">Welcome back to <?php echo APP_NAME; ?></p>
            </div>
            
            <?php if (isset($error)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6" role="alert">
                <?php echo sanitize($error); ?>
            </div>
            <?php endif; ?>
            
            <form method="POST" action="?page=login" class="space-y-6">
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-300 mb-2">Email Address</label>
                    <input type="email" id="email" name="email" required 
                           value="<?php echo isset($email) ? sanitize($email) : ''; ?>" 
                           placeholder="you@example.com"
                           class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-dsp-blue focus:border-transparent transition">
                </div>
                
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-300 mb-2">Password</label>
                    <input type="password" id="password" name="password" required 
                           placeholder="Enter your password"
                           class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-dsp-blue focus:border-transparent transition">
                </div>
                
                <button type="submit" class="w-full bg-gradient-to-r from-dsp-blue to-blue-600 text-white font-semibold py-3 px-4 rounded-lg hover:from-blue-600 hover:to-blue-700 focus:outline-none focus:ring-2 focus:ring-dsp-blue focus:ring-offset-2 focus:ring-offset-gray-800 transform transition hover:scale-[1.02] active:scale-[0.98] shadow-lg">
                    Login
                </button>
            </form>
            
            <div class="mt-6 text-center space-y-2">
                <p class="text-gray-400 text-sm">Don't have an account? <a href="?page=register" class="text-dsp-blue hover:text-blue-400 font-medium transition">Register here</a></p>
                <p class="text-gray-400 text-sm"><a href="?page=forgot-password" class="text-dsp-blue hover:text-blue-400 font-medium transition">Forgot your password?</a></p>
            </div>
        </div>
    </div>
</div>

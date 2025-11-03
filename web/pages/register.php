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

<div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-gray-900 via-gray-800 to-black py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        <div class="bg-gray-800 shadow-2xl rounded-lg p-8 border border-gray-700">
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-white mb-2">Register</h1>
                <p class="text-gray-400">Create your <?php echo APP_NAME; ?> account</p>
            </div>
            
            <?php if (isset($error)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6" role="alert">
                <?php echo sanitize($error); ?>
            </div>
            <?php endif; ?>
            
            <form method="POST" action="?page=register" class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="first_name" class="block text-sm font-medium text-gray-300 mb-2">First Name *</label>
                        <input type="text" id="first_name" name="first_name" required 
                               value="<?php echo isset($firstName) ? sanitize($firstName) : ''; ?>" 
                               placeholder="John"
                               class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-dsp-blue focus:border-transparent transition">
                    </div>
                    
                    <div>
                        <label for="last_name" class="block text-sm font-medium text-gray-300 mb-2">Last Name *</label>
                        <input type="text" id="last_name" name="last_name" required 
                               value="<?php echo isset($lastName) ? sanitize($lastName) : ''; ?>" 
                               placeholder="Doe"
                               class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-dsp-blue focus:border-transparent transition">
                    </div>
                </div>
                
                <div>
                    <label for="business_name" class="block text-sm font-medium text-gray-300 mb-2">Business Name (Optional)</label>
                    <input type="text" id="business_name" name="business_name" 
                           value="<?php echo isset($businessName) ? sanitize($businessName) : ''; ?>" 
                           placeholder="Your Business Name"
                           class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-dsp-blue focus:border-transparent transition">
                </div>
                
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-300 mb-2">Email Address *</label>
                    <input type="email" id="email" name="email" required 
                           value="<?php echo isset($email) ? sanitize($email) : ''; ?>" 
                           placeholder="you@example.com"
                           class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-dsp-blue focus:border-transparent transition">
                </div>
                
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-300 mb-2">Password *</label>
                    <input type="password" id="password" name="password" required 
                           placeholder="Minimum 8 characters"
                           class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-dsp-blue focus:border-transparent transition">
                    <p class="text-xs text-gray-400 mt-1">Minimum <?php echo PASSWORD_MIN_LENGTH; ?> characters</p>
                </div>
                
                <div>
                    <label for="confirm_password" class="block text-sm font-medium text-gray-300 mb-2">Confirm Password *</label>
                    <input type="password" id="confirm_password" name="confirm_password" required 
                           placeholder="Re-enter your password"
                           class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-dsp-blue focus:border-transparent transition">
                </div>
                
                <button type="submit" class="w-full bg-gradient-to-r from-dsp-blue to-blue-600 text-white font-semibold py-3 px-4 rounded-lg hover:from-blue-600 hover:to-blue-700 focus:outline-none focus:ring-2 focus:ring-dsp-blue focus:ring-offset-2 focus:ring-offset-gray-800 transform transition hover:scale-[1.02] active:scale-[0.98] shadow-lg">
                    Create Account
                </button>
            </form>
            
            <div class="mt-6 text-center">
                <p class="text-gray-400 text-sm">Already have an account? <a href="?page=login" class="text-dsp-blue hover:text-blue-400 font-medium transition">Login here</a></p>
            </div>
        </div>
    </div>
</div>

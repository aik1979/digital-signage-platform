<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?></title>
    <link rel="icon" type="image/png" href="favicon.png">
    <link rel="shortcut icon" href="favicon.ico">
    <script src="https://cdn.tailwindcss.com"></script>

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'dsp-blue': '#3498DB',
                        'dsp-green': '#5CB85C',
                        'dsp-red': '#E74C3C',
                        'dsp-dark': '#1a1a1a',
                        'dsp-gray': '#2a2a2a',
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="assets/css/custom.css">
</head>
<body class="bg-gray-900 text-gray-100 min-h-screen">
    <?php if ($isLoggedIn): ?>
    <nav class="bg-black border-b border-gray-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <!-- Logo -->
                <div class="flex items-center">
                    <img src="assets/images/logo.svg" alt="DSP Logo" class="h-10 w-auto">
                </div>
                
                <!-- Desktop Navigation -->
                <div class="hidden lg:flex items-center space-x-1">
                    <a href="?page=dashboard" class="px-4 py-2 rounded-md text-sm font-medium transition-colors <?php echo $page === 'dashboard' ? 'bg-dsp-blue text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?>">Dashboard</a>
                    <a href="?page=screens" class="px-4 py-2 rounded-md text-sm font-medium transition-colors <?php echo $page === 'screens' ? 'bg-dsp-blue text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?>">Screens</a>
                    <a href="?page=content" class="px-4 py-2 rounded-md text-sm font-medium transition-colors <?php echo $page === 'content' ? 'bg-dsp-blue text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?>">Content</a>
                    <a href="?page=playlists" class="px-4 py-2 rounded-md text-sm font-medium transition-colors <?php echo $page === 'playlists' ? 'bg-dsp-blue text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?>">Playlists</a>
                    <a href="?page=schedules" class="px-4 py-2 rounded-md text-sm font-medium transition-colors <?php echo $page === 'schedules' ? 'bg-dsp-blue text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?>">Schedules</a>
                    <a href="?page=getting-started" class="px-4 py-2 rounded-md text-sm font-medium transition-colors <?php echo $page === 'getting-started' ? 'bg-green-600 text-white' : 'text-green-400 hover:bg-green-900 hover:text-green-300'; ?>">📖 Guide</a>
                    <?php if ($user['email'] === 'aik1979@gmail.com' || (isset($user['is_admin']) && $user['is_admin'] == 1)): ?>
                    <a href="?page=admin" class="px-4 py-2 rounded-md text-sm font-medium transition-colors <?php echo $page === 'admin' ? 'bg-purple-600 text-white' : 'text-purple-400 hover:bg-purple-900 hover:text-purple-300'; ?>">🛡️ Admin</a>
                    <?php endif; ?>
                </div>
                
                <!-- Desktop Right Menu -->
                <div class="hidden lg:flex items-center space-x-4">

                    <span class="text-gray-300 text-sm">Welcome, <?php echo sanitize($_SESSION['user_name']); ?></span>
                    <a href="?page=settings" class="text-gray-300 hover:text-white text-sm transition-colors">Settings</a>
                    <a href="?page=logout" class="text-gray-300 hover:text-white text-sm transition-colors">Logout</a>
                </div>
                
                <!-- Mobile Hamburger Button -->
                <button id="mobile-menu-button" class="lg:hidden text-gray-300 hover:text-white focus:outline-none focus:text-white">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path id="menu-open-icon" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        <path id="menu-close-icon" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <!-- Mobile Menu -->
            <div id="mobile-menu" class="hidden lg:hidden pb-4">
                <div class="flex flex-col space-y-2">
                    <a href="?page=dashboard" class="px-4 py-2 rounded-md text-sm font-medium transition-colors <?php echo $page === 'dashboard' ? 'bg-dsp-blue text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?>">Dashboard</a>
                    <a href="?page=screens" class="px-4 py-2 rounded-md text-sm font-medium transition-colors <?php echo $page === 'screens' ? 'bg-dsp-blue text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?>">Screens</a>
                    <a href="?page=content" class="px-4 py-2 rounded-md text-sm font-medium transition-colors <?php echo $page === 'content' ? 'bg-dsp-blue text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?>">Content</a>
                    <a href="?page=playlists" class="px-4 py-2 rounded-md text-sm font-medium transition-colors <?php echo $page === 'playlists' ? 'bg-dsp-blue text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?>">Playlists</a>
                    <a href="?page=schedules" class="px-4 py-2 rounded-md text-sm font-medium transition-colors <?php echo $page === 'schedules' ? 'bg-dsp-blue text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?>">Schedules</a>
                    <a href="?page=getting-started" class="px-4 py-2 rounded-md text-sm font-medium transition-colors <?php echo $page === 'getting-started' ? 'bg-green-600 text-white' : 'text-green-400 hover:bg-green-900 hover:text-green-300'; ?>">📖 Guide</a>
                    <?php if ($user['email'] === 'aik1979@gmail.com' || (isset($user['is_admin']) && $user['is_admin'] == 1)): ?>
                    <a href="?page=admin" class="px-4 py-2 rounded-md text-sm font-medium transition-colors <?php echo $page === 'admin' ? 'bg-purple-600 text-white' : 'text-purple-400 hover:bg-purple-900 hover:text-purple-300'; ?>">🛡️ Admin</a>
                    <?php endif; ?>
                    <hr class="border-gray-700 my-2">

                    <a href="?page=settings" class="px-4 py-2 text-gray-300 hover:text-white text-sm transition-colors">Settings</a>
                    <a href="?page=logout" class="px-4 py-2 text-gray-300 hover:text-white text-sm transition-colors">Logout</a>
                </div>
            </div>
        </div>
    </nav>
    <?php endif; ?>
    
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <?php
        // Display flash messages
        $flash = getFlashMessage();
        if ($flash):
        $alertColors = [
            'success' => 'bg-green-100 border-green-400 text-green-700',
            'error' => 'bg-red-100 border-red-400 text-red-700',
            'warning' => 'bg-yellow-100 border-yellow-400 text-yellow-700',
            'info' => 'bg-blue-100 border-blue-400 text-blue-700'
        ];
        $colorClass = $alertColors[$flash['type']] ?? $alertColors['info'];
        ?>
        <div class="<?php echo $colorClass; ?> border px-4 py-3 rounded mb-6" role="alert">
            <?php echo sanitize($flash['message']); ?>
        </div>
        <?php endif; ?>

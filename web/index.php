<?php
/**
 * Digital Signage Platform
 * Main Entry Point
 */

// Start session
session_start();

// Load configuration
require_once __DIR__ . '/config/config.php';

// Load core includes
require_once __DIR__ . '/includes/Database.php';
require_once __DIR__ . '/includes/Auth.php';
require_once __DIR__ . '/includes/functions.php';

// Initialize database connection
$db = Database::getInstance();

// Get the requested page
$page = isset($_GET['page']) ? $_GET['page'] : 'home';

// Check if user is logged in
$auth = new Auth($db);
$isLoggedIn = $auth->isLoggedIn();

// Handle AJAX requests before any HTML output
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'save_playlist_items' && $page === 'playlists') {
        // This is an AJAX request, process it without HTML
        require_once __DIR__ . '/pages/playlists.php';
        exit; // Stop execution after AJAX response
    }
    // Handle schedule POST requests
    if (in_array($_POST['action'], ['create_schedule', 'update_schedule', 'delete_schedule']) && $page === 'schedules') {
        require_once __DIR__ . '/pages/schedules.php';
        exit;
    }
}

// Public pages (no login required)
$publicPages = ['login', 'register', 'forgot-password', 'reset-password'];

// Redirect to login if not authenticated and trying to access protected page
if (!$isLoggedIn && !in_array($page, $publicPages)) {
    header('Location: ?page=login');
    exit;
}

// Redirect to dashboard if logged in and trying to access login page
if ($isLoggedIn && $page === 'login') {
    header('Location: ?page=dashboard');
    exit;
}

// Include header
include __DIR__ . '/includes/header.php';

// Route to appropriate page
switch ($page) {
    case 'login':
        include __DIR__ . '/pages/login.php';
        break;
    case 'register':
        include __DIR__ . '/pages/register.php';
        break;
    case 'logout':
        include __DIR__ . '/pages/logout.php';
        break;
    case 'dashboard':
        include __DIR__ . '/pages/dashboard.php';
        break;
    case 'screens':
        include __DIR__ . '/pages/screens.php';
        break;
    case 'content':
        include __DIR__ . '/pages/content.php';
        break;
    case 'playlists':
        include __DIR__ . '/pages/playlists.php';
        break;
    case 'schedules':
        include __DIR__ . '/pages/schedules.php';
        break;
    case 'settings':
        include __DIR__ . '/pages/settings.php';
        break;
    case 'home':
    default:
        if ($isLoggedIn) {
            header('Location: ?page=dashboard');
        } else {
            header('Location: ?page=login');
        }
        exit;
}

// Include footer
include __DIR__ . '/includes/footer.php';

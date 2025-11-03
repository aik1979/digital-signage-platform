<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?></title>
    <link rel="icon" type="image/png" href="favicon.png">
    <link rel="shortcut icon" href="favicon.ico">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php if ($isLoggedIn): ?>
    <nav class="navbar">
        <div class="navbar-brand">
            <img src="assets/images/logo.jpg" alt="DSP Logo">
        </div>
        <ul class="navbar-menu">
            <li><a href="?page=dashboard" class="<?php echo $page === 'dashboard' ? 'active' : ''; ?>">Dashboard</a></li>
            <li><a href="?page=screens" class="<?php echo $page === 'screens' ? 'active' : ''; ?>">Screens</a></li>
            <li><a href="?page=content" class="<?php echo $page === 'content' ? 'active' : ''; ?>">Content</a></li>
            <li><a href="?page=playlists" class="<?php echo $page === 'playlists' ? 'active' : ''; ?>">Playlists</a></li>
            <li><a href="?page=schedules" class="<?php echo $page === 'schedules' ? 'active' : ''; ?>">Schedules</a></li>
        </ul>
        <div class="navbar-user">
            <span>Welcome, <?php echo sanitize($_SESSION['user_name']); ?></span>
            <a href="?page=settings">Settings</a>
            <a href="?page=logout">Logout</a>
        </div>
    </nav>
    <?php endif; ?>
    
    <main class="container">
        <?php
        // Display flash messages
        $flash = getFlashMessage();
        if ($flash):
        ?>
        <div class="alert alert-<?php echo $flash['type']; ?>">
            <?php echo sanitize($flash['message']); ?>
        </div>
        <?php endif; ?>

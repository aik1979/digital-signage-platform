<?php
// Simple test to see if admin page loads
session_start();
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/Database.php';
require_once __DIR__ . '/includes/Auth.php';
require_once __DIR__ . '/includes/functions.php';

$db = Database::getInstance();
$auth = new Auth($db);

echo "<!DOCTYPE html><html><head><title>Admin Test</title></head><body>";
echo "<h1>Admin Page Test</h1>";

echo "<p>Session ID: " . session_id() . "</p>";
echo "<p>Logged in: " . ($auth->isLoggedIn() ? 'YES' : 'NO') . "</p>";

if ($auth->isLoggedIn()) {
    $user = $auth->getUser();
    echo "<p>User: " . htmlspecialchars($user['email']) . "</p>";
    echo "<p>Is Admin: " . (($user['email'] === 'aik1979@gmail.com' || (isset($user['is_admin']) && $user['is_admin'] == 1)) ? 'YES' : 'NO') . "</p>";
    
    // Try to get users
    try {
        $users = $db->fetchAll("SELECT id, email FROM users LIMIT 5");
        echo "<p>Users query successful. Found " . count($users) . " users</p>";
        echo "<pre>" . print_r($users, true) . "</pre>";
    } catch (Exception $e) {
        echo "<p style='color:red'>Error fetching users: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p>Not logged in - redirecting to login would happen here</p>";
}

echo "</body></html>";
?>

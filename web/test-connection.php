<?php
/**
 * Database Connection and Authentication Test Script
 * Access this file directly to diagnose issues
 * DELETE THIS FILE after troubleshooting!
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Digital Signage Platform - Diagnostic Test</h1>";
echo "<hr>";

// Test 1: Check if config file exists
echo "<h2>Test 1: Configuration File</h2>";
if (file_exists(__DIR__ . '/config/config.php')) {
    echo "✅ config.php exists<br>";
    require_once __DIR__ . '/config/config.php';
    echo "✅ config.php loaded successfully<br>";
    echo "Database: " . DB_NAME . "<br>";
    echo "User: " . DB_USER . "<br>";
    echo "Host: " . DB_HOST . "<br>";
} else {
    echo "❌ config.php NOT FOUND!<br>";
    echo "Please copy config.sample.php to config.php<br>";
    exit;
}

echo "<hr>";

// Test 2: Database Connection
echo "<h2>Test 2: Database Connection</h2>";
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    echo "✅ Database connection successful<br>";
} catch (PDOException $e) {
    echo "❌ Database connection FAILED<br>";
    echo "Error: " . $e->getMessage() . "<br>";
    exit;
}

echo "<hr>";

// Test 3: Check if users table exists
echo "<h2>Test 3: Database Tables</h2>";
try {
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (count($tables) > 0) {
        echo "✅ Found " . count($tables) . " tables:<br>";
        echo "<ul>";
        foreach ($tables as $table) {
            echo "<li>" . $table . "</li>";
        }
        echo "</ul>";
    } else {
        echo "❌ No tables found! Please import database/schema.sql<br>";
        exit;
    }
    
    if (!in_array('users', $tables)) {
        echo "❌ 'users' table NOT FOUND!<br>";
        exit;
    }
} catch (PDOException $e) {
    echo "❌ Error checking tables: " . $e->getMessage() . "<br>";
    exit;
}

echo "<hr>";

// Test 4: Check admin user
echo "<h2>Test 4: Admin User</h2>";
try {
    $stmt = $pdo->query("SELECT id, email, first_name, last_name, is_active, email_verified FROM users WHERE email = 'admin@example.com'");
    $user = $stmt->fetch();
    
    if ($user) {
        echo "✅ Admin user found<br>";
        echo "ID: " . $user['id'] . "<br>";
        echo "Email: " . $user['email'] . "<br>";
        echo "Name: " . $user['first_name'] . " " . $user['last_name'] . "<br>";
        echo "Active: " . ($user['is_active'] ? 'Yes' : 'No') . "<br>";
        echo "Email Verified: " . ($user['email_verified'] ? 'Yes' : 'No') . "<br>";
    } else {
        echo "❌ Admin user NOT FOUND!<br>";
        echo "Creating admin user...<br>";
        
        // Create admin user
        $passwordHash = password_hash('admin123', PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("INSERT INTO users (email, password_hash, first_name, last_name, is_active, email_verified) VALUES (?, ?, ?, ?, 1, 1)");
        $stmt->execute(['admin@example.com', $passwordHash, 'Admin', 'User']);
        
        echo "✅ Admin user created successfully!<br>";
    }
} catch (PDOException $e) {
    echo "❌ Error checking admin user: " . $e->getMessage() . "<br>";
    exit;
}

echo "<hr>";

// Test 5: Test password verification
echo "<h2>Test 5: Password Verification</h2>";
try {
    $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE email = ?");
    $stmt->execute(['admin@example.com']);
    $user = $stmt->fetch();
    
    if ($user) {
        $testPassword = 'admin123';
        if (password_verify($testPassword, $user['password_hash'])) {
            echo "✅ Password verification successful<br>";
            echo "Password 'admin123' is correct<br>";
        } else {
            echo "❌ Password verification FAILED<br>";
            echo "The password hash in the database doesn't match 'admin123'<br>";
            
            // Fix the password
            echo "Fixing password...<br>";
            $newHash = password_hash('admin123', PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE email = ?");
            $stmt->execute([$newHash, 'admin@example.com']);
            echo "✅ Password updated successfully!<br>";
        }
    }
} catch (PDOException $e) {
    echo "❌ Error testing password: " . $e->getMessage() . "<br>";
    exit;
}

echo "<hr>";

// Test 6: Test session functionality
echo "<h2>Test 6: Session Support</h2>";
if (session_status() === PHP_SESSION_DISABLED) {
    echo "❌ Sessions are DISABLED<br>";
} else {
    echo "✅ Sessions are enabled<br>";
    
    // Check session save path
    $sessionPath = __DIR__ . '/sessions';
    if (is_dir($sessionPath)) {
        echo "✅ Session directory exists<br>";
        if (is_writable($sessionPath)) {
            echo "✅ Session directory is writable<br>";
        } else {
            echo "❌ Session directory is NOT writable<br>";
            echo "Run: chmod 755 " . $sessionPath . "<br>";
        }
    } else {
        echo "❌ Session directory does NOT exist<br>";
        echo "Creating session directory...<br>";
        mkdir($sessionPath, 0755, true);
        echo "✅ Session directory created<br>";
    }
}

echo "<hr>";

// Test 7: Test file upload directories
echo "<h2>Test 7: Upload Directories</h2>";
$uploadDirs = [
    'Content' => __DIR__ . '/uploads/content',
    'Thumbnails' => __DIR__ . '/uploads/thumbnails',
    'Logs' => __DIR__ . '/logs'
];

foreach ($uploadDirs as $name => $dir) {
    if (is_dir($dir)) {
        echo "✅ {$name} directory exists<br>";
        if (is_writable($dir)) {
            echo "✅ {$name} directory is writable<br>";
        } else {
            echo "❌ {$name} directory is NOT writable<br>";
            echo "Run: chmod 755 {$dir}<br>";
        }
    } else {
        echo "❌ {$name} directory does NOT exist<br>";
    }
}

echo "<hr>";

// Test 8: Test actual login
echo "<h2>Test 8: Simulated Login Test</h2>";
require_once __DIR__ . '/includes/Database.php';
require_once __DIR__ . '/includes/Auth.php';
require_once __DIR__ . '/includes/functions.php';

try {
    session_start();
    $db = Database::getInstance();
    $auth = new Auth($db);
    
    $result = $auth->login('admin@example.com', 'admin123');
    
    if ($result['success']) {
        echo "✅ LOGIN SUCCESSFUL!<br>";
        echo "Message: " . $result['message'] . "<br>";
        echo "Session user_id: " . $_SESSION['user_id'] . "<br>";
        echo "Session user_email: " . $_SESSION['user_email'] . "<br>";
    } else {
        echo "❌ LOGIN FAILED<br>";
        echo "Error: " . $result['message'] . "<br>";
    }
} catch (Exception $e) {
    echo "❌ Exception during login test: " . $e->getMessage() . "<br>";
    echo "Stack trace:<br><pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<hr>";
echo "<h2>✅ All Tests Complete!</h2>";
echo "<p><strong>⚠️ IMPORTANT: Delete this file (test-connection.php) after troubleshooting!</strong></p>";
echo "<p><a href='index.php'>Go to Login Page</a></p>";

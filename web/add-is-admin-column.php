<?php
/**
 * Migration: Add is_admin column to users table
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/Database.php';

$db = Database::getInstance();

try {
    // Check if column already exists
    $columns = $db->fetchAll("SHOW COLUMNS FROM users LIKE 'is_admin'");
    
    if (empty($columns)) {
        echo "Adding is_admin column to users table...\n";
        
        // Add the column
        $db->query("ALTER TABLE users ADD COLUMN is_admin TINYINT(1) DEFAULT 0 AFTER is_active");
        
        echo "✅ Column added successfully!\n";
        
        // Set the admin user
        $db->query("UPDATE users SET is_admin = 1 WHERE email = 'aik1979@gmail.com'");
        
        echo "✅ Admin privileges granted to aik1979@gmail.com\n";
    } else {
        echo "ℹ️  Column is_admin already exists\n";
    }
    
    echo "\n✅ Migration completed successfully!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>

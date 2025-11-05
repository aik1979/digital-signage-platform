<?php
/**
 * Migration: Create device_pairing table
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/Database.php';

$db = Database::getInstance();

try {
    echo "Creating device_pairing table...\n";
    
    $db->query("
        CREATE TABLE IF NOT EXISTS device_pairing (
            id INT AUTO_INCREMENT PRIMARY KEY,
            pairing_code VARCHAR(10) NOT NULL UNIQUE,
            device_id VARCHAR(50) NOT NULL,
            screen_id INT NULL,
            user_id INT NULL,
            status ENUM('pending', 'completed', 'expired') DEFAULT 'pending',
            expires_at DATETIME NOT NULL,
            paired_at DATETIME NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_pairing_code (pairing_code),
            INDEX idx_device_id (device_id),
            INDEX idx_status (status),
            INDEX idx_expires (expires_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    echo "✅ device_pairing table created successfully!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>

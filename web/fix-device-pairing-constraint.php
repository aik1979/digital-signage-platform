<?php
/**
 * Migration: Remove unique constraint on device_id in device_pairing table
 * This allows devices to be re-paired multiple times
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/Database.php';

$db = Database::getInstance();

try {
    echo "Checking device_pairing table structure...\n";
    
    // Check if unique constraint exists
    $indexes = $db->fetchAll("SHOW INDEX FROM device_pairing WHERE Column_name = 'device_id'");
    
    $hasUniqueConstraint = false;
    foreach ($indexes as $index) {
        if ($index['Non_unique'] == 0) {
            $hasUniqueConstraint = true;
            echo "Found unique constraint on device_id: " . $index['Key_name'] . "\n";
        }
    }
    
    if ($hasUniqueConstraint) {
        echo "Removing unique constraint on device_id...\n";
        
        // Drop the unique constraint
        $db->query("ALTER TABLE device_pairing DROP INDEX device_id");
        
        // Add a regular index instead
        $db->query("ALTER TABLE device_pairing ADD INDEX idx_device_id (device_id)");
        
        echo "✅ Unique constraint removed successfully!\n";
        echo "✅ Regular index added for performance.\n";
    } else {
        echo "✅ No unique constraint found on device_id. Table is already correct.\n";
    }
    
    echo "\nDevices can now be re-paired multiple times!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>

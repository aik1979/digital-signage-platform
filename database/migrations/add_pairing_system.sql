-- Add pairing system tables
-- Run this migration to add QR code pairing functionality

-- Table for device pairing codes
CREATE TABLE IF NOT EXISTS device_pairing (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pairing_code VARCHAR(10) UNIQUE NOT NULL,
    device_id VARCHAR(100) UNIQUE,
    screen_id INT DEFAULT NULL,
    status ENUM('pending', 'paired', 'expired') DEFAULT 'pending',
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    paired_at DATETIME DEFAULT NULL,
    INDEX idx_pairing_code (pairing_code),
    INDEX idx_device_id (device_id),
    INDEX idx_status (status),
    INDEX idx_screen_id (screen_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add pairing-related columns to screens table
ALTER TABLE screens 
ADD COLUMN pairing_code VARCHAR(10) DEFAULT NULL,
ADD COLUMN device_id VARCHAR(100) DEFAULT NULL;

-- Add indexes for pairing columns
ALTER TABLE screens
ADD INDEX idx_pairing_code (pairing_code),
ADD INDEX idx_device_id (device_id);

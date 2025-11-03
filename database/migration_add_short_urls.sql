-- Migration: Add URL shortener table
-- Run this on your existing database

CREATE TABLE IF NOT EXISTS short_urls (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    short_code VARCHAR(10) UNIQUE NOT NULL COMMENT 'Short code for URL (e.g., abc123)',
    original_url TEXT NOT NULL COMMENT 'Original long URL',
    playlist_id INT UNSIGNED DEFAULT NULL COMMENT 'Associated playlist if applicable',
    user_id INT UNSIGNED NOT NULL COMMENT 'User who created the short URL',
    clicks INT UNSIGNED DEFAULT 0 COMMENT 'Number of times accessed',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL DEFAULT NULL COMMENT 'Optional expiration date',
    is_active TINYINT(1) DEFAULT 1,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (playlist_id) REFERENCES playlists(id) ON DELETE CASCADE,
    INDEX idx_short_code (short_code),
    INDEX idx_user_id (user_id),
    INDEX idx_playlist_id (playlist_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

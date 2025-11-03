-- Migration: Add share token for secure public playlist viewing
-- Run this on your existing database

ALTER TABLE playlists 
    ADD COLUMN share_token VARCHAR(64) UNIQUE DEFAULT NULL COMMENT 'Unique token for public sharing' AFTER transition,
    ADD COLUMN share_enabled TINYINT(1) DEFAULT 0 COMMENT 'Enable public sharing' AFTER share_token;

-- Generate unique tokens for existing playlists
UPDATE playlists SET share_token = CONCAT('pl_', MD5(CONCAT(id, UNIX_TIMESTAMP(), RAND()))) WHERE share_token IS NULL;

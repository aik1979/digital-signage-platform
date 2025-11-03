-- Migration: Add transition effect to playlists table
-- Run this on your existing database

ALTER TABLE playlists 
    ADD COLUMN transition VARCHAR(20) DEFAULT 'fade' COMMENT 'Transition effect: fade, slide, zoom, none' AFTER description;

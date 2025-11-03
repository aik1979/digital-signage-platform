-- Migration: Add missing columns to schedules table
-- Run this on your existing database

ALTER TABLE schedules 
    CHANGE COLUMN day_of_week days_of_week VARCHAR(50) NOT NULL COMMENT 'Comma-separated: 0=Sun,1=Mon,...,6=Sat',
    ADD COLUMN start_date DATE DEFAULT NULL COMMENT 'Optional start date for seasonal schedules' AFTER end_time,
    ADD COLUMN end_date DATE DEFAULT NULL COMMENT 'Optional end date for seasonal schedules' AFTER start_date;

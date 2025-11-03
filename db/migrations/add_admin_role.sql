-- Add admin role column to users table
ALTER TABLE users ADD COLUMN IF NOT EXISTS is_admin TINYINT(1) DEFAULT 0 AFTER email_verified;

-- Set aik1979@gmail.com as admin
UPDATE users SET is_admin = 1 WHERE email = 'aik1979@gmail.com';

-- Create index for faster admin checks
CREATE INDEX IF NOT EXISTS idx_users_is_admin ON users(is_admin);

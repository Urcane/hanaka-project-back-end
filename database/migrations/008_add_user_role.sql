-- Add role column to users table (idempotent)
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'role');
SET @sql = IF(@col_exists = 0, "ALTER TABLE users ADD COLUMN role ENUM('customer', 'admin') NOT NULL DEFAULT 'customer' AFTER password_hash, ADD INDEX idx_users_role (role)", 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

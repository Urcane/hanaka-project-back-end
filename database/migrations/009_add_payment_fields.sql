-- Add Midtrans / QRIS payment fields to orders (idempotent)
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'payment_reference');
SET @sql = IF(@col_exists = 0, "ALTER TABLE orders ADD COLUMN payment_provider VARCHAR(20) DEFAULT NULL AFTER payment_status, ADD COLUMN payment_reference VARCHAR(100) DEFAULT NULL AFTER payment_provider, ADD COLUMN qr_string TEXT DEFAULT NULL AFTER payment_reference, ADD COLUMN qr_url VARCHAR(255) DEFAULT NULL AFTER qr_string, ADD COLUMN payment_expires_at DATETIME DEFAULT NULL AFTER qr_url", 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Extend payment_status enum to include expired/failed (safe to re-run)
ALTER TABLE orders MODIFY COLUMN payment_status ENUM('pending', 'paid', 'cod', 'expired', 'failed') NOT NULL DEFAULT 'pending';

-- Delivery drop-off coordinates (idempotent).
-- Nullable on purpose: pickup orders and every order placed before this feature
-- simply have no point, and both the tracking map and the owner map skip them.
SET @col_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'orders'
      AND COLUMN_NAME = 'delivery_lat'
);
SET @sql = IF(
    @col_exists = 0,
    "ALTER TABLE orders
        ADD COLUMN delivery_lat DECIMAL(10,7) DEFAULT NULL AFTER delivery_address,
        ADD COLUMN delivery_lng DECIMAL(10,7) DEFAULT NULL AFTER delivery_lat",
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

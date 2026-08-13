-- Widen the users.role enum with 'owner' (idempotent).
-- Owner is the shop proprietor: read-only management view (reports, charts)
-- plus the ability to broadcast announcements to every admin.
SET @needs_owner = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'users'
      AND COLUMN_NAME = 'role'
      AND COLUMN_TYPE NOT LIKE '%owner%'
);
SET @sql = IF(
    @needs_owner = 1,
    "ALTER TABLE users MODIFY COLUMN role ENUM('customer', 'admin', 'owner') NOT NULL DEFAULT 'customer'",
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

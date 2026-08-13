-- Per-size stock tracking (idempotent).
-- Stock lives on product_sizes, not products: a 16 cm Black Forest can run out
-- while the 20 cm is still available, and that is what the customer picks.
SET @col_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'product_sizes'
      AND COLUMN_NAME = 'stock'
);
SET @sql = IF(
    @col_exists = 0,
    "ALTER TABLE product_sizes
        ADD COLUMN stock INT NOT NULL DEFAULT 0 AFTER price,
        ADD COLUMN low_stock_threshold INT NOT NULL DEFAULT 5 AFTER stock",
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Give existing sizes a starting quota so the storefront is not "sold out"
-- right after the migration runs on an already-seeded database. Guarded by the
-- same flag as the ALTER: re-running migrate.php must never resurrect stock an
-- admin deliberately set to 0.
SET @sql = IF(
    @col_exists = 0,
    'UPDATE product_sizes SET stock = 10',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

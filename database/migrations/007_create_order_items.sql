CREATE TABLE IF NOT EXISTS order_items (
    id VARCHAR(20) PRIMARY KEY,
    order_id VARCHAR(20) NOT NULL,
    product_id VARCHAR(50) NOT NULL,
    product_name VARCHAR(100) NOT NULL,
    size_id VARCHAR(20) NOT NULL,
    size_label VARCHAR(10) NOT NULL,
    color_text VARCHAR(40) NOT NULL DEFAULT '',
    theme VARCHAR(40) NOT NULL DEFAULT '',
    message VARCHAR(60) NOT NULL DEFAULT '',
    quantity TINYINT UNSIGNED NOT NULL DEFAULT 1,
    unit_price INT NOT NULL,
    total_price INT NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id),
    INDEX idx_order_items_order (order_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

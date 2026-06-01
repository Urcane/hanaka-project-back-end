CREATE TABLE IF NOT EXISTS cart_items (
    id VARCHAR(20) PRIMARY KEY,
    cart_id VARCHAR(20) NOT NULL,
    product_id VARCHAR(50) NOT NULL,
    size_id VARCHAR(20) NOT NULL,
    color_text VARCHAR(40) NOT NULL DEFAULT '',
    theme VARCHAR(40) NOT NULL DEFAULT '',
    message VARCHAR(60) NOT NULL DEFAULT '',
    quantity TINYINT UNSIGNED NOT NULL DEFAULT 1,
    unit_price INT NOT NULL,
    total_price INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cart_id) REFERENCES carts(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id),
    FOREIGN KEY (size_id) REFERENCES product_sizes(id),
    INDEX idx_cart_items_cart (cart_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

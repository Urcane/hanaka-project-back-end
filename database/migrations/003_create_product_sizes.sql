CREATE TABLE IF NOT EXISTS product_sizes (
    id VARCHAR(20) PRIMARY KEY,
    product_id VARCHAR(50) NOT NULL,
    label VARCHAR(10) NOT NULL,
    full_label VARCHAR(50) NOT NULL,
    price INT NOT NULL,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_product_sizes_product (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

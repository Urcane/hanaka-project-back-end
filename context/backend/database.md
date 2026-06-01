# Backend — Database Schema

> ERD, migration SQL, dan seed data untuk MySQL.

---

## Entity Relationship Diagram

```
┌──────────────┐       ┌────────────────┐       ┌────────────────┐
│    users     │       │   products     │       │ product_sizes  │
├──────────────┤       ├────────────────┤       ├────────────────┤
│ id (PK)      │       │ id (PK)        │       │ id (PK)        │
│ full_name    │       │ name           │       │ product_id (FK)│──→ products
│ email (UQ)   │       │ short_desc     │       │ label          │
│ phone        │       │ long_desc      │       │ full_label     │
│ password_hash│       │ featured       │       │ price          │
│ created_at   │       │ cover_gradient │       └────────────────┘
│ updated_at   │       │ cover_image    │
└──────────────┘       │ max_msg_length │
       │               │ created_at     │
       │               │ updated_at     │
       │               └────────────────┘
       │
       ├──────────────────────────────┐
       │                              │
┌──────▼──────┐                ┌──────▼──────┐
│   carts     │                │   orders    │
├─────────────┤                ├─────────────┤
│ id (PK)     │                │ id (PK)     │
│ user_id (FK)│──→ users       │ order_number│ (UQ)
│ session_tok │                │ user_id (FK)│──→ users (nullable)
│ created_at  │                │ cust_name   │
│ updated_at  │                │ cust_phone  │
└─────────────┘                │ fulfill_meth│
       │                       │ pickup_date │
┌──────▼──────┐                │ pickup_time │
│ cart_items  │                │ address     │
├─────────────┤                │ address_note│
│ id (PK)     │                │ pay_method  │
│ cart_id (FK)│──→ carts       │ pay_status  │
│ product_id  │──→ products    │ status      │
│ size_id     │──→ product_sizes│ notes      │
│ color_text  │                │ total_price │
│ theme       │                │ created_at  │
│ message     │                │ updated_at  │
│ quantity    │                └─────────────┘
│ unit_price  │                       │
│ total_price │                ┌──────▼──────┐
│ created_at  │                │ order_items │
└─────────────┘                ├─────────────┤
                               │ id (PK)     │
                               │ order_id(FK)│──→ orders
                               │ product_id  │──→ products
                               │ product_name│
                               │ size_id     │──→ product_sizes
                               │ size_label  │
                               │ color_text  │
                               │ theme       │
                               │ message     │
                               │ quantity    │
                               │ unit_price  │
                               │ total_price │
                               └─────────────┘
```

---

## Migrations

### 001 — Users

```sql
CREATE TABLE users (
    id VARCHAR(20) PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    phone VARCHAR(20) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 002 — Products

```sql
CREATE TABLE products (
    id VARCHAR(50) PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    short_description TEXT,
    long_description TEXT,
    featured BOOLEAN DEFAULT FALSE,
    cover_gradient VARCHAR(255),
    cover_image VARCHAR(255) DEFAULT NULL,
    max_message_length INT DEFAULT 60,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 003 — Product Sizes

```sql
CREATE TABLE product_sizes (
    id VARCHAR(20) PRIMARY KEY,
    product_id VARCHAR(50) NOT NULL,
    label VARCHAR(10) NOT NULL,
    full_label VARCHAR(50) NOT NULL,
    price INT NOT NULL,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_product_sizes_product (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 004 — Carts

```sql
CREATE TABLE carts (
    id VARCHAR(20) PRIMARY KEY,
    user_id VARCHAR(20) DEFAULT NULL,
    session_token VARCHAR(100) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_carts_user (user_id),
    INDEX idx_carts_session (session_token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 005 — Cart Items

```sql
CREATE TABLE cart_items (
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
```

### 006 — Orders

```sql
CREATE TABLE orders (
    id VARCHAR(20) PRIMARY KEY,
    order_number VARCHAR(30) NOT NULL UNIQUE,
    user_id VARCHAR(20) DEFAULT NULL,
    customer_name VARCHAR(100) NOT NULL,
    customer_phone VARCHAR(20) NOT NULL,
    fulfillment_method ENUM('pickup', 'delivery') NOT NULL,
    pickup_date DATE DEFAULT NULL,
    pickup_time TIME DEFAULT NULL,
    delivery_address TEXT,
    address_note VARCHAR(120) DEFAULT '',
    payment_method ENUM('cash', 'qris') NOT NULL,
    payment_status ENUM('pending', 'paid', 'cod') NOT NULL DEFAULT 'pending',
    status ENUM('menunggu konfirmasi', 'diproses', 'siap diambil', 'diantar', 'selesai', 'dibatalkan') NOT NULL DEFAULT 'menunggu konfirmasi',
    notes TEXT,
    total_price INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_orders_user (user_id),
    INDEX idx_orders_number (order_number),
    INDEX idx_orders_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 007 — Order Items

```sql
CREATE TABLE order_items (
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
```

---

## Seed Data

### Products

```sql
INSERT INTO products (id, name, short_description, long_description, featured, cover_gradient, max_message_length) VALUES
('black-forest', 'Black Forest Cake', 'Manis, lembut, dengan perpaduan cokelat, krim, dan alsen segar dari ceri.', 'Tekstur lembut dan rasa cokelat yang kaya berpadu dengan krim segar...', TRUE, 'linear-gradient(135deg, #8a5a44 0%, #bc8b73 100%)', 60),
('red-velvet', 'Red Velvet Cake', 'Manis, lembut, sedikit cokelat dengan sentuhan keju krim yang gurih.', 'Tekstur lembut dan rasa manis ringan berpadu sentuhan cokelat...', TRUE, 'linear-gradient(135deg, #d38182 0%, #f0b1a6 100%)', 60),
('vanilla-cake', 'Vanila Cake', 'Sponge vanilla ringan dengan buttercream silky.', 'Sponge vanilla yang ringan dan lembut...', FALSE, 'linear-gradient(135deg, #f7e9d5 0%, #f3d7bb 100%)', 60),
('lemon-cake', 'Lemon Cake', 'Rasa lemon segar dengan frosting cream cheese ringan.', 'Cake lemon yang segar...', FALSE, 'linear-gradient(135deg, #f5e6a3 0%, #e8d77b 100%)', 60),
('rainbow-cake', 'Rainbow Cake', 'Cake warna-warni yang ceria dengan rasa vanilla lembut.', 'Cake berlapis warna-warni...', FALSE, 'linear-gradient(135deg, #f5a3a3 0%, #a3d5f5 50%, #a3f5c4 100%)', 60);
```

### Product Sizes (per product)

```sql
-- Pattern: size-{cm}-{product_code}
INSERT INTO product_sizes (id, product_id, label, full_label, price) VALUES
('size-16-bf', 'black-forest', '16', 'Ukuran 16 cm', 120000),
('size-18-bf', 'black-forest', '18', 'Ukuran 18 cm', 170000),
('size-20-bf', 'black-forest', '20', 'Ukuran 20 cm', 220000),
('size-22-bf', 'black-forest', '22', 'Ukuran 22 cm', 270000),
-- ... repeat for each product (rv, vc, lc, rc)
```

---

## Order Status Flow

```
menunggu konfirmasi → diproses → siap diambil → selesai
                              → diantar → selesai
                              → dibatalkan
```

| Status | Trigger |
|---|---|
| `menunggu konfirmasi` | Order baru dibuat |
| `diproses` | Payment confirmed (QRIS paid / admin confirm cash) |
| `siap diambil` | Kue selesai dibuat (pickup) |
| `diantar` | Kurir berangkat (delivery) |
| `selesai` | Customer sudah terima |
| `dibatalkan` | Admin/customer cancel |

---

## Payment Status

| Status | Keterangan |
|---|---|
| `pending` | QRIS belum dibayar |
| `paid` | QRIS sudah dibayar |
| `cod` | Cash on delivery (bayar saat pickup/terima) |

---

## ID Format

| Entity | Format | Contoh |
|---|---|---|
| User | `usr_{8char}` | `usr_a1b2c3d4` |
| Cart | `cart_{8char}` | `cart_x1y2z3w4` |
| Cart Item | `cart_{8char}` | `cart_m1n2o3p4` |
| Order | `ord_{8char}` | `ord_q1r2s3t4` |
| Order Number | `HNK-YYYYMMDD-HHMMSS-XXX` | `HNK-20260522-143052-847` |
| Product | slug | `black-forest` |
| Product Size | `size-{cm}-{code}` | `size-16-bf` |

---

## Notes

- Semua tabel menggunakan `utf8mb4_unicode_ci` untuk support emoji
- `ON DELETE CASCADE` pada relasi parent-child (order_items, cart_items)
- `ON DELETE SET NULL` pada user reference (agar order/cart tetap ada jika user dihapus)
- Guest carts menggunakan `session_token` alih-alih `user_id`
- `order_items` menyimpan snapshot data (product_name, size_label) agar tidak berubah jika produk diupdate

# Backend — Database Schema

> ERD, migration SQL, dan seed data untuk MySQL.

---

## Migrations

9 migration files — jalankan: `php database/migrate.php [--seed]`

| File | Tabel |
|---|---|
| 001 | `users` |
| 002 | `products` |
| 003 | `product_sizes` |
| 004 | `carts` |
| 005 | `cart_items` |
| 006 | `orders` |
| 007 | `order_items` |
| 008 | `users.role` (ALTER — tambah kolom ENUM customer/admin) |
| 009 | `orders` (ALTER — tambah kolom Midtrans payment fields) |

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
│ role         │       │ cover_gradient │       └────────────────┘
│ created_at   │       │ cover_image    │
│ updated_at   │       │ max_msg_length │
└──────────────┘       │ created_at     │
       │               │ updated_at     │
       │               └────────────────┘
       ├──────────────────────────────┐
       │                              │
┌──────▼──────┐                ┌──────▼──────────────┐
│   carts     │                │       orders        │
├─────────────┤                ├─────────────────────┤
│ id (PK)     │                │ id (PK)             │
│ user_id (FK)│──→ users       │ order_number (UQ)   │
│ session_tok │                │ user_id (FK)        │──→ users (nullable)
│ created_at  │                │ session_token       │
│ updated_at  │                │ customer_name       │
└─────────────┘                │ customer_phone      │
       │                       │ fulfillment_method  │
┌──────▼──────┐                │ pickup_date         │
│ cart_items  │                │ pickup_time         │
├─────────────┤                │ delivery_address    │
│ id (PK)     │                │ address_note        │
│ cart_id (FK)│──→ carts       │ payment_method      │
│ product_id  │──→ products    │ payment_status      │
│ size_id     │──→ product_sizes│ payment_provider   │ ← Midtrans
│ color_text  │                │ payment_reference   │ ← transaction_id
│ theme       │                │ qr_string           │ ← EMV QR string
│ message     │                │ qr_url              │ ← hosted QR image
│ quantity    │                │ payment_expires_at  │ ← UTC
│ unit_price  │                │ status              │
│ total_price │                │ notes               │
│ created_at  │                │ total_price         │
└─────────────┘                │ created_at          │
                               │ updated_at          │
                               └─────────────────────┘
                                        │
                               ┌────────▼──────┐
                               │  order_items  │
                               ├───────────────┤
                               │ id (PK)       │
                               │ order_id (FK) │──→ orders
                               │ product_id    │──→ products
                               │ product_name  │ (snapshot)
                               │ size_id       │──→ product_sizes
                               │ size_label    │ (snapshot)
                               │ color_text    │
                               │ theme         │
                               │ message       │
                               │ quantity      │
                               │ unit_price    │
                               │ total_price   │
                               └───────────────┘
```

---

## Tabel Detail

### users
```sql
CREATE TABLE users (
    id VARCHAR(20) PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    phone VARCHAR(20) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('customer', 'admin') NOT NULL DEFAULT 'customer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_users_email (email),
    INDEX idx_users_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### orders (kolom Midtrans — migration 009)
```sql
-- Tambahan dari migration 009
ALTER TABLE orders
  ADD COLUMN payment_provider VARCHAR(20) DEFAULT NULL,   -- 'midtrans'
  ADD COLUMN payment_reference VARCHAR(100) DEFAULT NULL, -- Midtrans transaction_id
  ADD COLUMN qr_string TEXT DEFAULT NULL,                 -- EMV QRIS string
  ADD COLUMN qr_url VARCHAR(255) DEFAULT NULL,            -- hosted QR image URL
  ADD COLUMN payment_expires_at DATETIME DEFAULT NULL;    -- UTC

-- payment_status enum diperluas
ALTER TABLE orders MODIFY COLUMN payment_status
  ENUM('pending', 'paid', 'cod', 'expired', 'failed') NOT NULL DEFAULT 'pending';
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
| `diproses` | QRIS paid (webhook) / admin confirm cash |
| `siap diambil` | Admin update (kue selesai dibuat, pickup) |
| `diantar` | Admin update (kurir berangkat, delivery) |
| `selesai` | Admin update (customer sudah terima) |
| `dibatalkan` | Admin / customer cancel |

---

## Payment Status

| Status | Keterangan |
|---|---|
| `pending` | QRIS belum dibayar |
| `paid` | QRIS sudah dibayar (Midtrans settlement) |
| `cod` | Cash — bayar saat pickup/terima |
| `expired` | QR kedaluwarsa (Midtrans expire) |
| `failed` | Pembayaran ditolak / dibatalkan |

---

## ID Format

| Entity | Format | Contoh |
|---|---|---|
| User | `usr_{8char hex}` | `usr_a1b2c3d4` |
| Cart | `cart_{8char hex}` | `cart_x1y2z3w4` |
| Cart Item | `cart_{8char hex}` | `cart_m1n2o3p4` |
| Order | `ord_{8char hex}` | `ord_q1r2s3t4` |
| Order Item | `oi_{8char hex}` | `oi_6ceaf3d4` |
| Order Number | `HNK-YYYYMMDD-HHMMSS-XXX` | `HNK-20260601-143213-348` |
| Product | slug | `black-forest` |
| Product Size | `size-{cm}-{code}` | `size-16-bf` |

---

## Seed Data

Jalankan: `php database/migrate.php --seed`

- `database/seeds/products_seeder.sql` — 5 produk + 20 sizes
- `database/seeds/admin_seeder.sql` — akun admin: `admin@hanakacake.com` / `Admin12345`

---

## Notes

- Semua tabel `utf8mb4_unicode_ci` (support emoji)
- `ON DELETE CASCADE` pada relasi parent-child (order_items, cart_items)
- `ON DELETE SET NULL` pada user reference (order/cart tetap ada jika user dihapus)
- Guest carts menggunakan `session_token`, bukan `user_id`
- `order_items` menyimpan snapshot (product_name, size_label) — tidak berubah meski produk diupdate
- `payment_expires_at` selalu disimpan **UTC** (`MidtransService::parseExpiry()`)

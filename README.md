# Hanaka Cake — Backend API

REST API backend untuk aplikasi pemesanan kue custom **Hanaka Cake**, dibangun dengan Slim PHP 4 dan MySQL 8.

---

## Tentang Project

Hanaka Cake adalah e-commerce kue custom untuk toko kue di Balikpapan, Kalimantan Timur. Backend ini menyediakan seluruh API yang digunakan oleh frontend React, mencakup:

- Autentikasi customer dan admin (JWT)
- Katalog produk (5 varian cake, 4 ukuran)
- Keranjang belanja (user & guest via session token)
- Order management (pickup / delivery)
- Pembayaran QRIS real via **Midtrans Core API**
- Admin dashboard (manajemen order, produk, customer)

**Frontend repo**: `../hanaka-project` (React 19 + Vite 8)

---

## Tech Stack

| Technology | Version | Fungsi |
|---|---|---|
| PHP | 8.1+ | Runtime |
| Slim Framework | 4.x | REST API micro-framework |
| MySQL | 8.0+ | Database |
| PHP-DI | 6.x | Dependency injection |
| firebase/php-jwt | 7.x | JWT token |
| vlucas/phpdotenv | 5.x | Environment variables |
| Monolog | 2.x | Logging |
| Midtrans | Core API | QRIS payment gateway |

---

## Requirements

- PHP 8.1+
- Composer 2.x
- MySQL 8.0+
- Ekstensi PHP: `pdo`, `pdo_mysql`, `json`, `curl`

---

## Instalasi

### 1. Clone & install dependencies

```bash
git clone <repo-url> hanaka-project-back-end
cd hanaka-project-back-end
composer install
```

### 2. Setup environment

```bash
cp .env.example .env
```

Edit `.env` sesuai konfigurasi lokal:

```env
# Database
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=hanaka_cake
DB_USERNAME=root
DB_PASSWORD=

# JWT — ganti dengan string random minimal 32 karakter
JWT_SECRET=ganti-dengan-string-random-minimal-32-karakter

# CORS — sesuaikan dengan URL frontend
CORS_ALLOWED_ORIGIN=http://localhost:5173

# Midtrans (opsional untuk dev tanpa QRIS)
MIDTRANS_SERVER_KEY=
MIDTRANS_IS_PRODUCTION=false
MIDTRANS_QRIS_ACQUIRER=gopay
```

### 3. Setup database

Buat database MySQL, lalu jalankan migration:

```bash
# Migration saja
php database/migrate.php

# Migration + seed data (produk + admin account)
php database/migrate.php --seed
```

Migration akan membuat 9 tabel: `users`, `products`, `product_sizes`, `carts`, `cart_items`, `orders`, `order_items`, dan menambah kolom role + payment fields.

Seed akan membuat:
- 5 produk cake + 20 ukuran
- Akun admin: `admin@hanakacake.com` / `Admin12345`

### 4. Jalankan server

```bash
composer start
# Server berjalan di http://localhost:8080
```

---

## Struktur Folder

```
hanaka-project-back-end/
├── public/
│   └── index.php           # Entry point (front controller)
├── src/
│   ├── Actions/            # Route handlers (thin controllers)
│   │   ├── Auth/           # register, login, logout, me
│   │   ├── Cart/           # CRUD cart
│   │   ├── Order/          # create, list, detail, mark paid
│   │   ├── Payment/        # GenerateQris, PaymentStatus, Webhook
│   │   ├── Product/        # list, detail
│   │   ├── Store/          # profile
│   │   └── Admin/          # dashboard, orders, products, customers
│   ├── Infrastructure/
│   │   ├── Database.php    # PDO singleton
│   │   ├── Repositories/   # UserRepo, ProductRepo, CartRepo, OrderRepo
│   │   └── Services/       # JwtService, MidtransService, SessionService
│   ├── Middleware/         # Cors, Jwt, AuthRequired, Admin, SecurityHeaders
│   └── Validation/         # Validator, AuthValidator, CartValidator, CheckoutValidator
├── app/
│   ├── routes.php          # Definisi semua route
│   ├── dependencies.php    # DI container bindings
│   ├── middleware.php      # Middleware stack
│   └── settings.php        # App settings
├── database/
│   ├── migrate.php         # Migration runner
│   ├── migrations/         # 001–009 SQL files
│   └── seeds/              # products_seeder.sql, admin_seeder.sql
├── .env.example            # Template environment
└── composer.json
```

---

## API Endpoints

Base URL: `http://localhost:8080/api`

### Auth
| Method | Path | Auth | Deskripsi |
|---|---|---|---|
| POST | `/auth/register` | — | Registrasi customer |
| POST | `/auth/login` | — | Login (return JWT) |
| POST | `/auth/logout` | JWT | Logout |
| GET | `/auth/me` | JWT | Data user saat ini |

### Products
| Method | Path | Auth | Deskripsi |
|---|---|---|---|
| GET | `/products` | — | Semua produk (`?featured=true`) |
| GET | `/products/:id` | — | Detail produk |

### Cart
| Method | Path | Auth | Deskripsi |
|---|---|---|---|
| GET | `/cart` | — | Isi keranjang (JWT atau X-Session-Token) |
| POST | `/cart/items` | — | Tambah item |
| PUT | `/cart/items/:id` | — | Update item |
| PATCH | `/cart/items/:id/quantity` | — | Update quantity |
| DELETE | `/cart/items/:id` | — | Hapus item |
| DELETE | `/cart` | — | Kosongkan keranjang |

### Orders
| Method | Path | Auth | Deskripsi |
|---|---|---|---|
| POST | `/orders` | — | Buat order dari cart |
| GET | `/orders` | JWT | Riwayat order user |
| GET | `/orders/:id` | — | Detail order |

### Payment
| Method | Path | Auth | Deskripsi |
|---|---|---|---|
| POST | `/payments/qris` | — | Generate QRIS via Midtrans |
| GET | `/payments/qris/status` | — | Cek status pembayaran live |
| POST | `/payments/webhook` | — | Notifikasi dari Midtrans |

### Admin (role: admin)
| Method | Path | Deskripsi |
|---|---|---|
| GET | `/admin/dashboard` | Statistik ringkasan |
| GET | `/admin/orders` | Semua order (filter + pagination) |
| GET | `/admin/orders/:id` | Detail order |
| PATCH | `/admin/orders/:id/status` | Update status order |
| PATCH | `/admin/orders/:id/payment-status` | Update status pembayaran |
| GET | `/admin/customers` | List semua customer |
| POST | `/admin/products` | Buat produk |
| PUT | `/admin/products/:id` | Update produk |
| DELETE | `/admin/products/:id` | Hapus produk |
| POST | `/admin/products/:id/sizes` | Tambah ukuran |
| PUT | `/admin/products/:id/sizes/:sizeId` | Update ukuran |
| DELETE | `/admin/products/:id/sizes/:sizeId` | Hapus ukuran |

---

## Payment QRIS (Midtrans)

Pembayaran QRIS menggunakan Midtrans Core API (server-side charge, bukan Snap).

**Yang diperlukan:** hanya `MIDTRANS_SERVER_KEY` di `.env`.

**Flow:**
1. Frontend `POST /api/payments/qris` → backend charge Midtrans → dapat `qrString` EMV
2. Frontend render `qrString` jadi gambar QR (npm `qrcode`)
3. Frontend polling `GET /api/payments/qris/status` tiap 5 detik
4. Customer scan QR → bayar → Midtrans kirim webhook ke `POST /api/payments/webhook`
5. Backend update status → frontend detect `paid` → redirect

**Testing lokal dengan ngrok:**
```bash
ngrok http 8080
# Set Notification URL di dashboard.sandbox.midtrans.com:
# https://xxxx.ngrok-free.app/api/payments/webhook
```

**Sandbox simulator:** [simulator.sandbox.midtrans.com](https://simulator.sandbox.midtrans.com)

---

## Catatan Teknis

### CORS
Sumber kebenaran CORS ada di `src/Application/ResponseEmitter/ResponseEmitter.php` — bukan `CorsMiddleware`. Header yang diizinkan mencakup `ngrok-skip-browser-warning` untuk keperluan development.

### Timezone
`expiry_time` dari Midtrans adalah WIB (Asia/Jakarta) tanpa offset. Selalu gunakan `MidtransService::parseExpiry()` untuk parsing — jangan `strtotime()` langsung karena PHP default timezone bisa berbeda.

### Auth Guard
- `AuthRequiredMiddleware` — endpoint yang wajib login
- `AdminMiddleware` — endpoint yang wajib role admin

---

## Commands

```bash
composer start          # Jalankan dev server (localhost:8080)
composer test           # Jalankan PHPUnit
php database/migrate.php         # Jalankan migration
php database/migrate.php --seed  # Migration + seed data
```

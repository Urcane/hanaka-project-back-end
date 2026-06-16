# Hanaka Cake — Dokumentasi Lengkap Project (Bundle)

> **Dokumen tunggal (single source of truth)** yang merangkum seluruh project Hanaka Cake — frontend React + backend Slim PHP — sesuai kondisi aktual codebase.
> Dokumen ini menggantikan `claude.md`, `CLAUDE.md`, dan `context.md` yang sudah usang (ketiganya masih menyebut backend "direncanakan", padahal backend sudah selesai dibangun).
> Dirancang agar siap dipakai sebagai bahan **PPT, laporan, proposal, atau dokumentasi teknis**.
>
> Terakhir diperbarui: **16 Juni 2026**

---

## Daftar Isi

1. [Ringkasan Eksekutif](#1-ringkasan-eksekutif)
2. [Profil Produk & Bisnis](#2-profil-produk--bisnis)
3. [Arsitektur Sistem (High Level)](#3-arsitektur-sistem-high-level)
4. [Tech Stack](#4-tech-stack)
5. [Backend — Struktur & Komponen](#5-backend--struktur--komponen)
6. [Model Data (Database Schema)](#6-model-data-database-schema)
7. [REST API — Daftar Endpoint](#7-rest-api--daftar-endpoint)
8. [Autentikasi & Keamanan](#8-autentikasi--keamanan)
9. [Pembayaran QRIS (Midtrans)](#9-pembayaran-qris-midtrans)
10. [Admin Panel (Server-Rendered)](#10-admin-panel-server-rendered)
11. [Frontend — Struktur & Alur](#11-frontend--struktur--alur)
12. [Alur Bisnis End-to-End](#12-alur-bisnis-end-to-end)
13. [Status Fitur & Progress](#13-status-fitur--progress)
14. [Roadmap & Pekerjaan Berikutnya](#14-roadmap--pekerjaan-berikutnya)
15. [Setup & Menjalankan Project](#15-setup--menjalankan-project)
16. [Kerangka Slide PPT (Saran)](#16-kerangka-slide-ppt-saran)

---

## 1. Ringkasan Eksekutif

**Hanaka Cake** adalah aplikasi web e-commerce untuk toko kue custom "Hanaka Cake" di Balikpapan, Kalimantan Timur. Aplikasi memungkinkan pelanggan memesan kue yang dikustomisasi (ukuran, warna, tema, pesan), checkout dengan pickup/delivery, dan membayar via Cash atau QRIS.

Project terdiri dari **dua repository**:

| Komponen | Repo | Teknologi | Status |
|---|---|---|---|
| **Frontend** | `hanaka-project-front-end` | React 19 + Vite 8 | ✅ Selesai (MVP) |
| **Backend** | `hanaka-project-back-end` | Slim PHP 4 + MySQL 8 | ✅ Selesai & berfungsi |

**Yang membedakan kondisi terkini dari dokumen lama:** backend bukan lagi rencana — sudah terimplementasi penuh, mencakup:
- **33+ Action classes** (REST API) untuk auth, produk, cart, order, payment, dan admin.
- **9 migration** + 2 seeder (database relasional MySQL).
- **Autentikasi JWT** dengan password ter-hash bcrypt (cost 12) + role customer/admin.
- **Pembayaran QRIS real** via Midtrans Core API (charge + webhook + polling status).
- **Admin Panel server-rendered** (PHP murni, tanpa framework view) untuk kelola order, produk, dan customer.

---

## 2. Profil Produk & Bisnis

| Aspek | Detail |
|---|---|
| Nama produk | Hanaka Cake |
| Jenis | E-commerce kue custom (cake ordering), B2C retail |
| Lokasi toko | Jl. DR. Sukono Rt 09 No 11, Karang Rejo, Balikpapan Kota, Kaltim 76124 |
| Jam operasional | 07.00 – 23.00 WITA |
| WhatsApp | 6281299998888 |
| Instagram | hanakacake.id |
| Bahasa | UI & pesan validasi: **Bahasa Indonesia** · Kode (variabel/fungsi): **English** |

### Katalog Produk (5 varian, 4 ukuran)

| Produk | Featured | Foto | Cover |
|---|---|---|---|
| Black Forest Cake | ✅ | `brownies.jpg` | gradient cokelat |
| Red Velvet Cake | ✅ | `strawberry-cake.jpg` | gradient merah |
| Vanila Cake | — | `vanilla-cake_*.jpg` | gradient krem |
| Lemon Cake | — | `lemon-cake_*.jpg` | gradient kuning |
| Rainbow Cake | — | `rainbow-cake_*.jpg` | gradient pelangi |

### Harga (sama untuk semua varian)

| Ukuran | Harga |
|---|---|
| 16 cm | Rp 120.000 |
| 18 cm | Rp 170.000 |
| 20 cm | Rp 220.000 |
| 22 cm | Rp 270.000 |

- Maksimal panjang pesan di kue: **60 karakter**.
- Quantity per item: **1–5**.

---

## 3. Arsitektur Sistem (High Level)

```
┌──────────────────────────┐         ┌──────────────────────────────────────┐
│   FRONTEND (React 19)     │  HTTPS  │       BACKEND (Slim PHP 4)            │
│   Vite 8 · React Router   │ ◄─────► │                                      │
│                           │  REST   │  public/index.php (front controller) │
│   - Customer storefront   │  + JWT  │     │                                │
│   - Cart / Checkout       │         │     ├── Middleware stack             │
│   - QRIS payment page     │         │     │   (SecurityHeaders, JWT, CORS) │
└──────────────────────────┘         │     │                                │
                                      │     ├── /api/*  → Action classes     │
┌──────────────────────────┐         │     │            (thin controllers)  │
│   ADMIN (browser)         │  HTML   │     │                                │
│   Server-rendered panel   │ ◄─────► │     └── /admin/* → Admin Controllers │
│   /admin/dashboard ...    │ cookie  │              (PHP views + layout)    │
└──────────────────────────┘         │                                      │
                                      │     Repositories → PDO → MySQL 8     │
       ┌─────────────────┐           │     Services: Jwt, Midtrans, Session  │
       │ Midtrans Core   │ ◄─webhook─┤                                      │
       │ API (QRIS)      │           └──────────────────────────────────────┘
       └─────────────────┘                          │
                                                     ▼
                                              ┌──────────────┐
                                              │   MySQL 8    │
                                              │  7 tabel     │
                                              └──────────────┘
```

**Pola arsitektur backend:** layered / clean-ish architecture
- **Actions** = thin controllers (1 action = 1 endpoint).
- **Repositories** = akses data (PDO), tidak ada ORM.
- **Services** = integrasi & lintas-domain (JWT, Midtrans, Session).
- **Validation** = validator server-side yang memirror aturan frontend.
- **Middleware** = CORS, JWT decode, security headers, auth/role guard.

---

## 4. Tech Stack

### Frontend
| Teknologi | Versi | Fungsi |
|---|---|---|
| React | 19 | UI library (+ React Compiler) |
| Vite | 8 | Build tool & dev server |
| React Router DOM | 7 | Client-side routing |
| qrcode (npm) | — | Render `qrString` EMV jadi gambar QR |
| ESLint | 9 | Flat config, react-hooks + react-refresh |
| CSS murni | — | Tanpa framework; Google Fonts: Fraunces + Manrope |

### Backend
| Teknologi | Versi | Fungsi |
|---|---|---|
| PHP | 8.1+ | Runtime |
| Slim Framework | 4.x | REST API micro-framework |
| MySQL | 8.0+ | Database relasional |
| PHP-DI | 6.x | Dependency injection container |
| firebase/php-jwt | 7.x | JWT encode/decode (HS256) |
| vlucas/phpdotenv | 5.x | Environment variables |
| Monolog | 2.x | Logging |
| Midtrans Core API | — | Payment gateway QRIS |
| PHPUnit / PHPStan / PHP_CodeSniffer | dev | Test & static analysis |

---

## 5. Backend — Struktur & Komponen

```
hanaka-project-back-end/
├── public/
│   ├── index.php              # Front controller (entry point)
│   ├── assets/                # admin.css, logo.png (admin panel)
│   └── uploads/products/      # Foto produk yang di-upload admin
├── app/
│   ├── routes.php             # Definisi semua route (API + admin web)
│   ├── dependencies.php       # Binding DI container
│   ├── middleware.php         # Middleware stack global
│   ├── repositories.php       # Binding repository
│   └── settings.php           # App settings
├── src/
│   ├── Actions/               # REST API handlers (thin controllers)
│   │   ├── Auth/              # Register, Login, Logout, Me
│   │   ├── Product/           # ListProducts, GetProduct
│   │   ├── Cart/             # Add, Get, Update, UpdateQuantity, Remove, Clear
│   │   ├── Order/            # Create, List, Get, MarkPaid
│   │   ├── Payment/          # GenerateQris, PaymentStatus, PaymentWebhook
│   │   ├── Store/            # GetProfile
│   │   └── Admin/            # 13 action: dashboard, orders, customers, products, sizes, image
│   ├── Admin/                 # ── Admin Panel server-rendered ──
│   │   ├── Controllers/       # Session, Dashboard, Order, Product, Customer
│   │   ├── Middleware/        # AdminAuthMiddleware (cookie-based)
│   │   └── Support/           # View (renderer), Cookie, Flash, helpers
│   ├── Infrastructure/
│   │   ├── Database.php        # PDO singleton
│   │   ├── Repositories/       # User, Product, Cart, Order
│   │   └── Services/           # Jwt, Midtrans, Session
│   ├── Middleware/             # Cors, Jwt, AuthRequired, Admin, SecurityHeaders
│   └── Validation/             # Validator, Auth, Cart, Checkout
├── database/
│   ├── migrate.php             # Migration runner (--seed untuk seed data)
│   ├── migrations/             # 001–009 (.sql)
│   └── seeds/                  # products_seeder.sql, admin_seeder.sql
├── templates/admin/            # PHP view (layout, dashboard, orders, products, customers)
└── tests/                      # PHPUnit (skeleton bawaan Slim)
```

**Catatan dua jalur "Admin":**
- `src/Actions/Admin/*` → **REST API** admin (`/api/admin/*`), JSON, dipakai bila admin dikelola lewat frontend/SPA.
- `src/Admin/Controllers/*` + `templates/admin/*` → **Admin Panel HTML** (`/admin/*`), server-rendered, login via handoff cookie dari frontend.

---

## 6. Model Data (Database Schema)

7 tabel inti + kolom tambahan (role, payment fields). Semua `id` berupa `VARCHAR` (ID berprefix dari aplikasi).

```
users ──┬─< carts ──< cart_items >── products ──< product_sizes
        │                                  ^
        └─< orders ──< order_items >────────┘
```

### `users`
`id`, `full_name`, `email` (unique), `phone`, `password_hash` (bcrypt), **`role`** ENUM(`customer`,`admin`), timestamps.

### `products`
`id`, `name`, `short_description`, `long_description`, `featured` (bool), `cover_gradient`, `cover_image`, `max_message_length` (default 60), timestamps.

### `product_sizes`
`id`, `product_id` (FK), `label` (e.g. `16`), `full_label` (e.g. `Ukuran 16 cm`), `price` (INT, rupiah).

### `carts` & `cart_items`
- `carts`: `id`, `user_id` (FK, nullable), `session_token` (untuk guest), timestamps.
- `cart_items`: `cart_id` (FK), `product_id`, `size_id`, `color_text`, `theme`, `message`, `quantity`, `unit_price`, `total_price`.

### `orders`
`id`, `order_number` (unique, format `HNK-YYYYMMDD-HHMMSS-XXX`), `user_id` (nullable), `session_token`, `customer_name`, `customer_phone`, `fulfillment_method` ENUM(`pickup`,`delivery`), `pickup_date`, `pickup_time`, `delivery_address`, `address_note`, `payment_method` ENUM(`cash`,`qris`), **`payment_status`** ENUM(`pending`,`paid`,`cod`,`expired`,`failed`), **`status`** ENUM(`menunggu konfirmasi`,`diproses`,`siap diambil`,`diantar`,`selesai`,`dibatalkan`), `total_price`, **payment fields** (`payment_provider`, `payment_reference`, `qr_string`, `qr_url`, `payment_expires_at`), timestamps.

### `order_items`
Snapshot item saat order dibuat: `order_id` (FK), `product_id`, `product_name`, `size_id`, `size_label`, `color_text`, `theme`, `message`, `quantity`, `unit_price`, `total_price`.

> **Desain penting:** `order_items` menyimpan *snapshot* nama produk & ukuran, jadi riwayat order tetap akurat walau produk diubah/dihapus admin.

---

## 7. REST API — Daftar Endpoint

Base URL: `http://localhost:8080/api`

### Auth
| Method | Path | Auth | Deskripsi |
|---|---|---|---|
| POST | `/auth/register` | — | Registrasi customer |
| POST | `/auth/login` | — | Login → return JWT |
| POST | `/auth/logout` | JWT | Logout |
| GET | `/auth/me` | JWT | Data user saat ini |

### Products
| Method | Path | Auth | Deskripsi |
|---|---|---|---|
| GET | `/products` | — | Semua produk (`?featured=true`) |
| GET | `/products/{id}` | — | Detail produk + sizes |

### Cart (JWT **atau** header `X-Session-Token` untuk guest)
| Method | Path | Deskripsi |
|---|---|---|
| GET | `/cart` | Isi keranjang |
| POST | `/cart/items` | Tambah item |
| PUT | `/cart/items/{id}` | Update item |
| PATCH | `/cart/items/{id}/quantity` | Update quantity |
| DELETE | `/cart/items/{id}` | Hapus item |
| DELETE | `/cart` | Kosongkan keranjang |

### Orders
| Method | Path | Auth | Deskripsi |
|---|---|---|---|
| POST | `/orders` | — | Buat order dari cart (guest diizinkan) |
| GET | `/orders` | JWT | Riwayat order user |
| GET | `/orders/{id}` | — | Detail order |
| PATCH | `/orders/{id}/pay` | — | Tandai sudah dibayar |

### Payment
| Method | Path | Deskripsi |
|---|---|---|
| POST | `/payments/qris` | Generate QRIS charge via Midtrans |
| GET | `/payments/qris/status` | Cek status pembayaran live |
| POST | `/payments/webhook` | Notifikasi dari Midtrans (signature SHA-512) |

### Store
| Method | Path | Deskripsi |
|---|---|---|
| GET | `/store/profile` | Info toko (nama, alamat, jam, WA, IG) |

### Admin API (`/api/admin/*`, guard `AdminMiddleware`)
| Method | Path | Deskripsi |
|---|---|---|
| GET | `/admin/dashboard` | Statistik ringkasan |
| GET | `/admin/orders` | Semua order (filter + pagination) |
| GET | `/admin/orders/{id}` | Detail order |
| PATCH | `/admin/orders/{id}/status` | Update status order |
| PATCH | `/admin/orders/{id}/payment-status` | Update status pembayaran |
| GET | `/admin/customers` | List customer |
| POST | `/admin/products` | Buat produk |
| PUT | `/admin/products/{id}` | Update produk |
| DELETE | `/admin/products/{id}` | Hapus produk |
| POST | `/admin/products/{id}/image` | Upload foto produk |
| POST | `/admin/products/{id}/sizes` | Tambah ukuran |
| PUT | `/admin/products/{id}/sizes/{sizeId}` | Update ukuran |
| DELETE | `/admin/products/{id}/sizes/{sizeId}` | Hapus ukuran |

---

## 8. Autentikasi & Keamanan

### JWT (`JwtService`)
- Algoritma **HS256**, secret dari `JWT_SECRET`, masa berlaku `JWT_EXPIRY` (default 86400 dtk / 24 jam).
- Payload: `sub` (userId), `email`, `role`, `iat`, `exp`.
- `JwtMiddleware` mendekode token dari header `Authorization: Bearer ...` dan menaruh data user ke request attribute (decode bersifat opsional — guard menolak yang benar wajib login).

### Guard Middleware
| Middleware | Fungsi |
|---|---|
| `SecurityHeadersMiddleware` | Tambah header keamanan (X-Frame-Options, dst.) |
| `JwtMiddleware` | Decode JWT (opsional, untuk semua request) |
| `CorsMiddleware` | Header CORS |
| `AuthRequiredMiddleware` | Tolak request tanpa login (401) |
| `AdminMiddleware` | Tolak non-admin pada `/api/admin/*` (403) |
| `AdminAuthMiddleware` (web) | Cek cookie JWT untuk panel `/admin/*` |

### Password
- Di-hash dengan **bcrypt cost 12** (`password_hash`). Admin default: `admin@hanakacake.com` / `Admin12345`.
- (Berbeda dari frontend MVP lama yang menyimpan plain text di localStorage — di backend sudah aman.)

> **Catatan teknis:** Sumber kebenaran CORS sebenarnya ada di `src/Application/ResponseEmitter/ResponseEmitter.php`, bukan hanya `CorsMiddleware`. Header `ngrok-skip-browser-warning` diizinkan untuk kebutuhan testing webhook lewat ngrok.

---

## 9. Pembayaran QRIS (Midtrans)

Menggunakan **Midtrans Core API** (server-side `/v2/charge`, bukan Snap). Hanya butuh `MIDTRANS_SERVER_KEY`.

**Alur:**
1. Frontend `POST /api/payments/qris` → backend memanggil `MidtransService::chargeQris()` → dapat `qr_string` (EMV), `qr_url`, `expiry_time`, `transaction_id`.
2. Frontend render `qr_string` jadi gambar QR (npm `qrcode`).
3. Frontend polling `GET /api/payments/qris/status` tiap ~5 detik.
4. Customer scan & bayar → Midtrans kirim webhook ke `POST /api/payments/webhook`.
5. Backend verifikasi **signature SHA-512** (`order_id + status_code + gross_amount + serverKey`) → update `payment_status` → frontend mendeteksi `paid` → redirect.

**Pemetaan status** (`MidtransService::mapPaymentStatus`):
`capture`/`settlement` → `paid` · `expire` → `expired` · `deny`/`cancel`/`failure` → `failed` · `pending` → `pending`.

**Timezone:** `expiry_time` Midtrans dikirim dalam WIB tanpa offset — selalu pakai `MidtransService::parseExpiry()` (jangan `strtotime()` langsung). Default fallback: now + 15 menit.

**Testing lokal:** `ngrok http 8080`, set Notification URL ke `https://xxxx.ngrok-free.app/api/payments/webhook`. Sandbox simulator: simulator.sandbox.midtrans.com.

---

## 10. Admin Panel (Server-Rendered)

Panel admin terpisah dari API — **HTML murni dirender PHP** (tanpa Twig/React), styling di `public/assets/admin.css`.

**Login handoff lintas-project:** admin login di frontend React (JWT tersimpan di localStorage) → frontend redirect ke `GET /admin/login?token=<jwt>` → `SessionController` verifikasi token & role `admin` → set cookie **HttpOnly** → redirect `/admin/dashboard`. Logout menghapus cookie & kembali ke login frontend.

**Halaman:**
| Route | Controller | Fungsi |
|---|---|---|
| `/admin/dashboard` | `DashboardController` | Statistik: total/pending/proses/selesai/batal order, jumlah customer & produk, revenue hari ini & total |
| `/admin/orders` | `OrderController@index` | Daftar order (filter) |
| `/admin/orders/{id}` | `OrderController@show` | Detail order + update status & payment-status |
| `/admin/customers` | `CustomerController@index` | Daftar customer |
| `/admin/products` | `ProductController@index` | Kelola produk + sizes (CRUD, upload foto) |

**Support classes:** `View` (renderer template + layout slot `{{ content }}`), `Cookie` (set/forget HttpOnly), `Flash` (pesan sekali tampil), `helpers.php`.

---

## 11. Frontend — Struktur & Alur

> Frontend ada di repo terpisah (`hanaka-project-front-end`). Ringkasan untuk kelengkapan dokumentasi.

**Routing (React Router v7):**
| Path | Halaman | Guard |
|---|---|---|
| `/` | HomePage (landing + best seller) | — |
| `/menu` | MenuPage (katalog) | — |
| `/menu/:productId` | CustomizeCakePage | — |
| `/cart` | CartPage | — |
| `/checkout` | CheckoutPage (`?mode=pickup\|delivery`) | — |
| `/payment/:orderId` | PaymentQrisPage | — |
| `/orders` | OrderHistoryPage | ProtectedRoute |
| `/login`, `/register` | Auth | GuestRoute |

**Pola kunci frontend:**
- **Model layer** murni di `src/models/` (auth, cart, checkout, order, product) — business logic terpisah dari komponen.
- **Context split 3 file** (`appContextObject.js` + `useApp.js` + `AppContext.jsx`) demi ESLint `react-refresh/only-export-components` — **jangan diubah**.
- **Custom validation framework** di `src/validation/customValidation.js` (tanpa Yup/Zod).
- Migrasi dari localStorage → API: layer `storageService.js` akan diganti `apiService.js` (fetch + JWT).

---

## 12. Alur Bisnis End-to-End

```
1. BROWSE     Customer lihat katalog (GET /products) → pilih produk
2. CUSTOMIZE  Pilih ukuran, warna, tema, pesan (≤60 char), qty (1–5)
3. CART       Tambah ke keranjang (POST /cart/items) — guest pakai X-Session-Token
4. CHECKOUT   Isi data + pilih pickup/delivery + metode bayar (POST /orders)
                 └─ order_number: HNK-YYYYMMDD-HHMMSS-XXX, status "menunggu konfirmasi"
5a. CASH      payment_status = cod → selesai di checkout
5b. QRIS      POST /payments/qris → tampil QR → polling status
                 └─ webhook Midtrans → payment_status = paid
6. FULFILL    Admin update status: diproses → siap diambil/diantar → selesai
7. HISTORY    Customer login lihat riwayat (GET /orders)
```

**Merge cart guest → user:** saat login/register, cart guest (`session_token`) digabung ke cart user.

---

## 13. Status Fitur & Progress

| Modul | Fitur | Status |
|---|---|---|
| **Auth** | Register, Login (JWT), Me, Logout, bcrypt, role | ✅ Selesai |
| **Produk** | List, detail, sizes, featured filter | ✅ Selesai |
| **Cart** | CRUD, guest via session token, merge ke user | ✅ Selesai |
| **Order** | Create, list, detail, mark paid, snapshot item | ✅ Selesai |
| **Payment** | QRIS real (Midtrans charge + webhook + status) | ✅ Selesai |
| **Admin API** | Dashboard, orders, customers, products, sizes, image | ✅ Selesai |
| **Admin Panel** | Server-rendered (dashboard, order, produk, customer) | ✅ Selesai |
| **Database** | 9 migration + seeder produk & admin | ✅ Selesai |
| **Keamanan** | JWT, role guard, security headers, CORS | ✅ Selesai |
| Notifikasi WA/Email | Status order ke customer | ⬜ Belum |
| Analytics/Laporan | Laporan penjualan lanjutan | ⬜ Belum |
| Promo/Diskon | Kupon, voucher | ⬜ Belum |
| Unit test backend | Coverage action/repository | ⬜ Minim (skeleton) |

---

## 14. Roadmap & Pekerjaan Berikutnya

**Prioritas tinggi**
- Integrasi penuh frontend → backend (ganti localStorage dengan `apiService.js`).
- Notifikasi WhatsApp/Email untuk perubahan status order.
- Unit & integration test backend (PHPUnit) untuk action & repository.

**Prioritas menengah**
- Laporan penjualan / analytics di admin panel.
- Sistem promo/diskon (kupon).
- Manajemen stok / inventory.

**Operasional / DevOps**
- Deploy frontend (Netlify/Vercel) + backend (VPS/shared hosting).
- `docker-compose.yml` sudah tersedia untuk environment lokal.
- CI sudah ada (`.github/workflows/tests.yml`).

---

## 15. Setup & Menjalankan Project

### Backend
```bash
composer install
cp .env.example .env          # isi DB, JWT_SECRET, CORS, MIDTRANS_SERVER_KEY

php database/migrate.php          # migration saja
php database/migrate.php --seed   # migration + seed (produk + admin)

composer start                # http://localhost:8080
composer test                 # PHPUnit
```
Admin default: `admin@hanakacake.com` / `Admin12345`.

### Frontend (repo terpisah)
```bash
npm install
npm run dev        # http://localhost:5173
npm run build
npm run lint
```

### Variabel Environment Penting (`.env`)
`DB_*`, `JWT_SECRET` (≥32 char), `JWT_EXPIRY`, `CORS_ALLOWED_ORIGIN`, `FRONTEND_URL`, `STORE_*`, `MIDTRANS_SERVER_KEY`, `MIDTRANS_IS_PRODUCTION`, `MIDTRANS_QRIS_ACQUIRER`.

---

## 16. Kerangka Slide PPT (Saran)

Urutan slide yang siap dipakai untuk presentasi:

1. **Cover** — Hanaka Cake · E-commerce Kue Custom · logo.
2. **Latar Belakang & Masalah** — toko kue di Balikpapan butuh sistem pemesanan online.
3. **Solusi & Ringkasan Produk** — storefront + admin panel + pembayaran QRIS (§1).
4. **Fitur Utama** — katalog, kustomisasi, cart, checkout pickup/delivery, QRIS, riwayat (§12, §13).
5. **Arsitektur Sistem** — diagram high level (§3).
6. **Tech Stack** — frontend + backend (§4).
7. **Model Data** — diagram relasi 7 tabel (§6).
8. **Alur Pemesanan** — flow end-to-end (§12).
9. **Pembayaran QRIS** — alur Midtrans (§9).
10. **Admin Panel** — screenshot dashboard + kelola order/produk (§10).
11. **Keamanan** — JWT, bcrypt, role guard (§8).
12. **Status & Progress** — apa yang sudah selesai (§13).
13. **Roadmap** — rencana lanjutan (§14).
14. **Demo / Penutup** — link, kontak, Q&A.

**Aset visual yang bisa dipakai:** logo (`public/assets/logo.png`), foto produk (`public/uploads/products/`), screenshot admin panel & storefront, diagram arsitektur (§3) dan ERD (§6) di dokumen ini.

---

*Dokumen ini adalah bundle dokumentasi resmi Hanaka Cake. Bila ada perubahan arsitektur signifikan, perbarui dokumen ini agar tetap menjadi single source of truth.*

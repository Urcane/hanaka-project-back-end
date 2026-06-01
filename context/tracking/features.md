# Feature Tracking

> Status semua fitur: done, in progress, planned.
> Terakhir update: 2026-06-01

---

## Legend

| Symbol | Status |
|---|---|
| ✅ | Done |
| 🔄 | In Progress |
| ⬜ | Todo (Planned) |
| ❌ | Cancelled |

---

## Frontend Features

### Core (P0 — Must Have)

| Feature | Status | Catatan |
|---|---|---|
| Login customer | ✅ | JWT — token di localStorage |
| Register customer | ✅ | 5 fields + validation |
| Home page (landing) | ✅ | Hero banner + best seller dari API |
| Menu katalog | ✅ | 5 varian, data dari API |
| Customize cake form | ✅ | Size, warna, tema, msg, qty |
| Cart (add, edit, remove) | ✅ | Full CRUD via API |
| Checkout form | ✅ | Pickup/delivery + payment |
| **QRIS payment page** | ✅ | **Real Midtrans QR, countdown, polling** |
| Order history | ✅ | Protected route, data dari API |
| Guest checkout | ✅ | Session token via X-Session-Token |
| Cart merge (guest→user) | ✅ | Saat login/register |
| Admin dashboard | ✅ | Stats, order management, product CRUD |

### Enhancement (P1 — Should Have)

| Feature | Status | Catatan |
|---|---|---|
| Responsive design | ✅ | Mobile breakpoint 900px |
| Loading states | ✅ | Skeleton + spinner |
| Polling payment status | ✅ | Tiap 5 detik, auto-redirect saat paid |
| Auto-redirect setelah paid | ✅ | 1.4 detik setelah detect paid |

### Nice-to-Have (P2)

| Feature | Status | Catatan |
|---|---|---|
| Image upload (cake reference) | ⬜ | Customer upload referensi |
| Search & filter produk | ⬜ | — |
| Toast notifications | ⬜ | Sukses/error feedback |
| Breadcrumb navigation | ⬜ | — |
| Error boundary | ⬜ | Network error handling |

### Future (P3)

| Feature | Status | Catatan |
|---|---|---|
| Dark mode | ⬜ | — |
| PWA (offline + install) | ⬜ | — |
| Multi-language (EN/ID) | ⬜ | — |
| Wishlist | ⬜ | — |
| Review/rating | ⬜ | — |
| Social login (Google) | ⬜ | — |

---

## Backend Features

### Core (P0 — Must Have)

| Feature | Status | Catatan |
|---|---|---|
| Project setup (Slim PHP 4 + MySQL) | ✅ | slim/slim-skeleton |
| Database migrations (9 files) | ✅ | Termasuk migration 009 Midtrans fields |
| User registration (bcrypt) | ✅ | cost 12 |
| User login (JWT) | ✅ | firebase/php-jwt |
| Products API (list, detail) | ✅ | Featured filter + sizes |
| Cart API (CRUD) | ✅ | User + guest session token |
| Order API (create, list, detail) | ✅ | Snapshot items |
| Cart merge (guest → user) | ✅ | Saat login/register |
| **QRIS via Midtrans Core API** | ✅ | **Real charge, QR string EMV, reuse valid QR** |
| **Payment status polling endpoint** | ✅ | **GET /api/payments/qris/status** |
| **Payment webhook (Midtrans)** | ✅ | **Signature verified, idempotent, always 200** |

### Enhancement (P1)

| Feature | Status | Catatan |
|---|---|---|
| Admin dashboard API | ✅ | Stats: orders, revenue, customers |
| Admin: manage orders | ✅ | Update status + payment status |
| Admin: manage products | ✅ | Full CRUD + sizes |
| Role-based auth (customer/admin) | ✅ | ENUM + AdminMiddleware |
| CORS fix (ResponseEmitter) | ✅ | ngrok-skip-browser-warning diizinkan |
| Rate limiting | ⬜ | Auth endpoints |
| Logging & monitoring | ⬜ | Saat ini log ke file saja |
| Image upload service | ⬜ | Foto produk |

### Nice-to-Have (P2)

| Feature | Status | Catatan |
|---|---|---|
| Email notification | ⬜ | Order confirmation |
| WhatsApp notification | ⬜ | Via API |
| API documentation (Swagger/Postman) | ⬜ | — |
| Automated tests | ⬜ | Unit + integration |

### Future (P3)

| Feature | Status | Catatan |
|---|---|---|
| Real QRIS payment gateway (production) | ⬜ | Ganti `MIDTRANS_IS_PRODUCTION=true` |
| Analytics dashboard | ⬜ | Sales report |
| Promo/discount system | ⬜ | Kupon |
| Inventory management | ⬜ | Stock tracking |
| Delivery tracking | ⬜ | Real-time location |

---

## User Stories

### Customer (Done)

- [x] US-01: Lihat katalog kue
- [x] US-02: Pilih ukuran kue
- [x] US-03: Tentukan warna dan tema
- [x] US-04: Tambahkan pesan di kue
- [x] US-05: Tambah kue ke keranjang
- [x] US-06: Edit pesanan di keranjang
- [x] US-07: Checkout pickup/delivery
- [x] US-08: Bayar via Cash/QRIS (Midtrans real)
- [x] US-09: Lihat riwayat order
- [x] US-10: Order tanpa login (guest)
- [x] US-11: Register dan login
- [x] US-12: Cart merge saat login
- [x] US-13: Lihat informasi toko

### Customer (Planned)

- [ ] US-14: Upload referensi gambar kue
- [ ] US-15: Terima notifikasi status order (email/WA)
- [ ] US-16: Re-order dari history
- [ ] US-17: Lihat estimasi waktu pengerjaan

### Admin (Done)

- [x] US-A1: Lihat daftar order masuk
- [x] US-A2: Ubah status order
- [x] US-A3: Kelola katalog produk (CRUD)
- [x] US-A4: Lihat statistik penjualan (dashboard)

### Admin (Planned)

- [ ] US-A5: Kirim notifikasi ke customer
- [ ] US-A6: Upload foto produk

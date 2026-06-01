# Feature Tracking

> Status semua fitur: done, in progress, planned.

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

| Feature | Status | Sprint | Catatan |
|---|---|---|---|
| Login customer | ✅ | 1 | Email + password |
| Register customer | ✅ | 1 | 5 fields + validation |
| Home page (landing) | ✅ | 2 | Hero banner + best seller |
| Menu katalog | ✅ | 2 | 5 varian, grid layout |
| Customize cake form | ✅ | 2 | Size, warna, tema, msg, qty |
| Cart (add, edit, remove) | ✅ | 2 | Full CRUD |
| Checkout form | ✅ | 3 | Pickup/delivery + payment |
| QRIS payment page | ✅ | 3 | Simulasi QR |
| Order history | ✅ | 3 | Protected route |
| Guest checkout | ✅ | 3 | Tanpa login |
| Cart merge (guest→user) | ✅ | 3 | Saat login/register |

### Enhancement (P1 — Should Have)

| Feature | Status | Sprint | Catatan |
|---|---|---|---|
| Responsive design | ✅ | 3 | Mobile breakpoint 900px |
| Loading states (spinners) | ⬜ | 8 | Setelah backend ready |
| Error boundary | ⬜ | 8 | Network error handling |
| Form auto-save draft | ⬜ | — | — |
| Order status real-time | ⬜ | — | WebSocket/polling |

### Nice-to-Have (P2)

| Feature | Status | Sprint | Catatan |
|---|---|---|---|
| Image upload (cake reference) | ⬜ | — | Customer upload referensi |
| Search & filter produk | ⬜ | — | — |
| Skeleton loading | ⬜ | 8 | — |
| Toast notifications | ⬜ | — | Sukses/error feedback |
| Breadcrumb navigation | ⬜ | — | — |

### Future (P3)

| Feature | Status | Sprint | Catatan |
|---|---|---|---|
| Dark mode | ⬜ | — | — |
| PWA (offline + install) | ⬜ | — | — |
| Multi-language (EN/ID) | ⬜ | — | — |
| Wishlist | ⬜ | — | — |
| Review/rating | ⬜ | — | — |
| Promo banners (dynamic) | ⬜ | — | — |
| Social login (Google) | ⬜ | — | — |

---

## Backend Features

### Core (P0 — Must Have)

| Feature | Status | Sprint | Catatan |
|---|---|---|---|
| Project setup (Slim + MySQL) | ⬜ | 4 | — |
| Database migrations | ⬜ | 4 | 7 tables |
| User registration (bcrypt) | ⬜ | 5 | — |
| User login (JWT) | ⬜ | 5 | — |
| Products API (list, detail) | ⬜ | 5 | — |
| Cart API (CRUD) | ⬜ | 6 | — |
| Order API (create, list, detail) | ⬜ | 6 | — |
| Mark order as paid | ⬜ | 6 | — |
| Cart merge (guest→user) | ⬜ | 6 | — |
| QRIS payment gateway | ⬜ | 7 | Midtrans/Xendit |
| Payment webhook | ⬜ | 7 | — |

### Enhancement (P1)

| Feature | Status | Sprint | Catatan |
|---|---|---|---|
| Admin dashboard | ⬜ | 9+ | Web-based |
| Admin: manage orders | ⬜ | 9+ | Status update |
| Admin: manage products | ⬜ | 9+ | CRUD + image |
| Image upload service | ⬜ | 9+ | — |
| Rate limiting | ⬜ | 7 | Auth endpoints |
| Logging & monitoring | ⬜ | 7 | — |

### Nice-to-Have (P2)

| Feature | Status | Sprint | Catatan |
|---|---|---|---|
| Email notification | ⬜ | — | Order confirmation |
| WhatsApp notification | ⬜ | — | Via API |
| API documentation (Swagger) | ⬜ | — | — |
| Automated tests | ⬜ | — | Unit + integration |

### Future (P3)

| Feature | Status | Sprint | Catatan |
|---|---|---|---|
| Analytics dashboard | ⬜ | — | Sales report |
| Promo/discount system | ⬜ | — | Kupon |
| Inventory management | ⬜ | — | Stock tracking |
| Multi-outlet support | ⬜ | — | — |
| Delivery tracking | ⬜ | — | Real-time location |

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
- [x] US-08: Bayar via Cash/QRIS
- [x] US-09: Lihat riwayat order
- [x] US-10: Order tanpa login (guest)
- [x] US-11: Register dan login
- [x] US-12: Cart merge saat login
- [x] US-13: Lihat informasi toko

### Customer (Planned)

- [ ] US-14: Upload referensi gambar kue
- [ ] US-15: Terima notifikasi status order
- [ ] US-16: Re-order dari history
- [ ] US-17: Lihat estimasi waktu pengerjaan

### Admin (Planned)

- [ ] US-A1: Lihat daftar order masuk
- [ ] US-A2: Ubah status order
- [ ] US-A3: Kelola katalog produk
- [ ] US-A4: Lihat laporan penjualan
- [ ] US-A5: Kirim notifikasi ke customer

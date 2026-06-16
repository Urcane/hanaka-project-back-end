# Hanaka Cake — Project Context & Development History

> ⚠️ **USANG (per 16 Juni 2026).** Dokumen ini masih menyebut backend "belum dimulai", padahal backend sudah selesai dibangun penuh.
> **Single source of truth terbaru: [`DOCUMENTATION.md`](./DOCUMENTATION.md).** Bagian di bawah dipertahankan hanya sebagai arsip ADR & history frontend.

> Dokumen ini mencatat seluruh konteks project, keputusan arsitektur, development history, dan tracking progress untuk frontend maupun backend. Gunakan sebagai referensi utama sebelum memulai sesi development baru.

---

## Daftar Isi

1. [Project Overview](#1-project-overview)
2. [Architecture Decisions](#2-architecture-decisions)
3. [Development History (Frontend)](#3-development-history-frontend)
4. [Development History (Backend)](#4-development-history-backend)
5. [Feature Tracking](#5-feature-tracking)
6. [Chat Session History](#6-chat-session-history)
7. [Known Issues & Technical Debt](#7-known-issues--technical-debt)
8. [Integration Plan](#8-integration-plan)
9. [Business Requirements](#9-business-requirements)

---

## 1. Project Overview

### Apa itu Hanaka Cake?

Hanaka Cake adalah aplikasi web e-commerce untuk toko kue custom "Hanaka Cake" di Balikpapan, Kalimantan Timur. Aplikasi ini memungkinkan customer untuk:

- Melihat katalog varian kue
- Mengkustomisasi pesanan (ukuran, warna, tema, pesan)
- Mengelola keranjang belanja
- Checkout dengan pilihan pickup/delivery
- Membayar via Cash atau QRIS
- Melihat riwayat order

### Tim & Repository

| Aspek | Detail |
|---|---|
| Repository Frontend | `Urcane/hanaka-project-front-end` |
| Repository Backend | *(belum dibuat)* |
| Branch utama | `master` |
| Hosting frontend | *(belum di-deploy)* |
| Hosting backend | *(belum di-deploy)* |

### Stakeholder

| Role | Keterangan |
|---|---|
| Owner | Hanaka Cake (toko kue di Balikpapan) |
| Developer | Urcane |
| Target User | Customer retail (B2C) |

---

## 2. Architecture Decisions

### ADR-001: React 19 + Vite 8 untuk Frontend

**Keputusan**: Menggunakan React 19 dengan Vite 8 sebagai build tool.

**Alasan**:
- React 19 mendukung React Compiler untuk optimisasi otomatis
- Vite 8 memberikan DX yang cepat dengan HMR
- Ekosistem library React sangat luas

**Konsekuensi**:
- Menggunakan `babel-plugin-react-compiler` via `@rolldown/plugin-babel`
- Perlu mematuhi rules React Compiler (no side effects in render)

---

### ADR-002: LocalStorage sebagai Temporary Persistence

**Keputusan**: Menyimpan semua data (users, carts, orders) di localStorage selama fase MVP.

**Alasan**:
- Memungkinkan development UI/UX tanpa backend
- Cepat untuk prototyping dan testing business flow
- Mudah di-migrate ke API nanti karena abstraksi di `storageService.js`

**Konsekuensi**:
- Data hilang jika user clear browser data
- Tidak ada multi-device sync
- Password tersimpan plain text (hanya untuk simulasi)
- **HARUS diganti saat backend ready**

---

### ADR-003: Custom Validation Framework (Tanpa Library)

**Keputusan**: Membuat sistem validasi sendiri di `src/validation/customValidation.js` alih-alih menggunakan library seperti Yup, Zod, atau React Hook Form.

**Alasan**:
- Mengurangi dependency dan bundle size
- Full kontrol atas pesan error (Bahasa Indonesia)
- Edukasi — memahami cara kerja validasi dari dasar

**Konsekuensi**:
- Schema-based validation yang bisa di-reuse
- Mendukung conditional validation via `when()`
- Perlu maintenance manual jika ada rule baru

---

### ADR-004: Context Split Pattern (3 File)

**Keputusan**: Memisahkan React Context menjadi 3 file terpisah:
1. `appContextObject.js` — `createContext(null)`
2. `useApp.js` — custom hook
3. `AppContext.jsx` — Provider component

**Alasan**:
- Mematuhi ESLint `react-refresh/only-export-components`
- HMR tidak break saat edit provider
- Clean separation of concerns

---

### ADR-005: Slim PHP 4 + MySQL untuk Backend (Planned)

**Keputusan**: Menggunakan Slim PHP 4 sebagai REST API framework dengan MySQL 8 sebagai database.

**Alasan**:
- Slim PHP ringan dan cocok untuk REST API
- MySQL mature dan banyak hosting mendukung
- PHP tersedia di hampir semua shared hosting Indonesia

**Konsekuensi**:
- Frontend akan berkomunikasi via REST API + JWT
- Perlu CORS middleware
- Perlu migration system untuk database schema

---

### ADR-006: Bilingual Code Convention

**Keputusan**: Kode ditulis dalam Bahasa Inggris, UI text dalam Bahasa Indonesia.

**Alasan**:
- Kode dalam English: standar industri, mudah dibaca developer manapun
- UI dalam Indonesian: target user adalah customer Indonesia

**Contoh**:
```js
// Kode English
function validateCheckoutInput(values) { ... }

// Error message Indonesian
validators.required('Nama pelanggan wajib diisi.')
```

---

## 3. Development History (Frontend)

### Sprint 1 — Project Setup & Auth (Mei 2026, Minggu 1-2)

| Tanggal | Task | Status | Catatan |
|---|---|---|---|
| Mei 2026 | Setup React 19 + Vite 8 | ✅ Selesai | Termasuk React Compiler |
| Mei 2026 | ESLint configuration | ✅ Selesai | Flat config, react-hooks + react-refresh |
| Mei 2026 | Routing setup (React Router v7) | ✅ Selesai | 9 routes + catch-all |
| Mei 2026 | AppLayout (header, nav, footer) | ✅ Selesai | Responsive |
| Mei 2026 | Login page + validasi | ✅ Selesai | Email + password |
| Mei 2026 | Register page + validasi | ✅ Selesai | fullName, email, phone, password, confirm |
| Mei 2026 | Custom validation framework | ✅ Selesai | Schema-based, conditional |
| Mei 2026 | Auth model (build account, validate) | ✅ Selesai | — |
| Mei 2026 | Guest/Protected route guards | ✅ Selesai | Redirect logic |
| Mei 2026 | Context + state management | ✅ Selesai | Context split pattern (3 file) |
| Mei 2026 | localStorage persistence | ✅ Selesai | storageService abstraction |

### Sprint 2 — Product & Cart (Mei 2026, Minggu 2-3)

| Tanggal | Task | Status | Catatan |
|---|---|---|---|
| Mei 2026 | Katalog produk (5 varian cake) | ✅ Selesai | Data statis di products.js |
| Mei 2026 | HomePage (landing + best seller) | ✅ Selesai | Hero banner + featured products |
| Mei 2026 | MenuPage (daftar produk + harga) | ✅ Selesai | Grid layout + price list panel |
| Mei 2026 | CustomizeCakePage | ✅ Selesai | Size, warna, tema, message, quantity |
| Mei 2026 | Cart model (build, rebuild, update qty) | ✅ Selesai | Immutable pattern |
| Mei 2026 | CartPage | ✅ Selesai | CRUD + fulfillment toggle |
| Mei 2026 | Edit cart item via URL param | ✅ Selesai | `/menu/:id?edit=:cartItemId` |
| Mei 2026 | Guest cart + merge saat login | ✅ Selesai | Key `__guest__` |

### Sprint 3 — Checkout & Payment (Mei 2026, Minggu 3-4)

| Tanggal | Task | Status | Catatan |
|---|---|---|---|
| Mei 2026 | CheckoutPage (form + validasi) | ✅ Selesai | Pickup/delivery conditional fields |
| Mei 2026 | Checkout model + payload builder | ✅ Selesai | — |
| Mei 2026 | Order model (create, mark paid) | ✅ Selesai | Order number format HNK-xxx |
| Mei 2026 | Payment QRIS page | ✅ Selesai | QR code via `qrcode` library |
| Mei 2026 | OrderHistoryPage | ✅ Selesai | Protected route, sorted by date |
| Mei 2026 | Guest checkout support | ✅ Selesai | Tanpa login, bisa order |
| Mei 2026 | Styling & responsive | ✅ Selesai | Mobile breakpoint 900px |
| Mei 2026 | SiteFooter (info toko) | ✅ Selesai | Alamat, WA, Instagram |

---

## 4. Development History (Backend)

> Backend belum dimulai. Bagian ini akan di-update seiring progress.

### Sprint 4 — Backend Setup (Planned)

| Task | Status | Catatan |
|---|---|---|
| Setup Slim PHP 4 + Composer | ⬜ Belum | — |
| Setup MySQL database | ⬜ Belum | — |
| Buat migration files | ⬜ Belum | 8 migration files |
| Seed data produk | ⬜ Belum | 5 produk + 20 sizes |
| Entry point + CORS middleware | ⬜ Belum | — |
| JWT service | ⬜ Belum | — |

### Sprint 5 — Auth & Products API (Planned)

| Task | Status | Catatan |
|---|---|---|
| `POST /api/auth/register` | ⬜ Belum | bcrypt hashing |
| `POST /api/auth/login` | ⬜ Belum | Return JWT |
| `GET /api/auth/me` | ⬜ Belum | Verify token |
| `GET /api/products` | ⬜ Belum | Include sizes |
| `GET /api/products/:id` | ⬜ Belum | Detail + sizes |
| Input validation (server-side) | ⬜ Belum | Mirror frontend rules |

### Sprint 6 — Cart & Order API (Planned)

| Task | Status | Catatan |
|---|---|---|
| Cart CRUD API | ⬜ Belum | User + guest session |
| `POST /api/orders` | ⬜ Belum | Validate + create |
| `GET /api/orders` | ⬜ Belum | Per-user, sorted |
| `PATCH /api/orders/:id/pay` | ⬜ Belum | Mark as paid |
| Cart merge (guest → user) | ⬜ Belum | Saat login/register |

### Sprint 7 — Payment & Polish (Planned)

| Task | Status | Catatan |
|---|---|---|
| QRIS payment gateway integration | ⬜ Belum | Midtrans/Xendit |
| Payment callback/webhook | ⬜ Belum | Auto update status |
| Error handling & logging | ⬜ Belum | — |
| Rate limiting | ⬜ Belum | Auth endpoints |
| API documentation (Postman/Swagger) | ⬜ Belum | — |

### Sprint 8 — Frontend-Backend Integration (Planned)

| Task | Status | Catatan |
|---|---|---|
| Buat `apiService.js` di frontend | ⬜ Belum | Wrapper fetch + JWT |
| Ganti localStorage ke API calls | ⬜ Belum | All CRUD operations |
| Loading states & skeletons | ⬜ Belum | UX improvement |
| Error boundary | ⬜ Belum | Network error handling |
| End-to-end testing | ⬜ Belum | — |

---

## 5. Feature Tracking

### Frontend Features

| Feature | Status | Priority | Catatan |
|---|---|---|---|
| Login/Register | ✅ Done | P0 | — |
| Home page (landing) | ✅ Done | P0 | — |
| Menu katalog | ✅ Done | P0 | — |
| Customize cake | ✅ Done | P0 | Size, warna, tema, msg, qty |
| Cart CRUD | ✅ Done | P0 | Add, edit, remove, qty |
| Checkout form | ✅ Done | P0 | Pickup/delivery + payment |
| QRIS payment page | ✅ Done | P0 | Simulasi QR |
| Order history | ✅ Done | P0 | Protected route |
| Guest checkout | ✅ Done | P1 | — |
| Cart merge (guest→user) | ✅ Done | P1 | — |
| Responsive design | ✅ Done | P1 | Mobile breakpoint 900px |
| Image upload preview | ⬜ Todo | P2 | Untuk cake custom reference |
| Dark mode | ⬜ Todo | P3 | — |
| PWA support | ⬜ Todo | P3 | Offline + install |
| Search/filter produk | ⬜ Todo | P2 | — |
| Wishlist | ⬜ Todo | P3 | — |
| Review/rating | ⬜ Todo | P3 | — |
| Multi-language (EN/ID) | ⬜ Todo | P3 | — |

### Backend Features

| Feature | Status | Priority | Catatan |
|---|---|---|---|
| User registration (bcrypt) | ⬜ Todo | P0 | — |
| User login (JWT) | ⬜ Todo | P0 | — |
| Products API | ⬜ Todo | P0 | — |
| Cart API | ⬜ Todo | P0 | — |
| Order API | ⬜ Todo | P0 | — |
| QRIS payment gateway | ⬜ Todo | P0 | Midtrans/Xendit |
| Admin dashboard | ⬜ Todo | P1 | Manage orders |
| Admin product management | ⬜ Todo | P1 | CRUD produk |
| Image upload | ⬜ Todo | P1 | Foto produk |
| Email notification | ⬜ Todo | P2 | Order confirmation |
| WhatsApp notification | ⬜ Todo | P2 | Via API |
| Order status webhook | ⬜ Todo | P1 | Auto update |
| Analytics dashboard | ⬜ Todo | P3 | Sales report |
| Promo/discount system | ⬜ Todo | P3 | Kupon, diskon |
| Inventory management | ⬜ Todo | P3 | Stock tracking |

---

## 6. Chat Session History

> Log sesi development dengan AI assistant. Update setiap kali ada sesi baru.

### Session Log

| # | Tanggal | Topik | Ringkasan | Output Utama |
|---|---|---|---|---|
| 1 | 2026-05-15 | Initial setup & context | Setup awal project, ESLint configuration, context split pattern | `react-lint-notes.md` di repo memory |
| 2 | 2026-05-15 | Continued development | Lanjutan development frontend | — |
| 3 | 2026-05-22 | Documentation & context files | Pembuatan `claude.md`, `setup.md`, dan `context.md` untuk dokumentasi project lengkap, termasuk spesifikasi backend | `claude.md`, `setup.md`, `context.md` |

### Keputusan Penting dari Chat Sessions

1. **Session 1 (15 Mei)**: ESLint `react-refresh/only-export-components` mengharuskan context di-split ke 3 file terpisah. Pattern ini harus dipertahankan.

2. **Session 3 (22 Mei)**: Diputuskan untuk membuat dokumentasi komprehensif yang mencakup frontend + backend specification, termasuk database schema, API endpoints, dan migration plan.

---

## 7. Known Issues & Technical Debt

### Security Issues (HARUS diperbaiki sebelum production)

| # | Issue | Severity | Detail |
|---|---|---|---|
| SEC-1 | Password plain text di localStorage | 🔴 Critical | Harus hash di backend |
| SEC-2 | Tidak ada CSRF protection | 🟡 Medium | Perlu token CSRF di form |
| SEC-3 | Tidak ada rate limiting | 🟡 Medium | Login brute force vulnerable |
| SEC-4 | JWT secret belum ada (frontend only) | 🟡 Medium | Belum ada auth backend |
| SEC-5 | Data sensitif di client-side | 🟡 Medium | Pindahkan ke server |

### Technical Debt

| # | Issue | Priority | Detail |
|---|---|---|---|
| TD-1 | Semua data di localStorage | P0 | Migrate ke backend API |
| TD-2 | Produk hardcoded di `products.js` | P0 | Pindah ke database |
| TD-3 | QRIS simulasi (bukan real payment) | P0 | Integrasi payment gateway |
| TD-4 | Tidak ada loading states | P1 | Perlu skeleton/spinner saat fetch API |
| TD-5 | Tidak ada error boundary | P1 | Network error handling |
| TD-6 | Tidak ada unit tests | P1 | Minimal test model layer |
| TD-7 | CSS dalam satu file besar | P2 | Pertimbangkan CSS modules atau split |
| TD-8 | Gambar produk hanya 2 (BF & RV) | P2 | Perlu foto untuk semua varian |
| TD-9 | Tidak ada image optimization | P2 | Lazy loading, WebP, srcset |
| TD-10 | Tidak ada SEO meta tags | P2 | React Helmet atau meta tags |
| TD-11 | Accessibility (a11y) belum lengkap | P2 | ARIA labels, keyboard nav |

### Bugs

| # | Bug | Status | Detail |
|---|---|---|---|
| — | *(belum ada bug yang tercatat)* | — | — |

---

## 8. Integration Plan

### Fase 1: Backend Foundation
```
1. Setup Slim PHP project
2. Buat database + migrations
3. Seed produk data
4. Implement auth endpoints (register, login, me)
5. Test dengan Postman/Thunder Client
```

### Fase 2: Core API
```
1. Implement products API (list, detail)
2. Implement cart API (CRUD)
3. Implement order API (create, list, detail, mark paid)
4. Guest cart via session token
5. Cart merge endpoint
```

### Fase 3: Frontend Migration
```
1. Buat apiService.js (fetch wrapper)
2. Ganti storageService → apiService di AppContext
3. Update auth flow (JWT token management)
4. Tambah loading states di semua page
5. Tambah error handling (network errors)
6. Test end-to-end semua flow
```

### Fase 4: Payment & Polish
```
1. Integrasi Midtrans/Xendit QRIS
2. Payment webhook/callback
3. Admin dashboard (opsional)
4. Deploy frontend (Netlify/Vercel)
5. Deploy backend (VPS/shared hosting)
```

---

## 9. Business Requirements

### User Stories (Customer)

| ID | User Story | Status |
|---|---|---|
| US-01 | Sebagai customer, saya bisa melihat katalog kue | ✅ Done |
| US-02 | Sebagai customer, saya bisa memilih ukuran kue | ✅ Done |
| US-03 | Sebagai customer, saya bisa menentukan warna dan tema kue | ✅ Done |
| US-04 | Sebagai customer, saya bisa menambahkan pesan di kue | ✅ Done |
| US-05 | Sebagai customer, saya bisa menambahkan kue ke keranjang | ✅ Done |
| US-06 | Sebagai customer, saya bisa mengedit pesanan di keranjang | ✅ Done |
| US-07 | Sebagai customer, saya bisa checkout dengan pilihan pickup atau delivery | ✅ Done |
| US-08 | Sebagai customer, saya bisa bayar via Cash atau QRIS | ✅ Done |
| US-09 | Sebagai customer, saya bisa melihat riwayat order saya | ✅ Done |
| US-10 | Sebagai customer, saya bisa order tanpa login (guest) | ✅ Done |
| US-11 | Sebagai customer, saya bisa register dan login | ✅ Done |
| US-12 | Sebagai customer, keranjang guest saya bergabung saat login | ✅ Done |
| US-13 | Sebagai customer, saya bisa melihat informasi toko | ✅ Done |
| US-14 | Sebagai customer, saya bisa upload referensi gambar kue | ⬜ Todo |
| US-15 | Sebagai customer, saya menerima notifikasi status order | ⬜ Todo |

### User Stories (Admin — Planned)

| ID | User Story | Status |
|---|---|---|
| US-A1 | Sebagai admin, saya bisa melihat daftar order masuk | ⬜ Todo |
| US-A2 | Sebagai admin, saya bisa mengubah status order | ⬜ Todo |
| US-A3 | Sebagai admin, saya bisa mengelola katalog produk | ⬜ Todo |
| US-A4 | Sebagai admin, saya bisa melihat laporan penjualan | ⬜ Todo |
| US-A5 | Sebagai admin, saya bisa mengirim notifikasi ke customer | ⬜ Todo |

### Pricing Model

| Ukuran | Harga |
|---|---|
| 16 cm | Rp 120.000 |
| 18 cm | Rp 170.000 |
| 20 cm | Rp 220.000 |
| 22 cm | Rp 270.000 |

> Semua 5 varian kue menggunakan pricing yang sama.

### Payment Methods

| Metode | Status | Keterangan |
|---|---|---|
| Cash (COD) | ✅ Aktif | Bayar saat pickup/delivery |
| QRIS | ⚠️ Simulasi | QR code lokal, belum real payment gateway |

### Fulfillment Methods

| Metode | Status | Keterangan |
|---|---|---|
| Pickup | ✅ Aktif | Ambil di toko, pilih tanggal & jam |
| Delivery | ✅ Aktif | Input alamat detail + catatan |

---

*Dokumen ini terakhir di-update: 22 Mei 2026*

# Hanaka Cake — AI Assistant Context

> Dokumen ini adalah referensi utama bagi AI assistant (Claude, Copilot, dsb.) yang bekerja di repository Hanaka Cake.
> Berisi ringkasan arsitektur, konvensi kode, business logic, dan instruksi penting agar setiap sesi bisa langsung produktif tanpa harus membaca ulang seluruh codebase.

---

## 1. Ringkasan Project

**Hanaka Cake** adalah aplikasi web pemesanan kue custom untuk toko kue "Hanaka Cake" yang berlokasi di Balikpapan, Kalimantan Timur. Saat ini terdiri dari **frontend React** dengan rencana integrasi **backend Slim PHP + MySQL**.

| Aspek | Detail |
|---|---|
| Nama produk | Hanaka Cake |
| Jenis | E-commerce kue custom (cake ordering) |
| Bahasa utama | Bahasa Indonesia (UI & validasi), kode ditulis dalam Bahasa Inggris |
| Target user | Customer toko kue (retail, B2C) |
| Lokasi toko | Jl. DR. Sukono Rt 09 No 11, Karang Rejo, Balikpapan Kota, Kaltim 76124 |
| Jam operasional | 07.00 AM – 11.00 PM WITA |

---

## 2. Tech Stack

### Frontend (Aktif)
- **React 19** dengan React Compiler (via `babel-plugin-react-compiler`)
- **Vite 8** sebagai build tool + dev server
- **React Router DOM v7** — client-side routing
- **qrcode** (npm) — generate QR code QRIS sebagai data URL
- **LocalStorage** — persistensi data sementara (user, cart, orders)
- **ESLint 9** — flat config, react-hooks + react-refresh plugin
- **CSS murni** — tanpa CSS framework, menggunakan Google Fonts (Fraunces + Manrope)

### Backend (Direncanakan)
- **Slim PHP 4** — REST API framework
- **MySQL 8** — database relasional
- **JWT / session-based auth** — autentikasi
- **Midtrans / Xendit** — payment gateway QRIS (menggantikan simulasi QR saat ini)

---

## 3. Arsitektur Frontend

```
src/
├── assets/              # Gambar statis (logo, hero banner, foto produk)
├── components/          # Komponen reusable (layout, route guard)
│   ├── AppLayout.jsx    # Shell utama: header + nav + outlet + footer
│   ├── GuestRoute.jsx   # Redirect ke / jika sudah login
│   ├── ProtectedRoute.jsx # Redirect ke /login jika belum login
│   └── SiteFooter.jsx   # Footer dengan info toko
├── context/             # React Context (global state)
│   ├── AppContext.jsx   # Provider — semua state & actions
│   ├── appContextObject.js  # createContext (dipisah utk ESLint react-refresh)
│   └── useApp.js        # Custom hook akses context
├── data/                # Data statis / katalog
│   └── products.js      # Katalog cake, ukuran, info toko
├── models/              # Business logic murni (tanpa React)
│   ├── authModel.js     # Validasi & build account
│   ├── cartModel.js     # Validasi customization, build/rebuild cart item
│   ├── checkoutModel.js # Validasi checkout, build payload
│   ├── orderModel.js    # Create order, mark as paid
│   └── productModel.js  # Query produk, hitung harga
├── pages/               # Route-level page components
│   ├── HomePage.jsx     # Landing page + best seller
│   ├── MenuPage.jsx     # Katalog semua varian cake
│   ├── CustomizeCakePage.jsx  # Form kustomisasi cake
│   ├── CartPage.jsx     # Keranjang belanja
│   ├── CheckoutPage.jsx # Form checkout (pickup/delivery, payment)
│   ├── PaymentQrisPage.jsx   # Halaman QRIS payment
│   ├── OrderHistoryPage.jsx  # Riwayat order (protected)
│   ├── LoginPage.jsx    # Login customer
│   └── RegisterPage.jsx # Registrasi customer
├── services/            # Abstraksi I/O
│   ├── storageService.js    # localStorage read/write
│   └── qrisService.js      # Generate QR code data URL
├── styles/
│   └── app.css          # Stylesheet utama (semua komponen)
├── utils/               # Helper functions
│   ├── currency.js      # formatRupiah() — Intl.NumberFormat
│   └── id.js            # createId(), createOrderNumber()
├── validation/
│   └── customValidation.js  # Validation framework custom
├── index.css            # CSS variables & body styles
├── main.jsx             # Entry point (BrowserRouter + AppProvider)
└── App.jsx              # Route definitions
```

---

## 4. Routing

| Path | Komponen | Guard | Keterangan |
|---|---|---|---|
| `/` | HomePage | — | Landing page |
| `/home` | → redirect `/` | — | Alias |
| `/menu` | MenuPage | — | Katalog cake |
| `/menu/:productId` | CustomizeCakePage | — | Kustomisasi cake + edit cart item |
| `/cart` | CartPage | — | Keranjang |
| `/checkout` | CheckoutPage | — | Form checkout (query `?mode=pickup\|delivery`) |
| `/payment/:orderId` | PaymentQrisPage | — | QRIS payment |
| `/orders` | OrderHistoryPage | ProtectedRoute | Riwayat order (harus login) |
| `/login` | LoginPage | GuestRoute | Login (redirect jika sudah login) |
| `/register` | RegisterPage | GuestRoute | Register |
| `*` | → redirect `/` | — | Catch-all |

---

## 5. Business Logic & Data Flow

### 5.1 Autentikasi
- **Registrasi**: fullName, email, phone, password, confirmPassword → validasi → `buildAccount()` → simpan ke users array di localStorage.
- **Login**: email + password → cari di users array → set sessionUserId.
- **Logout**: set sessionUserId = null.
- Password disimpan **plain text** di localStorage (simulasi — harus di-hash di backend nanti).
- Saat login/register, **guest cart di-merge** ke user cart.

### 5.2 Produk & Katalog
- 5 varian cake: Black Forest, Red Velvet, Vanila, Lemon, Rainbow.
- 4 ukuran standar: 16cm (Rp120.000), 18cm (Rp170.000), 20cm (Rp220.000), 22cm (Rp270.000).
- Hanya Black Forest dan Red Velvet yang punya foto; sisanya pakai `coverGradient` CSS.
- `featured: true` → tampil di Best Seller (HomePage).
- Max message length: 60 karakter per produk.

### 5.3 Cart
- Cart per-user (key: userId atau `__guest__`).
- Setiap cart item: productId, size, colorText, theme, message, quantity (1-5).
- Edit cart item → redirect ke `/menu/:productId?edit=:cartItemId`.
- Harga: `unitPrice × quantity`.

### 5.4 Checkout
- Pickup (ambil di toko) atau Delivery.
- Data: nama, telepon, tanggal/jam pengambilan (pickup) atau alamat (delivery).
- Metode bayar: Cash atau QRIS.
- Guest checkout diizinkan (tanpa login).

### 5.5 Order
- Format order number: `HNK-YYYYMMDD-HHMMSS-XXX`.
- Status: `menunggu konfirmasi` → `diproses` (setelah bayar QRIS).
- QRIS: generate QR code berisi payload `HANAKA-CAKE|ORDER:xxx|TOTAL:xxx|NAME:xxx`.
- Setelah place order, cart di-clear.

### 5.6 Persistensi
- Semua data disimpan di `localStorage` dengan prefix `hanaka_*_v1`.
- Keys: `hanaka_users_v1`, `hanaka_session_user_v1`, `hanaka_carts_by_user_v1`, `hanaka_orders_v1`.

---

## 6. Validasi

Sistem validasi custom di `src/validation/customValidation.js`:

- **Schema-based**: setiap field punya array validator.
- **Validators**: `required`, `email`, `phoneId`, `minLength`, `maxLength`, `oneOf`, `numeric`, `minNumber`, `maxNumber`, `strongPassword`, `sameAs`.
- **Conditional**: `when(predicate, validator)` — validasi berjalan hanya jika kondisi terpenuhi.
- **Output**: object `{ fieldName: errorMessage }`, cek via `hasAnyError()`.

---

## 7. Konvensi Kode

### Penamaan
- Komponen React: **PascalCase** (`CartPage.jsx`, `AppLayout.jsx`)
- Non-komponen: **camelCase** (`authModel.js`, `storageService.js`)
- CSS class: **kebab-case** (`cart-table-row`, `is-active`, `primary-button`)
- ID prefix: `usr_`, `cart_`, `ord_`

### Pola Kode
- **Model layer terpisah**: Business logic di `src/models/`, bukan di komponen.
- **Context split**: `appContextObject.js` (createContext) + `useApp.js` (hook) + `AppContext.jsx` (provider) — untuk mematuhi `react-refresh/only-export-components`.
- **Immutable state updates**: Selalu spread operator, tidak pernah mutasi langsung.
- **Controlled forms**: Setiap form pakai `useState` + `handleChange` pattern.
- **Validasi di submit**: `validateSchema()` → set errors → cek `hasAnyError()`.

### CSS
- Tanpa framework CSS. Semua di `src/styles/app.css` dan `src/index.css`.
- CSS custom properties (variables) untuk warna dan radius.
- Google Fonts: Fraunces (heading) + Manrope (body).
- Responsive via `@media (max-width: 900px)`.

### Build
- Vite 8 dengan `@vitejs/plugin-react` + `@rolldown/plugin-babel` untuk React Compiler.
- ESLint flat config (`eslint.config.js`), rule `no-unused-vars` mengabaikan `^[A-Z_]`.

---

## 8. Catatan Penting untuk AI Assistant

1. **Jangan ubah struktur context split** (3 file terpisah) — ini wajib untuk ESLint react-refresh.
2. **Password masih plain text** di localStorage — ini sengaja untuk MVP. Backend nanti harus hash dengan bcrypt.
3. **QRIS bukan real payment** — saat ini hanya generate QR dari string, bukan integrasi payment gateway.
4. **Data produk hardcoded** di `src/data/products.js` — nanti akan dipindah ke database.
5. **Pesan error/UI dalam Bahasa Indonesia** — pertahankan konsistensi bahasa untuk user-facing text.
6. **Kode ditulis dalam Bahasa Inggris** — variable, function, comment dalam English.
7. **Tidak pakai TypeScript** — project ini pure JavaScript + JSX.
8. **React 19 + React Compiler aktif** — pastikan kode compatible.

---

## 9. Rencana Integrasi Backend

Saat ini semua data disimpan di localStorage. Rencana migrasi ke backend:

| Frontend (sekarang) | Backend (target) |
|---|---|
| `storageService.js` → localStorage | REST API call via `fetch()` |
| `authModel.buildAccount()` | `POST /api/auth/register` |
| `authModel.validateLoginInput()` | `POST /api/auth/login` → JWT |
| `cartModel.buildCartItem()` | `POST /api/cart` |
| `orderModel.createOrder()` | `POST /api/orders` |
| `qrisService.generateQrisDataUrl()` | `POST /api/payments/qris` → real payment gateway |
| `products.js` hardcoded | `GET /api/products` dari MySQL |

---

## 10. Development History / Changelog

### Fase 1 — Frontend MVP (Mei 2026)
- Setup project React 19 + Vite 8 dengan React Compiler
- Implementasi sistem autentikasi (login/register) dengan localStorage
- Halaman Home dengan hero banner dan best seller section
- Katalog menu cake dengan 5 varian dan 4 ukuran
- Halaman kustomisasi cake (ukuran, warna, tema, catatan, quantity)
- Keranjang belanja dengan edit, hapus, update quantity
- Checkout flow (pickup/delivery, data customer, payment method)
- Simulasi QRIS payment dengan QR code generator
- Order history page (protected route)
- Custom validation framework (tanpa library eksternal)
- Responsive design untuk mobile
- Guest checkout support dengan cart merging saat login
- ESLint configuration dengan react-refresh compliance

### Fase 2 — Backend Integration (Planned)
- Setup Slim PHP 4 + MySQL 8
- REST API endpoints untuk auth, products, cart, orders, payments
- JWT authentication
- Password hashing (bcrypt)
- Real QRIS payment gateway integration (Midtrans/Xendit)
- Admin dashboard untuk manajemen pesanan
- Image upload untuk produk cake
- Email/WhatsApp notification untuk order status

---

## 11. Quick Commands

```bash
# Install dependencies
npm install

# Start development server
npm run dev

# Build for production
npm run build

# Preview production build
npm run preview

# Lint check
npm run lint
```

---

## 12. Assets

| File | Digunakan di | Keterangan |
|---|---|---|
| `logo.png` | AppLayout (header) | Logo toko di navbar |
| `big-hero.png` | HomePage | Banner utama landing page |
| `hero.png` | MenuPage | Background transparan di menu hero |
| `brownies.jpg` | HomePage, MenuPage, CustomizeCakePage | Foto Black Forest Cake |
| `strawberry-cake.jpg` | HomePage, MenuPage, CustomizeCakePage | Foto Red Velvet Cake |
| `vite.svg`, `react.svg` | — | Default Vite assets (tidak dipakai) |

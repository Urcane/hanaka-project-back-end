# Frontend — Overview

> Tech stack, struktur folder, konvensi kode, dan cara menjalankan frontend.
> Terakhir update: 2026-06-01

---

## Status: ✅ FULLY INTEGRATED WITH BACKEND

Frontend sudah terhubung penuh ke backend REST API.
Tidak ada lagi localStorage untuk data utama (auth, cart, orders, products).

---

## Tech Stack

| Technology | Version | Fungsi |
|---|---|---|
| React | 19.2.4 | UI library |
| React DOM | 19.2.4 | DOM renderer |
| React Router DOM | 7.14.1 | Client-side routing |
| Vite | 8.0.4 | Build tool & dev server |
| React Compiler | 1.0.0 | Auto-memoization (via Babel) |
| qrcode | 1.5.4 | Render EMV QR string dari Midtrans ke PNG |
| ESLint | 9.39.4 | Linter (flat config) |

---

## Quick Commands

```bash
npm install        # Install dependencies
npm run dev        # Start dev server (localhost:5173)
npm run build      # Build for production → dist/
npm run preview    # Preview production build
npm run lint       # Run ESLint
```

---

## Environment

```env
# .env
VITE_API_URL=http://localhost:8080/api
```

---

## Struktur Folder

```
src/
├── assets/              # Gambar statis (logo, hero, foto produk)
├── components/          # Reusable components (layout, route guards)
│   ├── AppLayout.jsx    # Shared layout (header + nav + footer)
│   ├── AdminLayout.jsx  # Admin layout
│   ├── AdminRoute.jsx   # Admin-only route guard
│   ├── GuestRoute.jsx   # Redirect ke / jika sudah login
│   └── ProtectedRoute.jsx # Redirect ke /login jika belum login
├── context/             # React Context (global state)
│   ├── AppContext.jsx   # Provider — semua state & actions via API
│   ├── appContextObject.js  # createContext (dipisah — ESLint react-refresh)
│   └── useApp.js        # Custom hook akses context
├── data/                # Data statis (produk lama — sudah tidak dipakai)
│   └── products.js      # ⚠ Legacy — data sekarang dari GET /api/products
├── models/              # Business logic murni (tanpa React)
│   ├── authModel.js     # Validasi login/register (masih dipakai)
│   ├── cartModel.js     # computeCartSubtotal (masih dipakai)
│   ├── checkoutModel.js # validateCheckoutInput, buildCheckoutPayload, PAYMENT_METHODS
│   ├── orderModel.js    # ⚠ Legacy — order sekarang dari API
│   └── productModel.js  # getFeaturedProducts, filter (masih dipakai)
├── pages/               # Page-level components (1 per route)
│   ├── HomePage.jsx
│   ├── MenuPage.jsx
│   ├── CustomizeCakePage.jsx
│   ├── CartPage.jsx
│   ├── CheckoutPage.jsx
│   ├── PaymentQrisPage.jsx  # ✅ Real Midtrans QR + countdown + polling
│   ├── OrderHistoryPage.jsx
│   ├── LoginPage.jsx
│   ├── RegisterPage.jsx
│   └── admin/           # Admin pages
│       ├── AdminDashboardPage.jsx
│       ├── AdminOrdersPage.jsx
│       ├── AdminOrderDetailPage.jsx
│       ├── AdminProductsPage.jsx
│       └── AdminCustomersPage.jsx
├── services/            # API abstraction layer
│   ├── apiService.js    # Base fetch wrapper (JWT + session token + ngrok header)
│   ├── authApi.js       # /auth/* endpoints
│   ├── cartApi.js       # /cart/* endpoints
│   ├── ordersApi.js     # /orders/* endpoints
│   ├── paymentApi.js    # /payments/qris + /payments/qris/status
│   ├── productsApi.js   # /products/* endpoints
│   ├── adminApi.js      # /admin/* endpoints
│   └── qrisService.js   # Render EMV qrString → PNG via npm qrcode
├── styles/
│   ├── app.css          # Stylesheet utama customer
│   └── admin.css        # Stylesheet admin
├── utils/
│   ├── currency.js      # formatRupiah()
│   ├── id.js            # createId(), createOrderNumber()
│   └── productImages.js # Mapping productId → imported image
├── validation/
│   └── customValidation.js  # Custom validation framework
├── index.css            # CSS variables & body styles
├── main.jsx             # Entry point
└── App.jsx              # Route definitions (customer + admin)
```

---

## Konvensi Kode

### Penamaan File
| Tipe | Convention | Contoh |
|---|---|---|
| React component | PascalCase | `CartPage.jsx`, `AppLayout.jsx` |
| Non-component JS | camelCase | `authModel.js`, `apiService.js` |

### Pola Kode Utama
1. **Context split** — 3 file terpisah (object, hook, provider) — WAJIB untuk ESLint react-refresh
2. **API layer di services/** — Semua fetch ke backend ada di sini, bukan di komponen/context
3. **Immutable state** — Selalu spread/map, tidak pernah mutasi langsung
4. **Controlled forms** — `useState` + `handleChange` pattern
5. **Validasi di submit** — `validateSchema()` → set errors → cek `hasAnyError()`

---

## State Management (AppContext.jsx)

State utama:
- `currentUser` — user yang login (dari `GET /api/auth/me`) atau `null`
- `isAuthLoading` — loading saat restore auth pada mount
- `products` — dari `GET /api/products`
- `cartItems`, `cartSubtotal`, `cartItemCount` — dari `GET /api/cart`
- `userOrders` — dari `GET /api/orders`

Actions utama:
- Auth: `registerAccount`, `loginAccount`, `logoutAccount`
- Cart: `addToCart`, `editCartItem`, `updateCartQuantity`, `removeCartItem`, `clearCart`
- Order: `placeOrder`, `getOrderById`, `refreshOrders`

Token management: JWT di `localStorage` (`hanaka_auth_token`), session token guest di `localStorage` (`hanaka_session_token`) — keduanya dikirim otomatis di setiap request via `apiService.js`.

---

## CORS

Semua request dari frontend ke backend (`localhost:8080`) sudah include header:
```js
'ngrok-skip-browser-warning': 'true'
```
Header ini diizinkan oleh backend (`ResponseEmitter.php`).

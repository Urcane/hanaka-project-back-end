# Frontend — Overview

> Tech stack, struktur folder, konvensi kode, dan cara menjalankan frontend.

---

## Tech Stack

| Technology | Version | Fungsi |
|---|---|---|
| React | 19.2.4 | UI library |
| React DOM | 19.2.4 | DOM renderer |
| React Router DOM | 7.14.1 | Client-side routing |
| Vite | 8.0.4 | Build tool & dev server |
| React Compiler | 1.0.0 | Auto-memoization (via Babel) |
| qrcode | 1.5.4 | Generate QR code QRIS |
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

## Struktur Folder

```
src/
├── assets/              # Gambar statis (logo, hero, foto produk)
├── components/          # Reusable components (layout, route guards)
├── context/             # React Context (global state management)
├── data/                # Data statis katalog produk
├── models/              # Business logic murni (tanpa React dependency)
├── pages/               # Page-level components (1 per route)
├── services/            # I/O abstraction (storage, QR generation)
├── styles/              # CSS stylesheets
├── utils/               # Pure utility functions
├── validation/          # Custom validation framework
├── index.css            # Global CSS variables
├── main.jsx             # Entry point
└── App.jsx              # Route definitions
```

---

## Konvensi Kode

### Penamaan File
| Tipe | Convention | Contoh |
|---|---|---|
| React component | PascalCase | `CartPage.jsx`, `AppLayout.jsx` |
| Non-component JS | camelCase | `authModel.js`, `storageService.js` |
| CSS | kebab-case | `app.css` |

### Penamaan dalam Kode
| Tipe | Convention | Contoh |
|---|---|---|
| Variable/function | camelCase | `cartItems`, `handleSubmit` |
| Component | PascalCase | `CustomizeCakePage`, `GuestRoute` |
| CSS class | kebab-case | `cart-table-row`, `is-active` |
| ID prefix | lowercase + underscore | `usr_`, `cart_`, `ord_` |
| Constant | camelCase (object) / UPPER_SNAKE (primitive) | `GUEST_CART_KEY` |

### Pola Kode Utama
1. **Model layer terpisah** — Business logic di `src/models/`, bukan di komponen
2. **Context split** — 3 file terpisah (object, hook, provider)
3. **Immutable state** — Selalu spread/map, tidak pernah mutasi langsung
4. **Controlled forms** — `useState` + `handleChange` pattern
5. **Validasi di submit** — `validateSchema()` → set errors → cek `hasAnyError()`

---

## Build Configuration

### Vite (`vite.config.js`)
```js
import { defineConfig } from 'vite'
import react, { reactCompilerPreset } from '@vitejs/plugin-react'
import babel from '@rolldown/plugin-babel'

export default defineConfig({
  plugins: [
    react(),
    babel({ presets: [reactCompilerPreset()] })
  ],
})
```

### ESLint (`eslint.config.js`)
- Flat config (ESLint 9)
- Plugins: `react-hooks`, `react-refresh`
- Rule: `no-unused-vars` ignore pattern `^[A-Z_]`
- Ignores: `dist/`

---

## State Management

Menggunakan React Context dengan pattern:
- `AppContext.jsx` — Provider yang hold semua state + actions
- State: `users`, `sessionUserId`, `cartsByUser`, `orders`
- Derived: `currentUser`, `cartItems`, `cartItemCount`, `cartSubtotal`, `userOrders`
- Actions: `registerAccount`, `loginAccount`, `logoutAccount`, `addToCart`, `editCartItem`, `updateCartQuantity`, `removeCartItem`, `clearCart`, `placeOrder`, `getOrderById`, `markCurrentUserOrderPaid`

Persistensi via `useEffect` → `storageService.js` → `localStorage`.

---

## Assets

| File | Digunakan di | Keterangan |
|---|---|---|
| `logo.png` | AppLayout | Logo toko di navbar |
| `big-hero.png` | HomePage | Banner utama landing |
| `hero.png` | MenuPage | Background transparan |
| `brownies.jpg` | HomePage, MenuPage, CustomizeCakePage | Black Forest |
| `strawberry-cake.jpg` | HomePage, MenuPage, CustomizeCakePage | Red Velvet |
| `vite.svg`, `react.svg` | — | Default (tidak dipakai) |

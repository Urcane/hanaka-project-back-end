# Frontend ↔ Backend Integration Plan

> Langkah-langkah detail untuk mengintegrasikan frontend dengan backend.

---

## Overview

Saat ini frontend berjalan sepenuhnya dengan localStorage. Integrasi ke backend dilakukan secara bertahap agar tidak break existing functionality.

---

## Fase 1: Backend Foundation (Prerequisite)

**Status: ✅ SELESAI**

```
✅ Setup Slim PHP project structure
✅ Database + migrations ready (7 tables)
✅ Seed products data (5 produk + 20 sizes)
✅ Auth endpoints working (register, login, me, logout)
✅ CORS middleware configured (localhost:5173)
□ Tested via Postman/Thunder Client
```

**Note**: Backend sudah diimplementasikan. Tinggal setup MySQL, run migrations, dan test.
Lihat `context/frontend-reference.md` untuk API reference lengkap.

---

## Fase 2: API Service Layer (Frontend)

Buat abstraksi API di frontend **tanpa mengubah business logic**:

### Step 1: Buat `src/services/apiService.js`

```js
const API_BASE = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8080/api'

let authToken = null

export function setAuthToken(token) {
  authToken = token
}

export function clearAuthToken() {
  authToken = null
}

async function request(method, path, body = null) {
  const headers = { 'Content-Type': 'application/json' }
  if (authToken) {
    headers['Authorization'] = `Bearer ${authToken}`
  }

  const options = { method, headers }
  if (body) {
    options.body = JSON.stringify(body)
  }

  const response = await fetch(`${API_BASE}${path}`, options)
  const data = await response.json()

  if (!response.ok) {
    throw { status: response.status, ...data }
  }

  return data
}

export const api = {
  get: (path) => request('GET', path),
  post: (path, body) => request('POST', path, body),
  put: (path, body) => request('PUT', path, body),
  patch: (path, body) => request('PATCH', path, body),
  delete: (path) => request('DELETE', path),
}
```

### Step 2: Buat `src/services/authApi.js`

```js
import { api, setAuthToken, clearAuthToken } from './apiService.js'

export async function apiRegister(values) {
  const data = await api.post('/auth/register', values)
  setAuthToken(data.token)
  return data
}

export async function apiLogin(values) {
  const data = await api.post('/auth/login', values)
  setAuthToken(data.token)
  return data
}

export async function apiLogout() {
  clearAuthToken()
  await api.post('/auth/logout').catch(() => {}) // best-effort
}

export async function apiGetMe() {
  return api.get('/auth/me')
}
```

### Step 3: Buat API services lainnya

```
src/services/
├── apiService.js      # Base fetch wrapper
├── authApi.js         # Auth endpoints
├── productsApi.js     # GET /products, GET /products/:id
├── cartApi.js         # Cart CRUD
├── ordersApi.js       # Orders CRUD
├── paymentApi.js      # QRIS payment
├── storageService.js  # ← KEEP untuk fallback/migration period
└── qrisService.js     # ← REMOVE setelah backend payment ready
```

---

## Fase 3: Context Migration

Update `AppContext.jsx` secara bertahap:

### Strategy: Feature Toggle

```js
const USE_API = import.meta.env.VITE_USE_API === 'true'
```

Ini memungkinkan switch antara localStorage dan API tanpa mengubah logic.

### Migration Order (paling mudah dulu):

1. **Products** — Paling simple, read-only
   - Replace `import { cakeCatalog }` dengan `useEffect(() => fetchProducts())`
   - Tambah loading state

2. **Auth** — Independent, tidak affect cart/order
   - Replace `loginAccount`/`registerAccount` dengan API calls
   - Simpan JWT di state (bukan localStorage)
   - Add `apiGetMe()` di mount untuk restore session

3. **Cart** — Depends on auth (untuk user identification)
   - Replace cart operations dengan API calls
   - Guest cart via session cookie

4. **Orders** — Depends on cart (create order from cart)
   - Replace order operations dengan API calls

5. **Payment** — Last, depends on order
   - Replace local QR generation dengan `POST /api/payments/qris`
   - Add polling for payment status

---

## Fase 4: UI Updates

Setelah API connected:

### Loading States
```jsx
function MenuPage() {
  const { products, isLoading } = useApp()

  if (isLoading) return <LoadingSkeleton />
  return <ProductGrid products={products} />
}
```

### Error Handling
```jsx
function ErrorBoundary({ children }) {
  // Catch network errors, show retry UI
}
```

### Optimistic Updates (Cart)
```jsx
// Update UI immediately, revert if API fails
const updateCartQuantity = async (itemId, quantity) => {
  const previousItems = cartItems
  setCartItems(items => items.map(...)) // optimistic

  try {
    await cartApi.updateQuantity(itemId, quantity)
  } catch {
    setCartItems(previousItems) // revert
    showError('Gagal update quantity')
  }
}
```

---

## Fase 5: Cleanup

Setelah semua terintegrasi:

```
□ Hapus src/services/storageService.js
□ Hapus src/services/qrisService.js (jika backend handle QR)
□ Hapus src/data/products.js (data dari API)
□ Update .env.example dengan VITE_API_BASE_URL
□ Remove localStorage fallback code
□ Update claude.md dan context docs
```

---

## Environment Configuration

### Development
```env
VITE_API_BASE_URL=http://localhost:8080/api
VITE_USE_API=true
```

### Production
```env
VITE_API_BASE_URL=https://api.hanakacake.com/api
VITE_USE_API=true
```

### Fallback (tanpa backend)
```env
VITE_USE_API=false
# Frontend akan pakai localStorage mode
```

---

## Testing Plan

| Step | Test |
|---|---|
| 1 | Register → verify user created di DB |
| 2 | Login → verify JWT valid |
| 3 | Add to cart → verify cart di DB |
| 4 | Checkout → verify order di DB |
| 5 | QRIS payment → verify QR generated |
| 6 | Guest checkout → verify session-based cart |
| 7 | Login after guest → verify cart merged |
| 8 | Order history → verify only user's orders shown |
| 9 | Error scenarios → verify graceful handling |

---

## Rollback Plan

Jika integrasi bermasalah:
1. Set `VITE_USE_API=false` → kembali ke localStorage mode
2. Frontend tetap functional tanpa backend
3. Fix backend issue, lalu enable kembali

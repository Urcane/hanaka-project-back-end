# Frontend Reference — Backend API Integration Guide

> Dokumen ini adalah referensi lengkap untuk AI assistant yang bekerja di **frontend** Hanaka Cake.
> Berisi semua detail backend API yang sudah diimplementasikan, mapping field frontend ↔ backend,
> dan panduan step-by-step integrasi.

---

## 1. Backend API Base

```
Development: http://localhost:8080/api
Production:  https://api.hanakacake.com/api (TBD)
```

Semua endpoint return JSON dengan format konsisten:

```js
// Success
{ "ok": true, "data...": ... }

// Error
{ "ok": false, "error": "Pesan error.", "errors": { "field": "Pesan per field." } }
```

---

## 2. Authentication (JWT)

### Token Flow
1. Register/Login → backend return `{ token: "eyJ..." }` 
2. Simpan token di **memory** (state/variable), bukan localStorage
3. Kirim token di setiap request: `Authorization: Bearer <token>`
4. Token expired → response 401 → redirect ke login
5. Logout → hapus token dari memory

### Guest Session
- Guest user diidentifikasi via **session token** (header `X-Session-Token`)
- Generate session token saat pertama kali add to cart (jika belum login)
- Simpan session token di state/localStorage
- Saat login/register, kirim session token → backend auto merge guest cart

---

## 3. Endpoint Reference

### 3.1 Auth

#### `POST /api/auth/register`
```js
// Request
{ "fullName": "Budi", "email": "budi@email.com", "phone": "081234567890", "password": "Pass1234", "confirmPassword": "Pass1234" }

// Response 201
{ "ok": true, "user": { "id": "usr_a1b2c3d4", "fullName": "Budi", "email": "budi@email.com", "phone": "081234567890", "role": "customer", "createdAt": "..." }, "token": "eyJ..." }

// Error 400
{ "ok": false, "error": "Data registrasi tidak valid.", "errors": { "email": "Masukkan format email yang valid." } }

// Error 409
{ "ok": false, "error": "Email ini sudah terdaftar. Silakan login.", "errors": { "email": "Email ini sudah terdaftar." } }
```

#### `POST /api/auth/login`
```js
// Request
{ "email": "budi@email.com", "password": "Pass1234" }

// Response 200
{ "ok": true, "user": { "id": "usr_a1b2c3d4", "fullName": "Budi", ... }, "token": "eyJ..." }

// Error 401
{ "ok": false, "error": "Email atau password belum sesuai." }
```

#### `POST /api/auth/logout` (Auth Required)
```js
// Response 200
{ "ok": true }
```

#### `GET /api/auth/me` (Auth Required)
```js
// Response 200
{ "ok": true, "user": { "id": "usr_a1b2c3d4", "fullName": "Budi", ... } }

// Error 401
{ "ok": false, "error": "Token tidak valid atau sudah expired." }
```

---

### 3.2 Products (Public — No Auth)

#### `GET /api/products`
Query param: `?featured=true` (optional)

```js
// Response 200
{
  "ok": true,
  "products": [
    {
      "id": "black-forest",
      "name": "Black Forest Cake",
      "shortDescription": "Manis, lembut...",
      "longDescription": "Tekstur lembut...",
      "featured": true,
      "coverGradient": "linear-gradient(135deg, #8a5a44 0%, #bc8b73 100%)",
      "coverImage": "brownies.jpg",       // null jika tidak ada foto
      "maxMessageLength": 60,
      "sizes": [
        { "id": "size-16-bf", "label": "16", "fullLabel": "Ukuran 16 cm", "price": 120000 },
        { "id": "size-18-bf", "label": "18", "fullLabel": "Ukuran 18 cm", "price": 170000 },
        { "id": "size-20-bf", "label": "20", "fullLabel": "Ukuran 20 cm", "price": 220000 },
        { "id": "size-22-bf", "label": "22", "fullLabel": "Ukuran 22 cm", "price": 270000 }
      ],
      "startingPrice": 120000
    }
  ]
}
```

#### `GET /api/products/{productId}`
```js
// Response 200
{ "ok": true, "product": { ...sameAsAbove } }

// Error 404
{ "ok": false, "error": "Produk tidak ditemukan." }
```

---

### 3.3 Cart

#### `GET /api/cart`
```js
// Headers: Authorization (optional) + X-Session-Token (for guest)
// Response 200
{
  "ok": true,
  "items": [
    {
      "id": "cart_m1n2o3p4",
      "productId": "black-forest",
      "productName": "Black Forest Cake",
      "productDescription": "Manis, lembut...",
      "productGradient": "linear-gradient(...)",
      "productImage": "brownies.jpg",
      "size": { "id": "size-18-bf", "label": "18", "fullLabel": "Ukuran 18 cm", "price": 170000 },
      "colorText": "Merah Muda",
      "theme": "Roblox",
      "message": "Happy Birthday",
      "quantity": 2,
      "unitPrice": 170000,
      "totalPrice": 340000
    }
  ],
  "subtotal": 340000,
  "itemCount": 2
}
```

#### `POST /api/cart/items`
```js
// Request
{ "productId": "black-forest", "sizeId": "size-18-bf", "colorText": "Merah Muda", "theme": "Roblox", "message": "Happy Birthday", "quantity": 2 }

// Response 201
{ "ok": true, "item": { ...cartItem }, "sessionToken": "abc123..." }
// sessionToken hanya dikembalikan saat guest pertama kali add item — simpan ini!
```

#### `PUT /api/cart/items/{itemId}`
```js
// Request (update seluruh item, productId tetap)
{ "sizeId": "size-20-bf", "colorText": "Biru", "theme": "Frozen", "message": "Selamat", "quantity": 1 }

// Response 200
{ "ok": true, "item": { ...updatedItem } }
```

#### `PATCH /api/cart/items/{itemId}/quantity`
```js
// Request
{ "quantity": 3 }

// Response 200
{ "ok": true, "item": { ...updatedItem } }
```

#### `DELETE /api/cart/items/{itemId}`
```js
// Response 200
{ "ok": true }
```

#### `DELETE /api/cart`
```js
// Response 200 — Clear all items
{ "ok": true }
```

---

### 3.4 Orders

#### `POST /api/orders`
```js
// Request
{
  "customerName": "Budi Santoso",
  "phone": "081234567890",
  "pickupMethod": "pickup",          // "pickup" | "delivery"
  "pickupDate": "2026-05-25",        // required if pickup
  "pickupTime": "14:00",             // required if pickup
  "address": "",                      // required if delivery, max 220
  "addressNote": "",                  // optional, max 120
  "paymentMethod": "qris"            // "cash" | "qris"
}

// Response 201
{
  "ok": true,
  "order": {
    "id": "ord_q1r2s3t4",
    "orderNumber": "HNK-20260522-143052-847",
    "userId": "usr_a1b2c3d4",        // null untuk guest
    "customerName": "Budi Santoso",
    "customerPhone": "081234567890",
    "fulfillmentMethod": "pickup",
    "pickupDate": "2026-05-25",
    "pickupTime": "14:00",
    "deliveryAddress": null,
    "addressNote": "",
    "paymentMethod": "qris",
    "paymentStatus": "pending",       // "pending" | "paid" | "cod"
    "status": "menunggu konfirmasi",
    "items": [ ...orderItems ],
    "totalPrice": 340000,
    "createdAt": "2026-05-22T07:30:00"
  }
}

// Error 400
{ "ok": false, "error": "Keranjang masih kosong." }
```

#### `GET /api/orders` (Auth Required)
```js
// Response 200
{ "ok": true, "orders": [ ...orderObjects ] }
```

#### `GET /api/orders/{orderId}`
```js
// Response 200
{ "ok": true, "order": { ...orderObject } }

// Error 404
{ "ok": false, "error": "Order tidak ditemukan." }
```

#### `PATCH /api/orders/{orderId}/pay`
```js
// Response 200 — Mark QRIS order as paid
{ "ok": true, "order": { ...updatedOrder, "paymentStatus": "paid", "status": "diproses" } }

// Error 400
{ "ok": false, "error": "Order ini bukan pembayaran QRIS." }
{ "ok": false, "error": "Order ini sudah dibayar." }
```

---

### 3.5 Payment

#### `POST /api/payments/qris`
```js
// Request
{ "orderId": "ord_q1r2s3t4" }

// Response 201
{
  "ok": true,
  "payment": {
    "orderId": "ord_q1r2s3t4",
    "orderNumber": "HNK-20260522-143052-847",
    "amount": 340000,
    "qrString": "HANAKA-CAKE|ORDER:HNK-20260522-143052-847|TOTAL:340000|NAME:Budi",
    "expiresAt": "2026-05-22T08:00:00+08:00",
    "status": "pending"
  }
}
```

---

### 3.6 Store

#### `GET /api/store/profile`
```js
// Response 200
{
  "ok": true,
  "store": {
    "name": "Hanaka Cake",
    "address": "Jl. DR. Sukono Rt 09 No 11, Karang Rejo, Balikpapan Kota, Kalimantan Timur. 76124",
    "hours": "07.00 AM - 11.00 PM",
    "whatsapp": "6281299998888",
    "instagram": "hanakacake.id"
  }
}
```

---

## 4. Field Mapping: Frontend ↔ Backend

### User
| Frontend (localStorage) | Backend (API Response) | Perubahan |
|---|---|---|
| `user.fullName` | `user.fullName` | Sama |
| `user.email` | `user.email` | Sama |
| `user.phone` | `user.phone` | Sama |
| `user.password` (plain text!) | Tidak di-return | Backend hash bcrypt |
| `sessionUserId` (localStorage) | JWT token (memory) | **Ganti mekanisme** |

### Product
| Frontend (`products.js`) | Backend (`GET /api/products`) | Perubahan |
|---|---|---|
| `product.shortDescription` | `product.shortDescription` | Sama |
| `product.sizes[].id` → `"size-16"` | `product.sizes[].id` → `"size-16-bf"` | **ID berbeda (include product suffix)** |
| `product.coverImage` (import static) | `product.coverImage` → filename string | **Perlu mapping ke asset** |
| Hardcoded di `products.js` | Fetch dari API | **Ubah ke async** |

### Cart Item
| Frontend (localStorage) | Backend (API Response) | Perubahan |
|---|---|---|
| `item.size.price` | `item.size.price` | Sama |
| `item.productDescription` | `item.productDescription` | Sama |
| `item.productGradient` | `item.productGradient` | Sama |
| Cart key `__guest__` | Session token (header) | **Ganti mekanisme** |
| `cartsByUser` object | Backend manages | **Hapus state lokal** |

### Order
| Frontend (localStorage) | Backend (API Response) | Perubahan |
|---|---|---|
| `order.fulfillmentMethod` | `order.fulfillmentMethod` | Sama |
| `order.deliveryAddress` | `order.deliveryAddress` | Sama |
| `order.items[]` (full cart items) | `order.items[]` (snapshot) | **items di backend = snapshot** |
| `order.paymentStatus` | `order.paymentStatus` | Sama |
| `order.status` | `order.status` | Sama |

---

## 5. Key Differences to Handle

### 5.1 Size ID Format Changed
```
Frontend sekarang: "size-16", "size-18", "size-20", "size-22"
Backend:          "size-16-bf", "size-18-rv", "size-16-vc", dll.
```
**Solusi**: Gunakan size ID dari API response, bukan hardcode.

### 5.2 Product Images
```
Frontend: import browniesImg from '../assets/brownies.jpg'
Backend:  product.coverImage = "brownies.jpg" (string)
```
**Solusi**: Mapping di frontend, atau serve images via backend static folder.

### 5.3 Guest Cart Identity
```
Frontend sekarang: cartsByUser['__guest__']
Backend:          X-Session-Token header / session_token cookie
```
**Solusi**: Simpan session token saat pertama add to cart (returned in response).

### 5.4 Async Operations
```
Frontend sekarang: Synchronous state updates
Backend:          Async fetch() → loading states needed
```
**Solusi**: Tambahkan `isLoading`, `error` state di context.

---

## 6. Suggested `apiService.js`

```js
const API_BASE = import.meta.env.VITE_API_URL || 'http://localhost:8080/api'

let authToken = null
let sessionToken = localStorage.getItem('hanaka_session_token') || null

export function setAuthToken(token) { authToken = token }
export function clearAuthToken() { authToken = null }
export function getSessionToken() { return sessionToken }
export function setSessionToken(token) {
  sessionToken = token
  localStorage.setItem('hanaka_session_token', token)
}

async function request(method, path, body = null) {
  const headers = { 'Content-Type': 'application/json' }

  if (authToken) {
    headers['Authorization'] = `Bearer ${authToken}`
  }
  if (sessionToken) {
    headers['X-Session-Token'] = sessionToken
  }

  const options = { method, headers }
  if (body) options.body = JSON.stringify(body)

  const res = await fetch(`${API_BASE}${path}`, options)
  const data = await res.json()

  if (!res.ok) {
    const error = new Error(data.error || 'Terjadi kesalahan.')
    error.status = res.status
    error.errors = data.errors || {}
    throw error
  }

  return data
}

export const api = {
  get:    (path) => request('GET', path),
  post:   (path, body) => request('POST', path, body),
  put:    (path, body) => request('PUT', path, body),
  patch:  (path, body) => request('PATCH', path, body),
  delete: (path) => request('DELETE', path),
}
```

---

## 7. Migration Checklist

### Phase 1: API Service + Products (Safest Start)
```
□ Buat src/services/apiService.js (fetch wrapper + token management)
□ Buat src/services/productsApi.js
□ Ganti products dari hardcoded → fetch GET /api/products
□ Tambah loading state untuk products
□ Update product image mapping (coverImage string → import)
□ Size ID sekarang pakai format baru (size-16-bf)
```

### Phase 2: Auth
```
□ Buat src/services/authApi.js
□ Update registerAccount → POST /api/auth/register
□ Update loginAccount → POST /api/auth/login
□ Update logoutAccount → clear token
□ Tambahkan auth restore (GET /api/auth/me saat app mount)
□ Hapus users array dari localStorage
□ Hapus password plain text storage
```

### Phase 3: Cart
```
□ Buat src/services/cartApi.js
□ Update addToCart → POST /api/cart/items
□ Update editCartItem → PUT /api/cart/items/{id}
□ Update updateCartQuantity → PATCH /api/cart/items/{id}/quantity
□ Update removeCartItem → DELETE /api/cart/items/{id}
□ Update clearCart → DELETE /api/cart
□ Handle sessionToken untuk guest cart
□ Hapus cartsByUser dari localStorage
```

### Phase 4: Orders + Payment
```
□ Buat src/services/ordersApi.js
□ Update placeOrder → POST /api/orders
□ Update order list → GET /api/orders
□ Update getOrderById → GET /api/orders/{id}
□ Update markPaid → PATCH /api/orders/{id}/pay
□ Update QRIS → POST /api/payments/qris (get qrString, generate QR locally)
□ Hapus orders dari localStorage
```

### Phase 5: Cleanup
```
□ Hapus src/services/storageService.js
□ Hapus src/data/products.js (data dari API)
□ Hapus localStorage keys hanaka_*_v1
□ Update .env → tambah VITE_API_URL
□ Update claude.md
```

---

## 8. Error Handling Pattern

```js
try {
  const data = await api.post('/auth/login', { email, password })
  // success
} catch (err) {
  if (err.status === 401) {
    setFormError('Email atau password belum sesuai.')
  } else if (err.status === 400 && err.errors) {
    setFieldErrors(err.errors)  // { email: "...", password: "..." }
  } else {
    setFormError('Terjadi kesalahan. Silakan coba lagi.')
  }
}
```

---

## 9. Env Configuration

### `.env` (Frontend)
```env
VITE_API_URL=http://localhost:8080/api
```

### `.env.production`
```env
VITE_API_URL=https://api.hanakacake.com/api
```

---

## 10. CORS Note

Backend sudah dikonfigurasi untuk menerima request dari `http://localhost:5173` (Vite dev server).
Jika frontend berjalan di port/domain lain, update `CORS_ALLOWED_ORIGIN` di backend `.env`.

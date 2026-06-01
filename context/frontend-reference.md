# Frontend Reference — Backend API Integration Guide

> Dokumen ini adalah referensi lengkap untuk AI assistant yang bekerja di **frontend** Hanaka Cake.
> Berisi semua detail backend API yang sudah diimplementasikan, mapping field frontend ↔ backend.
> Terakhir update: 2026-06-01

---

## Status: ✅ FULLY INTEGRATED

Frontend sudah terhubung penuh ke backend. Tidak ada lagi localStorage untuk data utama.

---

## 1. Backend API Base

```
Development: http://localhost:8080/api
Production:  https://api.hanakacake.com/api (TBD)
```

Semua endpoint return JSON dengan format konsisten:
```json
// Success
{ "ok": true, "data...": ... }

// Error
{ "ok": false, "error": "Pesan error.", "errors": { "field": "Pesan per field." } }
```

---

## 2. Authentication (JWT)

### Token Flow
1. Register/Login → backend return `{ token: "eyJ..." }`
2. Token disimpan di `localStorage` (`hanaka_auth_token`)
3. `apiService.js` kirim token di setiap request: `Authorization: Bearer <token>`
4. Token expired → response 401 → `apiGetMe()` gagal → `currentUser = null`
5. Logout → hapus token dari localStorage

### Guest Session
- Guest diidentifikasi via **session token** (header `X-Session-Token`)
- Token dibuat backend saat pertama kali add to cart (jika belum login)
- Disimpan di `localStorage` (`hanaka_session_token`)
- `apiService.js` kirim otomatis di setiap request jika ada
- Saat login/register, kirim session token → backend auto merge guest cart

---

## 3. Endpoint Reference

### 3.1 Auth

#### `POST /api/auth/register`
```json
// Request
{ "fullName": "Budi", "email": "budi@email.com", "phone": "081234567890", "password": "Pass1234", "confirmPassword": "Pass1234" }

// Response 201
{ "ok": true, "user": { "id": "usr_a1b2c3d4", "fullName": "Budi", "email": "...", "phone": "...", "role": "customer", "createdAt": "..." }, "token": "eyJ..." }
```

#### `POST /api/auth/login`
```json
// Request
{ "email": "budi@email.com", "password": "Pass1234" }

// Response 200
{ "ok": true, "user": { "id": "...", "role": "customer" | "admin", ... }, "token": "eyJ..." }

// Error 401
{ "ok": false, "error": "Email atau password belum sesuai." }
```

#### `POST /api/auth/logout` (Auth Required)
```json
{ "ok": true }
```

#### `GET /api/auth/me` (Auth Required)
```json
{ "ok": true, "user": { "id": "...", "role": "...", ... } }
```

---

### 3.2 Products (Public)

#### `GET /api/products` — `?featured=true` (optional)
```json
{
  "ok": true,
  "products": [{
    "id": "black-forest",
    "name": "Black Forest Cake",
    "shortDescription": "...",
    "longDescription": "...",
    "featured": true,
    "coverGradient": "linear-gradient(135deg, #8a5a44 0%, #bc8b73 100%)",
    "coverImage": "brownies.jpg",
    "maxMessageLength": 60,
    "sizes": [
      { "id": "size-16-bf", "label": "16", "fullLabel": "Ukuran 16 cm", "price": 120000 }
    ],
    "startingPrice": 120000
  }]
}
```

#### `GET /api/products/{productId}`
```json
{ "ok": true, "product": { ...sameAsAbove } }
```

---

### 3.3 Cart

#### `GET /api/cart`
Header: `Authorization` (optional) + `X-Session-Token` (guest)
```json
{
  "ok": true,
  "items": [{
    "id": "cart_m1n2o3p4",
    "productId": "black-forest",
    "productName": "Black Forest Cake",
    "productDescription": "...",
    "productGradient": "linear-gradient(...)",
    "productImage": "brownies.jpg",
    "size": { "id": "size-18-bf", "label": "18", "fullLabel": "Ukuran 18 cm", "price": 170000 },
    "colorText": "Merah Muda",
    "theme": "Ulang Tahun",
    "message": "Happy Birthday",
    "quantity": 2,
    "unitPrice": 170000,
    "totalPrice": 340000
  }],
  "subtotal": 340000,
  "itemCount": 2
}
```

#### `POST /api/cart/items`
```json
// Request
{ "productId": "black-forest", "sizeId": "size-18-bf", "colorText": "Merah Muda", "theme": "Ulang Tahun", "message": "Happy Birthday", "quantity": 2 }

// Response 201
{ "ok": true, "item": { ...cartItem }, "sessionToken": "abc123..." }
// sessionToken hanya dikembalikan saat guest pertama kali add item
```

#### `PUT /api/cart/items/{itemId}`
```json
// Request
{ "sizeId": "size-20-bf", "colorText": "Biru", "theme": "Frozen", "message": "Selamat", "quantity": 1 }

// Response 200
{ "ok": true, "item": { ...updatedItem } }
```

#### `PATCH /api/cart/items/{itemId}/quantity`
```json
{ "quantity": 3 }
```

#### `DELETE /api/cart/items/{itemId}` → `{ "ok": true }`
#### `DELETE /api/cart` → `{ "ok": true }`

---

### 3.4 Orders

#### `POST /api/orders`
```json
// Request
{
  "customerName": "Budi Santoso",
  "phone": "081234567890",
  "pickupMethod": "pickup" | "delivery",
  "pickupDate": "2026-06-05",     // required jika pickup
  "pickupTime": "14:00",          // required jika pickup
  "address": "",                   // required jika delivery, max 220
  "addressNote": "",              // optional, max 120
  "paymentMethod": "cash" | "qris"
}

// Response 201
{
  "ok": true,
  "order": {
    "id": "ord_q1r2s3t4",
    "orderNumber": "HNK-20260601-143213-348",
    "userId": "usr_xxx" | null,
    "customerName": "...",
    "customerPhone": "...",
    "fulfillmentMethod": "pickup" | "delivery",
    "pickupDate": "2026-06-05",
    "pickupTime": "14:00:00",
    "deliveryAddress": null,
    "addressNote": "",
    "paymentMethod": "qris",
    "paymentStatus": "pending" | "cod",
    "status": "menunggu konfirmasi",
    "items": [ ...orderItems ],
    "totalPrice": 170000,
    "createdAt": "2026-06-01T14:32:13"
  }
}
```

#### `GET /api/orders` (Auth Required)
```json
{ "ok": true, "orders": [ ...orderObjects ] }
```

#### `GET /api/orders/{orderId}`
```json
{ "ok": true, "order": { ...orderObject } }
```

---

### 3.5 Payment

#### `POST /api/payments/qris`
```json
// Request
{ "orderId": "ord_q1r2s3t4" }

// Response 201 (QR baru) atau 200 (reuse QR valid)
{
  "ok": true,
  "payment": {
    "orderId": "ord_q1r2s3t4",
    "orderNumber": "HNK-20260601-143213-348",
    "amount": 170000,
    "qrString": "00020101021226620014COM.GO-JEK...",
    "qrImageUrl": "https://api.sandbox.midtrans.com/v2/qris/.../qr-code",
    "expiresAt": "2026-06-01T12:41:08+00:00",
    "status": "pending"
  }
}
```

#### `GET /api/payments/qris/status?orderId={orderId}`
```json
{
  "ok": true,
  "status": "pending" | "paid" | "expired" | "failed",
  "order": { ...orderObject }
}
```

#### `POST /api/payments/webhook` — dari Midtrans, selalu return 200

---

### 3.6 Store

#### `GET /api/store/profile`
```json
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

## 4. apiService.js — Base Fetch Wrapper

```js
const API_BASE = import.meta.env.VITE_API_URL || 'http://localhost:8080/api'

let authToken = localStorage.getItem('hanaka_auth_token') || null
let sessionToken = localStorage.getItem('hanaka_session_token') || null

// Headers otomatis di semua request:
// - Content-Type: application/json
// - Authorization: Bearer <token>  (jika ada)
// - X-Session-Token: <token>       (jika ada)
// - ngrok-skip-browser-warning: true  (bypass ngrok interstitial)
```

---

## 5. Error Handling Pattern

```js
try {
  const data = await api.post('/auth/login', { email, password })
} catch (err) {
  if (err.status === 401) {
    setFormError('Email atau password belum sesuai.')
  } else if (err.status === 400 && err.errors) {
    setFieldErrors(err.errors)  // { fieldName: "Pesan error" }
  } else {
    setFormError('Terjadi kesalahan. Silakan coba lagi.')
  }
}
```

---

## 6. CORS

Backend mengizinkan request dari `http://localhost:5173` (Vite dev server).

**Penting**: Sumber kebenaran CORS ada di `src/Application/ResponseEmitter/ResponseEmitter.php`
(bukan `CorsMiddleware`) — dieksekusi terakhir dan override semua header.

Header yang diizinkan:
```
Access-Control-Allow-Headers: X-Requested-With, Content-Type, Accept, Origin,
                               Authorization, X-Session-Token, ngrok-skip-browser-warning
```

Jika kena CORS error:
1. Pastikan backend jalan dan `CORS_ALLOWED_ORIGIN=http://localhost:5173` di `.env`
2. Restart backend setelah ubah `.env`
3. `ngrok-skip-browser-warning` sudah diizinkan — tidak perlu tindakan tambahan

---

## 7. Env Frontend

```env
# hanaka-project/.env
VITE_API_URL=http://localhost:8080/api
```

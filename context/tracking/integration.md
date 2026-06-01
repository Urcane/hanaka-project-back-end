# Frontend ↔ Backend Integration Status

> Terakhir update: 2026-06-01

---

## Status Keseluruhan: ✅ FULLY INTEGRATED

Frontend dan backend sudah terhubung penuh. Tidak ada lagi localStorage untuk data utama.

---

## Status per Fitur

| Fitur | Status | Catatan |
|---|---|---|
| Products API | ✅ | Fetch dari `GET /api/products` |
| Auth (register/login/logout/me) | ✅ | JWT di localStorage |
| Cart CRUD | ✅ | User + guest via X-Session-Token |
| Cart merge | ✅ | Otomatis saat login/register |
| Order create | ✅ | `POST /api/orders` |
| Order list (history) | ✅ | `GET /api/orders` (auth required) |
| QRIS payment | ✅ | Midtrans Core API — real QR |
| Payment status polling | ✅ | `GET /api/payments/qris/status` tiap 5 detik |
| Payment webhook | ✅ | `POST /api/payments/webhook` dari Midtrans |
| Admin dashboard | ✅ | Full CRUD produk, manage orders |

---

## Frontend Services (../hanaka-project/src/services/)

| File | Endpoint yang dipakai |
|---|---|
| `apiService.js` | Base fetch wrapper — JWT + session token otomatis di semua request |
| `authApi.js` | `/auth/register`, `/auth/login`, `/auth/logout`, `/auth/me` |
| `productsApi.js` | `/products`, `/products/:id` |
| `cartApi.js` | `/cart`, `/cart/items`, `/cart/items/:id`, `/cart/items/:id/quantity` |
| `ordersApi.js` | `/orders`, `/orders/:id`, `/orders/:id/pay` |
| `paymentApi.js` | `/payments/qris`, `/payments/qris/status` |
| `adminApi.js` | `/admin/*` endpoints |
| `qrisService.js` | **Bukan API** — render EMV qrString ke PNG (npm `qrcode`) |

---

## Dev Setup

```bash
# Backend (port 8080)
cd hanaka-project-back-end
composer start          # php -S localhost:8080 -t public

# Database (sekali saja)
php database/migrate.php --seed

# Frontend (port 5173)
cd hanaka-project
npm run dev
```

---

## Env Frontend (hanaka-project/.env)

```env
VITE_API_URL=http://localhost:8080/api
```

## Env Backend (.env)

```env
CORS_ALLOWED_ORIGIN=http://localhost:5173
MIDTRANS_SERVER_KEY=Mid-server-xxxx
MIDTRANS_IS_PRODUCTION=false
MIDTRANS_QRIS_ACQUIRER=gopay
```

---

## CORS

**Sumber kebenaran CORS ada di `ResponseEmitter.php`** (bukan `CorsMiddleware`).
`ResponseEmitter` dieksekusi paling akhir dan **override semua header** dari middleware.

File: `src/Application/ResponseEmitter/ResponseEmitter.php`

Header yang diizinkan:
```
Access-Control-Allow-Headers: X-Requested-With, Content-Type, Accept, Origin,
                               Authorization, X-Session-Token, ngrok-skip-browser-warning
```

---

## Testing Payment (Lokal)

1. Jalankan backend + ngrok: `ngrok http 8080`
2. Set Notification URL di [dashboard.sandbox.midtrans.com](https://dashboard.sandbox.midtrans.com):
   ```
   https://xxxx.ngrok-free.app/api/payments/webhook
   ```
3. Buat order QRIS → scan QR atau gunakan [simulator.sandbox.midtrans.com](https://simulator.sandbox.midtrans.com)
4. Frontend polling auto-detect `paid` dalam ≤5 detik → redirect ke `/orders`

> ngrok URL berubah setiap restart (free plan). Update di Midtrans dashboard setiap kali restart.

---

## Yang Sudah Tidak Dipakai

| Yang dihapus / tidak dipakai | Keterangan |
|---|---|
| `storageService.js` | Sudah tidak dipakai untuk data utama |
| `localStorage` untuk users/cart/orders | Diganti API |
| `authModel.buildAccount()` | Diganti `POST /api/auth/register` |
| `orderModel.createOrder()` | Diganti `POST /api/orders` |
| QRIS simulasi string lokal | Diganti Midtrans real QR |

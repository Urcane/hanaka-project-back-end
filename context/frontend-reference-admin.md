# Hanaka Cake — Admin API Reference (Frontend Integration)

> Dokumentasi lengkap API admin untuk integrasi frontend admin dashboard.
> File ini melengkapi `frontend-reference.md` yang khusus customer-facing API.

---

## 1. Authentication (Role-Based)

### Login tetap menggunakan endpoint yang sama

```
POST /api/auth/login
```

**Request:**
```json
{
  "email": "admin@hanakacake.com",
  "password": "Admin12345"
}
```

**Response (berhasil):**
```json
{
  "ok": true,
  "user": {
    "id": "usr_admin001",
    "fullName": "Admin Hanaka",
    "email": "admin@hanakacake.com",
    "phone": "081299998888",
    "role": "admin",
    "createdAt": "2026-05-20T10:00:00.000Z"
  },
  "token": "eyJhbGciOiJIUzI1NiIs..."
}
```

### Perbedaan response untuk customer vs admin

| Field | Customer | Admin |
|---|---|---|
| `user.role` | `"customer"` | `"admin"` |

**Catatan penting:**
- Gunakan `user.role` dari response login/me untuk menentukan redirect
- Customer → redirect ke `/` (halaman utama)
- Admin → redirect ke `/admin/dashboard`
- Token disimpan sama seperti customer (localStorage/header)

### GET /api/auth/me — sekarang return role

```json
{
  "ok": true,
  "user": {
    "id": "usr_admin001",
    "fullName": "Admin Hanaka",
    "email": "admin@hanakacake.com",
    "phone": "081299998888",
    "role": "admin",
    "createdAt": "2026-05-20T10:00:00.000Z"
  }
}
```

---

## 2. Admin Authorization

Semua endpoint admin memerlukan:
- Header: `Authorization: Bearer <token>`
- Token harus milik user dengan `role: "admin"`

**Error responses:**
```json
// 401 — Token tidak ada / expired
{ "ok": false, "error": "Token tidak valid atau sudah expired." }

// 403 — Bukan admin
{ "ok": false, "error": "Akses ditolak. Anda bukan admin." }
```

---

## 3. Dashboard

### GET /api/admin/dashboard

Mengambil statistik ringkasan untuk admin dashboard.

**Response:**
```json
{
  "ok": true,
  "dashboard": {
    "totalOrders": 150,
    "pendingOrders": 12,
    "processingOrders": 8,
    "completedOrders": 120,
    "cancelledOrders": 10,
    "totalCustomers": 45,
    "totalProducts": 5,
    "todayRevenue": 1500000,
    "totalRevenue": 25000000
  }
}
```

| Field | Tipe | Keterangan |
|---|---|---|
| `totalOrders` | int | Total semua order |
| `pendingOrders` | int | Order status "menunggu konfirmasi" |
| `processingOrders` | int | Order status "diproses" |
| `completedOrders` | int | Order status "selesai" |
| `cancelledOrders` | int | Order status "dibatalkan" |
| `totalCustomers` | int | Total customer terdaftar (bukan admin) |
| `totalProducts` | int | Total produk di katalog |
| `todayRevenue` | int | Pendapatan hari ini (rupiah) |
| `totalRevenue` | int | Total pendapatan keseluruhan (rupiah) |

---

## 4. Order Management

### GET /api/admin/orders

List semua order dengan filter dan pagination.

**Query parameters:**

| Param | Tipe | Wajib | Default | Keterangan |
|---|---|---|---|---|
| `status` | string | ❌ | — | Filter status: `menunggu konfirmasi`, `diproses`, `siap diambil`, `diantar`, `selesai`, `dibatalkan` |
| `paymentStatus` | string | ❌ | — | Filter: `pending`, `paid`, `cod` |
| `limit` | int | ❌ | 50 | Max per page (max 100) |
| `offset` | int | ❌ | 0 | Skip N records |

**Request contoh:**
```
GET /api/admin/orders?status=diproses&limit=20&offset=0
```

**Response:**
```json
{
  "ok": true,
  "orders": [
    {
      "id": "ord_a1b2c3d4",
      "orderNumber": "HNK-20260520-143022-487",
      "userId": "usr_12345678",
      "customerName": "Budi Santoso",
      "customerPhone": "081234567890",
      "fulfillmentMethod": "pickup",
      "pickupDate": "2026-05-22",
      "pickupTime": "10:00",
      "deliveryAddress": null,
      "addressNote": "",
      "paymentMethod": "qris",
      "paymentStatus": "paid",
      "status": "diproses",
      "items": [
        {
          "id": "oi_11223344",
          "productId": "black-forest",
          "productName": "Black Forest Cake",
          "sizeId": "bf-18",
          "sizeLabel": "18 cm",
          "colorText": "Coklat tua",
          "theme": "Ulang tahun",
          "message": "Happy Birthday!",
          "quantity": 1,
          "unitPrice": 170000,
          "totalPrice": 170000
        }
      ],
      "totalPrice": 170000,
      "createdAt": "2026-05-20T14:30:22.000Z"
    }
  ],
  "total": 45,
  "limit": 20,
  "offset": 0
}
```

### GET /api/admin/orders/{orderId}

Detail satu order.

**Response:** Sama seperti item dalam array `orders` di atas, dibungkus dalam:
```json
{ "ok": true, "order": { ... } }
```

### PATCH /api/admin/orders/{orderId}/status

Update status order.

**Request:**
```json
{
  "status": "diproses"
}
```

**Status yang valid:**

| Status | Keterangan |
|---|---|
| `menunggu konfirmasi` | Order baru masuk |
| `diproses` | Sedang dibuat |
| `siap diambil` | Kue sudah siap (pickup) |
| `diantar` | Sedang diantar (delivery) |
| `selesai` | Order complete |
| `dibatalkan` | Order dibatalkan |

**Response:**
```json
{
  "ok": true,
  "order": { ... }
}
```

**Error (status tidak valid):**
```json
{
  "ok": false,
  "error": "Status tidak valid. Gunakan: menunggu konfirmasi, diproses, siap diambil, diantar, selesai, dibatalkan."
}
```

### PATCH /api/admin/orders/{orderId}/payment-status

Update status pembayaran order.

**Request:**
```json
{
  "paymentStatus": "paid"
}
```

**Status pembayaran yang valid:** `pending`, `paid`, `cod`

**Response:**
```json
{
  "ok": true,
  "order": { ... }
}
```

---

## 5. Customer Management

### GET /api/admin/customers

List semua customer (role = customer).

**Response:**
```json
{
  "ok": true,
  "customers": [
    {
      "id": "usr_12345678",
      "fullName": "Budi Santoso",
      "email": "budi@example.com",
      "phone": "081234567890",
      "role": "customer",
      "createdAt": "2026-05-15T08:30:00.000Z"
    }
  ]
}
```

---

## 6. Product Management

### POST /api/admin/products

Buat produk baru (opsional sekaligus dengan sizes).

**Request:**
```json
{
  "id": "tiramisu-cake",
  "name": "Tiramisu Cake",
  "shortDescription": "Cake tiramisu premium",
  "longDescription": "Kue tiramisu dengan bahan premium dan rasa autentik Italia.",
  "featured": false,
  "coverGradient": "linear-gradient(135deg, #8B6914, #D4A843)",
  "coverImage": null,
  "maxMessageLength": 60,
  "sizes": [
    { "label": "16 cm", "fullLabel": "16 cm (4-6 porsi)", "price": 150000 },
    { "label": "18 cm", "fullLabel": "18 cm (8-10 porsi)", "price": 200000 }
  ]
}
```

| Field | Tipe | Wajib | Keterangan |
|---|---|---|---|
| `id` | string | ✅ | ID unik produk (slug) |
| `name` | string | ✅ | Nama produk |
| `shortDescription` | string | ✅ | Deskripsi pendek |
| `longDescription` | string | ❌ | Deskripsi panjang |
| `featured` | bool | ❌ | Tampil di best seller (default: false) |
| `coverGradient` | string | ❌ | CSS gradient untuk cover |
| `coverImage` | string | ❌ | URL gambar cover |
| `maxMessageLength` | int | ❌ | Max karakter pesan (default: 60) |
| `sizes` | array | ❌ | Array ukuran (bisa ditambah terpisah) |

**Response (201):**
```json
{
  "ok": true,
  "product": {
    "id": "tiramisu-cake",
    "name": "Tiramisu Cake",
    "shortDescription": "Cake tiramisu premium",
    "longDescription": "Kue tiramisu dengan bahan premium...",
    "featured": false,
    "coverGradient": "linear-gradient(135deg, #8B6914, #D4A843)",
    "coverImage": null,
    "maxMessageLength": 60,
    "sizes": [
      { "id": "sz_a1b2c3d4", "label": "16 cm", "fullLabel": "16 cm (4-6 porsi)", "price": 150000 },
      { "id": "sz_e5f6g7h8", "label": "18 cm", "fullLabel": "18 cm (8-10 porsi)", "price": 200000 }
    ],
    "startingPrice": 150000
  }
}
```

### PUT /api/admin/products/{productId}

Update produk (partial update — hanya field yang dikirim yang berubah).

**Request (contoh):**
```json
{
  "name": "Tiramisu Cake Premium",
  "featured": true
}
```

**Response:**
```json
{
  "ok": true,
  "product": { ... }
}
```

### DELETE /api/admin/products/{productId}

Hapus produk beserta semua ukurannya.

**Response:**
```json
{
  "ok": true,
  "message": "Produk berhasil dihapus."
}
```

---

## 7. Product Size Management

### POST /api/admin/products/{productId}/sizes

Tambah ukuran baru ke produk.

**Request:**
```json
{
  "label": "24 cm",
  "fullLabel": "24 cm (12-15 porsi)",
  "price": 320000
}
```

| Field | Tipe | Wajib | Keterangan |
|---|---|---|---|
| `label` | string | ✅ | Label pendek (misal "16 cm") |
| `fullLabel` | string | ✅ | Label lengkap (misal "16 cm (4-6 porsi)") |
| `price` | int | ✅ | Harga dalam rupiah |

**Response (201):**
```json
{
  "ok": true,
  "size": {
    "id": "sz_newid123",
    "label": "24 cm",
    "fullLabel": "24 cm (12-15 porsi)",
    "price": 320000
  }
}
```

### PUT /api/admin/products/{productId}/sizes/{sizeId}

Update ukuran (partial update).

**Request:**
```json
{
  "price": 350000
}
```

**Response:**
```json
{
  "ok": true,
  "size": { "id": "sz_...", "label": "24 cm", "fullLabel": "24 cm (12-15 porsi)", "price": 350000 }
}
```

### DELETE /api/admin/products/{productId}/sizes/{sizeId}

Hapus ukuran dari produk.

**Response:**
```json
{
  "ok": true,
  "message": "Ukuran berhasil dihapus."
}
```

---

## 8. Ringkasan Semua Admin Endpoints

| Method | Path | Deskripsi |
|---|---|---|
| `GET` | `/api/admin/dashboard` | Dashboard statistik |
| `GET` | `/api/admin/orders` | List semua order (filter + pagination) |
| `GET` | `/api/admin/orders/{orderId}` | Detail order |
| `PATCH` | `/api/admin/orders/{orderId}/status` | Update status order |
| `PATCH` | `/api/admin/orders/{orderId}/payment-status` | Update status pembayaran |
| `GET` | `/api/admin/customers` | List semua customer |
| `POST` | `/api/admin/products` | Buat produk baru |
| `PUT` | `/api/admin/products/{productId}` | Update produk |
| `DELETE` | `/api/admin/products/{productId}` | Hapus produk |
| `POST` | `/api/admin/products/{productId}/sizes` | Tambah ukuran |
| `PUT` | `/api/admin/products/{productId}/sizes/{sizeId}` | Update ukuran |
| `DELETE` | `/api/admin/products/{productId}/sizes/{sizeId}` | Hapus ukuran |

Semua endpoint admin memerlukan `Authorization: Bearer <admin_token>` di header.

---

## 9. Frontend Mapping — Admin Context vs API

### Login Flow

```
Frontend login form → POST /api/auth/login
                    → response.user.role === 'admin' → redirect /admin/dashboard
                    → response.user.role === 'customer' → redirect /
```

### API Service Setup

```javascript
// apiService.js — Admin methods (tambahkan ke existing apiService)

const API_BASE = 'http://localhost:8080/api';

function authHeaders() {
  const token = localStorage.getItem('hanaka_token');
  return {
    'Content-Type': 'application/json',
    'Authorization': `Bearer ${token}`,
  };
}

// Dashboard
export async function getDashboard() {
  const res = await fetch(`${API_BASE}/admin/dashboard`, { headers: authHeaders() });
  return res.json();
}

// Orders
export async function getAdminOrders(params = {}) {
  const query = new URLSearchParams(params).toString();
  const res = await fetch(`${API_BASE}/admin/orders?${query}`, { headers: authHeaders() });
  return res.json();
}

export async function getAdminOrder(orderId) {
  const res = await fetch(`${API_BASE}/admin/orders/${orderId}`, { headers: authHeaders() });
  return res.json();
}

export async function updateOrderStatus(orderId, status) {
  const res = await fetch(`${API_BASE}/admin/orders/${orderId}/status`, {
    method: 'PATCH',
    headers: authHeaders(),
    body: JSON.stringify({ status }),
  });
  return res.json();
}

export async function updatePaymentStatus(orderId, paymentStatus) {
  const res = await fetch(`${API_BASE}/admin/orders/${orderId}/payment-status`, {
    method: 'PATCH',
    headers: authHeaders(),
    body: JSON.stringify({ paymentStatus }),
  });
  return res.json();
}

// Customers
export async function getCustomers() {
  const res = await fetch(`${API_BASE}/admin/customers`, { headers: authHeaders() });
  return res.json();
}

// Products CRUD
export async function createProduct(data) {
  const res = await fetch(`${API_BASE}/admin/products`, {
    method: 'POST',
    headers: authHeaders(),
    body: JSON.stringify(data),
  });
  return res.json();
}

export async function updateProduct(productId, data) {
  const res = await fetch(`${API_BASE}/admin/products/${productId}`, {
    method: 'PUT',
    headers: authHeaders(),
    body: JSON.stringify(data),
  });
  return res.json();
}

export async function deleteProduct(productId) {
  const res = await fetch(`${API_BASE}/admin/products/${productId}`, {
    method: 'DELETE',
    headers: authHeaders(),
  });
  return res.json();
}

// Sizes CRUD
export async function createProductSize(productId, data) {
  const res = await fetch(`${API_BASE}/admin/products/${productId}/sizes`, {
    method: 'POST',
    headers: authHeaders(),
    body: JSON.stringify(data),
  });
  return res.json();
}

export async function updateProductSize(productId, sizeId, data) {
  const res = await fetch(`${API_BASE}/admin/products/${productId}/sizes/${sizeId}`, {
    method: 'PUT',
    headers: authHeaders(),
    body: JSON.stringify(data),
  });
  return res.json();
}

export async function deleteProductSize(productId, sizeId) {
  const res = await fetch(`${API_BASE}/admin/products/${productId}/sizes/${sizeId}`, {
    method: 'DELETE',
    headers: authHeaders(),
  });
  return res.json();
}
```

---

## 10. Admin Default Account

| Field | Value |
|---|---|
| Email | `admin@hanakacake.com` |
| Password | `Admin12345` |
| Role | `admin` |

**Catatan:** Password ini hanya untuk development. Ubah di production.

---

## 11. Order Status Flow

```
menunggu konfirmasi → diproses → siap diambil → selesai
                                → diantar → selesai
                   → dibatalkan (bisa dari status manapun)
```

---

## 12. Migration Guide

Untuk mengaktifkan fitur admin, jalankan migration tambahan:

```sql
-- File: database/migrations/008_add_user_role.sql
ALTER TABLE users ADD COLUMN role ENUM('customer', 'admin') NOT NULL DEFAULT 'customer' AFTER password_hash;
ALTER TABLE users ADD INDEX idx_users_role (role);
```

Kemudian seed admin user:
```sql
-- File: database/seeds/admin_seeder.sql
-- Password: Admin12345
INSERT INTO users (id, full_name, email, phone, password_hash, role) VALUES
('usr_admin001', 'Admin Hanaka', 'admin@hanakacake.com', '081299998888',
 '$2y$12$yEntbgNY8IntM5.G.W.Oiu09.kHwNgYi7ecjkXURhCMrxvvi.R3.G', 'admin')
ON DUPLICATE KEY UPDATE role = 'admin';
```

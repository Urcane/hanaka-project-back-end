# Backend — Orders API

> Endpoint untuk membuat dan mengelola order.

---

## Endpoints

### POST `/api/orders`

Buat order baru dari cart items.

**Headers:** `Authorization: Bearer <token>` (opsional — guest allowed)

**Request:**
```json
{
  "customerName": "Budi Santoso",
  "phone": "081234567890",
  "pickupMethod": "pickup",
  "pickupDate": "2026-05-25",
  "pickupTime": "14:00",
  "address": "",
  "addressNote": "",
  "paymentMethod": "qris"
}
```

**Validation:**
| Field | Rules |
|---|---|
| customerName | required, minLength(3) |
| phone | required, valid Indonesian phone |
| pickupMethod | required, oneOf(['pickup', 'delivery']) |
| pickupDate | required if pickup |
| pickupTime | required if pickup |
| address | required if delivery, maxLength(220) |
| addressNote | maxLength(120) |
| paymentMethod | required, oneOf(['cash', 'qris']) |

**Logic:**
1. Validate input
2. Get cart items (must not be empty)
3. Verify all items still valid (product exists, size exists, prices correct)
4. Generate order ID & order number
5. Create order record
6. Copy cart items → order_items (snapshot)
7. Clear cart
8. Return order

**Success Response (201):**
```json
{
  "ok": true,
  "order": {
    "id": "ord_q1r2s3t4",
    "orderNumber": "HNK-20260522-143052-847",
    "userId": "usr_a1b2c3d4",
    "customerName": "Budi Santoso",
    "customerPhone": "081234567890",
    "fulfillmentMethod": "pickup",
    "pickupDate": "2026-05-25",
    "pickupTime": "14:00",
    "deliveryAddress": "Ambil di toko",
    "paymentMethod": "qris",
    "paymentStatus": "pending",
    "status": "menunggu konfirmasi",
    "items": [...],
    "totalPrice": 340000,
    "createdAt": "2026-05-22T07:30:00Z"
  }
}
```

**Error (400):**
```json
{
  "ok": false,
  "error": "Keranjang masih kosong."
}
```

---

### GET `/api/orders`

List orders untuk user yang login.

**Headers:** `Authorization: Bearer <token>` (required)

**Response (200):**
```json
{
  "ok": true,
  "orders": [
    {
      "id": "ord_q1r2s3t4",
      "orderNumber": "HNK-20260522-143052-847",
      "customerName": "Budi Santoso",
      "fulfillmentMethod": "pickup",
      "paymentMethod": "qris",
      "paymentStatus": "paid",
      "status": "diproses",
      "items": [
        {
          "id": "oi_abc123",
          "productName": "Black Forest Cake",
          "sizeLabel": "18",
          "colorText": "Merah Muda",
          "theme": "Roblox",
          "message": "Happy Birthday",
          "quantity": 2,
          "unitPrice": 170000,
          "totalPrice": 340000
        }
      ],
      "totalPrice": 340000,
      "createdAt": "2026-05-22T07:30:00Z"
    }
  ]
}
```

**Sorting:** `ORDER BY created_at DESC`

---

### GET `/api/orders/:orderId`

Detail satu order.

**Headers:** `Authorization: Bearer <token>` (opsional)

**Access Control:**
- User login → hanya bisa akses order miliknya (`user_id = currentUser.id`)
- Guest → hanya bisa akses order guest (`user_id IS NULL`) dengan session match

**Success Response (200):**
```json
{
  "ok": true,
  "order": { ... }
}
```

**Error (404):**
```json
{
  "ok": false,
  "error": "Order tidak ditemukan untuk sesi ini."
}
```

---

### PATCH `/api/orders/:orderId/pay`

Mark order sebagai sudah dibayar (simulasi — nanti diganti webhook).

**Headers:** `Authorization: Bearer <token>` (opsional)

**Logic:**
1. Find order by ID
2. Verify access (user owns order OR guest session match)
3. Verify `paymentMethod === 'qris'` dan `paymentStatus === 'pending'`
4. Update: `paymentStatus = 'paid'`, `status = 'diproses'`
5. Return updated order

**Success Response (200):**
```json
{
  "ok": true,
  "order": {
    ...
    "paymentStatus": "paid",
    "status": "diproses"
  }
}
```

---

## Order Number Generation

Format: `HNK-YYYYMMDD-HHMMSS-XXX`

```php
function generateOrderNumber(): string {
    $now = new \DateTime();
    $datePart = $now->format('Ymd');
    $timePart = $now->format('His');
    $randomPart = str_pad((string) random_int(100, 999), 3, '0', STR_PAD_LEFT);
    return "HNK-{$datePart}-{$timePart}-{$randomPart}";
}
```

---

## Order Status Transitions

| From | To | Trigger |
|---|---|---|
| `menunggu konfirmasi` | `diproses` | Payment confirmed |
| `diproses` | `siap diambil` | Admin: kue selesai (pickup) |
| `diproses` | `diantar` | Admin: kurir berangkat (delivery) |
| `siap diambil` | `selesai` | Admin: customer sudah ambil |
| `diantar` | `selesai` | Admin: customer terima |
| Any | `dibatalkan` | Admin/customer cancel |

---

## Guest Order Handling

- Guest order memiliki `user_id = NULL`
- Akses via session token (cookie/header)
- Session token valid selama X jam (configurable)
- Guest tidak bisa list orders (hanya akses langsung via orderId)

---

## Data Snapshot di order_items

Order items menyimpan **snapshot** data saat order dibuat:
- `product_name` — agar tidak berubah jika produk diupdate
- `size_label` — agar tidak berubah jika size diubah
- `unit_price` — agar harga tetap sesuai saat checkout

Ini memastikan order history selalu akurat meskipun data produk berubah.

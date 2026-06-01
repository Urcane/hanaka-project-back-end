# Frontend — Payment (QRIS)

> Alur pembayaran QRIS: QR generation, mark as paid, order status.

---

## Overview

- Saat ini QRIS adalah **simulasi** — bukan real payment gateway
- QR code di-generate dari string payload menggunakan library `qrcode`
- User menekan "Place my order" untuk menandai pembayaran selesai (manual)
- Nanti akan diganti integrasi **Midtrans** atau **Xendit**

---

## Payment Flow

```
CheckoutPage → placeOrder (paymentMethod: 'qris')
  ↓
Order dibuat dengan:
  - paymentStatus: 'pending'
  - status: 'menunggu konfirmasi'
  ↓
navigate('/payment/:orderId')
  ↓
PaymentQrisPage
  ↓
getOrderById(orderId) → cek akses
  ↓
generateQrisDataUrl(order) → QR code as data URL
  ↓
Tampilkan QR image
  ↓
User klik "Place my order"
  ↓
markCurrentUserOrderPaid(orderId)
  ↓
Order updated:
  - paymentStatus: 'paid'
  - status: 'diproses'
  ↓
if (login) → navigate('/orders')
if (guest) → navigate('/')
```

---

## QR Payload Format

```
HANAKA-CAKE|ORDER:HNK-20260522-143000-789|TOTAL:340000|NAME:Budi Santoso
```

Format: `HANAKA-CAKE|ORDER:{orderNumber}|TOTAL:{totalPrice}|NAME:{customerName}`

---

## QRIS Service (`src/services/qrisService.js`)

```js
import QRCode from 'qrcode'

export function buildQrisPayload(order) {
  return [
    'HANAKA-CAKE',
    `ORDER:${order.orderNumber}`,
    `TOTAL:${order.totalPrice}`,
    `NAME:${order.customerName}`,
  ].join('|')
}

export async function generateQrisDataUrl(order) {
  const payload = buildQrisPayload(order)
  return QRCode.toDataURL(payload, {
    width: 320,
    margin: 1,
    color: { dark: '#2a1d15', light: '#fff9f2' },
  })
}
```

---

## Order Object (setelah create)

```js
{
  id: "ord_x1y2z3w4",
  orderNumber: "HNK-20260522-143000-789",
  userId: "usr_abc123" | null,       // null untuk guest
  customerName: "Budi Santoso",
  customerPhone: "081234567890",
  fulfillmentMethod: "pickup" | "delivery",
  deliveryAddress: "Jl. ...",
  paymentMethod: "qris",
  paymentStatus: "pending" → "paid",
  status: "menunggu konfirmasi" → "diproses",
  notes: "...",
  items: [...cartItems],
  totalPrice: 340000,
  createdAt: "2026-05-22T07:30:00.000Z",
}
```

---

## Order Number Format

`HNK-YYYYMMDD-HHMMSS-XXX`

- `HNK` — prefix Hanaka
- `YYYYMMDD` — tanggal
- `HHMMSS` — jam:menit:detik
- `XXX` — random 3 digit (100-999)

Contoh: `HNK-20260522-143052-847`

---

## Access Control

- User yang login hanya bisa lihat order miliknya (`order.userId === currentUser.id`)
- Guest hanya bisa lihat order guest (`order.userId === null`)
- Jika order tidak ditemukan → tampil pesan error

---

## PaymentQrisPage States

| State | Tampilan |
|---|---|
| Loading QR | "Menyiapkan QR pembayaran..." |
| QR Error | "QR gagal dibuat. Silakan refresh halaman." |
| QR Ready | Tampilkan gambar QR |
| Order not found | "Order tidak ditemukan" + link kembali |
| Non-QRIS order | Redirect ke `/orders` |

---

## File Terkait

- `src/pages/PaymentQrisPage.jsx` — Payment page
- `src/services/qrisService.js` — QR code generation
- `src/models/orderModel.js` — `createOrder`, `markOrderAsPaid`
- `src/context/AppContext.jsx` — `placeOrder`, `getOrderById`, `markCurrentUserOrderPaid`
- `src/utils/id.js` — `createOrderNumber()`

---

## Rencana Migrasi ke Real Payment

| Sekarang | Target |
|---|---|
| QR dari string lokal | `POST /api/payments/qris` → response QR dari gateway |
| Manual "Place my order" button | Webhook callback dari Midtrans/Xendit |
| `paymentStatus` update manual | Auto-update via webhook |
| Tidak ada expiry | QR punya expiry time (e.g. 15 menit) |
| Tidak ada amount validation | Gateway memvalidasi nominal |

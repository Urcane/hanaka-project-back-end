# Frontend — Checkout

> Alur checkout: form data, validasi, payment selection, place order.

---

## Overview

- Checkout bisa dilakukan **tanpa login** (guest checkout)
- Dua mode fulfillment: **Pickup** (ambil di toko) atau **Delivery** (diantar)
- Mode ditentukan dari query param `?mode=pickup|delivery` (dari CartPage)
- Dua metode pembayaran: **Cash** (COD) atau **QRIS**

---

## Checkout Flow

```
CartPage → klik "Bayar"
  ↓
navigate('/checkout?mode=pickup|delivery')
  ↓
CheckoutPage renders form (pre-fill dari currentUser jika login)
  ↓ fill form + pilih payment method
handleSubmit()
  ↓
validateCheckoutInput(formValues)             ← checkoutModel.js
  ↓ jika valid
buildCheckoutPayload(formValues)             ← checkoutModel.js
  ↓
placeOrder(checkoutPayload) di context       ← AppContext
  ↓
createOrder({user, items, checkout})         ← orderModel.js
  ↓
clearCart()
  ↓
if (qris) → navigate('/payment/:orderId')
if (cash + login) → navigate('/orders')
if (cash + guest) → show success inline
```

---

## Form Fields

### Selalu ditampilkan
| Field | Type | Pre-fill | Rules |
|---|---|---|---|
| `customerName` | text | `currentUser?.fullName` | required, minLength(3) |
| `phone` | tel | `currentUser?.phone` | required, phoneId |
| `paymentMethod` | button select | — | required, oneOf(['cash','qris']) |

### Mode Pickup
| Field | Type | Rules |
|---|---|---|
| `pickupDate` | date | required (conditional) |
| `pickupTime` | time | required (conditional) |

### Mode Delivery
| Field | Type | Rules |
|---|---|---|
| `address` | text | required (conditional), maxLength(220) |
| `addressNote` | text | maxLength(120) — opsional |

---

## Checkout Payload (setelah validasi)

```js
{
  customerName: "Budi Santoso",
  phone: "081234567890",
  pickupMethod: "pickup" | "delivery",
  pickupDate: "2026-05-25" | "",         // kosong jika delivery
  pickupTime: "14:00" | "",              // kosong jika delivery
  address: "Jl. ..." | "Ambil di toko", // "Ambil di toko" jika pickup
  addressNote: "rumah warna biru",
  paymentMethod: "cash" | "qris",
}
```

---

## Conditional Validation

```js
pickupDate: [
  when(
    (values) => values.pickupMethod === 'pickup',
    validators.required('Tanggal pengambilan wajib diisi.')
  ),
],
address: [
  when(
    (values) => values.pickupMethod === 'delivery',
    validators.required('Alamat detail wajib diisi.')
  ),
  validators.maxLength(220, 'Alamat maksimal 220 karakter.'),
],
```

---

## Payment Methods

| ID | Label | After Order |
|---|---|---|
| `cash` | CASH | Langsung "menunggu konfirmasi" |
| `qris` | QRIS | Navigate ke `/payment/:orderId` |

---

## CheckoutPage Layout

```
┌─────────────────────────────────────────────┐
│ [Pickup/Delivery title]                     │
│                                             │
│ ┌──── Form Column ────┐ ┌── Notes Panel ──┐│
│ │ Nama Pelanggan      │ │ NOTES           ││
│ │ Nomor Pelanggan     │ │ (info pickup/   ││
│ │ Tanggal Pengambilan │ │  delivery)      ││
│ │ Jam Pengambilan     │ │                 ││
│ │ [Continue button]   │ │ Payment:        ││
│ │                     │ │ [CASH] [QRIS]   ││
│ └─────────────────────┘ └─────────────────┘│
└─────────────────────────────────────────────┘
```

---

## Guest vs Logged-in Checkout

| Aspek | Guest | Logged-in |
|---|---|---|
| Form pre-fill | Kosong | Nama & phone dari user |
| Setelah order (cash) | Tampil success inline + link login | Redirect ke `/orders` |
| Setelah order (qris) | Navigate ke payment page | Navigate ke payment page |
| Order tersimpan | `userId: null` | `userId: user.id` |

---

## File Terkait

- `src/pages/CheckoutPage.jsx` — Checkout form & logic
- `src/models/checkoutModel.js` — `validateCheckoutInput`, `buildCheckoutPayload`, `PAYMENT_METHODS`, `PICKUP_METHODS`
- `src/models/orderModel.js` — `createOrder`
- `src/context/AppContext.jsx` — `placeOrder`

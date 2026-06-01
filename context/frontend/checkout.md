# Frontend — Checkout

> Alur checkout: form data, validasi, payment selection, place order.
> Terakhir update: 2026-06-01

---

## Status: ✅ Order ke Backend

`placeOrder` sudah call `POST /api/orders` ke backend — tidak ada lagi `createOrder` ke localStorage.

---

## Checkout Flow

```
CartPage → klik "Bayar"
  ↓ navigate('/checkout?mode=pickup|delivery')
  ↓
CheckoutPage renders form (pre-fill dari currentUser jika login)
  ↓ fill form + pilih payment method
handleSubmit()
  ↓ validateCheckoutInput(formValues)   ← client-side
  ↓ jika valid → build payload
  ↓ placeOrder(payload) di context
  ↓ POST /api/orders
    { customerName, phone, pickupMethod, pickupDate, pickupTime,
      address, addressNote, paymentMethod }
  ↓ Backend: ambil cart dari DB (user/session token) → buat order → clear cart
  ↓ response: { order }
  ↓
if (qris) → navigate('/payment/:orderId')
if (cash + login) → navigate('/orders')
if (cash + guest) → show success inline
```

---

## Form Fields

### Selalu ditampilkan
| Field | Pre-fill | Rules |
|---|---|---|
| `customerName` | `currentUser?.fullName` | required, minLength(3) |
| `phone` | `currentUser?.phone` | required, phoneId |
| `paymentMethod` | — | required, oneOf(['cash','qris']) |

### Mode Pickup
| Field | Rules |
|---|---|
| `pickupDate` | required (conditional — jika pickupMethod=pickup) |
| `pickupTime` | required (conditional) |

### Mode Delivery
| Field | Rules |
|---|---|
| `address` | required (conditional), maxLength(220) |
| `addressNote` | maxLength(120) — opsional |

---

## Payload ke Backend

```js
{
  customerName: "Budi Santoso",
  phone: "081234567890",          // strip spasi/dash sebelum kirim
  pickupMethod: "pickup" | "delivery",
  pickupDate: "2026-06-05",       // kosong string jika delivery
  pickupTime: "14:00",            // kosong string jika delivery
  address: "Jl. ...",             // kosong string jika pickup
  addressNote: "rumah warna biru",
  paymentMethod: "cash" | "qris",
}
```

Backend resolve cart identity dari JWT/session token header — tidak perlu kirim cart data.

---

## Payment Methods

| ID | Label | Setelah Order |
|---|---|---|
| `cash` | CASH | Status `cod`, langsung selesai |
| `qris` | QRIS | Status `pending`, navigate ke `/payment/:orderId` |

---

## Guest vs Logged-in

| Aspek | Guest | Logged-in |
|---|---|---|
| Form pre-fill | Kosong | Nama & phone dari currentUser |
| Cart identity | X-Session-Token | JWT (user_id) |
| Setelah order (cash) | Tampil success inline | Redirect ke `/orders` |
| Setelah order (qris) | Navigate ke `/payment/:orderId` | Navigate ke `/payment/:orderId` |

---

## Error Handling

```js
try {
  const result = await placeOrder(payload)
  if (payload.paymentMethod === 'qris') {
    navigate(`/payment/${result.order.id}`)
  }
} catch (err) {
  if (err.status === 400 && err.errors) {
    setErrors(err.errors)         // field-level errors dari backend
  } else {
    setSubmitError(err.message)   // general error
  }
}
```

---

## File Terkait

- `src/pages/CheckoutPage.jsx` — Checkout form & logic
- `src/models/checkoutModel.js` — `validateCheckoutInput`, `PAYMENT_METHODS`, `PICKUP_METHODS`
- `src/services/ordersApi.js` — `apiPlaceOrder`
- `src/context/AppContext.jsx` — `placeOrder`

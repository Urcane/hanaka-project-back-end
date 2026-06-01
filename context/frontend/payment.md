# Frontend — Payment (QRIS)

> Alur pembayaran QRIS via Midtrans: QR generation, polling, auto-redirect.
> Terakhir update: 2026-06-01

---

## Status: ✅ Real Midtrans Integration

QR bukan lagi simulasi string lokal — sekarang pakai **Midtrans Core API** (sandbox).

---

## Payment Flow

```
CheckoutPage → placeOrder (paymentMethod: 'qris')
  ↓ POST /api/orders
Order dibuat:
  - paymentStatus: 'pending'
  - status: 'menunggu konfirmasi'
  ↓
navigate('/payment/:orderId')
  ↓
PaymentQrisPage mount
  ↓
[Effect 1] getOrderById(orderId)
  → GET /api/orders/:id (pakai X-Session-Token untuk guest)
  ↓
[Effect 2] apiCreateQrisPayment(order.id)
  → POST /api/payments/qris
  → Backend charge ke Midtrans → dapat EMV qrString + expiresAt (ISO-8601 UTC)
  ↓
generateQrisDataUrl({ qrString })
  → npm qrcode encode EMV string → data:image/png;base64,...
  → <img src={qrImage} />
  ↓
[Effect 3] Polling tiap 5 detik
  → GET /api/payments/qris/status?orderId=...
  → Backend live check ke Midtrans
  ↓
[Effect 4] Countdown dari expiresAt
  → new Date(payment.expiresAt) → mm:ss setiap detik
  ↓
Customer scan QR → bayar
  ↓
Midtrans → POST /api/payments/webhook (backend)
  → payment_status = 'paid', status = 'diproses'
  ↓
Polling detect 'paid'
  ↓
setTimeout 1.4s → navigate('/orders') [login] atau navigate('/') [guest]
```

---

## PaymentQrisPage States

| State | Tampilan |
|---|---|
| Loading order | "Memuat order..." |
| Order not found | "Order tidak ditemukan" + link kembali |
| Non-QRIS order | Redirect ke `/orders` |
| QR preparing | "Menyiapkan QR pembayaran..." |
| QR error | "QR gagal dibuat. Silakan refresh halaman." |
| QR ready | Gambar QR + nominal + countdown + petunjuk scan |
| Checking status | Button "Mengecek..." (disabled) |
| Paid | "Pembayaran diterima! Mengalihkan..." → redirect 1.4s |
| Expired | "QR sudah kedaluwarsa. Silakan buat pesanan baru." |
| Failed | "Pembayaran gagal/dibatalkan. Silakan buat pesanan baru." |

---

## API Calls

### `apiCreateQrisPayment(orderId)` — `paymentApi.js`
```js
// POST /api/payments/qris
// Returns data.payment:
{
  orderId: "ord_xxx",
  orderNumber: "HNK-...",
  amount: 170000,
  qrString: "00020101021226620014COM.GO-JEK...",  // EMV QRIS — encode ke QR image
  qrImageUrl: "https://api.sandbox.midtrans.com/...", // hosted image (tidak dipakai FE)
  expiresAt: "2026-06-01T12:41:08+00:00",         // ISO-8601 UTC — parse dengan new Date()
  status: "pending"
}
```

Jika QR masih valid (belum expired), backend return 200 (reuse) tanpa charge ulang.

### `apiCheckQrisStatus(orderId)` — `paymentApi.js`
```js
// GET /api/payments/qris/status?orderId=xxx
// Returns:
{
  ok: true,
  status: "pending" | "paid" | "expired" | "failed",
  order: { ...orderObject }
}
```

---

## qrisService.js

Fungsi ini **bukan** call ke backend — dia encode EMV string dari Midtrans ke gambar PNG:

```js
import QRCode from 'qrcode'

export async function generateQrisDataUrl({ qrString }) {
  return QRCode.toDataURL(qrString, {
    width: 320,
    margin: 1,
    color: { dark: '#2a1d15', light: '#fff9f2' },
  })
}
```

Input: `qrString` EMV QRIS dari Midtrans (250+ chars, format ISO 18004).
Output: `data:image/png;base64,...` → langsung ke `<img src={...} />`.

---

## Countdown Timer

```js
const expiresMs = payment?.expiresAt
  ? new Date(payment.expiresAt).getTime() - now  // now = Date.now(), tick setiap 1 detik
  : null

// Tampil jika expiresMs > 0
// "Berlaku: 14:53"
```

`expiresAt` dikirim backend sebagai ISO-8601 dengan offset (`+00:00`) sehingga
`new Date()` di browser parse benar di timezone manapun.

---

## File Terkait

- `src/pages/PaymentQrisPage.jsx` — Payment page (state machine)
- `src/services/paymentApi.js` — `apiCreateQrisPayment`, `apiCheckQrisStatus`
- `src/services/qrisService.js` — `generateQrisDataUrl` (render EMV → PNG)
- `src/services/ordersApi.js` — `apiFetchOrderById`, `apiMarkOrderPaid`
- `src/utils/currency.js` — `formatRupiah` (tampil nominal)

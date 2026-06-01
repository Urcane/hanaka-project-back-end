# Backend — Payment API (QRIS)

> Endpoint untuk pembayaran QRIS via Midtrans Core API.

---

## Status: ✅ IMPLEMENTED (Midtrans Sandbox)

Provider: **Midtrans Core API** (bukan Snap/Midtrans.js).
QR di-render di frontend dari `qrString` EMV menggunakan npm `qrcode`.

---

## Environment Variables

```env
MIDTRANS_SERVER_KEY=Mid-server-xxxx     # wajib — dari dashboard Midtrans
MIDTRANS_IS_PRODUCTION=false            # true untuk production
MIDTRANS_QRIS_ACQUIRER=gopay           # gopay (default) atau airpay
```

> Client Key dan Merchant ID **tidak dipakai** — kita pakai Core API server-side,
> bukan Snap/Midtrans.js yang butuh client key di browser.

---

## Endpoints

### `POST /api/payments/qris`

Generate QRIS charge via Midtrans. Jika QR masih valid (belum expired), endpoint
mengembalikan data QR yang sudah ada (reuse) tanpa charge ulang ke Midtrans.

**Request:**
```json
{ "orderId": "ord_q1r2s3t4" }
```

**Validation:**
1. `orderId` tidak kosong
2. Order ditemukan (user/session ownership check)
3. `paymentMethod === 'qris'`
4. `paymentStatus !== 'paid'`

**Response 201 (QR baru dibuat):**
```json
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

**Response 200 (reuse QR yang masih valid)**

**Error 400:** `Order ini bukan pembayaran QRIS.`  
**Error 400:** `Order ini sudah dibayar.`  
**Error 404:** `Order tidak ditemukan.`  
**Error 502:** `Gagal membuat pembayaran QRIS: <pesan dari Midtrans>`  
**Error 503:** `Pembayaran QRIS belum dikonfigurasi di server.`

---

### `GET /api/payments/qris/status?orderId=xxx`

Cek status pembayaran live dari Midtrans. Digunakan frontend untuk polling
(tiap 5 detik) sampai status `paid`/`expired`/`failed`.

**Query param:** `orderId` (internal order id, bukan order_number)

**Response 200:**
```json
{
  "ok": true,
  "status": "pending",
  "order": { ...orderObject }
}
```

`status` bisa: `pending` | `paid` | `expired` | `failed`

Jika Midtrans return `settlement` → otomatis update DB ke `paid` + `diproses`.

---

### `POST /api/payments/webhook`

Endpoint untuk HTTP notification dari Midtrans (webhook). **Selalu return 200 OK**
agar Midtrans tidak retry — rejection (signature invalid, order tidak ada) dilakukan
secara silent tanpa update data.

**Setup di Midtrans Dashboard:**
```
Settings → Configuration → Notification URL:
https://<domain-kamu>/api/payments/webhook
```

**Untuk testing lokal:** gunakan ngrok + URL yang di-update di dashboard setiap kali ngrok restart.

**Request dari Midtrans:**
```json
{
  "order_id": "HNK-20260601-143213-348",
  "transaction_status": "settlement",
  "status_code": "200",
  "gross_amount": "170000.00",
  "fraud_status": "accept",
  "signature_key": "<sha512(order_id+status_code+gross_amount+serverKey)>"
}
```

**Logic:**
1. Cek `order_id` dan `signature_key` ada → jika tidak, return 200 `ignored: missing fields`
2. Verifikasi SHA-512 signature → jika invalid, return 200 `ignored: invalid signature`
3. Cari order by `order_number` → jika tidak ada, return 200 `ignored: order not found`
4. Map `transaction_status` ke internal status:
   - `settlement` / `capture` + `accept` → `paid` → update DB + status `diproses`
   - `expire` → `expired`
   - `deny` / `cancel` / `failure` → `failed`
5. Return 200 `processed`

---

## File Implementasi

| File | Deskripsi |
|---|---|
| `src/Infrastructure/Services/MidtransService.php` | HTTP client Midtrans: `chargeQris()`, `getStatus()`, `verifySignature()`, `mapPaymentStatus()`, `parseExpiry()` |
| `src/Actions/Payment/GenerateQrisAction.php` | Handler `POST /api/payments/qris` |
| `src/Actions/Payment/PaymentStatusAction.php` | Handler `GET /api/payments/qris/status` |
| `src/Actions/Payment/PaymentWebhookAction.php` | Handler `POST /api/payments/webhook` |

---

## Catatan Penting: Timezone

**WAJIB dibaca sebelum menyentuh kode payment.**

PHP CLI default timezone di server dev = `Europe/Berlin`.
MySQL/OS = GMT+8 (WITA). Midtrans mengembalikan `expiry_time` sebagai
`"Y-m-d H:i:s"` dalam **WIB (Asia/Jakarta) tanpa offset**.

Jangan pernah pakai `strtotime()` / `date()` langsung untuk waktu payment.
Gunakan `MidtransService::parseExpiry()` yang parse sebagai Jakarta lalu simpan UTC.
Frontend menerima ISO-8601 dengan offset (`->format('c')`) sehingga `new Date()` di browser selalu benar.

---

## Payment Status Flow

```
Order dibuat (QRIS)
  ↓ payment_status = 'pending'
  ↓
POST /api/payments/qris → Midtrans charge → QR tampil di browser
  ↓ frontend polling tiap 5 detik via GET /api/payments/qris/status
  ↓
Customer scan & bayar
  ↓
Midtrans → POST /api/payments/webhook (settlement)
  ↓ payment_status = 'paid', status = 'diproses'
  ↓
Frontend polling detect 'paid' → redirect /orders (1.4 detik)
```

```
Order dibuat (Cash)
  ↓ payment_status = 'cod'
  ↓
Admin konfirmasi pembayaran di admin panel
  ↓ status = 'diproses'
```

---

## Payment Status Values

| Status | Keterangan |
|---|---|
| `pending` | QRIS belum dibayar |
| `paid` | QRIS sudah dibayar (settlement) |
| `cod` | Cash / bayar saat terima |
| `expired` | QR kedaluwarsa (15 menit) |
| `failed` | Pembayaran ditolak / dibatalkan |

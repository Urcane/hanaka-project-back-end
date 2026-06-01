# Backend — Payment API (QRIS)

> Endpoint untuk pembayaran QRIS dan integrasi payment gateway.

---

## Current State: Simulasi

Saat ini QRIS hanya simulasi (QR dari string lokal). Backend akan mengintegrasikan **Midtrans** atau **Xendit** untuk real QRIS payment.

---

## Endpoints

### POST `/api/payments/qris`

Generate QRIS payment untuk sebuah order.

**Request:**
```json
{
  "orderId": "ord_q1r2s3t4"
}
```

**Validation:**
1. Order exists
2. User has access to order
3. `paymentMethod === 'qris'`
4. `paymentStatus === 'pending'` (belum dibayar)

**Success Response (201):**
```json
{
  "ok": true,
  "payment": {
    "orderId": "ord_q1r2s3t4",
    "orderNumber": "HNK-20260522-143052-847",
    "amount": 340000,
    "qrImageUrl": "https://api.midtrans.com/v2/qris/...",
    "qrString": "00020101021126...",
    "expiresAt": "2026-05-22T08:00:00Z",
    "status": "pending"
  }
}
```

**Error (400):**
```json
{
  "ok": false,
  "error": "Order sudah dibayar."
}
```

---

## Midtrans Integration (Planned)

### Configuration
```env
MIDTRANS_SERVER_KEY=SB-Mid-server-xxxxx
MIDTRANS_CLIENT_KEY=SB-Mid-client-xxxxx
MIDTRANS_IS_PRODUCTION=false
```

### Create Transaction (Server-side)
```php
// Pseudo-code
class QrisPaymentService {
    public function createQrisPayment(Order $order): array {
        $params = [
            'transaction_details' => [
                'order_id' => $order->orderNumber,
                'gross_amount' => $order->totalPrice,
            ],
            'payment_type' => 'qris',
            'customer_details' => [
                'first_name' => $order->customerName,
                'phone' => $order->customerPhone,
            ],
        ];

        // Call Midtrans API
        $response = Http::post('https://api.midtrans.com/v2/charge', $params, [
            'Authorization' => 'Basic ' . base64_encode($this->serverKey . ':'),
        ]);

        return [
            'qrString' => $response['actions'][0]['url'],
            'transactionId' => $response['transaction_id'],
            'expiresAt' => $response['expiry_time'],
        ];
    }
}
```

---

## Payment Webhook (Notification)

### POST `/api/payments/webhook` (from Midtrans)

Midtrans akan mengirim notification saat payment status berubah.

**Request (from Midtrans):**
```json
{
  "transaction_status": "settlement",
  "order_id": "HNK-20260522-143052-847",
  "gross_amount": "340000.00",
  "payment_type": "qris",
  "signature_key": "..."
}
```

**Logic:**
1. Verify signature (prevent spoofing)
2. Find order by `order_number`
3. If `transaction_status === 'settlement'`:
   - Update `payment_status = 'paid'`
   - Update `status = 'diproses'`
4. If `transaction_status === 'expire'`:
   - Update `payment_status = 'expired'`
5. Return 200 OK

**Signature Verification:**
```php
$serverKey = $_ENV['MIDTRANS_SERVER_KEY'];
$hashed = hash('sha512',
    $notification['order_id'] .
    $notification['status_code'] .
    $notification['gross_amount'] .
    $serverKey
);

if ($hashed !== $notification['signature_key']) {
    return response(403, 'Invalid signature');
}
```

---

## Xendit Alternative

Jika memilih Xendit alih-alih Midtrans:

### Create QRIS
```php
$response = Http::post('https://api.xendit.co/qr_codes', [
    'reference_id' => $order->orderNumber,
    'type' => 'DYNAMIC',
    'currency' => 'IDR',
    'amount' => $order->totalPrice,
    'expires_at' => now()->addMinutes(15)->toIso8601String(),
], [
    'Authorization' => 'Basic ' . base64_encode($this->apiKey . ':'),
]);
```

### Webhook
Xendit mengirim callback ke URL yang dikonfigurasi di dashboard.

---

## QR Expiry

- QR code punya waktu expiry (default: **15 menit**)
- Setelah expire, user harus generate ulang
- Frontend harus tampilkan countdown timer
- Backend harus reject payment untuk expired QR

---

## Payment Status Flow

```
Order Created (QRIS)
  ↓ paymentStatus = 'pending'
  ↓
QR Generated → User scan & pay
  ↓
Webhook: settlement
  ↓ paymentStatus = 'paid', status = 'diproses'
  ↓
Order processing...
```

```
Order Created (Cash/COD)
  ↓ paymentStatus = 'cod'
  ↓
Admin konfirmasi terima pembayaran
  ↓ status = 'diproses'
```

---

## Frontend Integration

Setelah backend payment ready, frontend perlu:

1. **CheckoutPage**: Setelah `POST /api/orders`, call `POST /api/payments/qris`
2. **PaymentQrisPage**: Tampilkan QR dari `qrImageUrl` response
3. **Polling / WebSocket**: Check payment status berkala
4. **Expiry handling**: Tampilkan timer, allow regenerate

```js
// Frontend pseudo-code
const createPayment = async (orderId) => {
  const res = await apiService.post('/payments/qris', { orderId })
  return res.payment // { qrImageUrl, expiresAt, ... }
}

// Poll status every 3s
const checkStatus = async (orderId) => {
  const res = await apiService.get(`/orders/${orderId}`)
  if (res.order.paymentStatus === 'paid') {
    navigate('/orders')
  }
}
```

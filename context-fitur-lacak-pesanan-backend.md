# Membangun Fitur Baru: **Lacak Pesanan** — Sisi Backend (Slim PHP)

> **Pasangan backend dari dokumen frontend `context-fitur-lacak-pesanan.md`.** Frontend memanggil `GET /orders/track?number=...` dari halaman publik `/lacak`. Dokumen ini membangun **endpoint yang melayani panggilan itu** di backend Slim PHP — dengan satu twist keamanan yang penting dan sengaja diajarkan.
>
> Endpoint ini *belum ada* di codebase. Kamu menambahkannya sehingga kontrak yang diasumsikan frontend terpenuhi: `GET /api/orders/track?number=` → `200 { order }` atau `404`.
>
> ✅ Mengikuti pola Action/Repository yang sudah ada (`GetOrderAction`, `OrderRepository::findByOrderNumber`). Setelah implementasi: uji dengan `curl`.

---

## ⚠️ Pertanyaan paling penting: "Endpoint publik ini aman, nggak?"

**Jawaban singkat: konsepnya aman, TAPI implementasi naif-nya BAHAYA.** Ini justru inti ajar fitur ini.

Tracking publik via nomor pesanan adalah pola umum (persis lacak resi JNE/J&T — tanpa login). Yang berbahaya bukan "publik"-nya, tapi **apa yang dikembalikan**.

Lihat `OrderRepository::formatOrder()` (formatter yang dipakai endpoint order lain). Ia mengembalikan:

```
customerName, customerPhone, deliveryAddress, addressNote, ...
```

Itu **PII (Personally Identifiable Information)** — nama, nomor HP, dan **alamat rumah** pemesan. Kalau endpoint publik `/orders/track` mengembalikan `formatOrder()` apa adanya, maka:

> **Siapa pun yang tahu (atau menebak) sebuah nomor pesanan bisa memanen nama, no. HP, dan alamat rumah orang lain.**

Dan nomor pesanan **bisa ditebak**: formatnya `HNK-YYYYMMDD-HHMMSS-NNN`, bagian acak hanya 3 digit (`100–999`). Dengan tanggal & jam yang masuk akal, ruang tebaknya kecil → rawan **enumeration / scraping data pelanggan**.

**Solusinya (yang dipakai di implementasi ini):** jangan pernah pakai `formatOrder()` di jalur publik. Buat **proyeksi minimal** `formatTracking()` yang hanya membuka data non-sensitif: nomor, status, tanggal, total, dan ringkasan item. Kebetulan kontrak frontend memang **hanya butuh itu** — jadi keamanan dan kebutuhan UI sejalan, tanpa kompromi.

| Field | `formatOrder` (privat) | `formatTracking` (publik) |
|---|---|---|
| orderNumber, status, createdAt, totalPrice | ✅ | ✅ |
| items (nama, ukuran, qty, harga) | ✅ | ✅ |
| **customerName / customerPhone** | ✅ | ❌ **dibuang** |
| **deliveryAddress / addressNote** | ✅ | ❌ **dibuang** |
| userId, paymentMethod | ✅ | ❌ |

> **Prinsip:** *minimize data exposure* — endpoint hanya mengembalikan data seminimal yang dibutuhkan kliennya. Ini bentuk konkret dari prinsip **least privilege** pada level data.

### Pengerasan tambahan (disebut, belum diimplementasi)
- **Rate limiting** pada `/orders/track` untuk mempersempit enumeration (mis. 10 req/menit/IP). Infrastruktur rate-limit belum ada di project → dicatat sebagai roadmap, bukan blocker.
- (Opsional, lebih ketat) minta **konfirmasi kedua** seperti 4 digit terakhir nomor HP sebelum menampilkan status — menaikkan keamanan dengan biaya UX. Untuk MVP, proyeksi minimal sudah memadai.

---

## Kontrak API yang dipenuhi

```
GET /api/orders/track?number=HNK-20260624-103000-123
  (PUBLIK — tidak wajib JWT, tidak ada middleware guard)
  Sukses  : 200 { ok:true, order: { orderNumber, status, paymentStatus,
                                     fulfillmentMethod, totalPrice, createdAt, items[] } }
  Tak ada : 404 { ok:false, error:'Pesanan tidak ditemukan.' }
```

- **Tanpa PII** di respons (lihat bagian di atas).
- Param `number` di-`trim` + `UPPERCASE` di server (klien sudah menormalkan, tapi server tidak boleh percaya itu).
- Nomor kosong / tidak ketemu → **404 yang sama** ("Pesanan tidak ditemukan.") — tidak membedakan "format salah" vs "tidak ada", supaya tidak membocorkan info ke penebak.

---

## Peta fitur

```
                  ┌──────────────── ROUTE (PUBLIK) ────────────────┐
  GET /api/orders/│ routes.php (dalam grup /orders):                │
  track?number=   │   $orders->get('/track', TrackOrderAction)      │
  ───────────────►│   TANPA ->add(AuthRequiredMiddleware)           │
                  │   ⚠️ didaftarkan SEBELUM /{orderId}             │
                  └───────────────────────┬────────────────────────┘
                                          ▼
                  ┌──────────────── TrackOrderAction ──────────────┐
                  │ 1) ambil query 'number', trim + UPPERCASE       │
                  │ 2) kosong → 404 STOP                             │
                  │ 3) findByOrderNumber(number)  ── REPOSITORY      │
                  │    null → 404 STOP                               │
                  │ 4) formatTracking(order)  ← PROYEKSI MINIMAL     │
                  │ 5) successResponse({ order })  200              │
                  └───────────────────────┬────────────────────────┘
                                          ▼
                  ┌──────────────── OrderRepository ───────────────┐
                  │ findByOrderNumber(number)  (sudah ada)          │
                  │ formatTracking(row) → TANPA PII (baru)          │
                  └─────────────────────────────────────────────────┘
```

---

# BAGIAN A — Bangun dari Bawah ke Atas

🆕 = FILE BARU, ✏️ = EDIT FILE.

## Langkah 1 — ✏️ `src/Infrastructure/Repositories/OrderRepository.php` (Data)

`findByOrderNumber()` **sudah ada** (dipakai alur pembayaran) — kita reuse, tidak menulis ulang query. Yang baru hanya **proyeksi aman** `formatTracking()`.

```php
/**
 * Public projection for the order-tracking endpoint. Deliberately omits all
 * PII (customer name, phone, delivery address, notes) so the order number
 * alone cannot expose personal data of whoever placed the order.
 */
public static function formatTracking(array $row): array
{
    $items = [];
    foreach ($row['items'] ?? [] as $item) {
        $items[] = [
            'id' => $item['id'],
            'productName' => $item['product_name'],
            'sizeLabel' => $item['size_label'],
            'quantity' => (int) $item['quantity'],
            'totalPrice' => (int) $item['total_price'],
        ];
    }

    return [
        'orderNumber' => $row['order_number'],
        'status' => $row['status'],
        'paymentStatus' => $row['payment_status'],
        'fulfillmentMethod' => $row['fulfillment_method'],
        'totalPrice' => (int) $row['total_price'],
        'createdAt' => $row['created_at'],
        'items' => $items,
    ];
}
```

Tambahan kecil: aku beri komentar peringatan di atas `formatOrder()` bahwa formatter itu **memuat PII** dan tidak boleh dipakai di endpoint publik — agar developer berikutnya tidak keliru memakainya.

**Yang harus kamu jelaskan:**
- Dua formatter, dua tingkat keterbukaan: `formatOrder` (privat, lengkap) vs `formatTracking` (publik, minimal). Inilah cara teknis menegakkan *minimize data exposure*.
- Reuse `findByOrderNumber` (prepared statement → aman injection) — tidak ada query baru.

## Langkah 2 — 🆕 `src/Actions/Order/TrackOrderAction.php` (Action / thin controller)

Membaca **query string** (bukan body), menormalkan, mencari, lalu mengembalikan proyeksi aman. Strukturnya mirip `GetOrderAction`, tapi tanpa kepemilikan/sesi karena publik.

```php
<?php

declare(strict_types=1);

namespace App\Actions\Order;

use App\Actions\BaseAction;
use App\Infrastructure\Repositories\OrderRepository;
use Psr\Http\Message\ResponseInterface as Response;

class TrackOrderAction extends BaseAction
{
    private OrderRepository $orderRepo;

    public function __construct(OrderRepository $orderRepo)
    {
        $this->orderRepo = $orderRepo;
    }

    protected function action(): Response
    {
        $params = $this->request->getQueryParams();
        $number = strtoupper(trim((string) ($params['number'] ?? '')));

        if ($number === '') {
            return $this->errorResponse('Pesanan tidak ditemukan.', 404);
        }

        $order = $this->orderRepo->findByOrderNumber($number);
        if (!$order) {
            return $this->errorResponse('Pesanan tidak ditemukan.', 404);
        }

        // Public projection only — never expose customer PII here.
        return $this->successResponse([
            'order' => OrderRepository::formatTracking($order),
        ]);
    }
}
```

**Yang harus kamu jelaskan:**
1. **Query param, bukan body.** Endpoint `GET` → data ada di query string, diakses lewat `$this->request->getQueryParams()` (bukan `getBody()`). Ini beda dengan Action yang menerima POST/PUT.
2. **Server menormalkan ulang** (`trim` + `strtoupper`) walau frontend sudah melakukannya. Backend **tidak percaya** input klien.
3. **404 seragam.** Kosong dan tidak-ketemu sama-sama 404 dengan pesan identik — tidak memberi sinyal berbeda ke penebak nomor.
4. **`formatTracking`, bukan `formatOrder`** — keputusan keamanan inti fitur ini.

> **DI tanpa registrasi manual.** Seperti action lain, `TrackOrderAction` di-autowire PHP-DI lewat constructor `OrderRepository`. Tidak perlu menyentuh `app/dependencies.php`.

## Langkah 3 — ✏️ `app/routes.php` (Route — publik, urutan penting)

Daftarkan di dalam grup `/orders`, **TANPA** `AuthRequiredMiddleware`, dan **sebelum** route `/{orderId}`.

```php
// (1) import
use App\Actions\Order\TrackOrderAction;

// (2) di dalam $api->group('/orders', ...):
$orders->post('', CreateOrderAction::class);
$orders->get('/track', TrackOrderAction::class);   // ← publik, SEBELUM /{orderId}
$orders->get('', ListOrdersAction::class)->add(AuthRequiredMiddleware::class);
$orders->get('/{orderId}', GetOrderAction::class);
$orders->patch('/{orderId}/pay', MarkOrderPaidAction::class);
```

**Dua poin yang wajib kamu kuasai:**
1. **Kenapa tanpa guard?** Tamu yang memesan tanpa login pun harus bisa melacak pesanannya. Bandingkan dengan `GET /orders` (riwayat semua pesanan milik user) yang dilindungi `AuthRequiredMiddleware`. Keputusan "guard atau tidak" adalah desain akses, bukan asal pasang — **dan** keamanannya digeser ke level *data* (proyeksi minimal), bukan level *akses*.
2. **Kenapa urutan penting?** Route `/{orderId}` adalah pola wildcard. Kalau `/track` didaftarkan setelahnya (atau router memprioritaskan wildcard), `GET /orders/track` bisa salah-cocok ke `GetOrderAction` dengan `orderId = "track"`. Mendaftarkan segmen literal `/track` lebih dulu menjamin ia menang. (FastRoute memang memprioritaskan rute statis, tapi mendaftarkannya duluan membuat niat ini eksplisit dan aman.)

---

# BAGIAN B — Jejak Runtime (atas ke bawah)

Skenario: tamu (tanpa token) membuka `/lacak`, submit `HNK-20260624-103000-123`.

1. **(Frontend)** `apiTrackOrder` → `GET /api/orders/track?number=HNK-20260624-103000-123`. `apiService` boleh menempel `Authorization` kalau ada, tapi tidak wajib.
2. **(Middleware global)** `JwtMiddleware` jalan; karena tak ada/ tak relevan token, `userId` mungkin kosong — **tidak masalah**, route ini tak punya guard.
3. **(Route)** Slim cocokkan `GET /orders/track` ke `TrackOrderAction` (literal menang atas `/{orderId}`).
4. **(Action)** `getQueryParams()['number']` → `trim` + `UPPERCASE`. Tidak kosong → lanjut.
5. **(Repository)** `findByOrderNumber('HNK-...')` → prepared statement → baris order + item.
6. **(Action)** `formatTracking($order)` → buang PII, sisakan nomor/status/total/item.
7. **(Action)** `successResponse(['order' => ...])` → `{ ok:true, order:{...} }`, **200**.
8. **(Frontend)** `setOrder(found)` + `setStatus('success')` → kartu status tampil. Tanpa Context, tanpa login, tanpa PII bocor.

---

# BAGIAN C — Tiga Skenario Wajib

### Skenario 1 — Nomor kosong / param hilang
`?number=` kosong → `$number === ''` → **404 "Pesanan tidak ditemukan."** tanpa menyentuh DB.

### Skenario 2 — Tidak ditemukan / 404 (Repository)
Format benar tapi tak ada di DB → `findByOrderNumber` return `null` → **404** yang sama. Frontend `catch` → `err.status === 404` → `setStatus('notfound')`.

### Skenario 3 — Sukses tanpa membocorkan PII (Keamanan)
Nomor valid & ada → **200** dengan proyeksi minimal. Walau pemanggil anonim, respons **tidak memuat** nama/HP/alamat pemesan. Membuktikan endpoint publik bisa berguna tanpa mengorbankan privasi.

---

## Kontras dengan endpoint order lain (bahan sidang)

| Aspek | `GET /orders` (riwayat) | `GET /orders/{id}` (detail) | `GET /orders/track` (lacak) |
|---|---|---|---|
| Akses | wajib login (JWT) | login (milik sendiri) / guest (session token) | **publik** |
| Guard | `AuthRequiredMiddleware` | cek kepemilikan di Action | **tidak ada** |
| Lookup | `findByUser(userId)` | `findById` / `findBySession` | `findByOrderNumber` |
| Formatter | `formatOrder` (PII) | `formatOrder` (PII) | **`formatTracking` (tanpa PII)** |
| Kenapa aman | hanya order milik user | diverifikasi kepemilikan | **proyeksi data diminimalkan** |

> Inti: keamanan tidak selalu = "pasang guard". Untuk data yang memang publik per desain, keamanan digeser ke **seberapa banyak yang diungkap** (data minimization), bukan **siapa yang boleh akses**.

---

## Pemetaan ke BAB Skripsi

| Bagian fitur | Lapisan | BAB |
|---|---|---|
| `formatTracking` (proyeksi minimal, anti-PII) | Repository | Pembahasan — Keamanan Data / Privacy |
| `TrackOrderAction` (query param, 404 seragam) | Action / API | Integrasi Client–Server / Desain API |
| Route publik + urutan vs `/{orderId}` | Route | Perancangan — Routing & Akses |
| Validasi ulang input di server (trim/upper) | Action | Pembahasan — Defense in Depth |
| Enumeration & rate limiting | — | Pembahasan — Ancaman & Mitigasi |

---

## ✅ Self-Check
1. Kenapa endpoint publik ini **tidak boleh** memakai `formatOrder()`? Data apa yang bocor kalau dipakai?
2. Apa bedanya keamanan "berbasis guard" vs "berbasis minimalisasi data"? Yang mana dipakai di sini, kenapa?
3. Kenapa nomor kosong dan nomor tidak-ditemukan dikembalikan dengan 404 yang sama?
4. Kenapa route `/track` harus didaftarkan sebelum `/{orderId}`?
5. Kenapa server tetap `trim`+`UPPERCASE` walau frontend sudah menormalkan?
6. Apa ancaman *enumeration* di sini, dan mitigasi apa yang disarankan?

## 🛠 Latihan
- **Validasi format server-side:** tolak nomor yang tidak cocok regex `^HNK-\d{8}-\d{6}-\d{3}$` lebih awal (tetap balas 404, hemat query DB).
- **Rate limiting:** rancang middleware sederhana penghitung per-IP (mis. via tabel atau cache) untuk `/orders/track`.
- **Verifikasi kedua (capstone keamanan):** ubah kontrak agar butuh `?last4=` (4 digit terakhir HP) dan cocokkan di server sebelum menampilkan — diskusikan trade-off UX vs keamanan.
- **Unit test:** test `formatTracking` memastikan output **tidak** memuat key `customerPhone`/`deliveryAddress`, dan `TrackOrderAction` mengembalikan 404 saat `number` kosong.

## Pertanyaan Sidang
- "Endpoint ini publik — apa tidak berbahaya?" → Aman karena hanya mengembalikan proyeksi non-sensitif (`formatTracking`); PII pemesan tidak pernah diekspos. Keamanan ada di level data, bukan akses.
- "Bagaimana mencegah orang memanen data pelanggan lewat tebak nomor?" → (1) tidak ada PII di respons sehingga tebakan tidak bernilai; (2) rate limiting untuk mempersempit enumeration (roadmap).
- "Kenapa tidak dilindungi login saja?" → Karena tamu tanpa akun pun perlu melacak pesanannya (sama seperti lacak resi). Memaksa login mematahkan use-case-nya.
- "Validasi format di client cukup?" → Tidak; server menormalkan ulang dan tetap penentu via lookup DB.

---

## Ringkasan file (checklist implementasi)

| File | Status | Lapisan |
|---|---|---|
| `src/Infrastructure/Repositories/OrderRepository.php` | ✏️ tambah `formatTracking` + komentar peringatan pada `formatOrder` | Repository |
| `src/Actions/Order/TrackOrderAction.php` | 🆕 buat | Action |
| `app/routes.php` | ✏️ import + route publik `GET /orders/track` (sebelum `/{orderId}`) | Route |
| `app/dependencies.php` | — tidak perlu (autowiring PHP-DI) | DI |

> **Uji cepat:**
> ```bash
> composer start   # http://localhost:8080
> # ambil nomor order yang valid dari DB, lalu:
> curl "http://localhost:8080/api/orders/track?number=HNK-20260624-103000-123"
> ```
> Nomor ada → 200 `{ order }` **tanpa** customerPhone/deliveryAddress. Tidak ada / kosong → 404.

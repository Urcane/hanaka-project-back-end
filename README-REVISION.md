# Hanaka Cake — Catatan Revisi

> Dokumen ini merangkum **revisi yang ditambahkan di atas versi sebelumnya**: role Owner, manajemen stok, serta progress & peta pesanan.
> `README.md` (setup dasar backend) dan `DOCUMENTATION.md` (dokumentasi lengkap frontend + backend) tetap berlaku — dokumen ini pelengkap, bukan pengganti.
>
> Tanggal revisi: **13 Agustus 2026**

---

## Daftar Isi

1. [Ringkasan Revisi](#1-ringkasan-revisi)
2. [Cara Upgrade](#2-cara-upgrade)
3. [Revisi 1 — Role Owner](#3-revisi-1--role-owner)
4. [Revisi 2 — Manajemen Stok](#4-revisi-2--manajemen-stok)
5. [Revisi 3 — Progress & Peta Pesanan](#5-revisi-3--progress--peta-pesanan)
6. [Perubahan Skema Database](#6-perubahan-skema-database)
7. [Perubahan API](#7-perubahan-api)
8. [Daftar File Baru & Berubah](#8-daftar-file-baru--berubah)
9. [Cara Menguji](#9-cara-menguji)
10. [Batasan & Catatan Jujur](#10-batasan--catatan-jujur)

---

## 1. Ringkasan Revisi

| # | Revisi | Inti Perubahan |
|---|---|---|
| 1 | **Role Owner** | Role ketiga (`customer`/`admin`/`owner`) + panel `/owner/*`: laporan harian dengan filter tanggal, grafik, peta sebaran order, dan pemberitahuan ke seluruh admin |
| 2 | **Stok Produk** | Kuota stok **per ukuran** yang ditentukan admin, tampil live ke pelanggan, dipotong saat order dibuat dan dikembalikan saat order dibatalkan |
| 3 | **Progress & Peta** | Stepper status pesanan + minimap titik (Leaflet + OpenStreetMap) di halaman lacak pesanan, serta peta sebaran order delivery di dashboard owner |

Dua repo yang tersentuh:

| Repo | Peran |
|---|---|
| `hanaka-project-back-end` | Migration, role & guard, panel owner, logika stok, endpoint |
| `hanaka-project` | Stok live di storefront, peta pemilih titik di checkout, stepper + minimap di halaman lacak |

---

## 2. Cara Upgrade

### 2.1 Backend

```bash
cd hanaka-project-back-end

# 1) Jalankan migration baru (010–013)
php database/migrate.php

# 2) Buat akun owner default
mysql -u root hanaka_cake < database/seeds/owner_seeder.sql
```

Pada database yang benar-benar baru, cukup satu perintah:

```bash
php database/migrate.php --seed   # migration + produk + admin + owner
```

> Semua migration baru **idempotent** (dijaga lewat `INFORMATION_SCHEMA`), jadi `migrate.php` aman dijalankan berulang.
> `products_seeder.sql` **tidak** idempotent — pada database yang produknya sudah terisi, `--seed` akan berhenti dengan error *Duplicate entry 'black-forest'*. Error itu aman diabaikan: migration serta seeder admin & owner sudah selesai sebelum baris tersebut.

### 2.2 Variabel `.env` baru

```env
# Koordinat toko — titik asal rute di peta lacak pesanan,
# pusat peta pemilih lokasi di checkout, dan marker toko di peta owner.
STORE_LAT=-1.2654
STORE_LNG=116.8312

# Target penjualan harian (rupiah) untuk progress bar dashboard owner
SALES_TARGET_DAILY=1500000
```

> Nilai `STORE_LAT`/`STORE_LNG` di atas adalah **perkiraan** area Karang Rejo, Balikpapan. Ganti dengan titik toko yang sebenarnya (klik kanan lokasi toko di Google Maps → salin koordinat) agar rute di peta akurat.

### 2.3 Frontend

```bash
cd hanaka-project
npm install        # menambahkan dependensi baru: leaflet
npm run dev
```

Dependensi baru hanya satu: **`leaflet` ^1.9.4**. Tidak ada library chart — semua grafik owner digambar sebagai inline SVG.

### 2.4 Akun bawaan

| Role | Email | Password | Tujuan setelah login |
|---|---|---|---|
| Admin | `admin@hanakacake.com` | `Admin12345` | `/admin/dashboard` |
| Owner | `owner@hanakacake.com` | `Owner12345` | `/owner/dashboard` |

Keduanya login lewat form yang sama di frontend React. Ganti password bawaan sebelum dipakai di produksi.

---

## 3. Revisi 1 — Role Owner

### 3.1 Konsep

Owner adalah **pemilik toko**, bukan operator harian. Karena itu panelnya sengaja dipisah dari admin: owner membaca laporan dan mengirim instruksi, tidak menyentuh order satu per satu.

| Role | Fokus | Panel |
|---|---|---|
| `customer` | Belanja | Storefront React |
| `admin` | Operasional: order, produk, stok, customer | `/admin/*` |
| `owner` | Manajerial: laporan, grafik, peta, pemberitahuan | `/owner/*` |

### 3.2 Alur login (satu pintu, dua tujuan)

```
Login di frontend React
   └─ role customer → tetap di storefront
   └─ role admin/owner → redirect ke GET /admin/login?token=<jwt>
          └─ backend verifikasi JWT, baca role, set cookie HttpOnly
                 ├─ admin  → /admin/dashboard
                 └─ owner  → /owner/dashboard
```

Route handoff tetap satu (`/admin/login`) supaya tidak ada dua mekanisme sesi yang harus dijaga. Yang membedakan hanya tujuan redirect-nya.

**Salah masuk URL tidak dianggap gagal login.** Admin yang membuka `/owner/*` dialihkan ke `/admin/dashboard`, owner yang membuka `/admin/*` dialihkan ke `/owner/dashboard` — bukan dilempar ke halaman login, karena itu akan terasa seperti sesinya bermasalah padahal tidak.

### 3.3 Halaman panel owner

| Route | Isi |
|---|---|
| `/owner/dashboard` | Ringkasan + progress + grafik + peta sebaran order + stok perlu restock |
| `/owner/reports` | Laporan harian dengan filter tanggal (`?from=&to=`) |
| `/owner/reports/export` | Unduh laporan rentang tanggal sebagai CSV |
| `/owner/announcements` | Kirim & kelola pemberitahuan untuk seluruh admin |

**Dashboard** berisi:

- **Kartu ringkas** — pendapatan rentang, jumlah order, rata-rata per order, order selesai, order dibatalkan, pendapatan sepanjang waktu.
- **Progress bar** — pencapaian target penjualan hari ini (`SALES_TARGET_DAILY`) dan rasio order selesai pada rentang terpilih.
- **Grafik** — bar chart pendapatan harian, donut status order, bar produk terlaris. Semuanya **inline SVG murni**, tanpa library chart sama sekali.
- **Minimap titik** — peta Leaflet berisi titik order delivery pada rentang tanggal, warna titik mengikuti status order, popup berisi nomor order dan total.
- **Tabel stok perlu restock** — ukuran yang stoknya menyentuh ambang "menipis".

### 3.4 Laporan & filter tanggal

Filter menerima `?from=` dan `?to=` (format `Y-m-d`), lengkap dengan tombol preset: *Hari ini*, *7 hari*, *30 hari*, *Bulan ini*.

Input yang aneh diperbaiki, bukan ditolak — rentang terbalik ditukar otomatis, tanggal tidak valid jatuh ke default 30 hari terakhir, dan rentang dibatasi maksimal 365 hari agar satu query tidak menggambar bertahun-tahun data. Halaman laporan tidak boleh buntu hanya karena salah ketik tanggal.

Query laporan ada di `OrderRepository`:

| Method | Fungsi |
|---|---|
| `getDailyReport($from, $to)` | Baris per hari; hari tanpa order tetap diisi nol agar sumbu grafik kontinu |
| `getRangeSummary($from, $to)` | Total order, pendapatan, selesai, dibatalkan, rata-rata per order |
| `getStatusBreakdown($from, $to)` | Jumlah order per status |
| `getPaymentMethodBreakdown($from, $to)` | Jumlah & pendapatan per metode bayar |
| `getTopProducts($from, $to, $limit)` | Produk terlaris berdasar unit terjual |
| `getDeliveryPoints($from, $to)` | Titik order delivery untuk peta |

Semua angka pendapatan memakai filter `payment_status IN ('paid', 'cod')`, konsisten dengan `getTodayRevenue()` yang sudah ada sebelumnya.

### 3.5 Pemberitahuan owner → admin

- Owner menulis pemberitahuan di `/owner/announcements` (judul, isi, prioritas *info* / *penting*).
- Pemberitahuan muncul sebagai **banner di setiap halaman admin** sampai admin menekan *Tandai dibaca*.
- Status "sudah dibaca" disimpan **per-admin** di tabel `announcement_reads`. Satu admin menutup banner **tidak** menyembunyikannya dari admin lain — kalau tidak begitu, instruksi owner bisa hilang sebelum sempat dibaca seluruh tim.
- Owner bisa melihat berapa admin yang sudah membaca tiap pemberitahuan, dan menghapusnya.

---

## 4. Revisi 2 — Manajemen Stok

### 4.1 Stok melekat pada ukuran, bukan produk

Kolom `stock` dan `low_stock_threshold` ada di tabel `product_sizes`. Alasannya: 16 cm bisa habis sementara 20 cm masih tersedia, dan **ukuran** itulah yang dipilih pelanggan.

### 4.2 Sisi admin

Di `/admin/products`, tiap baris ukuran punya editor stok inline:

| Field | Fungsi |
|---|---|
| **Stok** | Kuota yang tersedia untuk dijual |
| **Batas menipis** | Ambang badge "Menipis" dan daftar restock di dashboard owner |

Badge status ditampilkan otomatis: **Tersedia** / **Menipis** / **Habis**. Form tambah ukuran juga sudah punya field stok awal (default 10) dan ambang menipis (default 5).

### 4.3 Sisi pelanggan (live)

| Halaman | Perilaku |
|---|---|
| Menu | Badge `Stok N` / `Stok habis` di kartu produk; kartu yang habis ditampilkan pudar. Refresh tiap 30 detik |
| Kustomisasi | Sisa stok tampil di bawah tiap lingkaran ukuran; ukuran habis di-*disable*; batas qty mengikuti sisa stok; tombol berubah jadi "Stok habis". Refresh tiap 20 detik |
| Keranjang | Menampilkan pesan bila penambahan qty ditolak karena stok |

Refresh juga dipicu saat tab browser kembali fokus, sehingga pelanggan yang meninggalkan tab lalu kembali tidak melihat angka basi. Ini polling biasa, bukan WebSocket — cukup untuk skala satu toko dan tidak menambah infrastruktur.

### 4.4 Kapan stok dipotong

| Titik | Perilaku |
|---|---|
| `POST /api/cart/items` | Tolak (**409**) bila qty melebihi stok — penjagaan awal yang ramah |
| `PUT /api/cart/items/{id}` | Cek ulang, termasuk saat pelanggan berpindah ke ukuran lain |
| `PATCH /api/cart/items/{id}/quantity` | Cek ulang terhadap stok terkini |
| `POST /api/orders` | **Otoritatif** — stok dipotong di dalam transaksi yang sama dengan pembuatan order |
| Status order → `dibatalkan` | Stok dikembalikan |

Pengecekan di keranjang sengaja **tidak** dianggap sebagai penguncian stok. Barang di keranjang belum tentu dibeli, jadi stok baru benar-benar berkurang saat order dibuat.

### 4.5 Perlindungan dari stok minus

```sql
UPDATE product_sizes SET stock = stock - :qty
WHERE id = :id AND stock >= :qty
```

Syarat `stock >= :qty` berada di dalam `WHERE`, lalu hasilnya diperiksa lewat `rowCount()`. Dua checkout yang berebut potongan kue terakhir di detik yang sama: satu berhasil, satu memperbarui 0 baris → gagal dengan `InsufficientStockException` → HTTP **409**. Database tidak pernah menyimpan stok negatif.

Saat checkout gagal karena stok, **keranjang pelanggan tidak dikosongkan** supaya isinya tinggal disesuaikan, bukan disusun ulang dari nol.

Pengembalian stok saat pembatalan dijaga oleh status sebelumnya, jadi menyimpan status `dibatalkan` dua kali tidak mengembalikan stok dua kali.

---

## 5. Revisi 3 — Progress & Peta Pesanan

### 5.1 Halaman Lacak Pesanan (`/lacak`)

Halaman ini publik — cukup nomor pesanan, tanpa login. Sekarang menampilkan dua hal baru di atas rincian item:

**Stepper progress** (`OrderProgress.jsx`) mengikuti alur pemenuhan, dan menyesuaikan diri dengan metode pengambilan:

```
Pickup    : menunggu konfirmasi → diproses → siap diambil → selesai
Delivery  : menunggu konfirmasi → diproses → diantar     → selesai
```

Order yang dibatalkan ditampilkan sebagai jalur terhenti disertai keterangan, bukan stepper kosong tanpa penjelasan.

**Minimap titik** (`DeliveryMiniMap.jsx`) menampilkan marker toko, marker tujuan, garis putus-putus penghubung, dan satu titik posisi yang bergerak mengikuti status pesanan.

### 5.2 Dari mana koordinatnya

Pelanggan menandai titik antar sendiri di **peta pada halaman checkout** (mode delivery): pin bisa digeser atau lokasi diklik langsung. Field ini **opsional** — order tanpa titik tetap sah, hanya tidak muncul di peta. Karena itu order pickup dan seluruh order lama sebelum fitur ini ada tetap tampil normal tanpa error.

Titik asal toko berasal dari `STORE_LAT`/`STORE_LNG` yang dilayani `GET /api/store/profile`.

### 5.3 Privasi halaman lacak

`OrderRepository::formatTracking()` mengekspos status, item, total, dan **titik antar** — tetapi tetap **tidak** mengekspos nama, nomor telepon, atau alamat tertulis pemesan. Nomor pesanan saja tidak boleh cukup untuk membongkar data pribadi orang lain. Koordinat disertakan karena pelanggan yang melacak pengantarannya memang sudah tahu ke mana barangnya menuju.

### 5.4 Peta sebaran di dashboard owner

Titik order delivery pada rentang tanggal terpilih, warna mengikuti status, popup berisi nomor order dan total. Order tanpa koordinat dilewati begitu saja. Leaflet di panel owner dimuat via CDN unpkg; data peta dikirim lewat blok `<script type="application/json">` sehingga tidak ada data order yang disisipkan ke dalam markup yang dieksekusi.

---

## 6. Perubahan Skema Database

| Migration | Isi |
|---|---|
| `010_add_owner_role.sql` | `users.role` → `ENUM('customer','admin','owner')` |
| `011_add_product_stock.sql` | `product_sizes` + `stock` INT, `low_stock_threshold` INT (default 5) |
| `012_create_announcements.sql` | Tabel `announcements` + `announcement_reads` |
| `013_add_order_coordinates.sql` | `orders` + `delivery_lat`, `delivery_lng` DECIMAL(10,7) nullable |

Seeder baru: `database/seeds/owner_seeder.sql`.

Relasi setelah revisi (9 tabel):

```
users ──┬─< carts ──< cart_items >── products ──< product_sizes (stock)
        │                                  ^
        ├─< orders ──< order_items >────────┘
        │     └─ delivery_lat / delivery_lng
        │
        └─< announcements ──< announcement_reads >── users
```

**Catatan pada migration 011:** pengisian stok awal `10` untuk ukuran yang sudah ada **hanya berjalan saat kolomnya baru dibuat**. Menjalankan ulang `migrate.php` tidak akan menghidupkan kembali stok yang sengaja Anda set ke `0`.

---

## 7. Perubahan API

### 7.1 Produk — field stok baru

`GET /api/products` dan `GET /api/products/{id}` sekarang mengirim:

```jsonc
{
  "id": "black-forest",
  "totalStock": 40,            // baru — total semua ukuran
  "sizes": [
    {
      "id": "size-16-bf",
      "label": "16",
      "price": 120000,
      "stock": 10,             // baru
      "lowStockThreshold": 5,  // baru
      "inStock": true,         // baru
      "lowStock": false        // baru
    }
  ]
}
```

Field lama tidak berubah, jadi konsumen API yang belum diperbarui tetap jalan.

### 7.2 Order — koordinat & konflik stok

`POST /api/orders` menerima dua field opsional:

```jsonc
{
  "deliveryLat": -1.2701,
  "deliveryLng": 116.8412
}
```

Keduanya hanya disimpan untuk order `delivery`, dan divalidasi sebagai angka dalam rentang lat/lng yang wajar.

Response error baru:

| Status | Kapan |
|---|---|
| **409** | Stok tidak mencukupi saat checkout (pesan menyebut produk, ukuran, dan sisa stok). Keranjang tidak dikosongkan |

### 7.3 Lacak pesanan

`GET /api/orders/track?number=HNK-...` kini menyertakan `deliveryLat` dan `deliveryLng` (tetap tanpa data pribadi).

### 7.4 Store profile

`GET /api/store/profile` menambahkan `lat` dan `lng`.

### 7.5 Admin — ukuran produk

`POST /api/admin/products/{id}/sizes` dan `PUT /api/admin/products/{id}/sizes/{sizeId}` menerima `stock` dan `lowStockThreshold`.

### 7.6 Route web baru

| Method | Path | Guard |
|---|---|---|
| GET | `/owner/dashboard` | owner |
| GET | `/owner/reports` · `/owner/reports/export` | owner |
| GET/POST | `/owner/announcements` | owner |
| POST | `/owner/announcements/{id}/delete` | owner |
| POST | `/admin/announcements/{id}/read` | admin |
| POST | `/admin/products/{id}/sizes/{sizeId}/stock` | admin |

---

## 8. Daftar File Baru & Berubah

### 8.1 Backend — file baru

```
database/migrations/010_add_owner_role.sql
database/migrations/011_add_product_stock.sql
database/migrations/012_create_announcements.sql
database/migrations/013_add_order_coordinates.sql
database/seeds/owner_seeder.sql

src/Owner/Middleware/OwnerAuthMiddleware.php
src/Owner/Controllers/Controller.php            # base: render, redirect, flash, resolveRange
src/Owner/Controllers/DashboardController.php
src/Owner/Controllers/ReportController.php
src/Owner/Controllers/AnnouncementController.php
src/Admin/Controllers/AnnouncementController.php # admin hanya menandai dibaca
src/Infrastructure/Repositories/AnnouncementRepository.php
src/Domain/Order/InsufficientStockException.php

templates/owner/layout.php
templates/owner/dashboard.php
templates/owner/reports/index.php
templates/owner/announcements/index.php
templates/owner/partials/{sidebar,flash,range-filter,bar-chart,donut-chart}.php
templates/admin/partials/announcements.php

public/assets/owner.css
public/assets/owner-map.js
```

### 8.2 Backend — file yang diubah

| File | Perubahan |
|---|---|
| `app/routes.php` | Grup route `/owner/*`, route pengumuman & stok di admin |
| `app/dependencies.php` | Binding `owner.view` + tiga controller owner |
| `app/repositories.php` | Binding `AnnouncementRepository` |
| `src/Infrastructure/Repositories/ProductRepository.php` | Field stok, `decrementStock`, `restoreStock`, `findLowStock` |
| `src/Infrastructure/Repositories/OrderRepository.php` | Potong/kembalikan stok, koordinat, 6 method laporan |
| `src/Admin/Middleware/AdminAuthMiddleware.php` | Owner dialihkan ke panelnya sendiri |
| `src/Admin/Controllers/SessionController.php` | Handoff menerima role owner |
| `src/Admin/Controllers/Controller.php` | Menyuntikkan pemberitahuan belum dibaca ke tiap halaman |
| `src/Admin/Controllers/ProductController.php` | `updateSizeStock()` + field stok saat tambah ukuran |
| `src/Actions/Cart/*` | Validasi stok saat tambah / ubah item / ubah qty |
| `src/Actions/Order/CreateOrderAction.php` | Tangani `InsufficientStockException` → 409 |
| `src/Actions/Admin/{Create,Update}ProductSizeAction.php` | Terima & validasi field stok |
| `src/Actions/Store/GetProfileAction.php` | Kirim `lat` / `lng` |
| `src/Validation/Validator.php` | Rule `floatBetween` (koordinat butuh presisi desimal) |
| `src/Validation/CheckoutValidator.php` | Field `deliveryLat` / `deliveryLng` |
| `templates/admin/layout.php`, `templates/admin/products/index.php` | Banner pemberitahuan, editor stok |
| `public/assets/admin.css` | Gaya banner pemberitahuan & editor stok |
| `.env.example` | `STORE_LAT`, `STORE_LNG`, `SALES_TARGET_DAILY` |

### 8.3 Frontend — file baru

```
src/components/OrderProgress.jsx        # stepper status
src/components/DeliveryMiniMap.jsx      # minimap titik di halaman lacak
src/components/LocationPickerMap.jsx    # pemilih titik antar di checkout
src/utils/mapSetup.js                   # setup Leaflet sekali pakai (ikon, tile, fallback)
src/services/storeApi.js                # GET /store/profile
```

### 8.4 Frontend — file yang diubah

| File | Perubahan |
|---|---|
| `src/models/productModel.js` | Helper stok (`getSizeStock`, `isSizeAvailable`, `getMaxOrderableQuantity`, dll.) |
| `src/models/trackModel.js` | Logika langkah progress & rasio posisi estimasi |
| `src/models/checkoutModel.js` | Koordinat pada payload checkout |
| `src/pages/MenuPage.jsx` | Badge stok + polling 30 detik |
| `src/pages/CustomizeCakePage.jsx` | Stok per ukuran, batas qty, polling 20 detik |
| `src/pages/CartPage.jsx` | Pesan error stok saat ubah qty |
| `src/pages/CheckoutPage.jsx` | Peta pemilih titik antar |
| `src/pages/TrackOrderPage.jsx` | Stepper + minimap (sekaligus membersihkan sisa kode percobaan) |
| `src/pages/LoginPage.jsx`, `src/components/AppLayout.jsx` | Handoff & tombol panel untuk role owner |
| `src/utils/adminHandoff.js` | `isStaffRole()` untuk admin + owner |
| `src/styles/app.css` | Gaya badge stok, stepper, dan peta |
| `package.json` | Dependensi `leaflet` |

**Pola yang dipertahankan:** Leaflet dipakai **langsung** (bukan `react-leaflet`) — instance peta hidup di dalam `useRef`, di luar siklus render React, agar aman dengan React 19 + React Compiler tanpa menambah dependensi. Struktur *context split* 3 file tidak disentuh sama sekali.

---

## 9. Cara Menguji

### 9.1 Role & panel

```bash
composer start   # http://localhost:8080
```

1. Login `owner@hanakacake.com` / `Owner12345` di frontend → otomatis mendarat di `/owner/dashboard`.
2. Login `admin@hanakacake.com` / `Admin12345` → mendarat di `/admin/dashboard`.
3. Sebagai owner, buka `/admin/dashboard` → dialihkan kembali ke `/owner/dashboard` (dan sebaliknya).
4. Buka `/owner/dashboard` tanpa login → dialihkan ke halaman login frontend.

### 9.2 Laporan

1. `/owner/reports?from=2026-08-01&to=2026-08-13` → angka mengikuti rentang.
2. Klik preset *7 hari* / *Bulan ini* → chip aktif ikut berpindah.
3. Klik **Export CSV** → file `laporan-hanaka-<from>-sd-<to>.csv` terunduh (ber-BOM UTF-8 supaya rapi di Excel).

### 9.3 Pemberitahuan

1. Sebagai owner, kirim pemberitahuan di `/owner/announcements`.
2. Buka panel admin → banner muncul di semua halaman.
3. Klik **Tandai dibaca** → banner hilang untuk admin itu saja, dan hitungan "dibaca N admin" di panel owner bertambah.

### 9.4 Stok

1. Di `/admin/products`, set stok ukuran 16 cm Black Forest jadi `2` → **Simpan**.
2. `GET /api/products/black-forest` → ukuran itu mengembalikan `"stock": 2`.
3. Di halaman kustomisasi, tombol `+` berhenti di 2 dan tombol tambah menolak qty 3.
4. Ubah stok jadi `0` di admin → dalam ≤20 detik ukuran itu ter-*disable* di halaman pelanggan tanpa reload.
5. Checkout 2 pcs → stok jadi `0`.
6. Ubah status order jadi `dibatalkan` di admin → stok kembali `2`; batalkan lagi → tetap `2`, tidak dobel.

### 9.5 Progress & peta

1. Checkout mode delivery sambil menggeser pin di peta → order tersimpan dengan koordinat.
2. Buka `/lacak`, masukkan nomor pesanan → stepper sesuai status, peta menampilkan toko + tujuan + titik perkiraan.
3. Ubah status order di admin (`diproses` → `diantar` → `selesai`) lalu lacak ulang → stepper dan titik ikut maju.
4. Lacak order **pickup** atau order lama tanpa koordinat → halaman tetap normal, peta hanya menampilkan toko, tanpa error di konsol.
5. Dashboard owner → titik order delivery muncul, warnanya sesuai status.

### 9.6 Kualitas kode

```bash
# backend
composer test
vendor/bin/phpcs

# frontend
npm run lint
npm run build
```

---

## 10. Batasan & Catatan Jujur

**Titik di peta lacak pesanan adalah estimasi, bukan GPS kurir.**
Sistem belum melacak posisi kurir secara real-time. Titik oranye diinterpolasi lurus antara toko dan alamat tujuan berdasarkan status pesanan (`diproses` ≈ 12%, `diantar` ≈ 60%, `selesai` = 100%). Label di UI menyatakan hal ini terang-terangan agar pelanggan tidak salah paham. Tracking kurir sungguhan tercatat sebagai pekerjaan lanjutan di roadmap `DOCUMENTATION.md`.

**Stok live memakai polling, bukan realtime.**
Jeda 20–30 detik (plus refresh saat tab kembali fokus). Artinya masih mungkin dua pelanggan melihat angka yang sama sebelum salah satunya checkout — dan justru untuk itulah pemotongan stok di level database dibuat *race-safe*: yang kalah mendapat pesan 409 yang jelas, bukan stok minus.

**Peta butuh koneksi internet.**
Tile OpenStreetMap dan (khusus panel owner) Leaflet dari CDN unpkg diambil dari jaringan. Saat demo atau sidang tanpa internet, peta akan kosong sementara stepper progress tetap tampil normal. Bila ini berisiko, pertimbangkan menyalin `leaflet.js`/`leaflet.css` ke `public/assets/` agar setidaknya tidak bergantung CDN.

**Dua hal yang sudah bermasalah sejak sebelum revisi ini** (tidak saya sentuh, tidak disebabkan perubahan ini):

- `composer test` gagal 3 test — test bawaan skeleton Slim yang menguji route `/users`, yang memang tidak pernah ada di `routes.php` project ini.
- `vendor/bin/phpcs` melaporkan error *End of line character is invalid* di seluruh repo karena file memakai CRLF (Windows).

**Folder `dist/` di repo frontend ikut ter-track git**, jadi `npm run build` mengubah file yang dipantau git. Isinya sekarang sudah sesuai source terbaru.

**Password bawaan owner (`Owner12345`) sama polanya dengan admin** — wajib diganti sebelum dipakai di produksi.

---

*Dokumen revisi ini melengkapi `README.md` (setup) dan `DOCUMENTATION.md` (dokumentasi lengkap, sudah diperbarui dengan seluruh perubahan di atas).*

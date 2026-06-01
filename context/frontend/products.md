# Frontend — Products & Catalog

> Data produk, model query, dan cara produk ditampilkan.

---

## Katalog Produk

### 5 Varian Cake

| ID | Nama | Featured | Foto | Gradient |
|---|---|---|---|---|
| `black-forest` | Black Forest Cake | ✅ | `brownies.jpg` | `#8a5a44 → #bc8b73` |
| `red-velvet` | Red Velvet Cake | ✅ | `strawberry-cake.jpg` | `#d38182 → #f0b1a6` |
| `vanilla-cake` | Vanila Cake | ❌ | — (gradient) | `#f7e9d5 → #f3d7bb` |
| `lemon-cake` | Lemon Cake | ❌ | — (gradient) | `#f5e6a3 → #e8d77b` |
| `rainbow-cake` | Rainbow Cake | ❌ | — (gradient) | `#f5a3a3 → #a3d5f5 → #a3f5c4` |

### 4 Ukuran Standar (Semua Produk Sama)

| Size ID | Label | Full Label | Harga |
|---|---|---|---|
| `size-16` | 16 | Ukuran 16 cm | Rp 120.000 |
| `size-18` | 18 | Ukuran 18 cm | Rp 170.000 |
| `size-20` | 20 | Ukuran 20 cm | Rp 220.000 |
| `size-22` | 22 | Ukuran 22 cm | Rp 270.000 |

### Max Message Length
- Semua produk: **60 karakter**

---

## Data Structure

### Product Object
```js
{
  id: 'black-forest',
  name: 'Black Forest Cake',
  shortDescription: '...',          // Untuk card di menu/home
  longDescription: '...',           // Untuk halaman detail
  featured: true,                   // Tampil di best seller
  coverGradient: 'linear-gradient(135deg, #8a5a44 0%, #bc8b73 100%)',
  sizes: [                          // Referensi ke standardSizes
    { id: 'size-16', label: '16', fullLabel: 'Ukuran 16 cm', price: 120000 },
    ...
  ],
  maxMessageLength: 60,
}
```

### Store Profile
```js
{
  name: 'Hanaka Cake',
  address: 'Jl. DR. Sukono Rt 09 No 11, Karang Rejo, Balikpapan Kota, Kalimantan Timur. 76124',
  operationalHours: '07.00 AM - 11.00 PM',
  pickupInfo: 'Pengambilan tersedia setiap hari, 07.00 - 23.00 WITA',
  whatsappNumber: '6281299998888',
  whatsappLabel: '0812-9999-8888',
  instagramHandle: 'hanakacake.id',
}
```

---

## Product Model Functions (`src/models/productModel.js`)

| Function | Return | Keterangan |
|---|---|---|
| `getAllProducts()` | `Product[]` | Semua produk dari katalog |
| `getFeaturedProducts()` | `Product[]` | Produk dengan `featured: true` |
| `findProductById(id)` | `Product \| null` | Cari produk by ID |
| `findSizeOption(product, sizeId)` | `SizeOption \| null` | Cari size dalam produk |
| `getProductStartingPrice(product)` | `number` | Harga termurah (min dari sizes) |
| `calculateUnitPrice(product, sizeId)` | `number` | Harga untuk size tertentu |

---

## Display Logic

### HomePage (Best Seller)
- Filter `featured: true` → tampil di grid "Best Seller"
- Hanya produk dengan foto (`productImages[product.id]`) yang tampil gambar
- Link "See More" → `/menu`

### MenuPage
- Tampilkan semua produk dalam grid
- Panel "Daftar Harga" di atas
- Produk tanpa foto → tampilkan div dengan `coverGradient` sebagai background
- Klik produk → navigate ke `/menu/:productId`

### CustomizeCakePage
- Load produk berdasarkan `useParams().productId`
- Jika produk tidak ditemukan → tampil pesan error + link ke menu
- Produk dengan foto → `<img>`, tanpa foto → gradient div

---

## Image Mapping

```js
const productImages = {
  'black-forest': browniesImg,    // src/assets/brownies.jpg
  'red-velvet': strawberryImg,    // src/assets/strawberry-cake.jpg
}
```

Produk lain (vanilla, lemon, rainbow) belum punya foto → pakai CSS gradient.

---

## File Terkait

- `src/data/products.js` — Data statis (cakeCatalog, standardSizes, storeProfile)
- `src/models/productModel.js` — Query functions
- `src/pages/HomePage.jsx` — Best seller display
- `src/pages/MenuPage.jsx` — Full catalog
- `src/pages/CustomizeCakePage.jsx` — Detail + form
- `src/assets/brownies.jpg` — Foto Black Forest
- `src/assets/strawberry-cake.jpg` — Foto Red Velvet

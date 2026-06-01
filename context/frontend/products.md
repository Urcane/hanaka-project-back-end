# Frontend — Products & Catalog

> Data produk, cara fetch dari API, dan cara produk ditampilkan.
> Terakhir update: 2026-06-01

---

## Status: ✅ Data dari API

Produk sudah fetch dari `GET /api/products` — bukan lagi dari `products.js` hardcoded.

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

### 4 Ukuran per Produk (Size ID include product code)

| Size ID | Label | Harga |
|---|---|---|
| `size-16-bf` | 16 cm | Rp 120.000 |
| `size-18-bf` | 18 cm | Rp 170.000 |
| `size-20-bf` | 20 cm | Rp 220.000 |
| `size-22-bf` | 22 cm | Rp 270.000 |

> Pattern: `size-{cm}-{code}` — `bf` = black-forest, `rv` = red-velvet, `vc` = vanilla-cake, `lc` = lemon-cake, `rc` = rainbow-cake

---

## Data Fetch

```js
// AppContext.jsx — useEffect on mount
fetchProducts()  // GET /api/products
  .then(data => setProducts(data))
  .finally(() => setIsLoadingProducts(false))
```

### Product Object dari API
```js
{
  id: 'black-forest',
  name: 'Black Forest Cake',
  shortDescription: '...',
  longDescription: '...',
  featured: true,
  coverGradient: 'linear-gradient(135deg, #8a5a44 0%, #bc8b73 100%)',
  coverImage: 'brownies.jpg',     // null jika tidak ada foto
  maxMessageLength: 60,
  sizes: [
    { id: 'size-16-bf', label: '16', fullLabel: 'Ukuran 16 cm', price: 120000 },
    { id: 'size-18-bf', label: '18', fullLabel: 'Ukuran 18 cm', price: 170000 },
    ...
  ],
  startingPrice: 120000
}
```

---

## Image Mapping

Backend mengembalikan `coverImage` sebagai string filename (e.g. `"brownies.jpg"`).
Frontend memetakan ke static import:

```js
// src/utils/productImages.js
import browniesImg from '../assets/brownies.jpg'
import strawberryImg from '../assets/strawberry-cake.jpg'

export const productImages = {
  'black-forest': browniesImg,
  'red-velvet': strawberryImg,
}
```

Produk tanpa foto → tampilkan div dengan CSS `coverGradient`.

---

## Product Model Functions (`src/models/productModel.js`)

| Function | Keterangan |
|---|---|
| `getFeaturedProducts(products)` | Filter `featured: true` dari array |
| `findProductById(products, id)` | Cari produk by ID |
| `findSizeOption(product, sizeId)` | Cari size dalam produk |
| `getProductStartingPrice(product)` | Harga termurah |
| `calculateUnitPrice(product, sizeId)` | Harga untuk size tertentu |

---

## File Terkait

- `src/services/productsApi.js` — `fetchProducts()`, `fetchProductById()`
- `src/models/productModel.js` — Query & filter functions
- `src/utils/productImages.js` — Mapping coverImage filename → static import
- `src/pages/HomePage.jsx` — Best seller (featured products)
- `src/pages/MenuPage.jsx` — Full catalog
- `src/pages/CustomizeCakePage.jsx` — Detail + form kustomisasi

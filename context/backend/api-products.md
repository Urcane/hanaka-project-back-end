# Backend — Products API

> Endpoint untuk mengambil data produk dan katalog.

---

## Endpoints

### GET `/api/products`

List semua produk (dengan sizes).

**Query Parameters:**
| Param | Type | Default | Keterangan |
|---|---|---|---|
| `featured` | boolean | — | Filter hanya produk featured |

**Response (200):**
```json
{
  "ok": true,
  "products": [
    {
      "id": "black-forest",
      "name": "Black Forest Cake",
      "shortDescription": "Manis, lembut...",
      "featured": true,
      "coverGradient": "linear-gradient(135deg, #8a5a44 0%, #bc8b73 100%)",
      "coverImage": "/uploads/products/brownies.jpg",
      "sizes": [
        { "id": "size-16-bf", "label": "16", "fullLabel": "Ukuran 16 cm", "price": 120000 },
        { "id": "size-18-bf", "label": "18", "fullLabel": "Ukuran 18 cm", "price": 170000 },
        { "id": "size-20-bf", "label": "20", "fullLabel": "Ukuran 20 cm", "price": 220000 },
        { "id": "size-22-bf", "label": "22", "fullLabel": "Ukuran 22 cm", "price": 270000 }
      ],
      "startingPrice": 120000
    },
    ...
  ]
}
```

**SQL Query:**
```sql
SELECT p.*, ps.id as size_id, ps.label, ps.full_label, ps.price
FROM products p
LEFT JOIN product_sizes ps ON ps.product_id = p.id
WHERE (:featured IS NULL OR p.featured = :featured)
ORDER BY p.featured DESC, p.name ASC
```

---

### GET `/api/products/:productId`

Detail satu produk.

**Response (200):**
```json
{
  "ok": true,
  "product": {
    "id": "black-forest",
    "name": "Black Forest Cake",
    "shortDescription": "Manis, lembut...",
    "longDescription": "Tekstur lembut dan rasa cokelat...",
    "featured": true,
    "coverGradient": "linear-gradient(135deg, #8a5a44 0%, #bc8b73 100%)",
    "coverImage": "/uploads/products/brownies.jpg",
    "maxMessageLength": 60,
    "sizes": [
      { "id": "size-16-bf", "label": "16", "fullLabel": "Ukuran 16 cm", "price": 120000 },
      { "id": "size-18-bf", "label": "18", "fullLabel": "Ukuran 18 cm", "price": 170000 },
      { "id": "size-20-bf", "label": "20", "fullLabel": "Ukuran 20 cm", "price": 220000 },
      { "id": "size-22-bf", "label": "22", "fullLabel": "Ukuran 22 cm", "price": 270000 }
    ]
  }
}
```

**Error (404):**
```json
{
  "ok": false,
  "error": "Produk tidak ditemukan."
}
```

---

## Response Field Mapping

| Frontend Field | Backend Field | DB Column |
|---|---|---|
| `id` | `id` | `products.id` |
| `name` | `name` | `products.name` |
| `shortDescription` | `shortDescription` | `products.short_description` |
| `longDescription` | `longDescription` | `products.long_description` |
| `featured` | `featured` | `products.featured` |
| `coverGradient` | `coverGradient` | `products.cover_gradient` |
| `coverImage` | `coverImage` | `products.cover_image` |
| `maxMessageLength` | `maxMessageLength` | `products.max_message_length` |
| `sizes[].id` | `sizes[].id` | `product_sizes.id` |
| `sizes[].label` | `sizes[].label` | `product_sizes.label` |
| `sizes[].fullLabel` | `sizes[].fullLabel` | `product_sizes.full_label` |
| `sizes[].price` | `sizes[].price` | `product_sizes.price` |

---

## Notes

- Endpoint ini **public** (tidak perlu authentication)
- Response camelCase (convert dari snake_case SQL)
- `coverImage` nullable — jika null, frontend pakai `coverGradient`
- `startingPrice` dihitung sebagai `MIN(sizes[].price)` — convenience field
- Product sizes selalu di-include dalam response (JOIN)

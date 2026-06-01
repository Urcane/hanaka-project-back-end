# Backend — Cart API

> Endpoint untuk keranjang belanja: CRUD item, update quantity.

---

## Cart Identity

- **Logged-in user**: Cart diidentifikasi via `user_id` dari JWT token
- **Guest**: Cart diidentifikasi via `session_token` (dari cookie atau header)
- Saat login/register, guest cart di-merge ke user cart

---

## Endpoints

### GET `/api/cart`

Ambil cart items untuk user/session saat ini.

**Headers:** `Authorization: Bearer <token>` (opsional — guest pakai session)

**Response (200):**
```json
{
  "ok": true,
  "items": [
    {
      "id": "cart_m1n2o3p4",
      "productId": "black-forest",
      "productName": "Black Forest Cake",
      "productDescription": "Manis, lembut...",
      "productGradient": "linear-gradient(...)",
      "size": {
        "id": "size-18-bf",
        "label": "18",
        "price": 170000
      },
      "colorText": "Merah Muda",
      "theme": "Roblox",
      "message": "Happy Birthday",
      "quantity": 2,
      "unitPrice": 170000,
      "totalPrice": 340000
    }
  ],
  "subtotal": 340000,
  "itemCount": 2
}
```

---

### POST `/api/cart/items`

Tambah item baru ke cart.

**Request:**
```json
{
  "productId": "black-forest",
  "sizeId": "size-18-bf",
  "colorText": "Merah Muda",
  "theme": "Roblox",
  "message": "Happy Birthday",
  "quantity": 2
}
```

**Validation:**
| Field | Rules |
|---|---|
| productId | required, must exist in products table |
| sizeId | required, must belong to product |
| colorText | required, maxLength(40) |
| theme | maxLength(40) |
| message | maxLength(product.max_message_length) |
| quantity | required, numeric, min(1), max(5) |

**Success Response (201):**
```json
{
  "ok": true,
  "item": {
    "id": "cart_newid123",
    "productId": "black-forest",
    "productName": "Black Forest Cake",
    ...
    "totalPrice": 340000
  }
}
```

**Error (400):**
```json
{
  "ok": false,
  "error": "Produk tidak ditemukan.",
  "errors": {
    "sizeId": "Ukuran cake tidak valid.",
    "colorText": "Warna kue wajib diisi."
  }
}
```

---

### PUT `/api/cart/items/:itemId`

Update seluruh data item (edit pesanan).

**Request:**
```json
{
  "sizeId": "size-20-bf",
  "colorText": "Biru Langit",
  "theme": "Frozen",
  "message": "Selamat Ulang Tahun",
  "quantity": 1
}
```

**Validation:** Sama seperti POST (kecuali productId tidak berubah).

**Success Response (200):**
```json
{
  "ok": true,
  "item": { ... }
}
```

---

### PATCH `/api/cart/items/:itemId/quantity`

Update quantity saja (dari tombol +/-).

**Request:**
```json
{
  "quantity": 3
}
```

**Validation:** numeric, min(1), max(5)

**Success Response (200):**
```json
{
  "ok": true,
  "item": { ... }
}
```

---

### DELETE `/api/cart/items/:itemId`

Hapus satu item dari cart.

**Response (200):**
```json
{
  "ok": true
}
```

---

### DELETE `/api/cart`

Clear semua items (dipanggil setelah order berhasil).

**Response (200):**
```json
{
  "ok": true
}
```

---

## Cart Merge (Internal — saat login/register)

Logic saat user login:
1. Cari cart by `session_token` (guest cart)
2. Cari cart by `user_id` (user cart)
3. Jika guest cart ada items:
   - Pindahkan semua items ke user cart
   - Hapus guest cart
4. Hapus session token cookie

```php
// Pseudo-code
function mergeGuestCart(string $sessionToken, string $userId): void {
    $guestCart = $this->cartRepo->findBySession($sessionToken);
    if (!$guestCart || empty($guestCart->items)) return;

    $userCart = $this->cartRepo->findOrCreateByUser($userId);

    foreach ($guestCart->items as $item) {
        $item->cart_id = $userCart->id;
        $this->cartItemRepo->save($item);
    }

    $this->cartRepo->delete($guestCart->id);
}
```

---

## Access Control

- User hanya bisa akses cart miliknya
- Guest hanya bisa akses cart dengan session_token yang sesuai
- Item yang di-PUT/PATCH/DELETE harus belong to user's cart

---

## Notes

- Cart dibuat secara lazy (create on first item add)
- `productName`, `productDescription`, `productGradient` diambil dari product table saat response (tidak disimpan di cart_items)
- `unitPrice` dan `totalPrice` dihitung dari `product_sizes.price * quantity`
- Response selalu include `subtotal` dan `itemCount` untuk convenience

# Frontend — Cart (Keranjang)

> Logic keranjang belanja: add, edit, remove, quantity, guest/user.
> Terakhir update: 2026-06-01

---

## Status: ✅ Data dari API

Cart sudah pakai backend API — tidak ada lagi `cartsByUser` di localStorage.

---

## Cart Item Structure (dari API response)

```js
{
  id: "cart_bd83466f",
  productId: "black-forest",
  productName: "Black Forest Cake",
  productDescription: "Manis, lembut...",
  productGradient: "linear-gradient(...)",
  productImage: "brownies.jpg",        // null jika tidak ada
  size: {
    id: "size-18-bf",
    label: "18",
    fullLabel: "Ukuran 18 cm",
    price: 170000,
  },
  colorText: "Merah Muda",
  theme: "Ulang Tahun",
  message: "Happy Birthday Mama!",
  quantity: 1,
  unitPrice: 170000,
  totalPrice: 170000,
}
```

---

## State di Context

```js
cartItems       // array cart item dari GET /api/cart
cartItemCount   // total quantity (bukan jumlah item unik)
cartSubtotal    // total harga
isCartLoading   // loading pada mount
```

Cart di-refresh via `refreshCart()` setelah setiap operasi (add/edit/remove/qty).

---

## Cart Operations

### Add to Cart
```
CustomizeCakePage → form submit
  ↓ validateCustomizationInput(product, values) ← client-side
  ↓ POST /api/cart/items
    { productId, sizeId, colorText, theme, message, quantity }
  ↓ jika guest baru: response.sessionToken → setSessionToken() → localStorage
  ↓ refreshCart() → GET /api/cart
  ↓ navigate('/cart')
```

### Edit Cart Item
```
CartPage → klik "Edit Pesanan"
  ↓ navigate('/menu/:productId?edit=:cartItemId')
  ↓ CustomizeCakePage load existing data
  ↓ PUT /api/cart/items/:id
    { sizeId, colorText, theme, message, quantity }
  ↓ refreshCart()
  ↓ navigate('/cart')
```

### Update Quantity
```
CartPage → klik +/−
  ↓ PATCH /api/cart/items/:id/quantity
    { quantity }
  ↓ refreshCart()
```

### Remove Item
```
CartPage → klik hapus
  ↓ DELETE /api/cart/items/:id
  ↓ refreshCart()
```

### Clear Cart
```
(dipanggil setelah placeOrder)
  ↓ DELETE /api/cart
  ↓ setCartItems([]), setCartSubtotal(0), setCartItemCount(0)
```

---

## Guest Cart

Guest diidentifikasi via `X-Session-Token` header (dikirim otomatis oleh `apiService.js`).
Session token digenerate backend saat pertama kali add to cart dan dikembalikan di response.
Token disimpan di localStorage (`hanaka_session_token`).

Saat login/register → backend auto-merge guest cart ke user cart.

---

## Validation (Customization Form — client-side)

| Field | Rules |
|---|---|
| `sizeId` | required, oneOf(product.sizes.map(s => s.id)) |
| `colorText` | required, maxLength(40) |
| `theme` | maxLength(40) — opsional |
| `quantity` | required, numeric, min(1), max(5) |
| `message` | maxLength(product.maxMessageLength) — opsional |

---

## File Terkait

- `src/services/cartApi.js` — `apiFetchCart`, `apiAddCartItem`, `apiUpdateCartItem`, `apiUpdateCartItemQuantity`, `apiRemoveCartItem`, `apiClearCart`
- `src/models/cartModel.js` — `validateCustomizationInput`, `computeCartSubtotal`
- `src/context/AppContext.jsx` — `addToCart`, `editCartItem`, `updateCartQuantity`, `removeCartItem`, `clearCart`, `refreshCart`
- `src/pages/CartPage.jsx` — Cart display & interactions
- `src/pages/CustomizeCakePage.jsx` — Add/edit form

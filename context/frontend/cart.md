# Frontend — Cart (Keranjang)

> Logic keranjang belanja: add, edit, remove, quantity, guest/user separation.

---

## Overview

- Cart **per-user**: setiap user (atau guest) punya cart sendiri
- Guest cart key: `__guest__`
- User cart key: `userId` (e.g. `usr_a1b2c3d4`)
- Quantity range: **1–5** per item
- Cart di-persist ke localStorage via `cartsByUser` object

---

## Cart Item Structure

```js
{
  id: "cart_x1y2z3w4",              // createId('cart')
  productId: "black-forest",
  productName: "Black Forest Cake",
  productDescription: "Manis, lembut...",
  productGradient: "linear-gradient(...)",
  size: {
    id: "size-18",
    label: "18",
    price: 170000,
  },
  colorText: "Merah Muda",          // Warna kue
  theme: "Roblox",                  // Tema kue (opsional)
  message: "Happy Birthday",        // Catatan tambahan
  quantity: 2,                      // 1–5
  unitPrice: 170000,                // = size.price
  totalPrice: 340000,               // = unitPrice × quantity
}
```

---

## Cart Operations

### Add to Cart
```
CustomizeCakePage → form submit
  ↓
validateCustomizationInput(product, values)    ← cartModel.js
  ↓ jika valid
addToCart(payload) di context
  ↓
resolveCustomization(payload)                  ← cek product & size valid
  ↓
buildCartItem({product, sizeOption, colorText, theme, message, quantity})
  ↓
append ke cartsByUser[activeCartKey]
  ↓
navigate('/cart')
```

### Edit Cart Item
```
CartPage → klik "Edit Pesanan"
  ↓
navigate('/menu/:productId?edit=:cartItemId')
  ↓
CustomizeCakePage loads with editingItem
  ↓ form pre-filled with existing data
form submit → editCartItem(itemId, payload)
  ↓
rebuildCartItem(existingItem, {sizeOption, colorText, theme, message, quantity})
  ↓
replace item in cartsByUser[activeCartKey]
  ↓
navigate('/cart')
```

### Update Quantity
```
CartPage → klik ＋/－
  ↓
updateCartQuantity(itemId, newQuantity)
  ↓
updateCartItemQuantity(item, quantity) → clamp 1-5, recalculate totalPrice
```

### Remove Item
```
CartPage → klik 🗑
  ↓
removeCartItem(itemId) → filter out dari array
```

### Clear Cart
```
(called internally setelah placeOrder)
clearCart() → set cartsByUser[activeCartKey] = []
```

---

## Validation (Customization Form)

| Field | Rules |
|---|---|
| `sizeId` | required, oneOf(product.sizes.map(s => s.id)) |
| `colorText` | required, maxLength(40) |
| `theme` | maxLength(40) — opsional |
| `quantity` | required, numeric, min(1), max(5) |
| `message` | maxLength(product.maxMessageLength) — opsional |

---

## Computed Values

| Computed | Formula | Sumber |
|---|---|---|
| `cartItems` | `cartsByUser[activeCartKey] ?? []` | Context |
| `cartItemCount` | `cartItems.reduce((c, item) => c + item.quantity, 0)` | Context |
| `cartSubtotal` | `cartItems.reduce((t, item) => t + item.totalPrice, 0)` | cartModel |

---

## CartPage UI

- Tabel: Items / QTY / Subtotal (3 kolom, responsive jadi 1 kolom di mobile)
- Setiap item menampilkan: nama, size, warna, tema, catatan, "Edit Pesanan" link
- Qty stepper (＋/－) per item
- Subtotal per item
- Tombol hapus (🗑) per item
- Fulfillment toggle (Pickup / Delivery) → menentukan query param di URL checkout
- Total bar: Total harga + tombol "Bayar" → navigate ke `/checkout?mode=pickup|delivery`

---

## File Terkait

- `src/models/cartModel.js` — `validateCustomizationInput`, `buildCartItem`, `rebuildCartItem`, `updateCartItemQuantity`, `computeCartSubtotal`
- `src/context/AppContext.jsx` — `addToCart`, `editCartItem`, `updateCartQuantity`, `removeCartItem`, `clearCart`
- `src/pages/CartPage.jsx` — Cart display & interactions
- `src/pages/CustomizeCakePage.jsx` — Add/edit form

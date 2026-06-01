# Frontend — Routing

> Definisi semua route dan navigation flow.

---

## Route Table

| Path | Component | Guard | Keterangan |
|---|---|---|---|
| `/` | `HomePage` | — | Landing page (hero + best seller) |
| `/home` | redirect → `/` | — | Alias |
| `/menu` | `MenuPage` | — | Katalog semua varian cake |
| `/menu/:productId` | `CustomizeCakePage` | — | Form kustomisasi + edit cart item |
| `/cart` | `CartPage` | — | Keranjang belanja |
| `/checkout` | `CheckoutPage` | — | Form checkout (`?mode=pickup\|delivery`) |
| `/payment/:orderId` | `PaymentQrisPage` | — | Halaman QRIS payment |
| `/orders` | `OrderHistoryPage` | `ProtectedRoute` | Riwayat order (harus login) |
| `/login` | `LoginPage` | `GuestRoute` | Login (redirect jika sudah login) |
| `/register` | `RegisterPage` | `GuestRoute` | Register (redirect jika sudah login) |
| `*` | redirect → `/` | — | Catch-all 404 |

---

## Route Guards

### `ProtectedRoute`
- Cek `currentUser` dari context
- Jika null → redirect ke `/login` dengan `state.redirectTo` (agar setelah login kembali ke halaman asal)

### `GuestRoute`
- Cek `currentUser` dari context
- Jika ada user → redirect ke `/`

---

## Layout Structure

```
<BrowserRouter>
  <AppProvider>
    <Routes>
      <Route element={<AppLayout />}>     ← Shared layout (header + nav + footer)
        <Route path="/" ... />
        <Route path="/menu" ... />
        <Route path="/menu/:productId" ... />
        <Route path="/cart" ... />
        <Route path="/checkout" ... />
        <Route path="/payment/:orderId" ... />
        <Route path="/orders" ... />       ← Wrapped in ProtectedRoute
      </Route>

      <Route path="/login" ... />          ← GuestRoute, NO AppLayout
      <Route path="/register" ... />       ← GuestRoute, NO AppLayout
      <Route path="*" redirect />
    </Routes>
  </AppProvider>
</BrowserRouter>
```

---

## Navigation Flow

```
HomePage → MenuPage → CustomizeCakePage → CartPage → CheckoutPage → PaymentQrisPage
                                              ↑                           ↓
                                              └─── edit item ────────────┘
                                                                         ↓
                                                                  OrderHistoryPage
```

### Login/Register Flow
```
Any page → /login → (redirect to original page)
                  ↕
              /register
```

### Query Parameters

| Route | Param | Fungsi |
|---|---|---|
| `/menu/:productId` | `?edit=:cartItemId` | Load data item untuk diedit |
| `/checkout` | `?mode=pickup\|delivery` | Pilihan fulfillment dari CartPage |

---

## File Terkait

- `src/App.jsx` — Route definitions
- `src/components/AppLayout.jsx` — Shared layout shell
- `src/components/ProtectedRoute.jsx` — Auth guard
- `src/components/GuestRoute.jsx` — Guest guard

# Frontend — Routing

> Definisi semua route dan navigation flow.
> Terakhir update: 2026-06-01

---

## Route Table

### Customer Routes

| Path | Component | Guard | Keterangan |
|---|---|---|---|
| `/` | `HomePage` | — | Landing page (hero + best seller) |
| `/home` | redirect → `/` | — | Alias |
| `/menu` | `MenuPage` | — | Katalog semua varian cake |
| `/menu/:productId` | `CustomizeCakePage` | — | Form kustomisasi + edit cart item |
| `/cart` | `CartPage` | — | Keranjang belanja |
| `/checkout` | `CheckoutPage` | — | Form checkout (`?mode=pickup\|delivery`) |
| `/payment/:orderId` | `PaymentQrisPage` | — | QRIS payment (Midtrans) |
| `/orders` | `OrderHistoryPage` | `ProtectedRoute` | Riwayat order (harus login) |
| `/login` | `LoginPage` | `GuestRoute` | Login (redirect jika sudah login) |
| `/register` | `RegisterPage` | `GuestRoute` | Register (redirect jika sudah login) |
| `*` | redirect → `/` | — | Catch-all |

### Admin Routes

| Path | Component | Guard | Keterangan |
|---|---|---|---|
| `/admin` | redirect → `/admin/dashboard` | `AdminRoute` | — |
| `/admin/dashboard` | `AdminDashboardPage` | `AdminRoute` | Statistik & ringkasan |
| `/admin/orders` | `AdminOrdersPage` | `AdminRoute` | List semua order |
| `/admin/orders/:orderId` | `AdminOrderDetailPage` | `AdminRoute` | Detail order + update status |
| `/admin/products` | `AdminProductsPage` | `AdminRoute` | CRUD produk & ukuran |
| `/admin/customers` | `AdminCustomersPage` | `AdminRoute` | List customer |

---

## Route Guards

### `ProtectedRoute`
- Cek `currentUser` dari context (tunggu `isAuthLoading`)
- Jika null → redirect ke `/login` dengan `state.redirectTo`

### `GuestRoute`
- Cek `currentUser` dari context
- Jika ada user → redirect ke `/`

### `AdminRoute`
- Cek `currentUser?.role === 'admin'`
- Jika bukan admin → redirect ke `/`
- Selama `isAuthLoading` → tampil loading

---

## Layout Structure

```
<BrowserRouter>
  <AppProvider>
    <Routes>

      {/* Customer layout */}
      <Route element={<AppLayout />}>
        <Route path="/" />
        <Route path="/menu" />
        <Route path="/menu/:productId" />
        <Route path="/cart" />
        <Route path="/checkout" />
        <Route path="/payment/:orderId" />
        <Route path="/orders" />   ← ProtectedRoute
      </Route>

      {/* Admin layout */}
      <Route path="/admin" element={<AdminRoute><AdminLayout /></AdminRoute>}>
        <Route index redirect /admin/dashboard />
        <Route path="dashboard" />
        <Route path="orders" />
        <Route path="orders/:orderId" />
        <Route path="products" />
        <Route path="customers" />
      </Route>

      {/* Auth — no layout */}
      <Route path="/login" />    ← GuestRoute
      <Route path="/register" /> ← GuestRoute
      <Route path="*" redirect />

    </Routes>
  </AppProvider>
</BrowserRouter>
```

---

## Query Parameters

| Route | Param | Fungsi |
|---|---|---|
| `/menu/:productId` | `?edit=:cartItemId` | Load data item untuk diedit |
| `/checkout` | `?mode=pickup\|delivery` | Pilihan fulfillment dari CartPage |

---

## File Terkait

- `src/App.jsx` — Route definitions
- `src/components/AppLayout.jsx` — Customer layout shell
- `src/components/AdminLayout.jsx` — Admin layout shell
- `src/components/ProtectedRoute.jsx` — Auth guard
- `src/components/GuestRoute.jsx` — Guest guard
- `src/components/AdminRoute.jsx` — Admin guard

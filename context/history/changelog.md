# Development Changelog

> Sprint-by-sprint log perkembangan project.

---

## Fase 1 — Frontend MVP (Mei 2026)

### Sprint 1: Project Setup & Auth (Minggu 1-2)

| Task | Status | Catatan |
|---|---|---|
| Setup React 19 + Vite 8 | ✅ | Termasuk React Compiler |
| ESLint configuration | ✅ | Flat config, react-hooks + react-refresh |
| Routing setup (React Router v7) | ✅ | 9 routes + catch-all |
| AppLayout (header, nav, footer) | ✅ | Responsive |
| Login page + validasi | ✅ | Email + password |
| Register page + validasi | ✅ | 5 fields + confirmPassword |
| Custom validation framework | ✅ | Schema-based, conditional |
| Auth model (build account, validate) | ✅ | — |
| Guest/Protected route guards | ✅ | Redirect logic |
| Context + state management | ✅ | Context split pattern (3 file) |
| localStorage persistence | ✅ | storageService abstraction |

### Sprint 2: Product & Cart (Minggu 2-3)

| Task | Status | Catatan |
|---|---|---|
| Katalog produk (5 varian cake) | ✅ | Data statis di products.js |
| HomePage (landing + best seller) | ✅ | Hero banner + featured products |
| MenuPage (daftar produk + harga) | ✅ | Grid layout + price list panel |
| CustomizeCakePage | ✅ | Size, warna, tema, message, quantity |
| Cart model (build, rebuild, update qty) | ✅ | Immutable pattern |
| CartPage | ✅ | CRUD + fulfillment toggle |
| Edit cart item via URL param | ✅ | `/menu/:id?edit=:cartItemId` |
| Guest cart + merge saat login | ✅ | Key `__guest__` |

### Sprint 3: Checkout & Payment (Minggu 3-4)

| Task | Status | Catatan |
|---|---|---|
| CheckoutPage (form + validasi) | ✅ | Pickup/delivery conditional fields |
| Checkout model + payload builder | ✅ | — |
| Order model (create, mark paid) | ✅ | Order number format HNK-xxx |
| Payment QRIS page | ✅ | QR code via `qrcode` library |
| OrderHistoryPage | ✅ | Protected route, sorted by date |
| Guest checkout support | ✅ | Tanpa login, bisa order |
| Styling & responsive | ✅ | Mobile breakpoint 900px |
| SiteFooter (info toko) | ✅ | Alamat, WA, Instagram |

---

## Fase 2 — Backend MVP (Mei 2026)

### Sprint 4: Backend Foundation

| Task | Status | Catatan |
|---|---|---|
| Setup Slim PHP 4 + Composer | ✅ | slim/slim-skeleton base |
| Environment config (phpdotenv) | ✅ | .env + .env.example |
| MySQL database schema | ✅ | 7 migration files |
| Seed data produk | ✅ | 5 produk + 20 sizes |
| Migration runner script | ✅ | database/migrate.php --seed |
| CORS middleware | ✅ | Whitelist origin |
| JWT middleware (extract & verify) | ✅ | firebase/php-jwt |
| Auth required middleware | ✅ | Route-level guard |
| Security headers middleware | ✅ | X-Content-Type, X-Frame, X-XSS |
| Base action class | ✅ | JSON response helpers |
| Session service | ✅ | Guest session token |

### Sprint 5: Auth & Products API

| Task | Status | Catatan |
|---|---|---|
| POST /api/auth/register | ✅ | bcrypt cost 12 + JWT + cart merge |
| POST /api/auth/login | ✅ | password_verify + JWT + cart merge |
| POST /api/auth/logout | ✅ | Stateless (client discards token) |
| GET /api/auth/me | ✅ | Auth required |
| GET /api/products | ✅ | Featured filter + sizes included |
| GET /api/products/:id | ✅ | Detail + sizes + startingPrice |
| Validation layer | ✅ | Schema-based, mirrors frontend |
| GET /api/store/profile | ✅ | Store info from env |

### Sprint 6: Cart & Order API

| Task | Status | Catatan |
|---|---|---|
| GET /api/cart | ✅ | User + guest session |
| POST /api/cart/items | ✅ | Product/size validation |
| PUT /api/cart/items/:id | ✅ | Full item update |
| PATCH /api/cart/items/:id/quantity | ✅ | Quantity only |
| DELETE /api/cart/items/:id | ✅ | Access control |
| DELETE /api/cart | ✅ | Clear all items |
| POST /api/orders | ✅ | Validate + snapshot + clear cart |
| GET /api/orders | ✅ | Per-user, sorted desc, auth required |
| GET /api/orders/:id | ✅ | User + guest access control |
| PATCH /api/orders/:id/pay | ✅ | Mark QRIS as paid |
| POST /api/payments/qris | ✅ | Generate QR string (simulasi) |
| Cart merge (guest → user) | ✅ | Saat login/register |

### Sprint 7: Admin Backend

| Task | Status | Catatan |
|---|---|---|
| Role-based auth (customer/admin) | ✅ | ENUM column on users table |
| Admin middleware | ✅ | 403 for non-admin |
| JWT includes role | ✅ | createToken(userId, email, role) |
| Admin seeder | ✅ | admin@hanakacake.com / Admin12345 |
| GET /api/admin/dashboard | ✅ | Stats: orders, revenue, customers |
| GET /api/admin/orders | ✅ | All orders + filter + pagination |
| GET /api/admin/orders/:id | ✅ | Detail order (admin view) |
| PATCH /api/admin/orders/:id/status | ✅ | Update order status |
| PATCH /api/admin/orders/:id/payment-status | ✅ | Update payment status |
| GET /api/admin/customers | ✅ | List all customers |
| POST /api/admin/products | ✅ | Create product + sizes |
| PUT /api/admin/products/:id | ✅ | Update product |
| DELETE /api/admin/products/:id | ✅ | Delete product + sizes |
| POST /api/admin/products/:id/sizes | ✅ | Add size to product |
| PUT /api/admin/products/:id/sizes/:sizeId | ✅ | Update size |
| DELETE /api/admin/products/:id/sizes/:sizeId | ✅ | Delete size |
| Login & Me return role field | ✅ | role: 'customer' or 'admin' |
| Register always creates customer | ✅ | Cannot register as admin |

### Sprint 8: Payment & Polish (Planned)

| Task | Status | Catatan |
|---|---|---|
| QRIS payment gateway | ⬜ | Midtrans/Xendit integration |
| Payment webhook | ⬜ | Auto update status |
| Rate limiting | ⬜ | Auth endpoints |
| API documentation | ⬜ | Postman collection |

### Sprint 9: Frontend-Backend Integration

| Task | Status | Catatan |
|---|---|---|
| Buat apiService.js | ⬜ | Wrapper fetch + JWT |
| Ganti localStorage ke API | ⬜ | All CRUD |
| Loading states & skeletons | ⬜ | UX improvement |
| Error boundary | ⬜ | Network errors |
| End-to-end testing | ⬜ | — |

---

## Fase 3 — Enhancement (Future)

### Sprint 9+

| Task | Status | Catatan |
|---|---|---|
| Admin dashboard | ⬜ | Order management |
| Image upload | ⬜ | Foto produk |
| Email notification | ⬜ | Order confirmation |
| WhatsApp notification | ⬜ | Via API |
| Search & filter produk | ⬜ | — |
| PWA support | ⬜ | Offline + install |

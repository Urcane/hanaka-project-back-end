# Development Changelog

> Sprint-by-sprint log perkembangan project.

---

## Fase 1 — Frontend MVP (Mei 2026)

### Sprint 1: Project Setup & Auth

| Task | Status |
|---|---|
| Setup React 19 + Vite 8 + React Compiler | ✅ |
| ESLint flat config (react-hooks + react-refresh) | ✅ |
| Routing setup (React Router v7) | ✅ |
| AppLayout (header, nav, footer) | ✅ |
| Login + Register page + validasi | ✅ |
| Custom validation framework (no library) | ✅ |
| Context split pattern (3 file — ESLint compliance) | ✅ |
| Guest/Protected route guards | ✅ |

### Sprint 2: Product & Cart

| Task | Status |
|---|---|
| Katalog produk (5 varian cake) | ✅ |
| HomePage (landing + best seller) | ✅ |
| MenuPage + CustomizeCakePage | ✅ |
| CartPage — full CRUD | ✅ |
| Edit cart item via URL param | ✅ |
| Guest cart + merge saat login | ✅ |

### Sprint 3: Checkout & Payment (Simulasi)

| Task | Status |
|---|---|
| CheckoutPage (pickup/delivery, validasi) | ✅ |
| Order model (create, mark paid) | ✅ |
| Payment QRIS page (simulasi QR string lokal) | ✅ |
| OrderHistoryPage (protected) | ✅ |
| Guest checkout support | ✅ |
| Responsive design (900px breakpoint) | ✅ |

---

## Fase 2 — Backend MVP (Mei 2026)

### Sprint 4: Backend Foundation

| Task | Status |
|---|---|
| Setup Slim PHP 4 + Composer (slim-skeleton) | ✅ |
| Environment config (phpdotenv) | ✅ |
| MySQL schema — 7 migration files | ✅ |
| Seed data (5 produk + 20 sizes + admin) | ✅ |
| CORS middleware (whitelist origin) | ✅ |
| JWT middleware (extract & verify token) | ✅ |
| Auth required middleware | ✅ |
| Security headers middleware | ✅ |
| Base action class (JSON response helpers) | ✅ |
| Session service (guest X-Session-Token) | ✅ |

### Sprint 5: Auth & Products API

| Task | Status |
|---|---|
| POST /api/auth/register (bcrypt + JWT + cart merge) | ✅ |
| POST /api/auth/login (password_verify + JWT) | ✅ |
| POST /api/auth/logout | ✅ |
| GET /api/auth/me | ✅ |
| GET /api/products (featured filter + sizes) | ✅ |
| GET /api/products/:id | ✅ |
| GET /api/store/profile | ✅ |
| Validation layer (schema-based, mirror frontend) | ✅ |

### Sprint 6: Cart & Order API

| Task | Status |
|---|---|
| GET/POST/PUT/PATCH/DELETE /api/cart/* | ✅ |
| POST /api/orders (validate + snapshot + clear cart) | ✅ |
| GET /api/orders (per-user, sorted, auth required) | ✅ |
| GET /api/orders/:id (user + guest access control) | ✅ |
| PATCH /api/orders/:id/pay (legacy — lihat issues) | ✅ |
| Cart merge guest → user | ✅ |

### Sprint 7: Admin Backend

| Task | Status |
|---|---|
| Role-based auth (customer/admin ENUM) | ✅ |
| Admin middleware (403 non-admin) | ✅ |
| JWT includes role | ✅ |
| GET /api/admin/dashboard (stats) | ✅ |
| GET/PATCH /api/admin/orders/* | ✅ |
| GET /api/admin/customers | ✅ |
| POST/PUT/DELETE /api/admin/products/* | ✅ |
| POST/PUT/DELETE /api/admin/products/:id/sizes/* | ✅ |

---

## Fase 3 — Frontend-Backend Integration (Mei–Juni 2026)

### Sprint 8: Full Integration

| Task | Status |
|---|---|
| apiService.js (fetch wrapper + JWT + session token) | ✅ |
| authApi.js + auth restore on mount (GET /me) | ✅ |
| productsApi.js — ganti hardcoded data | ✅ |
| cartApi.js — full CRUD + session token handling | ✅ |
| ordersApi.js — place, list, detail, mark paid | ✅ |
| paymentApi.js — create QRIS + check status | ✅ |
| adminApi.js — full admin endpoints | ✅ |
| AppContext.jsx — semua state dari API | ✅ |
| Admin pages (dashboard, orders, products, customers) | ✅ |

---

## Fase 4 — Midtrans QRIS Integration (Juni 2026)

### Sprint 9: Real Payment Gateway

| Task | Status | Catatan |
|---|---|---|
| MidtransService (charge, status, signature, tz-safe expiry) | ✅ | `src/Infrastructure/Services/MidtransService.php` |
| Migration 009 (payment fields di orders) | ✅ | payment_provider, qr_string, qr_url, payment_expires_at, enum expired/failed |
| GenerateQrisAction rewrite (Midtrans + reuse valid QR) | ✅ | Tidak double-charge jika QR masih valid |
| PaymentStatusAction (live poll ke Midtrans) | ✅ | `GET /api/payments/qris/status` |
| PaymentWebhookAction (signature verified, always 200) | ✅ | `POST /api/payments/webhook` |
| Routes + DI wiring | ✅ | — |
| Timezone fix (parseExpiry: WIB→UTC, emit ISO-8601+offset) | ✅ | Penting — PHP default tz = Europe/Berlin |
| paymentApi.js tambah apiCheckQrisStatus | ✅ | Frontend |
| PaymentQrisPage rewrite (real QR, countdown, polling, auto-redirect) | ✅ | Frontend |
| ngrok-skip-browser-warning header di apiService.js | ✅ | Frontend |
| CORS fix: ResponseEmitter.php override CorsMiddleware | ✅ | Tambah ngrok header + ganti HTTP_ORIGIN → CORS_ALLOWED_ORIGIN |
| End-to-end test (add cart → checkout → Midtrans → webhook → paid) | ✅ | Verified via curl + DB check |

---

## Fase 5 — Enhancement (Planned)

| Task | Priority | Catatan |
|---|---|---|
| Rate limiting auth endpoints | P1 | Brute force prevention |
| Remove/restrict PATCH /orders/:id/pay | P1 | Legacy endpoint, bypass Midtrans |
| Image upload produk | P1 | — |
| Email notification order | P2 | — |
| Automated tests | P1 | Unit + integration |
| Production deploy | P0 | VPS + domain + SSL |

# Backend Generation Guide

> Panduan untuk men-generate backend Hanaka Cake menggunakan AI assistant.

---

## 1. File Context yang Harus Dipindah ke Backend Project

Setelah membuat folder `hanaka-backend/`, copy file-file berikut ke dalamnya:

```
hanaka-backend/
├── context/                          ← BUAT folder ini
│   ├── claude.md                     ← COPY dari frontend (modifikasi minor)
│   ├── backend-overview.md           ← COPY dari context/backend/overview.md
│   ├── database.md                   ← COPY dari context/backend/database.md
│   ├── api-auth.md                   ← COPY dari context/backend/api-auth.md
│   ├── api-products.md               ← COPY dari context/backend/api-products.md
│   ├── api-cart.md                    ← COPY dari context/backend/api-cart.md
│   ├── api-orders.md                 ← COPY dari context/backend/api-orders.md
│   ├── api-payment.md                ← COPY dari context/backend/api-payment.md
│   ├── security.md                   ← COPY dari context/backend/security.md
│   └── frontend-reference.md         ← BUAT BARU (ringkasan frontend)
```

### Command untuk Copy (PowerShell)

```powershell
# Buat folder backend (sejajar dengan frontend)
mkdir d:\web-projects\hanaka-backend
mkdir d:\web-projects\hanaka-backend\context

# Copy semua context backend
Copy-Item "d:\web-projects\hanaka-project\context\backend\*" "d:\web-projects\hanaka-backend\context\" -Recurse

# Copy security
Copy-Item "d:\web-projects\hanaka-project\context\backend\security.md" "d:\web-projects\hanaka-backend\context\"
```

Lalu buat `claude.md` baru khusus backend (ada di bawah).

---

## 2. File `claude.md` untuk Backend

Buat file `hanaka-backend/claude.md` dengan isi yang ada di section bawah dokumen ini.

---

## 3. Prompt untuk Generate Backend

Copy prompt di bawah ini dan berikan ke AI assistant (Claude/Copilot) saat membuka project `hanaka-backend/`:

---

### PROMPT (Copy mulai dari sini)

```
Saya ingin membuat backend REST API untuk project "Hanaka Cake" — sebuah aplikasi e-commerce kue custom. Frontend React sudah selesai dan menggunakan localStorage sebagai simulasi. Sekarang saya butuh backend yang real.

## Tech Stack
- PHP 8.2+
- Slim Framework 4
- MySQL 8.0
- JWT Authentication (firebase/php-jwt)
- PDO untuk database
- Composer untuk dependency management

## Yang Harus Dibuat

### Tahap 1: Project Foundation
1. `composer.json` dengan semua dependencies
2. `public/index.php` — front controller entry point
3. `config/routes.php` — semua route definitions
4. `config/container.php` — DI container bindings
5. `config/middleware.php` — middleware stack
6. `config/settings.php` — app settings dari env
7. `.env.example` — template environment variables
8. `.htaccess` — Apache rewrite rules
9. `.gitignore`

### Tahap 2: Infrastructure
1. `src/Infrastructure/Database.php` — PDO singleton connection
2. `src/Middleware/CorsMiddleware.php` — CORS handling (allow localhost:5173)
3. `src/Middleware/JsonBodyParser.php` — parse JSON request body
4. `src/Middleware/JwtMiddleware.php` — extract & validate JWT dari Authorization header
5. `src/Infrastructure/Services/JwtService.php` — create & verify JWT tokens

### Tahap 3: Database
1. `database/migrations/001_create_users.sql`
2. `database/migrations/002_create_products.sql`
3. `database/migrations/003_create_product_sizes.sql`
4. `database/migrations/004_create_carts.sql`
5. `database/migrations/005_create_cart_items.sql`
6. `database/migrations/006_create_orders.sql`
7. `database/migrations/007_create_order_items.sql`
8. `database/seeds/products_seeder.sql` — seed 5 produk + 20 sizes
9. `database/migrate.php` — migration runner script

### Tahap 4: Validation Layer
1. `src/Validation/Validator.php` — base validator (schema-based, seperti frontend)
2. `src/Validation/AuthValidator.php` — register & login validation
3. `src/Validation/CartValidator.php` — cart item validation
4. `src/Validation/CheckoutValidator.php` — checkout validation

Validation rules harus MIRROR persis dengan frontend:
- Register: fullName (required, min 3), email (required, valid email, unique), phone (required, format Indonesia), password (required, min 8, huruf+angka), confirmPassword (required, same as password)
- Login: email (required, email format), password (required)
- Cart item: productId (required, exists), sizeId (required, belongs to product), colorText (required, max 40), theme (max 40), quantity (required, 1-5), message (max 60)
- Checkout: customerName (required, min 3), phone (required, format ID), pickupMethod (required, pickup|delivery), pickupDate (required if pickup), pickupTime (required if pickup), address (required if delivery, max 220), addressNote (max 120), paymentMethod (required, cash|qris)

### Tahap 5: Repositories
1. `src/Infrastructure/Repositories/UserRepository.php` — findByEmail, findById, create
2. `src/Infrastructure/Repositories/ProductRepository.php` — findAll, findById (with sizes)
3. `src/Infrastructure/Repositories/CartRepository.php` — findByUser, findBySession, create, delete, merge
4. `src/Infrastructure/Repositories/CartItemRepository.php` — CRUD
5. `src/Infrastructure/Repositories/OrderRepository.php` — create, findByUser, findById, updateStatus

Semua query HARUS menggunakan PDO prepared statements (no string interpolation).

### Tahap 6: Actions (Controllers)
Auth:
1. `src/Actions/Auth/RegisterAction.php` — POST /api/auth/register
2. `src/Actions/Auth/LoginAction.php` — POST /api/auth/login
3. `src/Actions/Auth/LogoutAction.php` — POST /api/auth/logout
4. `src/Actions/Auth/MeAction.php` — GET /api/auth/me

Products:
5. `src/Actions/Product/ListProductsAction.php` — GET /api/products
6. `src/Actions/Product/GetProductAction.php` — GET /api/products/{productId}

Cart:
7. `src/Actions/Cart/GetCartAction.php` — GET /api/cart
8. `src/Actions/Cart/AddCartItemAction.php` — POST /api/cart/items
9. `src/Actions/Cart/UpdateCartItemAction.php` — PUT /api/cart/items/{itemId}
10. `src/Actions/Cart/UpdateCartQuantityAction.php` — PATCH /api/cart/items/{itemId}/quantity
11. `src/Actions/Cart/RemoveCartItemAction.php` — DELETE /api/cart/items/{itemId}
12. `src/Actions/Cart/ClearCartAction.php` — DELETE /api/cart

Orders:
13. `src/Actions/Order/CreateOrderAction.php` — POST /api/orders
14. `src/Actions/Order/ListOrdersAction.php` — GET /api/orders
15. `src/Actions/Order/GetOrderAction.php` — GET /api/orders/{orderId}
16. `src/Actions/Order/MarkOrderPaidAction.php` — PATCH /api/orders/{orderId}/pay

Store:
17. `src/Actions/Store/GetProfileAction.php` — GET /api/store/profile

## Business Rules Penting

1. **Password**: Hash dengan `password_hash(PASSWORD_BCRYPT, cost 12)`, verify dengan `password_verify()`
2. **User ID format**: `usr_` + 8 random hex chars
3. **Cart ID format**: `cart_` + 8 random hex chars
4. **Order ID format**: `ord_` + 8 random hex chars
5. **Order Number format**: `HNK-YYYYMMDD-HHMMSS-XXX` (XXX = random 100-999)
6. **Guest cart**: Identifikasi via session_token (generated saat pertama add to cart, disimpan di cookie)
7. **Cart merge**: Saat login/register, jika ada guest cart → pindahkan semua items ke user cart, hapus guest cart
8. **Order items snapshot**: Simpan product_name dan size_label di order_items (agar tidak berubah jika produk diupdate)
9. **Payment status**: 'pending' (QRIS belum bayar), 'paid' (QRIS sudah bayar), 'cod' (cash)
10. **Order status**: 'menunggu konfirmasi' → 'diproses' (setelah bayar) → 'siap diambil'/'diantar' → 'selesai' atau 'dibatalkan'

## Response Format

Semua response JSON:
```json
// Success
{ "ok": true, "data": { ... } }

// Error
{ "ok": false, "error": "Pesan error.", "errors": { "field": "Pesan per field." } }
```

Error messages dalam Bahasa Indonesia.
Kode (variable, function, class) dalam Bahasa Inggris.

## Data Produk (untuk seeder)

5 produk cake:
- black-forest (Black Forest Cake, featured)
- red-velvet (Red Velvet Cake, featured)
- vanilla-cake (Vanila Cake)
- lemon-cake (Lemon Cake)
- rainbow-cake (Rainbow Cake)

4 ukuran per produk:
- 16cm = Rp120.000
- 18cm = Rp170.000
- 20cm = Rp220.000
- 22cm = Rp270.000

## Store Profile
- Nama: Hanaka Cake
- Alamat: Jl. DR. Sukono Rt 09 No 11, Karang Rejo, Balikpapan Kota, Kalimantan Timur. 76124
- Jam: 07.00 AM - 11.00 PM
- WhatsApp: 6281299998888
- Instagram: hanakacake.id

## Security Requirements
- PDO prepared statements EVERYWHERE (no string interpolation in SQL)
- Password hashing (bcrypt)
- JWT authentication
- CORS whitelist (bukan wildcard)
- Input validation di SEMUA endpoint
- Jangan expose internal errors di production
- .env di .gitignore

Buatkan semua file di atas secara lengkap, production-ready, dengan proper error handling. Setiap Action harus complete (validasi, query, response). Jangan skip atau placeholder.
```

### END OF PROMPT

---

## 4. Tips Penggunaan Prompt

### Jika terlalu besar untuk satu sesi:

Pecah jadi beberapa sesi:

**Sesi 1 — Foundation:**
> "Buat Tahap 1 (project foundation) dan Tahap 2 (infrastructure). Refer ke context/ folder untuk detail."

**Sesi 2 — Database:**
> "Buat Tahap 3 (database migrations + seeder + runner). Lihat context/database.md untuk schema lengkap."

**Sesi 3 — Validation + Repositories:**
> "Buat Tahap 4 (validation) dan Tahap 5 (repositories). Validation harus mirror frontend rules. Lihat context/api-*.md untuk detail."

**Sesi 4 — Auth + Products Actions:**
> "Buat Actions untuk Auth (register, login, logout, me) dan Products (list, detail). Lihat context/api-auth.md dan context/api-products.md."

**Sesi 5 — Cart + Orders Actions:**
> "Buat Actions untuk Cart (6 endpoints) dan Orders (4 endpoints). Lihat context/api-cart.md dan context/api-orders.md."

### Saat membuka sesi baru:
> "Baca claude.md dan semua file di context/ untuk memahami project ini sebelum mulai coding."

---

## 5. `claude.md` untuk Backend Project

Buat file ini di root folder backend:

```markdown
# Hanaka Cake Backend — AI Assistant Context

> Dokumen referensi utama untuk AI assistant yang bekerja di backend Hanaka Cake.

---

## Ringkasan Project

Backend REST API untuk Hanaka Cake — e-commerce kue custom di Balikpapan.
Frontend React sudah selesai (repo terpisah). Backend ini menyediakan API untuk auth, products, cart, orders, dan payment.

## Tech Stack
- PHP 8.2+ / Slim Framework 4
- MySQL 8.0 / PDO
- JWT auth (firebase/php-jwt)
- Composer dependency management

## Folder Structure
Lihat `context/backend-overview.md`

## Database Schema
Lihat `context/database.md`

## API Endpoints
- Auth: `context/api-auth.md`
- Products: `context/api-products.md`
- Cart: `context/api-cart.md`
- Orders: `context/api-orders.md`
- Payment: `context/api-payment.md`

## Security
Lihat `context/security.md`

## Konvensi Kode
- Namespace: `Hanaka\...` (PSR-4)
- File: PascalCase
- Method: camelCase
- SQL columns: snake_case
- Response: camelCase JSON
- Error messages: Bahasa Indonesia
- Code: Bahasa Inggris

## Catatan Penting
1. SEMUA query pakai PDO prepared statements
2. Password hash: `password_hash(PASSWORD_BCRYPT)`
3. Response format: `{ "ok": true/false, ... }`
4. ID format: `usr_xxx`, `cart_xxx`, `ord_xxx`
5. Order number: `HNK-YYYYMMDD-HHMMSS-XXX`
6. Guest cart via session_token cookie
7. Cart merge saat login/register
8. Order items = snapshot (simpan nama + harga saat checkout)
```

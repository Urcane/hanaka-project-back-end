# Backend — Overview

> Tech stack, struktur folder, setup, dan cara menjalankan backend Slim PHP.

---

## Status: ✅ IMPLEMENTED (Backend MVP + Admin)

---

## Tech Stack

| Technology | Version | Fungsi |
|---|---|---|
| PHP | 8.2+ | Runtime |
| Slim Framework | 4.x | REST API micro-framework |
| MySQL | 8.0+ | Relational database |
| Composer | 2.x | Dependency manager |
| PHP-DI | 7.x | Dependency injection container |
| firebase/php-jwt | 6.x | JWT token creation & validation |
| vlucas/phpdotenv | 5.x | Environment variable loading |
| tuupola/slim-jwt-auth | 3.x | JWT middleware for Slim |
| phpunit | 10.x | Unit testing |

---

## Setup

### Prerequisites
```bash
php -v         # 8.2+
composer -V    # 2.x+
mysql --version  # 8.0+
```

### Inisialisasi
```bash
mkdir hanaka-backend && cd hanaka-backend

composer init --name="hanaka/backend" --type="project"

# Core
composer require slim/slim:"^4.0"
composer require slim/psr7:"^1.6"
composer require php-di/php-di:"^7.0"

# Auth & Security
composer require firebase/php-jwt:"^6.10"
composer require tuupola/slim-jwt-auth:"^3.7"

# Utilities
composer require vlucas/phpdotenv:"^5.6"
composer require selective/basepath:"^2.2"

# Dev
composer require --dev phpunit/phpunit:"^10.0"
```

### Database
```sql
CREATE DATABASE hanaka_cake CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'hanaka_user'@'localhost' IDENTIFIED BY 'your_password';
GRANT ALL PRIVILEGES ON hanaka_cake.* TO 'hanaka_user'@'localhost';
FLUSH PRIVILEGES;
```

### Environment
```bash
cp .env.example .env
# Edit .env → isi DB credentials, JWT secret, dll
```

### Run Migrations
```bash
php database/migrate.php
```

### Run Server
```bash
# Dengan PHP built-in server
cd public
php -S localhost:8080

# Atau dengan XAMPP/Laragon, arahkan document root ke folder public/
```

---

## Struktur Folder

```
hanaka-backend/
├── composer.json
├── .env                        # Environment (JANGAN commit!)
├── .env.example                # Template
├── .gitignore
├── public/
│   ├── index.php               # Front controller (entry point)
│   └── .htaccess               # Apache rewrite
├── src/
│   ├── Actions/                # Route handlers (thin controllers)
│   │   ├── Auth/
│   │   │   ├── RegisterAction.php
│   │   │   ├── LoginAction.php
│   │   │   ├── LogoutAction.php
│   │   │   └── MeAction.php
│   │   ├── Product/
│   │   │   ├── ListProductsAction.php
│   │   │   └── GetProductAction.php
│   │   ├── Cart/
│   │   │   ├── GetCartAction.php
│   │   │   ├── AddCartItemAction.php
│   │   │   ├── UpdateCartItemAction.php
│   │   │   ├── UpdateCartQuantityAction.php
│   │   │   ├── RemoveCartItemAction.php
│   │   │   └── ClearCartAction.php
│   │   ├── Order/
│   │   │   ├── CreateOrderAction.php
│   │   │   ├── ListOrdersAction.php
│   │   │   ├── GetOrderAction.php
│   │   │   └── MarkOrderPaidAction.php
│   │   ├── Payment/
│   │   │   └── CreateQrisAction.php
│   │   └── Store/
│   │       └── GetProfileAction.php
│   ├── Domain/                 # Business entities
│   │   ├── User.php
│   │   ├── Product.php
│   │   ├── CartItem.php
│   │   ├── Order.php
│   │   └── OrderItem.php
│   ├── Infrastructure/         # External concerns
│   │   ├── Database.php
│   │   ├── Repositories/
│   │   │   ├── UserRepository.php
│   │   │   ├── ProductRepository.php
│   │   │   ├── CartRepository.php
│   │   │   └── OrderRepository.php
│   │   └── Services/
│   │       ├── JwtService.php
│   │       └── QrisPaymentService.php
│   ├── Middleware/
│   │   ├── CorsMiddleware.php
│   │   ├── JwtMiddleware.php
│   │   └── JsonBodyParser.php
│   └── Validation/
│       ├── Validator.php
│       ├── AuthValidator.php
│       ├── CartValidator.php
│       └── CheckoutValidator.php
├── config/
│   ├── routes.php              # Route definitions
│   ├── container.php           # DI bindings
│   ├── middleware.php          # Middleware stack
│   └── settings.php            # App settings
├── database/
│   ├── migrate.php             # Migration runner
│   ├── migrations/             # SQL migration files
│   └── seeds/                  # Seed data
├── storage/
│   └── logs/                   # App logs
└── tests/
    ├── Unit/
    └── Integration/
```

---

## Architecture Pattern

```
Request → Middleware → Action → Validator → Repository → Database
                                    ↓
                                 Response
```

- **Actions** — Thin controllers, hanya orchestrate
- **Validators** — Input validation (mirror frontend rules)
- **Repositories** — Database queries (PDO prepared statements)
- **Domain** — Entity classes (data structure)
- **Services** — External integrations (JWT, payment gateway)

---

## Environment Variables

```env
# App
APP_ENV=development
APP_DEBUG=true
APP_URL=http://localhost:8080

# Database
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=hanaka_cake
DB_USER=hanaka_user
DB_PASS=your_password

# JWT
JWT_SECRET=minimum-32-characters-random-string
JWT_EXPIRY=86400

# CORS
CORS_ORIGIN=http://localhost:5173

# Payment (Midtrans)
MIDTRANS_SERVER_KEY=
MIDTRANS_CLIENT_KEY=
MIDTRANS_IS_PRODUCTION=false
```

---

## CORS Configuration

Frontend (Vite) berjalan di `localhost:5173`, backend di `localhost:8080`.

Headers yang diperlukan:
```
Access-Control-Allow-Origin: http://localhost:5173
Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS
Access-Control-Allow-Headers: Content-Type, Authorization
Access-Control-Allow-Credentials: true
```

---

## Konvensi Kode Backend

| Aspek | Convention |
|---|---|
| Namespace | `Hanaka\...` (PSR-4) |
| File naming | PascalCase (`RegisterAction.php`) |
| Method naming | camelCase (`findByEmail`) |
| SQL naming | snake_case (`customer_name`, `order_items`) |
| Response format | `{ ok: bool, data: ..., error?: string }` |
| HTTP status codes | 200 OK, 201 Created, 400 Bad Request, 401 Unauthorized, 404 Not Found, 500 Server Error |

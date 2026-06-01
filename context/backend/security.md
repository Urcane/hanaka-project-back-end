# Backend — Security Requirements

> Checklist keamanan dan status implementasi.
> Terakhir update: 2026-06-01

---

## Security Checklist

### Authentication & Authorization

- [x] Password hashing: `password_hash($pw, PASSWORD_BCRYPT, ['cost' => 12])`
- [x] Password verification: `password_verify($input, $hash)`
- [x] JWT secret minimal 32 karakter (random, dari env)
- [x] JWT expiry reasonable (24 jam default)
- [x] Tidak return `password_hash` di response apapun
- [ ] Rate limiting pada `/api/auth/login` dan `/api/auth/register` ← **BELUM**
- [x] User hanya bisa akses data miliknya (order, cart)
- [x] Role-based access (customer/admin) via JWT claim + AdminMiddleware
- [ ] Guest session token memiliki expiry ← **BELUM**

### Input Validation

- [x] Validasi SEMUA input di server-side
- [x] Sanitize string input (trim, strip_tags via `Validator::sanitize()`)
- [x] Limit panjang input (maxLength semua field)
- [x] Validate numeric types (quantity, price)
- [x] Validate enum values (payment_method, fulfillment_method, status)
- [x] Reject unexpected fields (whitelist di setiap Action)

### SQL Injection Prevention

- [x] Gunakan PDO prepared statements SELALU
- [x] Parameterized queries di semua Repository

### CORS

- [x] Whitelist specific origin (dari `CORS_ALLOWED_ORIGIN` env)
- [x] Handle preflight (OPTIONS) requests
- [x] **Sumber kebenaran CORS: `ResponseEmitter.php`** (bukan CorsMiddleware — ResponseEmitter dieksekusi terakhir dan override semua header)

### Webhook Security (Payment)

- [x] Verify SHA-512 signature Midtrans
- [x] Idempotent: duplicate webhook dilayani tanpa efek samping
- [x] Always return 200 OK (termasuk reject — agar Midtrans tidak retry)
- [ ] Log webhook calls untuk audit ← **BELUM**
- [ ] IP whitelist Midtrans ← **Opsional**

### Environment

- [x] `.env` masuk `.gitignore`
- [x] `.env.example` sebagai template (tanpa secret)
- [x] Tidak ada credentials hardcoded di source code

### Error Handling

- [x] `APP_DEBUG` dari env (false = hide internal errors)
- [x] Return generic error ke client di production

---

## Known Security Issues

| # | Issue | Severity | Status |
|---|---|---|---|
| SEC-1 | `PATCH /api/orders/:id/pay` — client bisa klaim bayar tanpa verifikasi Midtrans | 🟡 Medium | ⬜ Open — remove/admin-gate |
| SEC-2 | Tidak ada rate limiting di auth endpoints | 🟡 Medium | ⬜ Open |
| SEC-3 | Guest session token tidak punya expiry | 🟢 Low | ⬜ Open |

---

## OWASP Top 10 Mapping

| Risk | Status | Mitigation |
|---|---|---|
| A01: Broken Access Control | ✅ | User isolation, role checks, JWT validation, session token |
| A02: Cryptographic Failures | ✅ | bcrypt cost 12, HTTPS (production), secure JWT secret |
| A03: Injection | ✅ | PDO prepared statements selalu |
| A04: Insecure Design | ✅ | Input validation, whitelist fields, principle of least privilege |
| A05: Security Misconfiguration | ✅ | .env, debug off in prod, security headers, CORS whitelist |
| A06: Vulnerable Components | ⬜ | Composer audit belum dijadwalkan rutin |
| A07: Auth Failures | ⬜ | Rate limiting belum ada |
| A08: Data Integrity Failures | ✅ | Webhook signature SHA-512 verified |
| A09: Logging Failures | ⬜ | Log webhook + auth events belum lengkap |
| A10: SSRF | ✅ | External call hanya ke Midtrans (hardcoded URL) |

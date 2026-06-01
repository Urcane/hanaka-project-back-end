# Backend — Security Requirements

> Checklist keamanan dan best practices untuk backend.

---

## Security Checklist

### Authentication & Authorization

- [ ] Password hashing: `password_hash($pw, PASSWORD_BCRYPT, ['cost' => 12])`
- [ ] Password verification: `password_verify($input, $hash)`
- [ ] JWT secret minimal 32 karakter (random, dari env)
- [ ] JWT expiry reasonable (24 jam default)
- [ ] Tidak return `password_hash` di response apapun
- [ ] Rate limiting pada `/api/auth/login` dan `/api/auth/register`
- [ ] User hanya bisa akses data miliknya (order, cart)
- [ ] Guest session token memiliki expiry

### Input Validation

- [ ] Validasi SEMUA input di server-side (jangan trust client)
- [ ] Sanitize string input (trim, strip tags jika perlu)
- [ ] Limit panjang input (maxLength di semua field)
- [ ] Validate numeric types (quantity, price)
- [ ] Validate enum values (payment_method, fulfillment_method, status)
- [ ] Reject unexpected fields (whitelist approach)

### SQL Injection Prevention

- [ ] Gunakan PDO prepared statements SELALU
- [ ] JANGAN interpolate user input ke SQL string
- [ ] Gunakan parameterized queries

```php
// ✅ BENAR
$stmt = $pdo->prepare('SELECT * FROM users WHERE email = :email');
$stmt->execute(['email' => $email]);

// ❌ SALAH — JANGAN PERNAH
$stmt = $pdo->query("SELECT * FROM users WHERE email = '$email'");
```

### XSS Prevention

- [ ] Escape output di response (untuk data yang akan ditampilkan di HTML)
- [ ] Set header `Content-Type: application/json` untuk API responses
- [ ] Sanitize user-generated content (nama, alamat, catatan)

### CORS

- [ ] Whitelist specific origin (`http://localhost:5173` di dev, domain production)
- [ ] JANGAN gunakan `Access-Control-Allow-Origin: *` di production
- [ ] Handle preflight (OPTIONS) requests
- [ ] Only allow needed methods dan headers

### HTTPS

- [ ] Enforce HTTPS di production
- [ ] Set `Secure` flag pada cookies
- [ ] Use HSTS header

### Error Handling

- [ ] Jangan expose internal error details di production (`APP_DEBUG=false`)
- [ ] Log errors ke file, bukan ke response
- [ ] Return generic error message ke client
- [ ] Handle semua exception types

```php
// Development
{ "ok": false, "error": "SQLSTATE[42S02]: Base table or views not found..." }

// Production (seharusnya)
{ "ok": false, "error": "Terjadi kesalahan. Silakan coba lagi." }
```

### File Upload (jika ada)

- [ ] Validate MIME type (jangan hanya extension)
- [ ] Limit file size (e.g. max 5MB)
- [ ] Generate random filename (jangan pakai original filename)
- [ ] Simpan di luar document root atau gunakan storage service
- [ ] Jangan execute uploaded files

### Environment

- [ ] `.env` masuk `.gitignore` (WAJIB)
- [ ] Gunakan `.env.example` sebagai template (tanpa secret)
- [ ] Jangan hardcode credentials di source code
- [ ] Rotate secrets secara berkala

---

## Security Headers (Middleware)

```php
// Tambahkan di response middleware
$response = $response
    ->withHeader('X-Content-Type-Options', 'nosniff')
    ->withHeader('X-Frame-Options', 'DENY')
    ->withHeader('X-XSS-Protection', '1; mode=block')
    ->withHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
```

---

## Webhook Security (Payment)

- [ ] Verify signature dari payment gateway
- [ ] Validate IP whitelist (jika provider menyediakan)
- [ ] Idempotency: handle duplicate webhook calls gracefully
- [ ] Log semua webhook calls untuk audit

---

## Data Privacy

- [ ] Jangan log sensitive data (password, full card number)
- [ ] Minimize data collection (hanya yang diperlukan)
- [ ] Provide way for users to delete their account (GDPR compliance)
- [ ] Hash/encrypt sensitive stored data jika diperlukan

---

## OWASP Top 10 Mapping

| Risk | Mitigation |
|---|---|
| A01: Broken Access Control | User isolation, role checks, JWT validation |
| A02: Cryptographic Failures | bcrypt, HTTPS, secure JWT secret |
| A03: Injection | PDO prepared statements |
| A04: Insecure Design | Input validation, principle of least privilege |
| A05: Security Misconfiguration | .env, debug off in prod, security headers |
| A06: Vulnerable Components | Composer audit, update dependencies |
| A07: Auth Failures | Rate limiting, strong password policy |
| A08: Data Integrity Failures | Webhook signature verification |
| A09: Logging Failures | Log auth events, webhook calls |
| A10: SSRF | Validate URLs, whitelist external calls |

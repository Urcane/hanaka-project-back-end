# Backend — Auth API

> Endpoint autentikasi: register, login, logout, get current user.

---

## Endpoints

### POST `/api/auth/register`

Register customer baru.

**Request:**
```json
{
  "fullName": "Budi Santoso",
  "email": "budi@email.com",
  "phone": "081234567890",
  "password": "Password123",
  "confirmPassword": "Password123"
}
```

**Success Response (201):**
```json
{
  "ok": true,
  "user": {
    "id": "usr_a1b2c3d4",
    "fullName": "Budi Santoso",
    "email": "budi@email.com",
    "phone": "081234567890",
    "createdAt": "2026-05-22T07:30:00Z"
  },
  "token": "eyJhbGciOiJIUzI1NiJ9..."
}
```

**Error Response (400):**
```json
{
  "ok": false,
  "error": "Email ini sudah terdaftar. Silakan login.",
  "errors": {
    "fullName": "Nama lengkap wajib diisi.",
    "email": "Masukkan format email yang valid."
  }
}
```

**Validation Rules:**
| Field | Rules |
|---|---|
| fullName | required, minLength(3) |
| email | required, valid email, unique |
| phone | required, valid Indonesian phone |
| password | required, min 8 chars, has letter + number |
| confirmPassword | required, same as password |

**Logic:**
1. Validate input
2. Check email uniqueness
3. Hash password: `password_hash($password, PASSWORD_BCRYPT)`
4. Generate user ID: `usr_` + 8 random chars
5. Insert to `users` table
6. Generate JWT token
7. If guest session has cart → merge to user cart
8. Return user + token

---

### POST `/api/auth/login`

Login existing customer.

**Request:**
```json
{
  "email": "budi@email.com",
  "password": "Password123"
}
```

**Success Response (200):**
```json
{
  "ok": true,
  "user": {
    "id": "usr_a1b2c3d4",
    "fullName": "Budi Santoso",
    "email": "budi@email.com",
    "phone": "081234567890",
    "createdAt": "2026-05-22T07:30:00Z"
  },
  "token": "eyJhbGciOiJIUzI1NiJ9..."
}
```

**Error Response (401):**
```json
{
  "ok": false,
  "error": "Email atau password belum sesuai."
}
```

**Logic:**
1. Validate input (email format, password not empty)
2. Find user by email
3. Verify password: `password_verify($password, $user->password_hash)`
4. Generate JWT token
5. If guest session has cart → merge to user cart
6. Return user + token

---

### POST `/api/auth/logout`

Invalidate session (optional, JWT biasanya stateless).

**Headers:** `Authorization: Bearer <token>`

**Response (200):**
```json
{
  "ok": true
}
```

---

### GET `/api/auth/me`

Get currently authenticated user.

**Headers:** `Authorization: Bearer <token>`

**Success Response (200):**
```json
{
  "ok": true,
  "user": {
    "id": "usr_a1b2c3d4",
    "fullName": "Budi Santoso",
    "email": "budi@email.com",
    "phone": "081234567890",
    "createdAt": "2026-05-22T07:30:00Z"
  }
}
```

**Error Response (401):**
```json
{
  "ok": false,
  "error": "Token tidak valid atau sudah expired."
}
```

---

## JWT Token

### Payload
```json
{
  "sub": "usr_a1b2c3d4",
  "email": "budi@email.com",
  "iat": 1716350000,
  "exp": 1716436400
}
```

### Configuration
- Algorithm: HS256
- Secret: dari `JWT_SECRET` env variable (min 32 chars)
- Expiry: `JWT_EXPIRY` env variable (default 86400 = 24 jam)

### Frontend Usage
```js
// Simpan token di memory (bukan localStorage untuk security)
const token = response.token

// Kirim di setiap request
fetch('/api/orders', {
  headers: { 'Authorization': `Bearer ${token}` }
})
```

---

## Password Security

```php
// Register
$hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

// Login
$valid = password_verify($inputPassword, $storedHash);
```

- Gunakan `PASSWORD_BCRYPT` dengan cost 12
- JANGAN simpan plain text password
- JANGAN log password
- JANGAN return password_hash di response

---

## Rate Limiting (Recommended)

| Endpoint | Limit |
|---|---|
| `/api/auth/login` | 5 attempts per IP per minute |
| `/api/auth/register` | 3 attempts per IP per minute |

Implementasi: Redis counter atau database-based throttle.

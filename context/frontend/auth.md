# Frontend — Authentication

> Flow autentikasi: register, login, logout, guest handling.

---

## Overview

- Autentikasi berbasis **localStorage** (MVP — akan diganti JWT + backend)
- Support **guest checkout** tanpa login
- Cart guest otomatis **merge** ke user cart saat login/register

---

## Data Model

### User Object (tersimpan di localStorage)
```js
{
  id: "usr_a1b2c3d4",          // createId('usr')
  fullName: "Nama Lengkap",
  email: "user@email.com",     // normalized lowercase
  phone: "081234567890",       // normalized tanpa spasi/dash
  password: "plaintext123",    // ⚠️ PLAIN TEXT — hanya simulasi!
  createdAt: "2026-05-22T..."  // ISO string
}
```

### Session State
- `sessionUserId` → ID user yang sedang login, atau `null`
- Disimpan di localStorage key `hanaka_session_user_v1`

---

## Registration Flow

```
RegisterPage (form)
  ↓ submit
validateRegistrationInput(values)    ← authModel.js
  ↓ jika valid
cek email unique di users array
  ↓ jika unique
buildAccount(values)                 ← authModel.js
  ↓
setUsers([...users, account])
setSessionUserId(account.id)
mergeGuestCartToUser()               ← merge cart __guest__ → user
  ↓
navigate(redirectTo || '/')
```

### Validasi Register
| Field | Rules |
|---|---|
| `fullName` | required, minLength(3) |
| `email` | required, email format |
| `phone` | required, phoneId (Indonesia) |
| `password` | required, strongPassword (8+ char, huruf + angka) |
| `confirmPassword` | required, sameAs('password') |

---

## Login Flow

```
LoginPage (form)
  ↓ submit
validateLoginInput(values)           ← authModel.js
  ↓ jika valid
cari user di array: email match + password match
  ↓ jika found
setSessionUserId(user.id)
mergeGuestCartToUser()               ← merge cart __guest__ → user
  ↓
navigate(redirectTo || '/')
```

### Validasi Login
| Field | Rules |
|---|---|
| `email` | required, email format |
| `password` | required |

### Error Messages
- Email tidak ditemukan / password salah: `"Email atau password belum sesuai."`
- Email sudah terdaftar (register): `"Email ini sudah terdaftar. Silakan login."`

---

## Logout Flow

```
AppLayout → button Logout
  ↓ click
logoutAccount()
  ↓
setSessionUserId(null)
navigate('/')
```

---

## Guest Cart Merge

Saat user login/register, guest cart di-merge:

```js
function mergeGuestCartToUser(previousCarts, userId) {
  const guestCart = previousCarts['__guest__'] ?? []
  const userCart = previousCarts[userId] ?? []

  if (!guestCart.length) return { ...previousCarts, [userId]: userCart }

  return {
    ...previousCarts,
    [userId]: [...userCart, ...guestCart],
    ['__guest__']: [],
  }
}
```

---

## Redirect After Auth

- `LoginPage` dan `RegisterPage` membaca `location.state.redirectTo`
- `ProtectedRoute` menyimpan `pathname + search` ke state saat redirect ke login
- Setelah auth sukses → navigate ke `redirectTo` atau fallback `/`

---

## File Terkait

- `src/pages/LoginPage.jsx` — Login form
- `src/pages/RegisterPage.jsx` — Register form
- `src/models/authModel.js` — `validateRegistrationInput`, `validateLoginInput`, `buildAccount`
- `src/context/AppContext.jsx` — `registerAccount`, `loginAccount`, `logoutAccount`
- `src/components/GuestRoute.jsx` — Redirect jika sudah login
- `src/components/ProtectedRoute.jsx` — Redirect jika belum login

---

## Catatan Migrasi ke Backend

| Sekarang (Frontend) | Target (Backend) |
|---|---|
| Password plain text di localStorage | `password_hash(PASSWORD_BCRYPT)` di MySQL |
| User array di localStorage | `users` table di MySQL |
| `sessionUserId` di localStorage | JWT token di httpOnly cookie |
| Email lookup di array | SQL query `WHERE email = ?` |
| Guest cart key `__guest__` | Session token dari backend |

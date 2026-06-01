# Frontend — Authentication

> Flow autentikasi: register, login, logout, guest handling.
> Terakhir update: 2026-06-01

---

## Status: ✅ Real Backend Auth (JWT)

Autentikasi sudah pakai backend — tidak ada lagi localStorage user/password.

---

## Mekanisme Auth

- **JWT token** disimpan di `localStorage` (`hanaka_auth_token`)
- Token dikirim otomatis via `Authorization: Bearer <token>` di setiap request (`apiService.js`)
- Auth restore saat app mount: `GET /api/auth/me` → set `currentUser`
- Guest cart pakai `X-Session-Token` header (session token di localStorage `hanaka_session_token`)
- Login/Register → guest cart otomatis di-merge ke user cart (backend handle)

---

## Registration Flow

```
RegisterPage (form)
  ↓ submit
validateRegistrationInput(values)    ← authModel.js (client-side)
  ↓ jika valid
POST /api/auth/register
  ↓ response: { user, token }
setAuthToken(token) → localStorage
setCurrentUser(user)
  ↓
navigate(redirectTo || '/')
```

### Validasi Register (client-side, authModel.js)
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
validateLoginInput(values)           ← authModel.js (client-side)
  ↓ jika valid
POST /api/auth/login
  ↓ response: { user, token }
setAuthToken(token) → localStorage
setCurrentUser(user)
  ↓
if (user.role === 'admin') → navigate('/admin/dashboard')
else → navigate(redirectTo || '/')
```

### Error Handling
| HTTP | Pesan |
|---|---|
| 401 | "Email atau password belum sesuai." |
| 400 + errors | Tampil per-field |

---

## Logout Flow

```
AppLayout → button Logout
  ↓
logoutAccount()
  ↓
POST /api/auth/logout (best-effort)
clearAuthToken() → hapus dari localStorage
setCurrentUser(null)
navigate('/')
```

---

## Auth Restore (App Mount)

```js
// AppContext.jsx — useEffect on mount
apiGetMe()
  .then(user => setCurrentUser(user))
  .catch(() => setCurrentUser(null))
  .finally(() => setIsAuthLoading(false))
```

Selama `isAuthLoading === true`, route guard menunggu sebelum redirect.

---

## Guest Cart Merge

Backend otomatis merge saat login/register:
1. Frontend kirim `X-Session-Token` header saat call `POST /api/auth/login` atau `/register`
2. Backend pindahkan semua cart item dari session token ke user cart

---

## Role-Based Access

| Role | Redirect setelah login | Akses admin panel |
|---|---|---|
| `customer` | `redirectTo` atau `/` | ❌ |
| `admin` | `/admin/dashboard` | ✅ |

`AdminRoute` component mengecek `currentUser.role === 'admin'`.

---

## File Terkait

- `src/pages/LoginPage.jsx` — Login form
- `src/pages/RegisterPage.jsx` — Register form
- `src/services/authApi.js` — `apiLogin`, `apiRegister`, `apiLogout`, `apiGetMe`
- `src/services/apiService.js` — Token management (setAuthToken, clearAuthToken)
- `src/models/authModel.js` — Client-side validation (validateLoginInput, validateRegistrationInput)
- `src/context/AppContext.jsx` — `registerAccount`, `loginAccount`, `logoutAccount`
- `src/components/GuestRoute.jsx` — Redirect jika sudah login
- `src/components/ProtectedRoute.jsx` — Redirect ke /login jika belum login
- `src/components/AdminRoute.jsx` — Redirect jika bukan admin

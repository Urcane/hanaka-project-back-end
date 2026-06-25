# Membangun Fitur Baru: **Ubah Profil** — Sisi Backend (Slim PHP)

> **Pasangan backend dari dokumen frontend `context-fitur-ubah-profil.md`.** Di sisi frontend kamu sudah membuat `PUT /auth/profile` dipanggil dari `apiUpdateProfile`. Dokumen ini membangun **endpoint yang menerima panggilan itu** di backend Slim PHP, mengikuti arsitektur berlapis Hanaka (Action → Validation → Repository → Database) tanpa mengubah fitur lain.
>
> Endpoint ini *belum ada* di codebase. Kamu menambahkannya sehingga kontrak yang diasumsikan frontend benar-benar terpenuhi: `PUT /auth/profile` → `200 { user }`, atau `400 { errors: { phone } }`.
>
> ✅ Semua kode mengikuti pola yang sudah dipakai `RegisterAction`/`MeAction`/`AuthValidator` — bukan pola baru. Setelah implementasi: jalankan `composer start` dan uji dengan `curl`/Postman.

---

## Apa yang dibangun

Endpoint **`PUT /api/auth/profile`** yang menerima `{ fullName, phone }` dari user yang sudah login, memvalidasi di server, cek nomor telepon tidak bentrok dengan akun lain, lalu menyimpan ke tabel `users` dan mengembalikan data user terbaru.

Kenapa fitur ini bagus untuk materi backend: dia menyentuh **keempat lapisan** dengan bersih, dan butuh **guard beneran** (`AuthRequiredMiddleware`) karena hanya untuk user login — analog langsung dari `ProtectedRoute` di frontend.

| Lapisan backend | Yang kamu buat | Analogi di frontend |
|---|---|---|
| **Route + Guard** | `PUT /auth/profile` + `->add(AuthRequiredMiddleware::class)` | `ProtectedRoute` |
| **Validation** (server-side) | `validateProfile()` di `AuthValidator` (reuse rule yang ada) | `profileModel.js` |
| **Action** (thin controller) | `UpdateProfileAction` baru | `ProfilePage` handleSubmit |
| **Repository** (akses data) | `findByPhoneExcept()` + `updateProfile()` di `UserRepository` | — (tak ada di FE) |

> **Prinsip kunci: defense in depth.** Frontend sudah memvalidasi `fullName`/`phone`. Backend **memvalidasi lagi** — karena request bisa datang dari mana saja (curl, Postman, klien yang dimodifikasi), tidak boleh percaya input klien. Inilah jawaban "Apakah validasi client cukup?" → **tidak**.

---

## Kontrak API yang dipenuhi

Ini kontrak yang sama persis dengan yang diasumsikan dokumen frontend — sekarang kita yang menyediakannya:

```
PUT /api/auth/profile
  Header : Authorization: Bearer <jwt>   (wajib — dipaksa AuthRequiredMiddleware)
  Body   : { fullName, phone }
  Sukses : 200 { ok:true, user: { id, fullName, email, phone, role, createdAt } }
  Gagal  :
    400 { ok:false, error:'...', errors: { fullName?, phone? } }   ← validasi / nomor dipakai
    401 { ok:false, error:'Token tidak valid atau sudah expired.' } ← belum login
    404 { ok:false, error:'User tidak ditemukan.' }                 ← user hilang
```

Catatan penting:
- **Email & password TIDAK ikut** — endpoint ini sengaja hanya untuk `fullName` + `phone`. Mengubah email butuh verifikasi ulang (di luar scope), password punya endpoint sendiri nanti.
- Format respons (`ok`, `error`, `errors`) mengikuti `BaseAction::successResponse()` / `errorResponse()` yang sudah ada — tidak menciptakan format baru.

---

## Peta fitur

```
                    ┌──────────────── ROUTE + GUARD ─────────────────┐
  PUT /api/auth/    │ routes.php:                                     │
  profile  ────────►│   $auth->put('/profile', UpdateProfileAction)  │
                    │        ->add(AuthRequiredMiddleware::class)     │
                    │  belum login (userId kosong) → 401, STOP        │
                    └───────────────────────┬────────────────────────┘
                                            │ (userId ada → lanjut)
                                            ▼
                    ┌──────────────── UpdateProfileAction ───────────┐
                    │ 1) $userId = getUserId()  (dijamin guard)       │
                    │ 2) sanitize body → { fullName, phone }          │
                    │ 3) validateProfile()  ─── VALIDATION            │
                    │    ada error → errorResponse(400, errors) STOP  │
                    │ 4) cek phone bentrok ── REPOSITORY              │
                    │    bentrok → 400 errors.phone STOP              │
                    │ 5) updateProfile() ──── REPOSITORY → DB         │
                    │ 6) successResponse({ user })  200               │
                    └───────────────────────┬────────────────────────┘
                                            ▼
                    ┌──────────────── UserRepository ────────────────┐
                    │ findByPhoneExcept(phone, userId)  → cek unik    │
                    │ updateProfile(id, fullName, phone) → UPDATE     │
                    │ formatUser(row) → bentuk JSON (tanpa hash!)     │
                    └─────────────────────────────────────────────────┘
```

---

# BAGIAN A — Bangun dari Bawah ke Atas

Sama seperti dokumen frontend: lapisan terdalam dulu (yang tidak bergantung apa pun), lalu naik. 🆕 = FILE BARU, ✏️ = EDIT FILE.

## Langkah 1 — ✏️ `src/Infrastructure/Repositories/UserRepository.php` (Data)

Tambahkan dua method: cek nomor telepon milik akun lain, dan update profil. Reuse pola PDO prepared-statement yang sudah dipakai `create()`/`findById()`.

```php
// tambahkan di dalam class UserRepository, mis. setelah create()

/**
 * Cari user lain (selain $exceptId) yang memakai nomor telepon ini.
 * Dipakai untuk menjaga nomor telepon tetap unik antar akun.
 */
public function findByPhoneExcept(string $phone, string $exceptId): ?array
{
    $stmt = $this->db->prepare(
        'SELECT * FROM users WHERE phone = :phone AND id != :id LIMIT 1'
    );
    $stmt->execute(['phone' => $phone, 'id' => $exceptId]);
    $user = $stmt->fetch();
    return $user ?: null;
}

/**
 * Update nama & nomor telepon user, lalu kembalikan baris terbaru.
 */
public function updateProfile(string $id, string $fullName, string $phone): ?array
{
    $stmt = $this->db->prepare(
        'UPDATE users SET full_name = :full_name, phone = :phone WHERE id = :id'
    );
    $stmt->execute([
        'full_name' => $fullName,
        'phone' => $phone,
        'id' => $id,
    ]);

    return $this->findById($id); // baca ulang → data fresh + timestamps
}
```

**Yang harus kamu jelaskan:**
- **Prepared statement** (`:phone`, `:id`) → aman dari SQL injection. Inilah kenapa kita tak pernah merangkai string SQL manual.
- `findByPhoneExcept` mengecualikan `$exceptId` (dirinya sendiri) — kalau tidak, user yang menyimpan tanpa mengubah nomor akan "bentrok dengan dirinya sendiri".
- `updateProfile` **tidak menyentuh** `email`, `password_hash`, atau `role` → kolom sensitif tak bisa diubah lewat jalur ini.
- `formatUser()` (sudah ada) tidak pernah memuat `password_hash` → hash tidak akan bocor ke respons.

> **Kenapa baca ulang dengan `findById` setelah UPDATE?** Supaya respons memuat data kanonik dari DB (termasuk `created_at`, `role`), bukan sekadar memantulkan input. Pola yang sama dipakai `create()`.

## Langkah 2 — ✏️ `src/Validation/AuthValidator.php` (Validation)

Tambahkan schema profil + daftar field yang diizinkan. **Reuse rule yang sama** dengan register (`required`, `minLength`, `maxLength`, `phoneId`) — konsistensi aturan di seluruh app.

```php
// tambahkan method di dalam class AuthValidator

public function validateProfile(array $data): array
{
    $schema = [
        'fullName' => [
            ['type' => 'required', 'message' => 'Nama lengkap wajib diisi.'],
            ['type' => 'minLength', 'min' => 3, 'message' => 'Nama lengkap minimal 3 karakter.'],
            ['type' => 'maxLength', 'max' => 100, 'message' => 'Nama lengkap maksimal 100 karakter.'],
        ],
        'phone' => [
            ['type' => 'required', 'message' => 'Nomor telepon wajib diisi.'],
            ['type' => 'phoneId', 'message' => 'Masukkan nomor telepon Indonesia yang valid.'],
        ],
    ];

    return $this->validate($data, $schema);
}

public static function allowedProfileFields(): array
{
    return ['fullName', 'phone'];
}
```

**Yang harus kamu jelaskan:**
- Schema ini **subset dari `validateRegister`** — persis aturan `fullName` & `phone`, tanpa `email`/`password`. Aturan minimal-3-karakter & format telepon Indonesia ditegakkan dua kali (FE + BE), pesannya pun konsisten.
- `allowedProfileFields()` = **whitelist**. Dipakai `Validator::sanitize()` di Action untuk membuang field liar (mis. kalau klien nakal mengirim `role: 'admin'`, field itu langsung dibuang sebelum masuk logika). Ini pertahanan **mass-assignment**.
- `phoneId` di-resolve oleh `Validator::applyRule()` → regex `^(08|628|\+628)[0-9]{8,12}$` (sudah ada, tidak menulis regex baru).

## Langkah 3 — 🆕 `src/Actions/Auth/UpdateProfileAction.php` (Action / thin controller)

Di sini guard (route), validasi, dan repository bertemu. Strukturnya meniru `RegisterAction` (constructor DI + `action()`), tapi lebih ramping.

```php
<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Actions\BaseAction;
use App\Infrastructure\Repositories\UserRepository;
use App\Validation\AuthValidator;
use App\Validation\Validator;
use Psr\Http\Message\ResponseInterface as Response;

class UpdateProfileAction extends BaseAction
{
    private UserRepository $userRepo;

    public function __construct(UserRepository $userRepo)
    {
        $this->userRepo = $userRepo;
    }

    protected function action(): Response
    {
        // ── GUARD: userId dijamin ada oleh AuthRequiredMiddleware,
        //    tapi kita tetap defensif (mirror MeAction). ──
        $userId = $this->getUserId();
        if (!$userId) {
            return $this->errorResponse('Token tidak valid atau sudah expired.', 401);
        }

        $user = $this->userRepo->findById($userId);
        if (!$user) {
            return $this->errorResponse('User tidak ditemukan.', 404);
        }

        // ── SANITIZE + VALIDATION (server-side) ──
        $body = $this->getBody();
        $data = Validator::sanitize($body, AuthValidator::allowedProfileFields());

        $validator = new AuthValidator();
        $errors = $validator->validateProfile($data);
        if (!empty($errors)) {
            return $this->errorResponse('Data profil tidak valid.', 400, $errors);
        }

        // ── ATURAN BISNIS: nomor telepon unik antar akun ──
        $clash = $this->userRepo->findByPhoneExcept($data['phone'], $userId);
        if ($clash) {
            return $this->errorResponse('Nomor telepon sudah dipakai akun lain.', 400, [
                'phone' => 'Nomor telepon sudah dipakai akun lain.',
            ]);
        }

        // ── PERSIST ──
        $updated = $this->userRepo->updateProfile($userId, $data['fullName'], $data['phone']);

        return $this->successResponse([
            'user' => UserRepository::formatUser($updated),
        ]);
    }
}
```

**Pola yang wajib kamu kuasai (paralel dengan tiga pola baru di `ProfilePage`):**
1. **Guard berlapis** — middleware sudah menolak yang belum login, tapi Action tetap cek `getUserId()` (seperti `MeAction`). Pertahanan tidak pernah cuma satu lapis.
2. **Sanitize → Validate → Bisnis → Persist** — urutan baku setiap Action mutasi data. Validasi format dulu (cepat, tak sentuh DB); aturan bisnis (cek unik) butuh DB jadi belakangan.
3. **Error per-field** (`errors.phone`) — bentuk yang sama persis yang dibaca `catch` di `ProfilePage` untuk menaruh pesan di bawah field telepon.

> **DI tanpa registrasi manual.** `UpdateProfileAction` punya `UserRepository` di constructor. PHP-DI mengandalkan **autowiring** — sama seperti `MeAction`/`RegisterAction`, tidak perlu menambah apa pun di `app/dependencies.php`. `UserRepository` membangun koneksi PDO sendiri lewat `Database::getConnection()`.

## Langkah 4 — ✏️ `app/routes.php` (Route + Guard)

Daftarkan route di dalam grup `/auth`, sebaris pola dengan `/me`. Wajib `->add(AuthRequiredMiddleware::class)`.

```php
// (1) import di blok use, dekat action Auth lainnya
use App\Actions\Auth\UpdateProfileAction;

// (2) di dalam $api->group('/auth', ...), setelah route /me:
$auth->put('/profile', UpdateProfileAction::class)->add(AuthRequiredMiddleware::class);
```

Hasil akhir grup auth:

```php
$api->group('/auth', function (Group $auth) {
    $auth->post('/register', RegisterAction::class);
    $auth->post('/login', LoginAction::class);
    $auth->post('/logout', LogoutAction::class)->add(AuthRequiredMiddleware::class);
    $auth->get('/me', MeAction::class)->add(AuthRequiredMiddleware::class);
    $auth->put('/profile', UpdateProfileAction::class)->add(AuthRequiredMiddleware::class); // ← baru
});
```

> **Kenapa `AuthRequiredMiddleware`, bukan `AdminMiddleware`?** `AuthRequiredMiddleware` = "harus login (userId ada), kalau tidak → 401". `AdminMiddleware` = "harus login **dan** role admin → 403 kalau bukan". Ubah profil cukup login biasa, jadi yang pertama. Ini analogi langsung dari pilihan `ProtectedRoute` vs guard admin di frontend.

> **Alur JWT (otomatis).** `JwtMiddleware` global (lihat `app/middleware.php`) sudah men-decode `Authorization: Bearer <jwt>` di **setiap** request dan menaruh `userId` ke request attribute. `AuthRequiredMiddleware` tinggal membaca attribute itu. Kamu tidak menulis logika token apa pun di Action.

---

# BAGIAN B — Jejak Runtime (atas ke bawah)

Skenario: user login mengirim `PUT /api/auth/profile` dengan body `{ "fullName": "Budi S", "phone": "081298765432" }` dan header `Authorization: Bearer <jwt>`.

1. **(Middleware global)** `JwtMiddleware` decode JWT → set attribute `userId = usr_xxx` di request.
2. **(Route guard)** Slim cocokkan `PUT /api/auth/profile` → jalankan `AuthRequiredMiddleware`: `userId` ada → lanjut ke Action. (Kalau kosong → balas **401**, berhenti.)
3. **(Action)** `getUserId()` → `usr_xxx`. `findById` → user ada (kalau tidak → **404**).
4. **(Action)** `getBody()` ambil JSON body → `Validator::sanitize(body, ['fullName','phone'])` membuang field lain, `trim` + `strip_tags` tiap string.
5. **(Validation)** `validateProfile($data)` → `validate()` jalankan schema → return `[]` (valid).
6. **(Bisnis)** `findByPhoneExcept('081298765432', 'usr_xxx')` → `null` (tidak bentrok) → lanjut.
7. **(Repository)** `updateProfile('usr_xxx', 'Budi S', '081298765432')` → `UPDATE users ...` → `findById` baca ulang.
8. **(Action)** `successResponse(['user' => formatUser($updated)])` → `BaseAction` bungkus jadi `{ ok:true, user:{...} }`, status **200**.
9. **(Frontend)** menerima `{ user }` → Context `setCurrentUser(user)` → nama di header ikut update → pesan "Profil berhasil diperbarui."

---

# BAGIAN C — Tiga Skenario Wajib

### Skenario 1 — Validasi gagal di server (Validation)
Klien (mis. curl yang melewati validasi frontend) kirim `fullName: ""`. `validateProfile` return `{ fullName: 'Nama lengkap wajib diisi.' }` → `errorResponse('Data profil tidak valid.', 400, errors)` → **berhenti sebelum menyentuh DB**. Membuktikan backend tidak bergantung pada validasi frontend (defense in depth).

### Skenario 2 — Nomor sudah dipakai akun lain / 400 (Bisnis)
Format nomor benar (lolos `phoneId`), tapi sudah dipakai user lain. `findByPhoneExcept` mengembalikan baris → `errorResponse('...', 400, ['phone' => 'Nomor telepon sudah dipakai akun lain.'])`. Frontend membaca `err.errors.phone` → pesan muncul **tepat di bawah field telepon**. Inilah kontrak error per-field yang membuat FE & BE klop.

### Skenario 3 — Belum login → 401 (Guard)
Request tanpa `Authorization` (atau token kedaluwarsa). `JwtMiddleware` tidak men-set `userId`. `AuthRequiredMiddleware` lihat `userId` kosong → balas **401 `{ ok:false, error:'Token tidak valid atau sudah expired.' }`** tanpa pernah memanggil Action. Di sisi frontend, `apiService` melempar `Error{ status:401 }` dan flow logout/redirect ke `/login` jalan.

---

## Beda dengan endpoint Register (biar paham, bukan copy-paste)

| Aspek | Register | Update Profil |
|---|---|---|
| Method & path | `POST /auth/register` | `PUT /auth/profile` |
| Guard | tanpa (publik) | `AuthRequiredMiddleware` (wajib login) |
| Field | fullName, email, phone, password, confirmPassword | **hanya** fullName, phone |
| Operasi DB | `INSERT` (create) | `UPDATE` (updateProfile) |
| Cek unik | email (`findByEmail`) → 409 | phone milik akun lain (`findByPhoneExcept`) → 400 |
| Password | hash bcrypt + simpan | tidak disentuh |
| Token | terbitkan JWT baru | tidak diubah |
| Respons | `{ user, token }` 201 | `{ user }` 200 |

---

## Pemetaan ke BAB Skripsi

| Bagian fitur | Lapisan | BAB |
|---|---|---|
| `validateProfile` (reuse rule) | Validation | Perancangan — Logika / Validasi Server |
| `UpdateProfileAction` + kontrak error per-field | Action / API | Integrasi Client–Server / Desain API |
| `PUT /auth/profile` + `AuthRequiredMiddleware` | Route + Middleware | Perancangan — Keamanan (Authentication guard) |
| `findByPhoneExcept` + prepared statement | Repository | Implementasi — Akses Data & Keamanan SQL |
| Validasi dua lapis (FE + BE) | Validation + Action | Pembahasan — Defense in Depth |
| Jejak runtime end-to-end | semua | Implementasi & Pengujian |

---

## ✅ Self-Check
1. Method apa saja yang ditambahkan ke `UserRepository`, dan kenapa `findByPhoneExcept` butuh parameter `$exceptId`?
2. Kenapa pakai `AuthRequiredMiddleware`, bukan `AdminMiddleware`?
3. Apa fungsi `Validator::sanitize()` + `allowedProfileFields()`, dan serangan apa yang dicegahnya?
4. Kenapa Action tetap mengecek `getUserId()` padahal sudah ada middleware?
5. Kenapa backend tetap memvalidasi `fullName`/`phone` padahal frontend sudah memvalidasi?
6. Di skenario 400 nomor-sudah-dipakai, bentuk respons apa yang membuat pesan bisa muncul di bawah field telepon di frontend?
7. Bagaimana JWT bisa berubah jadi `userId` di Action tanpa kode token di `UpdateProfileAction`?

## 🛠 Latihan
- **Field opsional `instagram`:** tambahkan kolom `instagram` (migration baru), masukkan ke `allowedProfileFields()` & schema (validator non-`required` otomatis skip nilai kosong), dan ke `updateProfile`.
- **Audit log:** catat perubahan profil ke Monolog (`LoggerInterface`) — siapa, kapan, dari nilai apa ke apa (tanpa data sensitif).
- **Unit test (capstone):** tulis test PHPUnit untuk `validateProfile` (1 valid, 1 nomor salah format) dan test repository (update lalu `findById` mengembalikan nilai baru). Gunakan skeleton di `tests/`.
- **Trace manual:** tulis urutan dari request masuk sampai respons 200, sebut middleware & method repository yang dilewati (tanpa lihat Bagian B).

## Pertanyaan Sidang
- "Di mana nama divalidasi minimal 3 karakter di backend?" → `AuthValidator::validateProfile`, rule `minLength` min 3 (di-resolve `Validator::applyRule`).
- "Kalau request tanpa token mengakses `/auth/profile`, apa yang terjadi?" → `AuthRequiredMiddleware` balas 401 sebelum Action dijalankan.
- "Bagaimana mencegah nomor telepon ganda antar akun?" → `findByPhoneExcept` cek user lain dengan nomor sama → 400 error per-field `phone`.
- "Kenapa email tidak bisa diubah di endpoint ini?" → tidak masuk `allowedProfileFields` & `updateProfile` tidak menyentuh kolom email; ubah email butuh verifikasi terpisah.
- "Apakah validasi frontend cukup?" → Tidak; request bisa dari curl/Postman, backend wajib validasi ulang + tegakkan aturan bisnis (uniqueness).
- "Bagaimana SQL injection dicegah?" → semua query pakai prepared statement berparameter, tidak ada string SQL yang dirangkai dari input.

---

## Ringkasan file (checklist implementasi)

| File | Status | Lapisan |
|---|---|---|
| `src/Infrastructure/Repositories/UserRepository.php` | ✏️ tambah `findByPhoneExcept` + `updateProfile` | Repository |
| `src/Validation/AuthValidator.php` | ✏️ tambah `validateProfile` + `allowedProfileFields` | Validation |
| `src/Actions/Auth/UpdateProfileAction.php` | 🆕 buat | Action |
| `app/routes.php` | ✏️ import + route `PUT /auth/profile` (`AuthRequiredMiddleware`) | Route + Guard |
| `app/dependencies.php` | — tidak perlu (autowiring PHP-DI) | DI |

> **Uji cepat setelah implementasi:**
> ```bash
> composer start   # http://localhost:8080
> # ambil token via POST /api/auth/login, lalu:
> curl -X PUT http://localhost:8080/api/auth/profile \
>   -H "Authorization: Bearer <jwt>" \
>   -H "Content-Type: application/json" \
>   -d '{"fullName":"Budi S","phone":"081298765432"}'
> ```
> Tanpa header → 401. Nomor sudah dipakai → 400 `errors.phone`. Valid → 200 `{ user }`.

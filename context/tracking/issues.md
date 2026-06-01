# Known Issues & Technical Debt

> Bug, issues, dan technical debt yang perlu ditangani.
> Terakhir update: 2026-06-01

---

## Security Issues

| # | Issue | Severity | Status | Detail |
|---|---|---|---|---|
| SEC-1 | Password plain text di localStorage (frontend lama) | 🔴 Critical | ✅ Fixed | Frontend sudah pakai backend auth (JWT). localStorage tidak lagi menyimpan password. |
| SEC-2 | Tidak ada rate limiting | 🟡 Medium | ⬜ Open | Login/register masih bisa di-brute-force. Tambah rate limiter di level middleware atau nginx. |
| SEC-3 | PATCH /api/orders/:id/pay masih ada | 🟡 Medium | ⬜ Open | Endpoint lama yang memungkinkan client klaim "sudah bayar" sendiri tanpa verifikasi Midtrans. Perlu di-remove atau dibatasi admin-only. |
| SEC-4 | Midtrans Server Key di .env plain | 🟢 Low | ⬜ Open | Aman selama .env di .gitignore (sudah). Rotate key secara berkala. |
| SEC-5 | Guest session token tidak punya expiry | 🟢 Low | ⬜ Open | Token disimpan tanpa TTL di DB. Tambah cleanup job untuk cart guest yang idle. |

---

## Technical Debt

| # | Issue | Priority | Status | Detail | Fix Plan |
|---|---|---|---|---|---|
| TD-1 | `PATCH /api/orders/:id/pay` tidak lagi diperlukan | P1 | ⬜ Open | Endpoint ini bypass Midtrans — bayar diakui hanya dari client. Sekarang ada webhook + polling. | Remove atau restrict ke admin only |
| TD-2 | Tidak ada tests | P1 | ⬜ Open | Zero test coverage backend. Skeleton test ada tapi belum diisi. | Unit test Actions + Validators |
| TD-3 | CSS frontend dalam 1 file besar | P2 | ⬜ Open | `app.css` ~900+ baris | CSS modules atau split per page |
| TD-4 | Gambar produk hanya 2 | P2 | ⬜ Open | 3 produk pakai CSS gradient | Foto semua 5 varian |
| TD-5 | Tidak ada SEO meta tags | P2 | ⬜ Open | Title statis, no og:tags | React Helmet atau meta |
| TD-6 | Accessibility (a11y) basic | P2 | ⬜ Open | Beberapa ARIA labels missing | Audit + fix |
| TD-7 | Rate limiting belum ada | P1 | ⬜ Open | Auth endpoint vulnerable brute force | Middleware atau nginx limit |
| TD-8 | Tidak ada 404 page | P3 | ⬜ Open | Catch-all redirect ke / | Buat 404 page |
| TD-9 | qrisService.js masih ada di frontend | P3 | ⬜ Open | Masih dipakai untuk render QR dari string. Ini sudah correct usage, bukan legacy. | Keep — tidak perlu dihapus |
| TD-10 | context.md dan context tracking masih outdated | P2 | ✅ Fixed | Sudah diupdate 2026-06-01 | — |

---

## Bugs

| # | Bug | Severity | Status | Detail | Fix |
|---|---|---|---|---|---|
| BUG-1 | CORS blocked dari localhost:5173 | 🔴 High | ✅ Fixed | `ResponseEmitter.php` (Slim skeleton) override header dari `CorsMiddleware` — `ngrok-skip-browser-warning` tidak diizinkan | Tambah header di `ResponseEmitter.php` + ganti `$_SERVER['HTTP_ORIGIN']` ke `$_ENV['CORS_ALLOWED_ORIGIN']` |

---

## Performance Concerns

| # | Issue | Impact | Catatan |
|---|---|---|---|
| PERF-1 | Polling setiap 5 detik | Medium | Bisa diganti WebSocket di masa depan, tapi polling cukup untuk MVP |
| PERF-2 | No code splitting (frontend) | Medium | Tambah `React.lazy()` jika app membesar |
| PERF-3 | Image belum dioptimasi | Low | Gambar belum di-compress / convert ke WebP |

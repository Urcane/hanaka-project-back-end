# Known Issues & Technical Debt

> Bug, issues, dan technical debt yang perlu ditangani.

---

## Security Issues

| # | Issue | Severity | Status | Detail |
|---|---|---|---|---|
| SEC-1 | Password plain text di localStorage | 🔴 Critical | ⬜ Open | Harus hash di backend. Saat ini disimpan tanpa enkripsi di browser — ini hanya untuk simulasi MVP. JANGAN deploy ke production tanpa backend. |
| SEC-2 | Tidak ada CSRF protection | 🟡 Medium | ⬜ Open | Perlu CSRF token di form saat backend ready |
| SEC-3 | Tidak ada rate limiting | 🟡 Medium | ⬜ Open | Login brute force vulnerable |
| SEC-4 | Data sensitif di client | 🟡 Medium | ⬜ Open | Semua data (termasuk password) ada di localStorage — accessible via DevTools |
| SEC-5 | Tidak ada input sanitization (XSS) | 🟢 Low | ⬜ Open | React sudah escape by default, tapi backend perlu sanitize |

---

## Technical Debt

| # | Issue | Priority | Status | Detail | Fix Plan |
|---|---|---|---|---|---|
| TD-1 | Semua data di localStorage | P0 | ⬜ Open | Tidak ada persistensi server | Integrasi backend API |
| TD-2 | Produk hardcoded | P0 | ⬜ Open | Data statis di `products.js` | Pindah ke database + API |
| TD-3 | QRIS simulasi | P0 | ⬜ Open | QR dari string, bukan real payment | Integrasi Midtrans/Xendit |
| TD-4 | Tidak ada loading states | P1 | ⬜ Open | UX buruk saat async operations | Tambah spinner/skeleton |
| TD-5 | Tidak ada error boundary | P1 | ⬜ Open | App crash jika network error | React Error Boundary |
| TD-6 | Tidak ada tests | P1 | ⬜ Open | Zero test coverage | Unit test model layer |
| TD-7 | CSS dalam 1 file besar | P2 | ⬜ Open | `app.css` ~500 baris | CSS modules atau split per page |
| TD-8 | Gambar produk hanya 2 | P2 | ⬜ Open | 3 produk pakai gradient | Foto semua varian |
| TD-9 | Image tanpa optimization | P2 | ⬜ Open | Tidak ada lazy load, WebP, srcset | Optimize assets |
| TD-10 | Tidak ada SEO meta tags | P2 | ⬜ Open | Title statis, no description | React Helmet atau meta |
| TD-11 | Accessibility (a11y) basic | P2 | ⬜ Open | Beberapa ARIA labels missing | Audit + fix |
| TD-12 | Tidak ada 404 page | P3 | ⬜ Open | Catch-all redirect ke / | Buat 404 page |
| TD-13 | Tidak ada favicon proper | P3 | ⬜ Open | Masih default | Buat branding favicon |

---

## Bugs

| # | Bug | Severity | Status | Steps to Reproduce | Fix |
|---|---|---|---|---|---|
| — | *(Belum ada bug tercatat)* | — | — | — | — |

---

## Performance Concerns

| # | Issue | Impact | Catatan |
|---|---|---|---|
| PERF-1 | Context re-renders | Low | React Compiler handles ini, tapi perlu monitor jika state bertambah |
| PERF-2 | No code splitting | Medium | Semua pages di-load sekaligus. Tambah `React.lazy()` jika app membesar |
| PERF-3 | Font loading (Google) | Low | Bisa tambah `font-display: swap` dan preload |
| PERF-4 | Image sizes | Low | Gambar belum di-compress optimal |

---

## Cara Menambah Issue

Format:
```markdown
| TD-XX | [Deskripsi singkat] | [P0/P1/P2/P3] | ⬜ Open | [Detail] | [Rencana fix] |
```

Severity/Priority:
- **P0**: Blocker — harus diperbaiki sebelum production
- **P1**: Important — perbaiki segera setelah P0 selesai
- **P2**: Nice-to-have — perbaiki jika ada waktu
- **P3**: Backlog — someday/maybe

Status:
- ⬜ Open
- 🔄 In Progress
- ✅ Fixed
- ❌ Won't Fix

# Architecture Decision Records (ADR)

> Catatan keputusan arsitektur yang sudah diambil untuk project Hanaka Cake.

---

## ADR-001: React 19 + Vite 8 untuk Frontend

**Status**: ✅ Diterima  
**Tanggal**: Mei 2026

**Konteks**: Perlu memilih frontend framework dan build tool.

**Keputusan**: Menggunakan React 19 dengan Vite 8 + React Compiler.

**Alasan**:
- React 19 mendukung React Compiler (auto-memoization)
- Vite 8 sangat cepat untuk development (HMR instant)
- Ekosistem React matang dan luas

**Konsekuensi**:
- Pakai `babel-plugin-react-compiler` via `@rolldown/plugin-babel`
- Kode harus comply dengan rules React Compiler (no side effects in render)
- Bundle size lebih kecil karena compiler menghilangkan unnecessary re-renders

---

## ADR-002: LocalStorage sebagai Temporary Persistence

**Status**: ✅ Diterima (temporary, akan di-replace)  
**Tanggal**: Mei 2026

**Konteks**: Butuh persistensi data selama backend belum ada.

**Keputusan**: Simpan semua data (users, carts, orders) di localStorage.

**Alasan**:
- Memungkinkan full UI/UX testing tanpa server
- Cepat untuk prototyping business flow
- Abstraksi via `storageService.js` memudahkan migrasi nanti

**Konsekuensi**:
- Data hilang kalau user clear browser
- Password plain text (simulasi saja)
- **HARUS diganti ke backend API saat fase integrasi**

---

## ADR-003: Custom Validation (Tanpa Library)

**Status**: ✅ Diterima  
**Tanggal**: Mei 2026

**Konteks**: Perlu validasi form yang mendukung Bahasa Indonesia dan conditional rules.

**Keputusan**: Buat framework validasi sendiri di `src/validation/customValidation.js`.

**Alasan**:
- Zero external dependency → bundle lebih kecil
- Full kontrol atas pesan error (Bahasa Indonesia)
- Belajar fundamental validasi
- Schema-based + conditional (`when()`) sudah cukup untuk kebutuhan ini

**Konsekuensi**:
- Maintenance manual jika perlu rule baru
- Tidak ada komunitas/docs seperti Yup/Zod
- Tetap bisa di-replace dengan library nanti jika project membesar

---

## ADR-004: Context Split Pattern (3 File)

**Status**: ✅ Diterima  
**Tanggal**: Mei 2026

**Konteks**: ESLint `react-refresh/only-export-components` error jika provider dan hook di-export dari file yang sama.

**Keputusan**: Split React Context menjadi 3 file:
1. `appContextObject.js` — hanya `createContext(null)`
2. `useApp.js` — hanya custom hook
3. `AppContext.jsx` — hanya `AppProvider` component

**Alasan**:
- Mematuhi ESLint react-refresh rule
- HMR tidak break saat edit provider
- Separation of concerns jelas

**Konsekuensi**:
- Import path sedikit lebih verbose
- Pola ini WAJIB dipertahankan — jangan merge kembali ke 1 file

---

## ADR-005: Slim PHP 4 + MySQL untuk Backend

**Status**: 📋 Planned  
**Tanggal**: Mei 2026

**Konteks**: Butuh backend untuk persistensi real, auth aman, dan payment gateway.

**Keputusan**: Slim PHP 4 sebagai REST API + MySQL 8 untuk database.

**Alasan**:
- Slim ringan, cocok untuk REST API tanpa overhead besar
- MySQL stabil dan mature
- PHP + MySQL tersedia di hampir semua hosting Indonesia
- Cocok untuk project skala kecil-menengah

**Alternatif yang dipertimbangkan**:
- Laravel → terlalu berat untuk scope ini
- Express.js + PostgreSQL → bisa, tapi owner lebih familiar PHP
- Supabase/Firebase → vendor lock-in

---

## ADR-006: Bilingual Code Convention

**Status**: ✅ Diterima  
**Tanggal**: Mei 2026

**Konteks**: Target user Indonesia, tapi kode harus maintainable secara global.

**Keputusan**:
- **Kode** (variable, function, class, comment): Bahasa Inggris
- **UI text** (label, error message, placeholder): Bahasa Indonesia

**Contoh**:
```js
// Code in English
function validateCheckoutInput(values) { ... }

// UI text in Indonesian
validators.required('Nama pelanggan wajib diisi.')
```

---

## ADR-007: CSS Murni (Tanpa Framework)

**Status**: ✅ Diterima  
**Tanggal**: Mei 2026

**Konteks**: Perlu styling yang ringan tanpa dependency besar.

**Keputusan**: CSS murni dengan custom properties, tanpa Tailwind/Bootstrap/dll.

**Alasan**:
- Zero build overhead untuk CSS
- Full kontrol atas design token
- Bundle size minimal
- Belajar CSS fundamental

**Konsekuensi**:
- Semua style dalam `app.css` (satu file besar)
- Naming convention: BEM-like dengan kebab-case
- Responsive manual via `@media (max-width: 900px)`
- Bisa di-refactor ke CSS Modules jika project membesar

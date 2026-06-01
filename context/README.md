# Hanaka Cake — Context Documentation

> Folder ini berisi seluruh konteks project yang diorganisir per fitur/topik.
> Digunakan sebagai referensi bagi developer dan AI assistant.

---

## Struktur Folder

```
context/
├── README.md                 ← Kamu di sini
├── architecture.md           # Keputusan arsitektur (ADR)
│
├── frontend/                 # Konteks frontend per fitur
│   ├── overview.md           # Tech stack, struktur folder, konvensi
│   ├── routing.md            # Routes & navigation
│   ├── auth.md               # Autentikasi (login/register)
│   ├── products.md           # Katalog produk & data
│   ├── cart.md               # Keranjang belanja
│   ├── checkout.md           # Checkout flow
│   ├── payment.md            # QRIS payment
│   ├── validation.md         # Custom validation framework
│   └── styling.md            # CSS & design system
│
├── backend/                  # Konteks backend per fitur
│   ├── overview.md           # Tech stack, struktur folder, setup
│   ├── database.md           # Schema, ERD, migrations
│   ├── api-auth.md           # Auth API endpoints
│   ├── api-products.md       # Products API
│   ├── api-cart.md           # Cart API
│   ├── api-orders.md         # Orders API
│   ├── api-payment.md        # Payment API
│   └── security.md           # Security requirements & checklist
│
├── history/                  # Development history & tracking
│   ├── changelog.md          # Sprint-by-sprint changelog
│   ├── sessions.md           # Chat session log
│   └── decisions.md          # Architecture Decision Records
│
└── tracking/                 # Progress tracking
    ├── features.md           # Feature backlog & status
    ├── issues.md             # Known issues & technical debt
    └── integration.md        # Frontend ↔ Backend integration plan
```

---

## Cara Penggunaan

### Untuk AI Assistant
Baca file yang relevan sebelum mulai kerja. Contoh:
- Mau ubah cart? Baca `frontend/cart.md` + `backend/api-cart.md`
- Mau setup backend? Baca `backend/overview.md` + `backend/database.md`
- Mau cek progress? Baca `tracking/features.md`

### Untuk Developer
- Update `history/changelog.md` setiap kali selesai sprint
- Update `history/sessions.md` setiap kali ada sesi dengan AI
- Update `tracking/features.md` setiap kali fitur selesai atau ditambah
- Update `tracking/issues.md` jika menemukan bug/tech debt baru

---

## Quick Links

| Topik | File |
|---|---|
| Gambaran besar project | `architecture.md` |
| Cara setup frontend | `frontend/overview.md` |
| Cara setup backend | `backend/overview.md` |
| Database schema | `backend/database.md` |
| Semua API endpoints | `backend/api-*.md` |
| Progress fitur | `tracking/features.md` |
| Bug & tech debt | `tracking/issues.md` |
| Rencana integrasi FE↔BE | `tracking/integration.md` |

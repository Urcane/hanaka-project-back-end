# Frontend — Styling & Design System

> CSS conventions, design tokens, dan responsive approach.

---

## Approach

- **CSS murni** — tanpa Tailwind, Bootstrap, atau framework lainnya
- **2 file CSS**: `src/index.css` (global variables + body) dan `src/styles/app.css` (semua komponen)
- **Google Fonts**: Fraunces (heading) + Manrope (body)
- **CSS Custom Properties** untuk theming
- **Responsive**: single breakpoint `@media (max-width: 900px)`

---

## Design Tokens (CSS Variables)

### `src/index.css`
```css
:root {
  --bg-cream: #fff8ee;
  --bg-blush: #ffe3cf;
  --surface: rgba(255, 255, 255, 0.82);
  --ink: #35241a;
  --muted: #6e5646;
  --accent: #d4694c;
  --accent-2: #a56f35;
  --stroke: rgba(94, 58, 36, 0.16);
  --shadow: 0 24px 60px rgba(67, 40, 25, 0.14);
}
```

### `src/styles/app.css`
```css
:root {
  --surface: rgba(255, 250, 244, 0.9);
  --surface-strong: #fff7ee;
  --ink: #2f2018;
  --muted: #6f5848;
  --accent: #c8683d;
  --accent-dark: #934428;
  --line: rgba(99, 61, 40, 0.18);
  --danger: #b13f3f;
  --success: #2f7f51;
  --radius-lg: 24px;
  --radius-md: 14px;
  --shadow-soft: 0 20px 48px rgba(69, 41, 27, 0.13);
}
```

---

## Typography

| Role | Font | Weight |
|---|---|---|
| Heading (h1, h2, h3) | Fraunces | 500, 700 |
| Body | Manrope | 400, 500, 600, 700 |
| Fallback | 'Segoe UI', sans-serif | — |

---

## Color Palette

| Token | Hex | Usage |
|---|---|---|
| `--ink` | `#2f2018` | Body text |
| `--muted` | `#6f5848` | Secondary text |
| `--accent` | `#c8683d` | Primary buttons, links |
| `--accent-dark` | `#934428` | Nav active, dark sections |
| `--danger` | `#b13f3f` | Error messages |
| `--success` | `#2f7f51` | Status badges |
| `--line` | `rgba(99,61,40,0.18)` | Borders |
| `--surface` | `rgba(255,250,244,0.9)` | Card backgrounds |

---

## Naming Convention

- **kebab-case** untuk semua class names
- **State modifier**: `.is-active`, `.is-selected`
- **Component-scoped**: prefix dengan nama section (e.g. `cart-table-row`, `detail-hero-img`)
- **Utility-like**: `stack-gap-lg`, `stack-gap-md`, `muted-text`, `inline-button`

---

## Button Variants

| Class | Appearance | Usage |
|---|---|---|
| `.primary-button` | Orange/accent, white text | Primary CTA |
| `.secondary-button` | White, bordered | Secondary action |
| `.ghost-button` | Transparent, bordered | Tertiary (e.g. logout) |
| `.danger-button` | Light red, red text | Destructive action |

---

## Layout Patterns

### `.app-shell`
```css
display: grid;
grid-template-rows: auto 1fr auto;  /* header | main | footer */
```

### `.page-shell`
```css
width: min(1150px, 100%);
margin: 0 auto;
padding: 20px clamp(12px, 3vw, 24px) 30px;
```

### `.panel`
Standard card container:
```css
border: 1px solid var(--line);
border-radius: var(--radius-lg);
padding: clamp(16px, 2vw, 22px);
background: var(--surface);
box-shadow: var(--shadow-soft);
```

### `.stack-gap-lg` / `.stack-gap-md`
Vertical stack with gap:
```css
display: grid;
gap: 18px; /* lg */
gap: 12px; /* md */
```

---

## Responsive Breakpoint

Single breakpoint: `max-width: 900px`

Changes at mobile:
- `.detail-layout`, `.checkout-grid`, `.fulfillment-toggle` → single column
- `.cart-table-head` → hidden
- `.cart-table-row` → single column, no grid
- `.site-header` → flex-wrap
- `.site-nav` → flex-wrap
- `.site-footer` → single column

---

## Background

Body menggunakan layered gradient:
```css
background:
  radial-gradient(circle at 12% 20%, rgba(255,255,255,0.95) 0, transparent 38%),
  radial-gradient(circle at 88% 10%, rgba(248,207,177,0.85) 0, transparent 34%),
  linear-gradient(160deg, var(--bg-cream) 0%, var(--bg-blush) 100%);
```

---

## File Terkait

- `src/index.css` — Global variables, body background, reset
- `src/styles/app.css` — All component styles
- `index.html` — No external CSS (fonts loaded via @import in app.css)

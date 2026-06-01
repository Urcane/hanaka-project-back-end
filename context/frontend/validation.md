# Frontend — Custom Validation Framework

> Schema-based validation system yang dibangun tanpa library eksternal.

---

## Overview

File: `src/validation/customValidation.js`

- **Schema-based**: setiap form punya schema (object of field → validators[])
- **First-error-per-field**: stop validasi field setelah menemukan error pertama
- **Conditional validation**: via `when(predicate, validator)`
- **Pesan dalam Bahasa Indonesia**
- **Reusable validators** yang bisa dikombinasikan

---

## Core API

### `validateSchema(schema, values) → errors`

```js
const schema = {
  email: [
    validators.required('Email wajib diisi.'),
    validators.email('Format email belum valid.'),
  ],
  password: [
    validators.required('Password wajib diisi.'),
    validators.strongPassword(),
  ],
}

const errors = validateSchema(schema, formValues)
// → { email: "Email wajib diisi." } atau {} jika valid
```

### `hasAnyError(errors) → boolean`

```js
if (hasAnyError(errors)) {
  // Ada error, jangan submit
  return
}
```

---

## Available Validators

| Validator | Signature | Keterangan |
|---|---|---|
| `required` | `(message?)` | Field tidak boleh kosong (setelah trim) |
| `email` | `(message?)` | Format email valid (`x@x.x`) |
| `phoneId` | `(message?)` | Nomor telepon Indonesia (`+62/62/08...`) |
| `minLength` | `(min, message?)` | Minimal N karakter |
| `maxLength` | `(max, message?)` | Maksimal N karakter |
| `oneOf` | `(allowedValues[], message?)` | Harus salah satu dari array |
| `numeric` | `(message?)` | Harus angka |
| `minNumber` | `(min, message?)` | Angka minimal N |
| `maxNumber` | `(max, message?)` | Angka maksimal N |
| `strongPassword` | `(message?)` | Min 8 char + huruf + angka |
| `sameAs` | `(fieldName, message?)` | Sama dengan field lain |

---

## Conditional Validation (`when`)

```js
import { when } from '../validation/customValidation.js'

const schema = {
  address: [
    when(
      (values) => values.pickupMethod === 'delivery',
      validators.required('Alamat wajib diisi.')
    ),
  ],
}
```

`when(predicate, validator)` — validator hanya dijalankan jika `predicate(values)` return `true`.

---

## Validator Implementation Pattern

Setiap validator adalah **higher-order function** yang return function:

```js
validators.required = (message = 'Field ini wajib diisi.') => (value) => {
  return normalizeText(value) ? null : message
}

validators.sameAs = (fieldName, message) => (value, values) => {
  return normalizeText(value) === normalizeText(values[fieldName]) ? null : message
}
```

- Return `null` → valid
- Return `string` → error message

---

## Usage Pattern di Component

```jsx
import { validateSchema, hasAnyError } from '../validation/customValidation.js'

function MyForm() {
  const [formValues, setFormValues] = useState({...})
  const [errors, setErrors] = useState({})

  const handleSubmit = (event) => {
    event.preventDefault()
    const validationErrors = validateSchema(mySchema, formValues)
    setErrors(validationErrors)
    if (hasAnyError(validationErrors)) return
    // proceed...
  }

  return (
    <form onSubmit={handleSubmit} noValidate>
      <input name="email" value={formValues.email} onChange={handleChange} />
      {errors.email && <span className="field-error">{errors.email}</span>}
    </form>
  )
}
```

---

## Digunakan di:

| Model | Schema |
|---|---|
| `authModel.js` | `registrationSchema`, `loginSchema` |
| `cartModel.js` | Dynamic schema per-product (customization) |
| `checkoutModel.js` | `checkoutSchema` (conditional fields) |

---

## Text Normalization

```js
function normalizeText(value) {
  if (value === null || value === undefined) return ''
  return String(value).trim()
}
```

- `null`/`undefined` → empty string
- Selalu `trim()` sebelum validasi
- Validators skip jika value empty (kecuali `required`)

---

## File Terkait

- `src/validation/customValidation.js` — Framework core
- `src/models/authModel.js` — Auth validation schemas
- `src/models/cartModel.js` — Cart customization validation
- `src/models/checkoutModel.js` — Checkout validation schema

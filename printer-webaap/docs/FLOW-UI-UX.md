# Customer UI/UX Flow — Home to Receipt

Last updated: 2026-11-04
Branch at time of writing: `fitur/dialog` (commit `5f31d8c`)

This document describes the **customer-facing print ordering flow** of the
"Nama Print" Laravel app: from the home page, through PDF upload, the print
form, payment confirmation, up to the receipt page. It also lists every known
error path and what the UI does when it happens.

Audience: another AI or developer who must change this flow without breaking it.
Everything here is derived from the current code; if code and this document
disagree, **the code wins** and this document must be updated.

Paths are relative to the app root (`printer-webaap/`).

---

## 1. Flow at a glance

```
GET /                          Home (service picker)
  └─ "Cetak Terjadwal"
       ↓
GET /upload/terjadwal          Upload PDF (max 10, PDF only)
  └─ "Lanjutkan" → POST /upload/terjadwal  (XHR + loading overlay)
       ↓
GET /formulir/terjadwal?order= Print form + price estimate
  └─ "Lanjut proses pembayaran" → POST /order/create (native form submit)
       ↓
GET /order/konfirmasi/{id}     Payment confirmation + 20-minute countdown
  ├─ QRIS  → POST /order/token → Midtrans Snap popup
  └─ Kasir → POST /order/konfirmasi/{id}/kasir
       ↓
GET /struk/{id}                Receipt / payment status
  └─ polls GET /struk/order/{id} every 3s until paid / failed
```

Only **Terjadwal** (scheduled pickup) is wired end to end. **Cetak Kilat** is
still a static prototype (see §7).

### Route table

| Method | Path | Controller / view | Purpose |
| --- | --- | --- | --- |
| GET | `/` | `BerandaController` → `beranda.blade.php` | Service picker + resume in-progress order |
| GET | `/upload/terjadwal` | `UploadTerjadwalController::show` → `upload-terjadwal.blade.php` | Start a new order |
| POST | `/upload/terjadwal` | `UploadTerjadwalController::store` | Store PDFs, create `orders` + `files`, return file ids |
| GET | `/formulir/terjadwal` | `FormulirTerjadwalController::get` → `formulir-terjadwal.blade.php` | Print settings + customer data |
| POST | `/order/create` | `OrderController::createorder_terjadwal` | Validate + compute price, then redirect to confirmation |
| GET | `/order/konfirmasi/{order}` | `KonfirmasiPembayaranController::show` → `order-konfirmasi.blade.php` | Method picker, timer, pay button |
| POST | `/order/konfirmasi/{order}/kasir` | `KonfirmasiPembayaranController::bayarDiKasir` | Mark order as pay-at-cashier |
| POST | `/order/token` | `OrderController::bayarSekarang` | Create Midtrans Snap token |
| GET | `/struk/{id}` | `strukController::get` → `test_struk.blade.php` | Receipt shell (static markup, filled by JS) |
| GET | `/struk/order/{id}` | `OrderController::strukOrder` | JSON status; also reconciles payment with Midtrans |
| GET | `/terjadwal/keluar` | inline closure in `routes/web.php` | Leave the flow (forget session) → `/` |

---

## 2. Shared concepts

### 2.1 Services

| Service id (`files.layanan`) | Label | Payment methods | Notes |
| --- | --- | --- | --- |
| `terjadwal` | "Terjadwal" | QRIS only | Active end-to-end flow |
| `kilat-langsung` | "Ambil di tempat" | QRIS or cashier | Confirmation page ready, no upload/order UI yet |
| `kilat-edit` | "Ambil di tempat" | Cashier only | Price not final; total shown as `Rp. —` |

`KonfirmasiPembayaranController` derives the service from the **first file** of
the order. A single order never mixes services.

### 2.2 Order status

`orders.status_pembayaran` is `ENUM('paid','pending','failed','refunded','processing')`.

```
pending ──(QRIS paid, receipt poll)──> paid
   │
   ├──(cashier chosen)──────────────> processing ──(seller confirms)──> paid   [not implemented yet]
   ├──(expired)─────────────────────> failed
   └──(Midtrans deny/cancel/expire)─> failed
                                      refunded (Midtrans refund)
```

`processing` has **two different meanings**:

1. Cashier flow: user chose "Bayar di Kasir", waiting for seller.
2. Short-lived claim while the receipt page asks Midtrans for a status.

The confirmation page treats `processing` + `metode_pembayaran = kasir` as the
"waiting for cashier" state; any other `processing` order is redirected to the
receipt page.

### 2.3 Session vs `orders.browser_session`

- Laravel session key `terjadwal_order` holds the active order while the user
  is inside the flow:
  `{ orderid (hex), sessionid, files {hex → {id,name,total_pages,localPath}}, where }`.
- `orders.browser_session` stores the Laravel session id of the browser that
  uploaded the files. It is the **source of truth for resuming** after a reload
  or a lost session.
- `orders.session_id` points to the `user` row (name + WhatsApp) created when
  the form is submitted.

Cleared when:

| Event | What is cleared | Code |
| --- | --- | --- |
| Upload page opened | session key + `browser_session` of this browser's pending orders | `UploadTerjadwalController::show` |
| Payment paid | session key + `browser_session` | `OrderController::strukOrder` |
| Expired / failed / refunded / done | session key + `browser_session` | `BerandaController::lupakanOrder` |
| "Kembali" on upload page | session key only (`/terjadwal/keluar`) | `routes/web.php` |

### 2.4 Client storage

| Key | Storage | Written by | Cleared by |
| --- | --- | --- | --- |
| `printer:formulir-terjadwal` | `localStorage` | Upload success (`gabungFileKeFormulir`), form inputs | Upload page load, `localStorage.clear()` after paid |
| `printer:file-names` | `sessionStorage` | Upload page before POST | Upload page load |
| `snap-token` | `localStorage` | `bukaSnap()` | Form page load (`removeItem`), paid/error path |

Never store secrets here. `snap-token` is only a Midtrans Snap token string.

### 2.5 Time limits (`App\Service\KedaluwarsaOrder`)

| Limit | Start | Duration | Applies to |
| --- | --- | --- | --- |
| Upload limit | `orders.tanggal_pesan` | 60 minutes | pending orders that never opened confirmation |
| Payment limit | `orders.tanggal_bayar` | 20 minutes | pending orders that opened confirmation / requested a token |

- Opening the confirmation page stamps `tanggal_bayar` **once** (`tandaiBayar`),
  so a reload does **not** reset the countdown.
- Returning from confirmation to the form (`FormulirTerjadwalController::get`)
  clears `tanggal_bayar`; the countdown starts again the next time the
  confirmation page is opened. The 60-minute upload limit still applies.
- When an order expires: status becomes `failed` and **all uploaded files are
  deleted from storage** (`KedaluwarsaOrder::kadaluarsakan`). The order row stays.
- Expiry is checked lazily (home, form, confirmation, token, cashier endpoint,
  dashboard sweep) — there is no background cron.

### 2.6 Money rules

- Per file: `subtotal = price_per_page(paper, color) × pages_to_print × copies + Σ package prices`.
- Order: `total_pembayaran = Σ file subtotal + biaya_admin`.
- `biaya_admin` is read from `pengaturan_toko.biaya_admin` (row id 1) on the
  confirmation page, falling back to `orders.biaya_admin` if settings are
  missing. The confirmation page then **syncs** `orders.biaya_admin` and
  `orders.total_pembayaran` so Midtrans charges what the user sees.
- `kilat-edit` never syncs: its total stays empty (`Rp. —`) until the cashier
  recalculates after editing.
- Handling fee is a placeholder: computed as `total − subtotal − admin`, always
  Rp 0 today. Receipt hides the row when it is 0.
- Formatting is always `Rp. 5.500` (dot as thousands separator).

---

## 3. Step by step

### Step 0 — Home `/`

**What the user sees**

- Header: "logo jasa" placeholder + "nama print".
- Two sketch buttons: **Cetak Kilat** and **Cetak Terjadwal**.
- Optional red/grey notice panel when the server redirected here with an error
  (`session('error')`), e.g. files deleted because the order expired.

**Actions**

- "Cetak Terjadwal" navigates to `/upload/terjadwal` (`data-href`).
- "Cetak Kilat" currently has **no destination** (static prototype).

**Resume logic — `BerandaController::__invoke`**

Before rendering the picker, the controller looks up the newest order with
`browser_session = current session id`:

| Order state | Result |
| --- | --- |
| No order | Show home |
| Expired | Delete files, mark `failed`, clear marker → `/` with error notice |
| `paid` | Clear marker → show home |
| `processing` + `kasir` | Rebuild session → confirmation page (waiting for cashier) |
| `processing` (other) | Redirect to `/struk/{id}` |
| `pending` + `total_pembayaran > 0` | Rebuild session → confirmation page |
| `pending` + `total_pembayaran = 0` | Rebuild session → form page |
| `failed` / `refunded` / other | Clear marker → show home |

Rebuilding the session (`pulihkanSession`) re-reads files from the database and
storage, so the user can continue even after cookies were preserved but the
Laravel session payload was lost.

---

### Step 1 — Upload `/upload/terjadwal`

**What the user sees**

- Same header/brand as home, with a **Kembali** button (→ `/terjadwal/keluar`).
- Panel: badge "Cetak Kilat 1 Jam Jadi", title "Tinggal Upload PDF, Langsung
  Jadi", dropzone card, "Pilih File PDF Sekarang" button.
- After choosing files: `N / 10 file dipilih` + a list (name, size, remove ✕).
- Bottom: **Lanjutkan**.

**Client-side rules (`resources/js/pages/upload-terjadwal.js`)**

- Accepts only `application/pdf` (by MIME or `.pdf` extension); non-PDF files
  are skipped with a message.
- Max 10 (`data-max`); extra files are skipped with a message.
- Duplicates (same name + size) are ignored.
- Opening this page **resets** the flow: clears `printer:formulir-terjadwal`,
  `printer:file-names`, the session key and releases this browser's pending
  `browser_session` markers.

**Upload interaction (important for UX)**

1. Click **Lanjutkan** with no file → inline warning "Pilih minimal satu file
   PDF dulu."; nothing is sent.
2. Otherwise the button becomes **"Mengunggah…"**, is disabled, and a full-screen
   loading card appears:
   - dashed rotating spinner, title "Mengunggah file…"
   - text "Mengunggah N file PDF. Jangan tutup halaman ini."
   - real upload progress bar + percentage (XHR `upload.progress`)
   - at 100%: "File terkirim. Menyiapkan halaman formulir…"
3. File names are stored in `sessionStorage` *before* the request.
4. `POST /upload/terjadwal` (multipart) returns
   `{ redirect, callback: [{ id, name, total_pages }] }`; ids are merged into
   `printer:formulir-terjadwal` (`gabungFileKeFormulir`), then the browser
   navigates to `redirect`.
5. On failure the loading card closes, the button returns to "Lanjutkan", and
   the server/network message appears inline under the file list.

**Server-side (`UploadTerjadwalController`)**

- Validates `files` → required, array, 1–10, each `mimes:pdf`.
- Creates one `orders` row (`pending`, `browser_session = session id`) and one
  `files` row per PDF inside a DB transaction. Page count comes from
  `smalot/pdfparser`.
- Stores files under `storage/app/private/orders/{orderHex}/`.
- JSON response only when the request expects JSON (the page's XHR does).

---

### Step 2 — Form `/formulir/terjadwal?order={hex}`

**Guard before render (`FormulirTerjadwalController::get`)**

| Condition | Result |
| --- | --- |
| No session order | Page still renders with `modal-formulir` open: "Sesi formulir tidak ditemukan…" |
| Session order hex not found | Redirect `/` with "Order tidak ditemukan…" |
| Order expired | Delete files, mark `failed`, redirect `/` with expiry message |
| Order status ≠ `pending` | Redirect to confirmation page |
| `tanggal_bayar` set (came back from confirmation) | Clear it → payment countdown restarts later |

Unusable data is reported in the `modal-formulir` dialog: no files, unreadable
page count, no paper rows, or all paper prices 0.

**What the user sees**

- Header brand + **Kembali** button (always → `/upload/terjadwal`, i.e. start over).
- Customer fields: Nama lengkap, WhatsApp, Tanggal ambil, Pukul.
- Per-file print settings, switchable with a file combo (multiple uploads):
  orientation, paper size (radio, carries `harga_color` / `harga_grayscale`),
  color (default **hitam putih**), quality, copies, custom pages, notes,
  optional add-on packages.
- Per-file subtotal and order subtotal, live.
- **Lanjut proses pembayaran**.

**State handling**

- Every change is written to `printer:formulir-terjadwal`, keyed per file name,
  including the server-provided `id`, `total_pages`, and `id_paper`.
- `id_paper` is not sent by the upload page; it is resolved from the selected
  paper radio (by radio name) both on init and again at submit.
- Returning to this page keeps previous input (localStorage), so "edit order"
  works without re-uploading.

**Inline validation (client, on change and on submit)**

| Field | Rule | Message |
| --- | --- | --- |
| WhatsApp | digits after trimming spaces, `-`, `.`, `(`, `)`; must match `(+62|0)8…`, 10–14 digits | "Nomor whatsapp tidak valid. Contoh: 08123456789." |
| Tanggal | required, not before today | "Tanggal tidak boleh sebelum hari ini." |
| Pukul | required; if today, not earlier than now | "Pukul tidak boleh lebih awal dari waktu sekarang." |
| Halaman | optional; same grammar as server | "Format halaman tidak valid…", "Range… tidak valid", "Halaman N melebihi total halaman file (M halaman)." |

On submit the first invalid field is scrolled into view and focused.

**Custom page validation on submit (all files)**

- Invalid format / range / page beyond that file's `total_pages` → `modal-halaman`
  ("Custom halaman tidak sesuai", message names the file).
- Missing `id_paper` → `modal-halaman` ("Ukuran kertas belum dipilih").
- No saved form data → `modal-halaman` ("Data formulir tidak ditemukan").
- `modal-halaman` only closes; it does **not** navigate away.

**Submit (`POST /order/create`)**

The page builds hidden inputs from localStorage and calls `requestSubmit()`
(native navigation, not fetch):

```
orderid, nama, no_wa, tanggal_ambil (ISO 8601 with offset),
files[i][id|orientasi|ukuran|warna|kualitas|jumlah|halaman|catatan|id_paper|paket[]]
```

Server (`createorder_terjadwal`):

1. `orderid` must match the session order, otherwise redirect back with an error.
2. Validate all fields (name, WA 10–14, ISO pickup time ≥ now+5h, files 1–10,
   orientation ∈ portrait/landscape, color ∈ berwarna/hitam putih,
   quality ∈ rendah/standar/tinggi, copies ≥ 1, `id_paper` required,
   custom pages rule).
3. Reject pickup times outside store hours/open state (`JadwalToko`).
4. Reject expired orders (deletes files, redirect `/`).
5. Reject non-pending orders (redirect to confirmation).
6. In a transaction with `lockForUpdate`: recompute each file's subtotal
   (pages within `total_pages`, paper price by color, copies, packages),
   rewrite `configuration`, reset old package rows, then update the order with
   `biaya_admin`, `total_pembayaran`, `total_dibayar = 0`, `session_id`.
7. Redirect to `/order/konfirmasi/{hex}`.

Any validation or business error makes Laravel redirect back; the form page
renders `modal-info` (type error) automatically with the first message, or
`modal-info` with `session('error')` for thrown exceptions. Closing it keeps the
user on the form (data is still in localStorage).

---

### Step 3 — Confirmation `/order/konfirmasi/{hex}`

**Guard before render (`KonfirmasiPembayaranController::show`)**

| Condition | Result |
| --- | --- |
| Unknown / invalid hex | Redirect `/` + "Order tidak ditemukan. Silakan upload ulang file Anda." |
| `paid` or `processing` (not cashier) | Redirect `/struk/{hex}` |
| Any other non-`pending` status | Redirect `/` + "Order ini sudah tidak bisa dibayar karena statusnya …" |
| Expired | Delete files, mark `failed`, redirect `/` + expiry message |
| First visit for this order | Stamp `tanggal_bayar`, compute `sisa_detik` |
| `processing` + `kasir` | Render "menunggu kasir" state (no timer, CTA disabled) |

**What the user sees**

- Header brand + **Kembali**.
- Title "Konfirmasi Pembayaran".
- Payment countdown pill: "Sisa waktu pembayaran MM:SS" (hidden in cashier-waiting state).
- Badge: "Terjadwal" or "Ambil di tempat".
- Card "Rincian dokumen" (per file: name, pages, size, color, price).
- Card "Pilih Metode Pembayaran" with selectable options (arrow keys + Space/Enter
  supported, `role="radiogroup"`). Options depend on the service (§2.1).
- Card "Ringkasan Pembayaran": subtotal, Biaya Admin, optional Biaya Penanganan.
- Sticky footer: "Total Bayar" + CTA whose label follows the selected method
  ("Bayar Sekarang Via QRIS" / "Bayar di Kasir").

**Payment countdown**

- Initial value comes from the server (`sisa_detik`, derived from
  `tanggal_bayar + 20 min`); each second is decremented client-side.
- Reaching `00:00` stops the countdown, disables the CTA, and opens
  `modal-kadaluarsa` (non-dismissible, single button "Kembali ke beranda" → `/`).
- The order itself is only marked `failed` on the next server-side expiry check.
- In the cashier-waiting state there is no countdown at all.

**Clicking the CTA (all methods)**

1. Guard: expired, already waiting for cashier, or a payment in progress → ignore.
2. Open `modal-bayar` — **"Lanjut ke pembayaran?"** with a warning:
   going back to change files/configuration will make the payment server reject
   the order and force a re-upload from scratch.
   - Text differs for cashier ("…tidak cocok dengan data di kasir…").
   - **Tidak** / ✕ / backdrop / Escape → dialog closes only. Nothing is sent,
     the countdown keeps running.
   - **Ya, lanjutkan** → dialog closes, the **Kembali button is hidden**, and the
     method flow below starts.
3. Method flow:
   - **Kasir** → `POST /order/konfirmasi/{hex}/kasir`.
   - **QRIS** → show full-screen loader ("Mohon tunggu.."), then reuse
     `localStorage.snap-token` if present, otherwise wait ~1s and
     `POST /order/token`, then open Snap.

**QRIS details**

- `POST /order/token` returns `{ token }`, sets `metode_pembayaran = 'qris'`,
  and stamps `tanggal_bayar` if it was empty.
- Snap token expiry is computed from the remaining time (`sisaDetik − 5s`,
  converted to minutes).
- Snap callbacks:
  - `onSuccess` → clear all `localStorage` → `/struk/{hex}`.
  - `onPending` → keep the user on the page (no navigation).
  - `onError` → clear `localStorage` + error dialog.
  - `onClose` → nothing.
- The Finish Redirect URL is configured in the Midtrans dashboard, not in code.

**Cashier details**

- Server claims the order atomically: `pending` → `processing`,
  `metode_pembayaran = 'kasir'`, `tanggal_bayar = now()`.
- Success opens `modal-kasir` ("Pesanan berhasil" / "Silakan bayar di kasir",
  with the order id). Closing it reloads the page, which now renders the
  waiting state (no timer, CTA disabled, notice text explaining the seller will
  confirm).
- Repeating the request for an order already in `processing + kasir` returns the
  same success payload (idempotent).

**Back navigation**

| Situation | Kembali goes to |
| --- | --- |
| Order editable (`terjadwal`, not waiting for cashier) | Form page (edit mode); timer resets, price recalculated on submit |
| `terjadwal` but not editable | Upload page |
| Other services | `/formulir/kilat` |
| After "Ya, lanjutkan" | Button is hidden; only browser back/refresh remains |

---

### Step 4 — Receipt `/struk/{hex}`

**What the user sees**

1. **Status card** first: spinner + rotating text
   ("Memferivikasi pembayaran", "Menunggu pembayaran", …) and "Order: {hex}".
2. When the order is paid, the status card is replaced by the receipt card:
   QR code (encodes the receipt URL), Order ID, customer name, order date,
   pickup date, item list (name, paper size, pages, copies, price),
   admin fee, handling fee (only if > 0), total, paid amount, notes, item count.
3. Actions: **Unduh Sekarang** (`window.print()`) and **Salin URL Struk**
   (clipboard with `execCommand` fallback, shows "URL struk disalin." for ~2.5s).
4. Modals:
   - `modal-struk` — "Simpan struk ini", opened automatically once the receipt
     renders (screenshot/save reminder).
   - `modal-keluar` — "Keluar dari struk?", opened by **Kembali**; actions
     "Tetap di sini" / "Ya, keluar" (→ `/`).

**Polling (`resources/js/pages/test-struk.js`)**

Every 3 seconds: `GET /struk/order/{hex}` (JSON, `cache: no-store`).

| Response | UI |
| --- | --- |
| `status = paid` | Clear `localStorage`, render receipt, open `modal-struk`, stop polling |
| `status = failed` or `refunded` | Error card: "Pembayaran gagal / dikembalikan" |
| HTTP 410 | Error card: "Order tidak ditemukan / kedaluwarsa" |
| Other non-OK, 5× in a row (~15s) | Error card: "Order tidak ditemukan / kedaluwarsa" |
| Other non-OK, < 5× | Keep polling (tolerates Midtrans not having recorded the transaction yet) |
| `status = pending` / `processing` | Keep polling |
| Network error | Log warning, keep polling |

The error card offers no retry; the user leaves through **Kembali** → `modal-keluar` → `/`.

**Server reconciliation (`strukOrder`)**

This endpoint is intentionally side-effecting:

1. Validate the hex id (404 if invalid).
2. If the order is still `pending`, claim it atomically
   (`pending` → `processing`) and ask Midtrans for the transaction status:
   - `settlement` / accepted `capture` → `paid`
   - `deny` / `cancel` / `expire` / `failure` → `failed`
   - `refund` / `partial_refund` → `refunded`
   - anything else → back to `pending`
   - Midtrans error/not found → roll the claim back to `pending`, return 404.
3. On `paid`: insert one `file_queue` row per file for the least-loaded computer.
   Queue failures are logged and never break the receipt.
4. On `paid`: set `total_dibayar = total_pembayaran`.
5. Response body for `paid` adds the file list (joined with `configuration`) and
   clears the session key + `browser_session`.

There is no Midtrans webhook in this app: the receipt poll is currently what
advances `pending` orders to their final status.

**Printing**

`@media print` hides the header, the action buttons, and the copy status. The
receipt card's hand-drawn SVG border is replaced by a CSS border (SVG scaling
differs when printed), the page is capped at 480 px and centered, the QR is
fixed at 150 px, and dividers stay dashed.

---

## 4. Error and edge-case matrix

### Upload

| Trigger | UI feedback | Recovery |
| --- | --- | --- |
| Lanjutkan with no file | Inline "Pilih minimal satu file PDF dulu." | Pick files |
| Non-PDF selected | Inline "N file dilewati karena bukan PDF." | Pick PDF |
| More than 10 files | Inline "N file dilewati karena maksimal 10 file." | Remove extras |
| Validation 422 | Loading closes, first server message inline | Retry |
| Corrupt / unreadable PDF | Loading closes, parser message inline (e.g. "Unable to find startxref") | Re-export PDF |
| Network failure | "Tidak bisa menghubungi server. Coba lagi." | Retry |

### Form

| Trigger | UI feedback | Recovery |
| --- | --- | --- |
| No session order | `modal-formulir` open → close goes to `/upload/terjadwal` | Re-upload |
| Order hex missing | Redirect `/` + notice banner | Re-upload |
| Order expired | Files deleted, redirect `/` + expiry message | Re-upload |
| Order already paid/processing | Redirect to confirmation | — |
| Pages unreadable / no files / paper price 0 | `modal-formulir` open → close goes to upload | Re-upload / contact admin |
| Subtotal still Rp 0 | `modal-subtotal` → close goes to upload | Re-upload / contact admin |
| Invalid WA / date / time / custom pages | Inline field error + focus | Fix field |
| Custom pages exceed a file's `total_pages` | `modal-halaman` naming the file | Fix pages |
| `id_paper` missing | `modal-halaman` "Ukuran kertas belum dipilih" | Pick paper size |
| Store closed or pickup outside opening hours | `modal-info` with the server message | Change date/time |
| Server exception (file mismatch, invalid paper, …) | `modal-info` with the message | Fix input / re-upload |
| Double click | Button disabled + `sedangMengirim` guard | — |

### Confirmation

| Trigger | UI feedback | Recovery |
| --- | --- | --- |
| Unknown order | Redirect `/` + banner | Start over |
| Expired | Files deleted, redirect `/` + message | Re-upload |
| Status not payable | Redirect `/` + "statusnya …" | — |
| Countdown reaches 0 | `modal-kadaluarsa` (cannot dismiss) | "Kembali ke beranda" |
| Cancel payment dialog | Dialog closes, no request | Try again |
| Session missing (token request) | 410 → timer stopped + error dialog | Dialog close → `/` |
| Session/order mismatch (token) | 404 → error dialog | Dialog close → `/` |
| Status changed meanwhile | 409 → error dialog | Dialog close → `/` |
| Midtrans order id already used | 409, order set `failed`, message asks to refill the form | Dialog close → `/` |
| Midtrans unreachable | 502 → error dialog | Retry before expiry |
| Snap script not loaded | Error dialog "Komponen pembayaran Midtrans belum termuat…" | Reload page |
| Snap `onError` | Error dialog + `localStorage` cleared | Retry |
| Cashier: session mismatch | 403 → error dialog | Dialog close → `/` |
| Cashier: expired | 410, timer stopped → error dialog | Dialog close → `/` |
| Cashier: status changed | 409 → error dialog | Dialog close → `/` |
| Any error dialog closed | `location.replace('/')` | Start from home |

### Receipt

| Trigger | UI feedback | Recovery |
| --- | --- | --- |
| No order id in URL | Error card "Order ID tidak ditemukan di URL" | Go home |
| 404 ×5 / 410 / failed / refunded | Error card with reason | Kembali → home |
| Still pending | Status card keeps rotating | Wait (polling) |
| QR library blocked | Receipt still renders without QR | — |
| Clipboard blocked | Silent fallback via `execCommand` | — |

---

## 5. UX rules that must be preserved

1. **Payment methods are locked by service**: terjadwal = QRIS only,
   kilat-edit = cashier only, kilat-langsung = both.
2. **Never reset the payment countdown on reload.** It is derived from
   `orders.tanggal_bayar`; only returning to the form clears it.
3. **One confirmation dialog before charging.** "Ya, lanjutkan" is the only path
   that requests a token or claims a cashier order.
4. **Hide the back button once payment started** (after "Ya, lanjutkan"), so the
   user cannot edit files under a live payment.
5. **Expired means deleted.** Files are removed from storage; the user must
   re-upload and cannot resume that order.
6. **Errors never leave the user on a dead page.** Closing an error dialog on the
   confirmation page returns to `/`.
7. **The receipt is the proof of purchase.** It can be printed, has a shareable
   URL, and clearing `localStorage` must happen before showing it.
8. **Terjadwal defaults to hitam putih** and the paper size chosen at upload is
   preserved (`id_paper` resolved from the paper radio).
9. **Admin fee always comes from `pengaturan_toko`**, not from a snapshot.
10. **Hand-drawn (`wired-elements`) look is required** on every customer page:
    cards, buttons, inputs, modals, dividers.

---

## 6. What is not implemented yet

| Gap | Impact |
| --- | --- |
| Cetak Kilat is a static prototype | Home button is inert; `/formulir/kilat` has hardcoded values and no backend |
| Seller confirmation for cashier orders | `processing` never becomes `paid` from a UI action |
| Receipt page for cashier orders | Polling sees `processing` forever (no success/error branch) |
| Midtrans webhook / background expiry | Status only advances when the user is on the receipt page or someone triggers a check |
| Killat upload / order creation | Confirmation supports `kilat-*`, but no way to create such orders |
| Admin dashboard status editing | Dashboard lists orders only |

Known code smells worth fixing when touching this area:

- `UploadTerjadwalController::store` has an ineffective `return response()->json(...)`
  inside the `DB::transaction` closure with HTTP status 304 for unreadable page
  counts; real parser failures surface as exceptions/JSON 500 messages.
- The receipt page is reachable by URL without authentication (intentional for
  sharing, but `/struk/order/{id}` returns full order data).
- There is no `beforeunload` guard on the confirmation page; after "Ya,
  lanjutkan" the browser back button and refresh still work even though the
  in-page back button is hidden.

---

## 7. File map

| Concern | File |
| --- | --- |
| Resume logic | `app/Http/Controllers/BerandaController.php` |
| Upload (store + reset) | `app/Http/Controllers/UploadTerjadwalController.php` |
| Form guard + data problems | `app/Http/Controllers/FormulirTerjadwalController.php` |
| Order creation + pricing | `app/Http/Controllers/OrderController.php::createorder_terjadwal` |
| Snap token | `app/Http/Controllers/OrderController.php::bayarSekarang` |
| Receipt JSON + reconciliation | `app/Http/Controllers/OrderController.php::strukOrder` |
| Confirmation page data + cashier | `app/Http/Controllers/KonfirmasiPembayaranController.php` |
| Expiry rules | `app/Service/KedaluwarsaOrder.php` |
| Opening-hours rules | `app/Service/JadwalToko.php` |
| Home UI | `resources/views/beranda.blade.php`, `resources/js/pages/beranda.js` |
| Upload UI | `resources/views/upload-terjadwal.blade.php`, `resources/js/pages/upload-terjadwal.js`, `resources/css/upload-terjadwal.css` |
| Form UI | `resources/views/formulir-terjadwal.blade.php`, `resources/js/pages/formulir-terjadwal.js`, `resources/css/formulir-terjadwal.css` |
| Confirmation UI | `resources/views/order-konfirmasi.blade.php`, `resources/js/pages/order-konfirmasi.js`, `resources/css/order-konfirmasi.css` |
| Receipt UI | `resources/views/test_struk.blade.php`, `resources/js/pages/test-struk.js`, `resources/css/test-struk.css` |
| Shared modal | `resources/views/components/modal.blade.php`, `resources/js/shared/modal.js` |
| Shared storage keys | `resources/js/shared/file-store.js` |
| Wired re-draw helper | `resources/js/wired/sketch.js` |

Build with `npm run build` after touching any CSS/JS.

---

## 8. Test coverage map

`php artisan test --filter="KonfirmasiPembayaran|CustomHalamanOrder|FormulirTerjadwal|OrderKadaluarsaPembayaran|OrderTerjadwalJadwal"`

| Test file | Covers |
| --- | --- |
| `tests/Feature/KonfirmasiPembayaranTest.php` | Method matrix, timer start, status redirects, cashier flow, token errors |
| `tests/Feature/CustomHalamanOrderTest.php` | Custom page validation, re-submit, duplicate submit |
| `tests/Feature/FormulirTerjadwalTest.php` | Form guards, edit mode, server validation |
| `tests/Feature/OrderKadaluarsaPembayaranTest.php` | 20-minute expiry, file deletion |
| `tests/Feature/OrderTerjadwalJadwalTest.php` | Opening-hours rejection |

When changing flow behavior, update the matching test **and** the table in §4.

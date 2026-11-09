# Customer UI/UX Flow — Home to Receipt

Last updated: 2026-11-09
Branch at time of writing: `fitur/kasir-konfirmasi-order`

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
  ├─ "Cetak Terjadwal"
  │    ↓
  │  GET /upload/terjadwal          Upload PDF (max 10, PDF only)
  │    └─ "Lanjutkan" → POST /upload/terjadwal  (XHR + loading overlay)
  │         ↓
  │  GET /formulir/terjadwal?order= Print form + price estimate
  │    └─ "Lanjut proses pembayaran" → POST /order/create (native form submit)
  │         ↓
  │  GET /order/konfirmasi/{id}     Payment confirmation + 20-minute countdown
  │    ├─ QRIS  → POST /order/token → Midtrans Snap popup
  │    └─ Kasir → POST /order/konfirmasi/{id}/kasir
  │         ↓
  │  GET /struk/{id}                Receipt / payment status
  │    └─ polls GET /struk/order/{id} every 3s until paid / failed
  │
  └─ "Cetak Kilat"
       ↓
  GET /scan/qr                  Camera scans the computer's QR token
    └─ GET /scan/url/{token} (route "verified.token") verifies it
         ↓
  GET /print/kilat              Choose where to edit
    ├─ Edit di Komputer  → /print/kilat/upload/komputer  (layanan kilat-edit)
    └─ Edit di Handphone → /print/kilat/upload/handphone (layanan kilat-langsung)
         └─ upload PDF → /print/kilat/formulir/{mode} (form without WhatsApp)
              └─ "Lanjut proses pembayaran" → /order/konfirmasi/{id}
                   ├─ kilat-edit → konfirmasi menyebut komputer tujuan edit
                   └─ kasir → menunggu pembayaran di kasir
```

Only **Terjadwal** (scheduled pickup) is wired end to end. **Cetak Kilat** now
has a full customer path: scan the computer QR → choose edit location → upload
PDF → kilat form (no WhatsApp) → payment confirmation. What is still missing is
the in-store editing step for `kilat-edit` orders and the print/edit UI after
the form (see §6).

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
| GET | `/scan/qr` | `Route::view` → `scan-qr.blade.php` | Camera scanner for the computer QR (Cetak Kilat) |
| GET | `/scan/url/{token}` | `Api\ComputerController::scanQr` (route `verified.token`) | Verify a scanned QR token, JSON payload |
| GET | `/print/kilat` | `Route::view` → `print-kilat.blade.php` | Choose edit location (computer / handphone) |
| GET | `/print/kilat/upload/{mode}` | `KilatController::upload` → `kilat-upload.blade.php` | Upload PDF for a kilat mode |
| GET | `/print/kilat/formulir/{mode}` | `FormulirTerjadwalController::get` → `kilat-formulir.blade.php` | Kilat print form (no WhatsApp) |
| POST | `/order/konfirmasi/{order}/batalkan` | `KonfirmasiPembayaranController::batalkan` | Kembali: cancel order, delete files + session |
| GET | `/order/konfirmasi/{order}/status` | `KonfirmasiPembayaranController::status` | Lightweight `{ status, paid, kode }` for the Kembali status check |
| GET | `/dashboard/kasir` | `KasirController::index` → `Dashboard/selection/kasir.blade.php` | Cashier queue; search by name, code, or order id |
| POST | `/dashboard/kasir/{id}/lunas` | `KasirController::lunas` | Mark cashier order paid, record final total, queue files |
| POST | `/dashboard/kasir/{id}/batal` | `KasirController::batal` | Mark cashier order failed and delete its files |

---

## 2. Shared concepts

### 2.1 Services

| Service id (`files.layanan`) | Label | Payment methods | Notes |
| --- | --- | --- | --- |
| `terjadwal` | "Terjadwal" | QRIS only | Active end-to-end flow |
| `kilat-langsung` | "Ambil di tempat" | QRIS or cashier | Upload + form (no WhatsApp, no pickup date/time) implemented; no editing step |
| `kilat-edit` | "Ambil di tempat" | Cashier only | Upload + form with **name only** (no print settings, no date/time); total "akan ditentukan di kasir" |

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
receipt page. The dashboard kasir page resolves that state: **Pembayaran
berhasil** → `paid` (plus final total and print queue), **Batalkan** → `failed`
(files deleted).

### 2.3 Session vs `orders.browser_session`

- Laravel session key `terjadwal_order` holds the active order while the user
  is inside the flow:
  `{ orderid (hex), sessionid, files {hex → {id,name,total_pages,localPath}}, where }`.
- `kilat_computer_id` (hex of `computer.id`) is written by `scanQr` and copied to
  `orders.computer_id` when a kilat order is created. It is cleared when the
  terjadwal upload page starts a new order.
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
| `printer:computer-token` | `sessionStorage` | Scan page after a verified QR | Not cleared automatically |
| `printer:computer` | `sessionStorage` | Scan page (verified payload: `computerid`, `userSession`, `exp`, `jti`) | Not cleared automatically |

Never store secrets here. `snap-token` is only a Midtrans Snap token string, and
the computer token is a short-lived HMAC token (60-70 s) that only binds the
browser to a printer computer.

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
- "Cetak Kilat" navigates to `/scan/qr` (`data-href`), the camera scanner.

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

### Step 0b — Cetak Kilat: scan the computer QR `/scan/qr`

Reached from the home page's **Cetak Kilat** button (`data-href` in
`beranda.blade.php`). This step binds the phone browser to a specific printer
computer before any printing happens.

**What the user sees**

- Header brand + **Kembali** (→ `/`).
- Centered title "Scan QR di Komputer Terdekat!".
- A single large camera card (3:4) with corner guides.
- Short hint under the card: point the camera at the QR on the computer screen;
  the QR changes every 60 seconds.

**Camera states** (inside the card)

| State | UI |
| --- | --- |
| Starting | dashed spinner + "Menyiapkan kamera…" |
| Live | camera feed + four white corner guides, no overlay |
| Permission denied / no camera / insecure context | error icon + explanation + **Coba Lagi** button |
| Verifying | full-screen loading card "Memeriksa QR…" (camera already stopped) |

Camera rules: `facingMode: environment` (rear camera preferred), video muted +
`playsinline`, frame sampled every ~140 ms, downscaled to 640 px wide, decoded
with `jsqr`. Scanning pauses while verifying so the same QR cannot fire twice.
The camera is stopped on `pagehide`, and restarted when the user returns to the
tab or closes an error dialog.

**Scan → verify flow**

1. QR text is normalized: raw token, or the last path segment / `token` query
   value when a URL was scanned.
2. A token is `body.signature` (exactly one dot). Anything else → error dialog
   "QR yang dipindai bukan QR komputer…".
3. `GET /scan/url/{token}` (`route('verified.token')`, JSON):
   `Api\ComputerController::scanQr` verifies the HMAC signature and `exp`
   (`App\Service\tokenComputer`).
4. Failure (non-2xx or `{ error: true }`) → `modal-info` (type error) with the
   server message; closing the dialog restarts the camera.
5. Success (200, payload `{ computerid, userSession, exp, jti }`) → `scanQr`
   also stores the computer id in session (`kilat_computer_id`), while the token
   and payload are stored in `sessionStorage` → navigate to `/print/kilat`.

**Verification errors**

| Trigger | Message shown |
| --- | --- |
| Token expired / signature invalid (404) | "token sudah kadaluarsa" from the server |
| Missing token (304 branch) | Server message (unreachable through the UI) |
| Server 5xx | "Server sedang bermasalah…" |
| Network failure | "Tidak bisa menghubungi server…" |
| Scanned text is not a token | "QR yang dipindai bukan QR komputer…" |

Camera requires a secure context: HTTPS in production, or `localhost` during
development. `navigator.mediaDevices` is undefined on plain-HTTP origins, and
the page shows the "browser tidak memberi akses kamera" message instead.

---

### Step 0c — Choose edit location `/print/kilat`

One large card with two buttons:
**Edit di Komputer** → `/print/kilat/upload/komputer`,
**Edit di Handphone** → `/print/kilat/upload/handphone`.

The verified computer token stays in `sessionStorage`
(`printer:computer-token`, `printer:computer`) for the next steps.

---

### Step 0d — Kilat upload and form

**Upload** (`/print/kilat/upload/{mode}`)

- Same page and script as `/upload/terjadwal` (`kilat-upload.blade.php` reuses
  `upload-terjadwal.css` + `upload-terjadwal.js`), including the progress
  overlay, file list, PDF-only + max-10 rules.
- Header title tells the mode: "Upload PDF untuk Edit di Komputer/Handphone".
- The **Lanjutkan** button carries `data-layanan`; the shared upload script
  appends it to the multipart request.
- `POST /upload/terjadwal` validates and stores the files with
  `files.layanan = kilat-edit` (komputer) or `kilat-langsung` (handphone), then
  redirects to `/print/kilat/formulir/{mode}?order={hex}`.
- For kilat uploads, the order also gets `orders.computer_id` from the scanned
  QR (session key `kilat_computer_id`), so the confirmation page knows which
  computer the customer must use. Terjadwal orders never set it.
- Opening the upload page starts a new order and releases this browser's pending
  order marker (same reset as the terjadwal upload page).

**Form** (`/print/kilat/formulir/{mode}`)

- `FormulirTerjadwalController::get` renders `kilat-formulir.blade.php` when the
  route has a `mode`; the page reuses the terjadwal CSS + JS.
- Title is **"Formulir Cetak Kilat"** with a subtitle for the mode
  ("Edit di Komputer" / "Edit di Handphone") — never the terjadwal title.
- **No pickup date/time**: the date & pukul fieldsets are removed for both kilat
  modes. `POST /order/create` unsets the `tanggal_ambil` rule for kilat orders
  and uses `now()` for the store-hours check.
- `kilat-langsung` (handphone): customer name + per-file print settings +
  packages + live subtotal, same as the terjadwal form minus WhatsApp and
  date/time.
- `kilat-edit` (komputer): **only the customer name is visible**
  (`.konfigurasi-cetak.is-hidden` hides file picker, orientation, paper, color,
  quality, copies, custom pages, notes, and packages). The footer reads
  **"SUB TOTAL: akan ditentukan di kasir"**; the hidden fields still submit their
  defaults so the order row stays valid. A short note explains that the file is
  edited at the store computer and the price is set by the cashier.
- Server-side safety net: `POST /order/create` makes `no_wa` optional when the
  order's files are kilat, and stores `user.no_wa = ''` for new customers
  (the column is NOT NULL). The WhatsApp value is preserved if it already exists
  for the same session.
- On success the user lands on `/order/konfirmasi/{hex}`:
  - `kilat-langsung` → QRIS + cashier,
  - `kilat-edit` → cashier only, total shown as `Rp. —` ("Harga akan
    menyesuaikan").
- The confirmation **Kembali** returns to the kilat form for `pending` orders;
  the form controller clears `tanggal_bayar`, so the 20-minute countdown starts
  over when the user returns to confirmation.
- URL/service guard: opening the kilat form with a terjadwal session (or the
  reverse) redirects to the matching form, so the page never mixes services.

**Resume**: an unfinished kilat order (pending, `total_pembayaran = 0`) makes
`/` redirect to `/print/kilat/formulir/{mode}?order={hex}` instead of the
terjadwal form.

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
- **Kode order** pill under the title (6 characters, e.g. `A7K2QX`) — read it to
  the cashier or let the cashier type it into `/dashboard/kasir`.
- Payment countdown pill: "Sisa waktu pembayaran MM:SS" (hidden in cashier-waiting state).
- Badge: "Terjadwal" or "Ambil di tempat".
- Card "Rincian dokumen" (per file: name, pages, size, color, price).
- `kilat-edit` only: card "Komputer untuk edit" with the scanned computer name
  and three steps (edit at that computer, then pay at the cashier). Card is
  rendered for both `pending` and cashier-waiting states; hidden when the order
  has no `computer_id`.
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
- Success opens `modal-kasir` ("Pesanan berhasil" + the server message). Closing
  it reloads the page, which now renders the waiting state (no timer, CTA
  disabled, notice text explaining the seller will confirm).
- `kilat-edit`: the message, notice, and CTA label name the target computer
  (`orders.computer_id`), e.g. "Silakan menuju komputer PC Edit 1 untuk
  mengedit file Anda" and a footer label "Komputer: PC Edit 1". Without a
  scanned computer the older "menunggu konfirmasi kasir" wording is kept.
- Repeating the request for an order already in `processing + kasir` returns the
  same success payload (idempotent).

**Back navigation**

**Kembali cancels the order, after checking the database and warning.** Before
the user confirms "Ya, lanjutkan", clicking Kembali (or pressing the browser
back button) first calls `GET /order/konfirmasi/{hex}/status`:

- **Already paid** → `modal-sukses-keluar` ("Pembayaran berhasil…"). Closing it
  (button, ✕, backdrop, Escape) redirects to `/` (`location.replace`) so the
  customer starts from zero. The server never cancels a paid order.
- **Not paid** → `modal-keluar` warning:

> "Pembayaran kamu belum dikonfirmasi. Kalau kamu kembali ke halaman sebelumnya
> sekarang, pesanan ini langsung dibatalkan dan pembayaran dianggap gagal
> (hangus)…"

- **Tidak, tetap di sini** / ✕ / backdrop / Escape → dialog closes, nothing is
  sent. Browser back is intercepted with a `history.pushState` sentinel +
  `popstate`, so the page is not left.
- **Ya, batalkan pesanan** → hidden POST to
  `/order/konfirmasi/{hex}/batalkan`:
  - the order is claimed atomically (`pending`, or `processing + kasir`) and set
    to `failed`;
  - uploaded files and related rows are deleted (`KedaluwarsaOrder::batalkan`);
  - `browser_session` and the flow session (`terjadwal_order`,
    `kilat_computer_id`) are cleared, so `/` starts from zero;
  - the user lands on `/` with the notice "Pesanan dibatalkan dan pembayaran
    sebelumnya dianggap gagal."
- If the status check fails (network), the warning is shown as a safe fallback;
  the cancel endpoint still refuses a paid order and redirects to `/` with a
  success notice.

After "Ya, lanjutkan" the button is hidden; only browser back/refresh remains.
Cross-document history (e.g. a fresh tab with no same-document entry) cannot be
intercepted — there is no `beforeunload` prompt.

---

### Step 3b — Dashboard kasir `/dashboard/kasir`

Admin page (sidebar **Kasir**, `VerifyTokenMiddleware`) to resolve cashier orders.

**What the cashier sees**

- Two stat cards: number of waiting orders and the total nominal waiting.
- Search field: matches **customer name**, **order code**, or the order id hex.
- One card per waiting order (`metode_pembayaran = kasir`, status `processing`):
  kode order (mono, large), service badge, customer name + WhatsApp, file count,
  current total, order time, and the time the customer chose cashier payment.
- Actions:
  - **Pembayaran berhasil** → modal asks for **Total dibayar (Rp)** (prefilled
    from `orders.total_pembayaran`; for `kilat-edit` the cashier types the final
    price after editing). Submitting sets `paid`, updates
    `total_pembayaran`/`total_dibayar`, and enqueues every file via
    `App\Service\AntreanCetak`.
  - **Batalkan** → confirmation modal; sets `failed`, clears
    `browser_session`, and deletes the order's files.
- Empty state when the queue is empty or the search finds nothing; Flash toasts
  for success / error / warning (e.g. no printer computer available).

Both actions are atomic: an order that was already paid/failed is not changed
again, and the page shows an error toast asking for a reload.

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

### Scan QR (`/scan/qr`)

| Trigger | UI feedback | Recovery |
| --- | --- | --- |
| Camera permission denied | In-card error + **Coba Lagi** | Allow camera in browser settings, retry |
| No camera device | In-card error "Kamera tidak ditemukan…" | Use another device |
| Camera used by another app | In-card error "Kamera sedang dipakai aplikasi lain…" | Close that app, retry |
| Plain HTTP origin (no `mediaDevices`) | In-card error about HTTPS/localhost | Open via HTTPS |
| Scanned text is not a token | Error dialog "QR yang dipindai bukan QR komputer…" | Close dialog → camera restarts |
| Token expired / bad signature (404) | Error dialog with "token sudah kadaluarsa" | Scan the newest QR (regenerated every 60 s) |
| Server error (5xx) | Error dialog "Server sedang bermasalah…" | Retry |
| Network failure | Error dialog "Tidak bisa menghubungi server…" | Retry |
| User leaves the page mid-verify | Camera tracks stopped (`pagehide`) | Scan again |

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
4. **Leaving before confirmation is destructive and warned.** Kembali or the
   browser back button first checks `GET /order/konfirmasi/{hex}/status`.
   Paid → success dialog → `/`; not paid → `modal-keluar`, and only
   "Ya, batalkan pesanan" posts to `/order/konfirmasi/{hex}/batalkan`
   (order → `failed`, files + session deleted, back to `/`). After
   "Ya, lanjutkan" the button is hidden.
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
11. **Scan before print (Kilat).** The phone must verify the computer QR through
    `/scan/url/{token}` before `/print/kilat` is shown; never navigate on a
    client-side check alone.

---

## 6. What is not implemented yet

| Gap | Impact |
| --- | --- |
| Kilat edit-at-computer flow not built | `kilat-edit` orders reach confirmation and cashier payment, but there is no page where the store edits the uploaded file or recalculates the final price |
| `/formulir/kilat` is still a static prototype | Not linked from the new flow; kept only as an old mockup |
| Scanned token is not consumed | `scanQr` verifies the HMAC + `exp` and remembers the computer in session, but the same token can still be scanned again until it expires (60 s) |
| Seller confirmation for cashier orders | Dashboard kasir can now set `paid` / `failed`, but the final `kilat-edit` price is typed manually (no price recalculation UI yet) |
| Receipt page for cashier orders | Polling sees `processing` until the cashier acts; once confirmed it switches to the paid receipt |
| Confirmation page does not poll | After the cashier confirms/cancels, the customer only sees the new status after reloading |
| Midtrans webhook / background expiry | Status only advances when the user is on the receipt page or someone triggers a check |
| Admin dashboard status editing | Dashboard lists orders; status changes only through the kasir page |

Known code smells worth fixing when touching this area:

- `UploadTerjadwalController::store` has an ineffective `return response()->json(...)`
  inside the `DB::transaction` closure with HTTP status 304 for unreadable page
  counts; real parser failures surface as exceptions/JSON 500 messages.
- The receipt page is reachable by URL without authentication (intentional for
  sharing, but `/struk/order/{id}` returns full order data).
- The confirmation page intercepts same-document back (`history.pushState` +
  `popstate`) and warns before cancelling, but there is no `beforeunload` guard:
  refresh or a cross-document back still leaves without a prompt.

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
| Scan QR UI | `resources/views/scan-qr.blade.php`, `resources/js/pages/scan-qr.js`, `resources/css/scan-qr.css` |
| Print choice UI | `resources/views/print-kilat.blade.php`, `resources/js/pages/print-kilat.js`, `resources/css/print-kilat.css` |
| Kilat upload UI | `app/Http/Controllers/KilatController.php`, `resources/views/kilat-upload.blade.php` (reuses `upload-terjadwal.css` + `.js`) |
| Kilat form UI | `resources/views/kilat-formulir.blade.php` (reuses `formulir-terjadwal.css` + `.js`) |
| QR token verify | `app/Http/Controllers/Api/ComputerController.php::scanQr`, `app/Service/tokenComputer.php` |
| Upload UI | `resources/views/upload-terjadwal.blade.php`, `resources/js/pages/upload-terjadwal.js`, `resources/css/upload-terjadwal.css` |
| Form UI | `resources/views/formulir-terjadwal.blade.php`, `resources/js/pages/formulir-terjadwal.js`, `resources/css/formulir-terjadwal.css` |
| Confirmation UI | `resources/views/order-konfirmasi.blade.php`, `resources/js/pages/order-konfirmasi.js`, `resources/css/order-konfirmasi.css` |
| Receipt UI | `resources/views/test_struk.blade.php`, `resources/js/pages/test-struk.js`, `resources/css/test-struk.css` |
| Shared modal | `resources/views/components/modal.blade.php`, `resources/js/shared/modal.js` |
| Shared storage keys | `resources/js/shared/file-store.js` |
| Wired re-draw helper | `resources/js/wired/sketch.js` |

Build with `npm run build` after touching any CSS/JS. The scan page uses the
`jsqr` npm package (bundled into `scan-qr.js`).

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

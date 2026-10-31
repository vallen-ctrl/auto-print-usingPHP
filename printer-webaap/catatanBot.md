# catatanBot.md — Progress Notes for the Next AI

Last updated: 2026-10-31
Working directory: `D:\laragon\www\printer-webaap`
Branch at time of writing: `fitur/kadaluarsa-pembayaran-order` (commit `5d91dbe`)

This file records what has been done, what the current business rules are, and what is
still missing, so the next agent does not have to rediscover it.

---

## 1. Project snapshot

- **Stack:** Laravel `^13.17`, PHP `^8.3` (dev machine runs PHP 8.5), Pest `^5.3`.
- **Public pages** (`/`, `/upload/terjadwal`, `/formulir/terjadwal`): Blade + Vite +
  `wired-elements` sketch-style CSS (`resources/css/beranda.css`, etc.).
- **Admin dashboard** (`/dashboard/*`): Blade + Tailwind/Flexbite-style utility classes,
  custom `Dashboard.layout`, `x-lucide-*` icon components, `x-toast` component.
- **Database:** production uses MySQL (`armbian` host, database `laravel`). Tests use
  SQLite (`database/database.sqlite`) with `RefreshDatabase` enabled in `tests/TestCase.php`.
- **Midtrans** Snap is used for payment (`config/midtrans.php`, `\Midtrans\Snap`).
- UI copy and code comments are written in **Indonesian**. Keep it that way.

## 2. Git / repository layout (important)

- The Git repository root is **`D:\laragon\www`**, NOT `printer-webaap`. The Laravel app is
  tracked inside the `printer-webaap/` folder of that repository. `git status` / `git diff`
  run from `D:\laragon\www\printer-webaap` show paths relative to that folder, while
  `git ls-tree` from the same folder shows paths relative to the current directory — do not
  be confused by this.
- Remote: `git@github.com:vallen-ctrl/auto-print-usingPHP.git` (`origin`).
- Rules:
  - Never commit/push to `main`.
  - One feature branch per feature, format `fitur/<short-description>`.
  - Verify before pushing: `git diff --stat origin/main HEAD` must only contain intended
    files under `printer-webaap/`, and no deletions.
  - Only push the feature branch, e.g. `git push origin fitur/<name>`.
- Branches produced in this session series (all stacked on each other):
  1. `fitur/perbaikan-status-antrean-komputer` — commit `5fbc4b7`
  2. `fitur/rapikan-aksi-komputer` — commit `f5ea7a4`
  3. `fitur/kadaluarsa-pembayaran-order` — commits `ab72779`, then `5d91dbe`
     (this is the current branch; it supersedes the first two because they are its ancestors)

## 3. Work completed in this session series

### 3.1 Komputer page (500 error + tidy-up)

Files: `app/Http/Controllers/KomputerController.php`,
`resources/views/Dashboard/selection/komputer.blade.php`, `routes/web.php`,
`tests/Feature/KomputerStatusTest.php`.

- Root cause of the `POST /dashboard/komputer/files/status` **500**: route pointed to
  `KomputerController@getFileStat`, which did not exist. Added `getFileStat()` returning
  queue counts keyed by lowercase hex computer id, always including zero-filled entries.
- Blade polling JS was broken (`getElementById('file-status-pending-' + keys)`, `complited`
  typo, elements that do not exist). It now updates `tr[data-computer-id]` rows via
  `[data-queue="pending|processing|completed|failed"]`.
- `komputer.destroy` route changed from `GET /komputer/destroy/{id}` to
  `DELETE /komputer/{id}`; the modal form now uses `POST` + `@method('DELETE')`.
- `retry()` bug fixed: `Computer::findOrFail($bin)->first()->name` used the name of the
  first computer in the table. Now uses the actual computer, and reports the number of
  files re-queued.
- Added `binaryId()` helper: invalid/absent hex ids now return 404 instead of a 500 from
  `hex2bin()`.

### 3.2 Order expiry / payment time limits (current feature)

New/changed files:

- `app/Service/KedaluwarsaOrder.php` — **new**, single source of truth for all expiry rules.
- `database/migrations/2026_10_31_120000_add_tanggal_bayar_to_orders_table.php` — **new**,
  adds nullable `orders.tanggal_bayar`.
- `app/Models/Order.php` — `tanggal_bayar` added to `$fillable` and casts.
- `app/Http/Controllers/OrderController.php` — uses the service, `bayarSekarang()` rewritten.
- `app/Http/Controllers/FormulirTerjadwalController.php` — expiry guard before rendering.
- `routes/web.php` — root `/` closure now performs the expiry check.
- `resources/views/beranda.blade.php` + `resources/css/beranda.css` — flash `error` notice.
- `resources/views/Dashboard/selection/order.blade.php` — badge map now knows `failed` and
  `refunded`.
- `resources/views/snap.blade.php` — shows `data.message` via `alert()` on failure (this is
  only the temporary/manual test page for Snap).
- `databasescheme.md` — documents `orders.tanggal_bayar`.
- `tests/Feature/OrderKadaluarsaPembayaranTest.php` — 13 tests for all rules and flows.

#### Current rules (authoritative)

`KedaluwarsaOrder` constants:

```php
public const BATAS_UPLOAD_MENIT = 60; // counted from orders.tanggal_pesan
public const BATAS_BAYAR_MENIT  = 20; // counted from orders.tanggal_bayar
```

| Order state | Counted from | Expires after |
|---|---|---|
| `pending`, never pressed "Bayar sekarang" (`tanggal_bayar IS NULL`) | `orders.tanggal_pesan` (set on upload) | 60 minutes |
| `pending`, pressed "Bayar sekarang" (`tanggal_bayar IS NOT NULL`) | `orders.tanggal_bayar` (set when Snap token is created) | 20 minutes (Midtrans Snap token itself expires after 15) |

When an order expires:

- `status_pembayaran` is set to `failed` (atomic `UPDATE ... WHERE status_pembayaran = 'pending'`).
- Uploaded files are deleted from disk (`Storage::disk('local')`), plus `file_queue`,
  `files`, and `layanan_terpilih` rows (configuration cascades). **The `orders` row is kept**
  so it still appears in the dashboard table with a red `failed` badge.

Where the rule is enforced:

1. `GET /` (`routes/web.php` closure): if the session has a `terjadwal_order`, the order is
   looked up. Missing order → forget session. Expired order → delete files, mark `failed`,
   forget session, `redirect('/')->with('error', pesan)`. Otherwise redirect to the form.
2. `FormulirTerjadwalController::get()`: same guard before rendering the form.
3. `OrderController::createorder_terjadwal()`: guard after the store-schedule validation,
   before the DB transaction that writes the order.
4. `OrderController::get()` (dashboard order list): `kadaluarsakanSemua()` sweeps both rules.
5. `OrderController::bayarSekarang()`: rejects expired orders with HTTP 410 JSON.

#### `bayarSekarang()` contract (payment entry point)

Documented in the method docblock. Response shapes:

- `200 { token, batas_bayar_menit: 20, messgae }` → open Snap popup.
- `410 { message, error: true }` → order expired; files already deleted.
- `409 { message, error: true }` → order status is not `pending`.
- `404 { message, error: true }` → session/order id mismatch or order missing.

After a Snap token is successfully created, `KedaluwarsaOrder::tandaiBayar()` writes
`orders.tanggal_bayar = now()`, which starts the 20-minute countdown. Clicking "Bayar
sekarang" again within the window issues a new token and restarts the 20 minutes.

**Why `tanggal_bayar` instead of marking the order `processing`:** `strukOrder()` only
processes orders whose status is `pending`. Marking the order `processing` when the popup
opens would make payment confirmation skip the order forever. Using a separate timestamp
keeps the status `pending` until Midtrans confirms payment.

## 4. Payment front end is NOT built yet

**This is the most important thing for the next agent.** There is no real payment page.
The only consumer of `POST /order/token` (`create.snap.token`) is `resources/views/snap.blade.php`,
served by the temporary `GET /test` route (`route('test')`). The `OrderController` code and
the `KedaluwarsaOrder` service are prepared for the real flow and their docblocks/comments
say so explicitly.

What the future payment front end should do:

1. After the order form is submitted, show order summary + total and a "Bayar sekarang" button.
2. On click: `POST /order/token` with `{ orderid: <hex> }`.
   - `200` → `window.snap.pay(token, {...})`, show a countdown using `batas_bayar_menit`.
   - `410/409/404` → show `data.message` and a link back to `/` (upload again).
3. After Snap callback (`onSuccess`/`onPending`/`onClose`), poll
   `GET /struk/order/{orderidHex}` (`order.struk` → `OrderController::strukOrder()`), which
   claims the order and asks Midtrans for the real status, then enqueues the file for
   printing (`addFileQueue()`) when paid.

Known gaps / risks to keep in mind:

- `strukOrder()` is intentionally untouched and still only handles `pending`. Do not mark
  orders `processing` elsewhere without updating it.
- Expiry cleanup happens on page loads (root/form/dashboard/bayar). There is **no scheduler
  or cron**; if nobody opens those pages, expired orders and their files stay on disk.
  A scheduled command calling `KedaluwarsaOrder::kadaluarsakanSemua()` would be the natural
  next improvement (see `routes/console.php`).
- Midtrans payment methods that settle much later (e.g. bank VA) can theoretically settle
  after the 20-minute window, in which case the order is already `failed` and the files are
  gone. Snap tokens themselves expire in 15 minutes, so this is unlikely but possible.
- `orders.tanggal_pesan` / `orders.tanggal_bayar` have no index; fine for now, worth adding
  if the orders table grows.

## 5. Other known issues (pre-existing, unrelated to the above)

- `php artisan test` fails on `tests/Feature/Api/*` (many 404s and
  `orders has no column named pengaturan_id`). These are stale tests/schema mismatches that
  existed before this session; `database/factories/OrderFactory.php` still references a
  non-existent `pengaturan_id` column. Non-API tests are green.
- Run only the relevant suites when validating work:
  ```sh
  php artisan test tests/Feature/KomputerStatusTest.php
  php artisan test tests/Feature/OrderKadaluarsaPembayaranTest.php
  php artisan test tests/Feature/OrderTerjadwalJadwalTest.php tests/Feature/Service
  ```
  Latest result: 41 passed, 132 assertions across the laptop/order feature suites.
- `OrderController::destroy()` still calls `hex2bin($id)` without validating the hex, and
  `FileQueue`/`LayananTerpilih` imports remain used; low priority.

## 6. Required steps after pulling this work

```sh
php artisan migrate     # adds orders.tanggal_bayar
npm run build           # beranda.css changed (flash notice); public/build is gitignored
php artisan test tests/Feature/OrderKadaluarsaPembayaranTest.php
```

## 7. Conventions used (keep consistent)

- Indonesian user-facing copy and Indonesian code comments; English is only used for this
  note file.
- Business rules live in `app/Service/*` (see `JadwalToko` for the existing pattern), not
  duplicated across controllers.
- Expiry/cleanup logic must stay idempotent and atomic (conditional `UPDATE` before deleting
  files) so concurrent requests cannot delete twice.
- Validation/business errors for the payment endpoint are JSON (`{ message, error }`), not
  redirects, because the caller uses `fetch`.

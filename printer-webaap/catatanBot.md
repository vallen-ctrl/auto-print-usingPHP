Last updated: 2026-10-31
Working directory: `D:\laragon\www\printer-webaap`
Branch at time of writing: `fitur/kadaluarsa-pembayaran-order` (commit `1ec73f5`)

This file records what has been done, what the current business rules are, and what is
still missing, so the next agent does not have to rediscover it.

## Ringkasan

**Target feature: front-end pembayaran (halaman bayar + popup Midtrans Snap). It does NOT exist yet.**
The only caller of `POST /order/token` today is the temporary page `resources/views/snap.blade.php`
(`GET /test`, `route('test')`). Backend rules and JSON contract for that page are ready.

Business rules now enforced (source of truth: `app/Service/KedaluwarsaOrder.php`):

- Order `pending` with `tanggal_bayar` NULL → expires 60 minutes after `orders.tanggal_pesan` (upload time).
- Order `pending` with `tanggal_bayar` set → expires 20 minutes after `orders.tanggal_bayar` (set when Snap token is created; Snap token itself dies after 15).
- Expired order → `status_pembayaran = failed`, uploaded file deleted from `Storage::disk('local')`, plus `file_queue`, `files`, `layanan_terpilih` rows; the `orders` row is kept so the dashboard still lists it as `failed`.
- Enforced at: `GET /`, `FormulirTerjadwalController::get()`, `OrderController::createorder_terjadwal()`, dashboard order list (`OrderController::get()`), and `OrderController::bayarSekarang()`.

What the next agent must know:

- `POST /order/token` (`create.snap.token`) returns JSON: `200 { token, batas_bayar_menit: 20, messgae }`, `410` order expired (files already deleted), `409` status not `pending`, `404` session/order mismatch. On non-200 show `message` and send the user back to `/`.
- After the Snap callback, poll `GET /struk/order/{orderIdHex}` (`order.struk` → `OrderController::strukOrder()`). It only processes orders whose status is `pending`, so do not set `processing` anywhere before/inside that check.
- No scheduler/cron exists: expiry is only cleaned when those pages are opened. A scheduled `KedaluwarsaOrder::kadaluarsakanSemua()` is still missing.
- `orders.tanggal_bayar` is a new column → run `php artisan migrate`; `resources/css/beranda.css` changed → run `npm run build`.

## Detail Perubahan

### `app/Service/KedaluwarsaOrder.php` (new)
Single place for all expiry logic; previously duplicated as private helpers in `OrderController` (removed).
- Constants: `BATAS_UPLOAD_MENIT = 60`, `BATAS_BAYAR_MENIT = 20`.
- `sudahKadaluarsa(Order $order): bool` — true only for `pending` orders past the applicable limit.
- `pesanKadaluarsa(Order $order): string` — Indonesian message for the user; wording differs for the upload vs payment limit.
- `kadaluarsakan(Order $order): bool` — atomic claim (`UPDATE ... WHERE status_pembayaran = 'pending'` + matching time condition), then deletes files; returns false if another request already handled it.
- `kadaluarsakanSemua(): int` — sweeps both rules; used by the dashboard order list.
- `tandaiBayar(Order $order): void` — writes `orders.tanggal_bayar = now()`; call it right after a Snap token is successfully created.
- `hapusFile(string $orderId): void` — deletes storage file + `file_queue` + `layanan_terpilih` + `files` rows (configuration cascades). Also used by `OrderController::destroy()`.
- `cariDariHex(?string $orderIdHex): ?Order` — validates 32-char hex and returns the order or null.

### `database/migrations/2026_10_31_120000_add_tanggal_bayar_to_orders_table.php` (new)
Adds nullable `orders.tanggal_bayar` (timestamp). Run `php artisan migrate`.

### `app/Models/Order.php`
`tanggal_bayar` added to `$fillable` and to `$casts` as `datetime`.

### `app/Http/Controllers/OrderController.php`
- Constructor now injects `KedaluwarsaOrder`; all private expiry/file helpers were deleted in favour of the service.
- `get()` (dashboard order list): calls `$this->kedaluwarsa->kadaluarsakanSemua()` before querying.
- `destroy()`: uses `$this->kedaluwarsa->hapusFile($order->id)`.
- `createorder_terjadwal()`: after the store-schedule check and before the write transaction, rejects expired/missing orders by forgetting the session and redirecting to `/` with `error`.
- `bayarSekarang()`: rewritten response contract documented in its docblock; expired order → 410 + files deleted; non-`pending` → 409; on success returns `token` + `batas_bayar_menit` and then calls `tandaiBayar($order)`.
- `strukOrder()` and `addFileQueue()` are unchanged on purpose.

### `app/Http/Controllers/FormulirTerjadwalController.php`
Injects `KedaluwarsaOrder`; `get()` validates the session order before rendering: missing → forget session + redirect `/`; expired → mark failed, delete files, forget session, redirect `/` with `error`. Valid sessions are re-put into the session as before.

### `routes/web.php`
The `GET /` closure now receives `KedaluwarsaOrder`. When a `terjadwal_order` session exists: missing order → forget session and show the homepage; expired order → mark failed, delete files, forget session, `redirect('/')->with('error', ...)`; otherwise redirect to `formulir.terjadwal` with the order id.

### `resources/views/beranda.blade.php` + `resources/css/beranda.css`
Homepage renders `session('error')` in a `.panel-notice` alert box; `.page-main` became a column flex container with a gap. Requires `npm run build` (assets are gitignored).

### `resources/views/Dashboard/selection/order.blade.php`
`$badgeBayar` map now includes `failed` (red) and `refunded` (muted) besides the legacy `fail`.

### `resources/views/snap.blade.php`
On non-OK response it now shows `alert(data.message)` in addition to `console.error`. This page is still the temporary manual test page for Snap.

### `databasescheme.md`
`ORDERS` block documents the new `tanggal_bayar` column.

### `tests/Feature/OrderKadaluarsaPembayaranTest.php` (new)
13 Pest tests: 60-minute upload expiry via dashboard, non-expiry at 30 minutes, 20-minute payment expiry and non-expiry via `tanggal_bayar`, `paid` orders untouched, `tandaiBayar()` shifting the limit, `bayarSekarang()` 410 on both limits, 409 for `failed`, 410 without session, `/` redirect + flash + file deletion for expired order, `/` redirect to form for valid order, form route redirect for expired order.
Run with: `php artisan test tests/Feature/OrderKadaluarsaPembayaranTest.php`.

# Blade + npm + Vite

## Setup

Check `package.json` first. If `wired-elements` is already there, skip installation.

```bash
npm install --save-exact wired-elements
```

`--save-exact` pins the version so behavior does not change when the library is updated. If npm only offers a release candidate, the known-working version is `3.0.0-rc.6`.

## File structure

- View: `resources/views/<name>.blade.php`
- CSS: `resources/css/<name>.css`
- JS: `resources/js/app.js`

## View template

```blade
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Page title</title>
  <link href="https://fonts.googleapis.com/css2?family=Caveat:wght@600&family=Patrick+Hand&display=swap" rel="stylesheet">
  @vite(['resources/css/<name>.css', 'resources/js/app.js'])
</head>
<body>
  <!-- page content -->
</body>
</html>
```

Fonts stay on the CDN (no need to bundle them). Set `lang` to match the page language.

## resources/js/app.js

```js
import 'wired-elements';
```

Vite handles `type="module"` automatically.

## Dynamic values (subtotal example)

Prices are stored in `data-harga` and computed in JS, not PHP.

```html
<wired-checkbox roughness="1.2" class="paket" data-harga="2000">Package 1</wired-checkbox>
<wired-checkbox roughness="1.2" class="paket" data-harga="2000">Package 2</wired-checkbox>

<p>SUBTOTAL: Rp <span id="subtotal">0</span></p>
```

```js
import 'wired-elements';

const boxes = document.querySelectorAll('.paket');
const out = document.getElementById('subtotal');

function hitung() {
  let total = 0;
  boxes.forEach(b => { if (b.checked) total += Number(b.dataset.harga); });
  out.textContent = total.toLocaleString('id-ID');
}

boxes.forEach(b => b.addEventListener('change', hitung));
hitung();
```

## Running

```bash
php artisan serve
npm run dev
```
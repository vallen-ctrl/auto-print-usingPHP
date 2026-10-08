---
name: wired-web-designer
description: Membangun halaman web bergaya sketsa/hand-drawn dengan HTML vanila atau Blade view Laravel, memakai library wired-elements (wiredjs.com), berdasarkan gambar desain mobile dari user. Gunakan skill ini setiap kali user meminta membuat halaman web, landing page, UI, layout, atau view Laravel dari gambar/mockup/screenshot desain, menyebut wired-elements, wiredjs, gaya sketsa/tulisan tangan, atau meminta desain mobile-first yang juga responsif di desktop, walaupun user tidak menyebut nama skill ini.
---

# Wired Web Designer

Skill ini membuat satu halaman web dari gambar desain. Hasilnya HTML vanila atau Blade view Laravel (tanpa framework frontend), memakai komponen wired-elements, dirancang mobile-first, lalu diperluas ke desktop dengan isi yang sama.

Bahasa balasan mengikuti bahasa user. Bahasa teks di dalam halaman mengikuti gambar desain; kalau gambar tidak menunjukkan teks yang jelas, tanyakan.

## Alur kerja

Ada lima tahap dan tiap tahap punya titik konfirmasi. Jangan lompat ke tahap berikutnya sebelum titik konfirmasinya selesai.

### Tahap 0: Buka percakapan dengan satu pesan

Di pesan pertama, minta dua hal sekaligus supaya tidak menambah putaran percakapan: gambar desain (mobile), dan nama file halaman (contoh: `beranda`, dipakai sebagai nama view/CSS).

Gambar desain wajib. Minta dengan tegas dan jangan mulai mendesain tanpa referensi visual. Kalau user benar-benar tidak punya gambar, terima salah satu pengganti ini: tautan Figma atau screenshot situs yang mirip, atau deskripsi tertulis yang rinci (daftar bagian halaman, urutan, dan isi tiap bagian). Setelah deskripsi diterima, tulis ulang pemahamanmu sebagai daftar bagian halaman dan minta user mengoreksi sebelum lanjut.

untuk format keluaran menggunakan
- **Blade view Laravel.** Struktur file mengikuti konvensi Laravel: view di `resources/views/<nama>.blade.php`, CSS di `resources/css/<nama>.css`, dan JS di `resources/js/app.js`. Route didaftarkan user di `routes/web.php` (misalnya `Route::view('/formulir-cetak', 'formulir-cetak');`).

Untuk Blade, dimuat melalui wired-elements:
Chek terlebih dahlu apakah sudah terinstall atau belum, jika sudah maka anda bisa langung merefrensikannya saja jika belum maka install terlebih dahulu.
- **npm + Vite.** `npm install wired-elements`, lalu `import 'wired-elements';` di `resources/js/app.js`. Library ikut ter-bundle, tidak butuh CDN saat production. Panggil lewat `@vite(['resources/css/<nama>.css', 'resources/js/app.js'])` di Blade. 

nilai roughness (lihat Tahap 1). pakai 1.2.

### Tahap 1: Inspirasi desain

Setelah gambar diterima, deskripsikan apa yang kamu lihat: suasana, palet warna dominan, jenis bentuk (sudut, garis, bayangan), dan gaya ilustrasi atau ikon. Ambil nilai warna dari gambar dan simpan sebagai token di tahap 3.

Titik konfirmasi: tampilkan ringkasan pendek (3-5 kalimat) dan palet warna dalam kode hex, lalu lanjut kecuali user mengoreksi.

#### Roughness

Wired-elements menggambar bentuk sketsanya dengan derajat "kegoyangan" yang disebut roughness. Nilainya berupa angka desimal, makin besar makin berantakan:

| Nilai | Efek | Cocok untuk |
|---|---|---|
| 0.8 | Nyaris lurus, sedikit goyang | Formulir rapi, tabel |
| 1.2 | Halus, jelas tangan | Default untuk kebanyakan UI |
| 1.6 | Sketsa terasa, hidup | Formulir informal, kartu |
| 2.0+ | Coretan kasar | Poster, ilustrasi |

Roughness adalah atribut **per elemen**, bukan setelan global. Library ini tidak menyediakan API global resmi untuk mengubah roughness semua elemen sekaligus. Karena itu, roughness selalu ditulis sebagai atribut di setiap elemen wired:

```html
<wired-input roughness="1.6" placeholder="Nama lengkap" style="width:100%"></wired-input>
<wired-button roughness="1.6" elevation="2">Kirim</wired-button>
```
Jangan pakai script yang menambahkan atribut roughness setelah render; pendekatan itu belum terverifikasi untuk semua elemen di versi 3.0.0-rc.6 dan menyulitkan debugging. Cukup tulis atribut per elemen, walau agak verbose.

### Tahap 2: Analisis tata ruang dan daftar elemen

Gambar dari user adalah desain mobile. Analisis mobile dulu, baru tentukan versi desktop.

Yang harus dihasilkan: urutan bagian halaman dari atas ke bawah, struktur tiap bagian (kolom, jarak, perataan), dan daftar elemen wired yang dibutuhkan. Untuk versi desktop, tentukan bagaimana tata letak berubah (misalnya satu kolom menjadi dua atau tiga kolom, navigasi bawah menjadi navigasi atas). Tata letak boleh berbeda antara mobile dan desktop, tetapi setiap field, teks, dan tombol yang ada di mobile harus ada juga di desktop, dan sebaliknya.

Pilih hanya elemen wired yang dipakai desain. Jangan memuat atau membaca dokumentasi semua elemen. Baca bagian "Lampiran: Referensi ringkas wired-elements" di akhir dokumen ini untuk atribut dan contoh pakai elemen yang umum; buka dokumentasi resmi di GitHub hanya untuk elemen yang tidak ada di sana:
`https://github.com/rough-stuff/wired-elements/blob/master/docs/<nama-elemen>.md`

Elemen yang tersedia: wired-button, wired-calendar, wired-card, wired-checkbox, wired-combo, wired-dialog, wired-divider, wired-fab, wired-icon-button, wired-image, wired-input, wired-item, wired-link, wired-listbox, wired-progress-ring, wired-progress, wired-radio-group, wired-radio, wired-search-input, wired-slider, wired-spinner, wired-tabs, wired-textarea, wired-toggle, wired-video.

Titik konfirmasi: tampilkan daftar bagian halaman dan daftar elemen yang dipilih beserta alasannya (satu baris per elemen), lalu tunggu persetujuan user sebelum menulis kode. Jika user sudah bilang "langsung saja", boleh lanjut tanpa menunggu.

### Tahap 3: Implementasi layout

Tulis HTML/CSS atau Blade/CSS dengan aturan di bagian "Aturan teknis", "Tipografi", dan "Warna dan spasi" di bawah. Kerjakan mobile dulu (CSS dasar tanpa media query untuk mobile), lalu tambahkan `@media (min-width: ...)` untuk layar lebih besar.

Struktur halaman HTML statis:

```html
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Judul halaman</title>
  <link href="https://fonts.googleapis.com/css2?family=Caveat:wght@600&family=Patrick+Hand&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="namafile.css">
  <script type="module" src="https://unpkg.com/wired-elements@3.0.0-rc.6?module"></script>
</head>
<body>
  <!-- isi halaman -->
</body>
</html>
```

Versi wired-elements dikunci (`@3.0.0-rc.6`) agar hasil tidak berubah sendiri saat library diperbarui. Sebelum menulis, cek versi terbaru yang stabil di npm dan pakai versi itu kalau berbeda. Jangan pakai URL tanpa versi.

#### Varian untuk Laravel Blade

Kalau outputnya Blade view, struktur `namafile.blade.php` sama seperti HTML di atas, dengan tiga perbedaan:

1. Nama file berakhiran `.blade.php` dan ditaruh di `resources/views/`.
2. CSS ada di `resources/css/namafile.css`, JS ada di `resources/js/app.js`.
3. Kalau memakai CDN, tag `<script>` ditulis langsung di Blade. Kalau memakai npm + Vite, ganti tag `<script>` dan `<link rel="stylesheet">` untuk CSS lokal dengan direktif `@vite`:

```blade
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Judul halaman</title>
  <link href="https://fonts.googleapis.com/css2?family=Caveat:wght@600&family=Patrick+Hand&display=swap" rel="stylesheet">
  @vite(['resources/css/namafile.css', 'resources/js/app.js'])
</head>
<body>
  <!-- isi halaman -->
</body>
</html>
```

Lalu di `resources/js/app.js`:

```js
import 'wired-elements';

// logika nilai dinamis (kalau ada), lihat "Nilai dinamis di Blade"
```

Font Google tetap dimuat lewat CDN karena tidak ada alasan membundel font.

Untuk proyek Laravel dengan Vite, jangan tulis URL unpkg untuk wired-elements. Kunci versi di `package.json` supaya perilaku tidak berubah saat library diperbarui.

#### Nilai dinamis di Blade

Kalau desain punya nilai yang berubah mengikuti interaksi browser (misalnya sub total yang berubah saat checkbox paket dicentang), jangan hitung di PHP. Tulis logika di `resources/js/app.js` atau script terpisah yang dimuat lewat `@vite`. Simpan harga sebagai `data-harga` di elemen atau konstanta di JS, supaya tidak hard-coded berulang.

Blade hanya menghasilkan HTML statis saat dirender; interaktivitas sepenuhnya di sisi browser. Contoh pola sub total:

```html
<wired-checkbox class="paket" data-harga="2000">Paket 1</wired-checkbox>
<wired-checkbox class="paket" data-harga="2000">Paket 2</wired-checkbox>
<wired-checkbox class="paket" data-harga="2000">Paket 3</wired-checkbox>

<p>SUB TOTAL: Rp <span id="subtotal">4000</span></p>
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

### Tahap 4: Finishing dan presentasi

Sebelum menyerahkan hasil, jalankan daftar cek di bagian "Daftar cek sebelum menyerahkan". Perbaiki yang gagal, jangan hanya mencatatnya.

Simpan hasil sesuai format yang disepakati di tahap 0. Untuk HTML statis: `namafile.css` + `namafile.html`. Untuk PHP polos: `namafile.css` + `namafile.php`. Untuk Laravel Blade: `resources/views/namafile.blade.php`, `resources/css/namafile.css`, dan `resources/js/app.js`.

Lalu presentasikan ke user dalam paragraf singkat yang mencakup: bagian halaman yang dibuat, elemen wired yang dipakai, perbedaan layout mobile dan desktop, keputusan desain yang kamu ambil sendiri (misalnya warna atau ukuran yang kamu perkirakan dari gambar), dan cara membukanya.

- Untuk HTML statis, cukup buka di browser.
- Untuk `.php` polos, jalankan lewat server PHP, misalnya `php -S localhost:8000` di folder file.
- Untuk Blade, jalankan lewat proyek Laravel: `php artisan serve`, atau `npm run dev` kalau memakai Vite untuk aset.

Sebutkan juga bahwa halaman butuh koneksi internet kalau font atau wired-elements dimuat lewat CDN. Kalau semua dimuat lewat npm + Vite, font Google tetap butuh internet; sebutkan itu.

## Aturan teknis

Wired-elements menggambar bentuk sketsanya dengan JavaScript berdasarkan ukuran elemen saat dirender, jadi bentuknya bisa terlihat salah saat ukuran layar berubah. Karena itu:

- Beri elemen wired lebar fleksibel (`width: 100%` atau `max-width`), jangan lebar tetap dalam piksel.
- Bungkus elemen wired dalam kontainer `display: block` dengan lebar yang jelas. Elemen kustom secara bawaan `inline`, yang sering merusak ukuran.
- Kalau setelah resize atau rotasi layar gambar sketsanya tidak menyesuaikan, pasang `ResizeObserver` pada kontainer dan panggil `requestUpdate()` pada elemen wired di dalamnya. Uji dulu apakah ini perlu; jangan menambah kode yang tidak dibutuhkan.
- Library ini sudah lama tidak aktif dikembangkan, jadi verifikasi perilaku di browser, jangan menganggap semua atribut di dokumentasi pasti bekerja.
- Elemen wired dimuat lewat `type="module"`, jadi definisi elemen baru siap setelah skrip termuat. Beri gaya fallback (ukuran minimal dan `display: block`) agar tidak ada loncatan tata letak saat menunggu.
- Gunakan elemen HTML semantik (`header`, `nav`, `main`, `section`, `footer`) untuk struktur, dan elemen wired untuk komponen interaktif dan hiasan.
- Gambar wajib punya `alt`. Elemen wired yang menerima input wajib punya label yang terbaca (atribut `placeholder` atau `<label>` di sekitarnya).
- Jangan bergantung pada gambar dari luar yang mungkin tidak tersedia. Kalau desain butuh gambar yang tidak diberikan, pakai kotak placeholder (`wired-card` atau `div` dengan ukuran tetap rasio) dan sebutkan di presentasi.
- Wired-elements adalah Web Components, jadi tetap berfungsi di Blade view. Blade hanya menghasilkan HTML; JavaScript dari Vite atau CDN yang mengaktifkan elemen `<wired-input>`, `<wired-button>`, dan seterusnya. Tidak ada konflik antara PHP dan Web Components.
- Kalau memakai npm + Vite, pastikan `import 'wired-elements'` ada di entry JS yang dimuat lewat `@vite`. Vite menangani `type="module"` secara otomatis. Kunci versi di `package.json` supaya perilaku tidak berubah saat library diperbarui.

Breakpoint yang dipakai: mobile dasar (target uji 360px dan 390px), tablet mulai 768px, desktop mulai 1024px. Batasi lebar konten desktop dengan `max-width` sekitar 1120px dan pusatkan.

## Tipografi

Font hanya dua: Caveat (weight 600) dan Patrick Hand (weight 400). Hanya dua weight itu yang dimuat, jadi jangan memakai Medium, SemiBold, atau Bold lewat `font-weight`, karena browser akan memalsukannya dan hasilnya jelek. Pemetaan yang dipakai:

- Heading, display, dan subheading: Caveat 600. Ini menggantikan Bold dan SemiBold di tabel skala.
- Body, caption, dan label: Patrick Hand 400. Ini menggantikan Regular dan Medium.
- Hierarki dibangun lewat ukuran dan warna, bukan lewat weight.

Deklarasi font:

```css
:root {
  --font-heading: "Caveat", "Patrick Hand", "Segoe Print", "Bradley Hand", "Comic Sans MS", cursive;
  --font-body: "Patrick Hand", "Segoe Print", "Bradley Hand", "Comic Sans MS", cursive;
}
```

Heading memakai `--font-heading`, sisanya `--font-body`. Kalau user secara eksplisit ingin urutan font tunggal seperti aslinya, pakai satu `font-family` itu untuk semuanya.

Skala ukuran: basis 16px, rasio 1.25. Karena Caveat dan Patrick Hand punya x-height kecil, body diperbesar sedikit ke 17px agar terbaca nyaman. Display dan Heading 1 memakai `clamp()` agar tidak terlalu besar di layar 360px.

```css
:root {
  --fs-display: clamp(2.25rem, 1.6rem + 3vw, 3.06rem); /* maks ~49px */
  --fs-h1: clamp(1.9rem, 1.4rem + 2.2vw, 2.44rem);    /* maks ~39px */
  --fs-h2: clamp(1.6rem, 1.3rem + 1.4vw, 1.94rem);    /* maks ~31px */
  --fs-h3: 1.56rem;   /* 25px */
  --fs-sub: 1.25rem;  /* 20px */
  --fs-body: 1.0625rem; /* 17px */
  --fs-caption: 0.8125rem; /* 13px */
  --fs-label: 0.75rem;     /* 12px */
}
```

| Level | Ukuran | Font | Line height | Letter spacing | Fungsi |
|---|---|---|---|---|---|
| Display | `--fs-display` | Caveat | 1.1-1.2 | -0.02em | Hero, judul halaman pembuka |
| Heading 1 | `--fs-h1` | Caveat | 1.2 | -0.015em | Judul halaman |
| Heading 2 | `--fs-h2` | Caveat | 1.25 | -0.01em | Judul bagian |
| Heading 3 | `--fs-h3` | Caveat | 1.3 | -0.005em | Sub-bagian |
| Subheading | `--fs-sub` | Patrick Hand | 1.4 | 0 | Pengantar bagian, penekanan |
| Body | `--fs-body` | Patrick Hand | 1.5-1.6 | 0 | Teks isi utama |
| Caption | `--fs-caption` | Patrick Hand | 1.4 | 0.01em | Keterangan gambar, catatan kecil |
| Label | `--fs-label` | Patrick Hand | 1.3 | 0.03em | Kategori, label, tag |

Letter spacing ditulis dalam `em` (−2% menjadi `-0.02em`). Label tidak memakai huruf kapital semua dan tidak memakai spacing lebar, karena font tulisan tangan menjadi sulit dibaca dengan gaya itu. Kalau desain jelas memakai huruf kapital, ikuti desain.

Panjang baris teks isi 45-75 karakter: batasi kontainer teks dengan `max-width: 65ch`.

## Warna dan spasi

Warna diambil dari gambar desain di tahap 1 dan didefinisikan sekali sebagai token di `:root`, lalu dipakai lewat `var(--...)`. Jangan menaruh nilai hex langsung di aturan komponen. Token minimal:

```css
:root {
  --color-bg: #fffdf7;
  --color-surface: #ffffff;
  --color-text: #2b2b2b;
  --color-muted: #6b6b6b;
  --color-primary: #3b6fd4;
  --color-accent: #e8813a;
  --color-border: #2b2b2b;
}
```

Nilai di atas hanya bawaan; ganti dengan warna dari desain. Wired-elements mewarnai lewat CSS custom property atau atribut seperti `--wired-button-bg-color`, jadi cek dokumentasi elemen untuk nama variabel yang tepat.

Kontras teks terhadap latar minimal 4.5:1 untuk teks biasa. Periksa pasangan warna yang kamu pilih, terutama teks `--color-muted`.

Spasi memakai skala kelipatan 4px, yaitu `--space-1: 4px`, `--space-2: 8px`, `--space-3: 12px`, `--space-4: 16px`, `--space-6: 24px`, `--space-8: 32px`, `--space-12: 48px`. Jangan pakai angka spasi di luar skala ini.

## Daftar cek sebelum menyerahkan

Periksa tiap poin dan perbaiki yang gagal. Kalau kamu tidak bisa membuka browser untuk memverifikasi, katakan itu di presentasi dan tandai poin mana yang belum teruji, jangan mengklaim sudah diuji.

- Tidak ada scroll horizontal pada lebar 360px, 768px, dan 1024px.
- Meta `viewport` terpasang.
- Semua field, teks, dan tombol dari versi mobile ada di versi desktop.
- Area sentuh tombol, tautan, dan kontrol input minimal sekitar 44×44px di mobile.
- Elemen wired tergambar dengan benar setelah halaman dimuat dan setelah ukuran layar berubah.
- Nilai roughness yang disepakati benar-benar terpasang sebagai atribut di semua elemen wired, dan tidak ada elemen yang tertinggal dengan roughness bawaan.
- Hanya dua weight font yang dipakai (Caveat 600 dan Patrick Hand 400), dan semua ukuran teks berasal dari token skala.
- Tidak ada nilai warna atau spasi hard-coded di luar token.
- Gambar punya `alt`, input punya label, kontras teks memenuhi 4.5:1.
- File bernama sesuai permintaan user, dan CSS terpisah dari layout. Untuk Blade, file berada di `resources/views/`, `resources/css/`, dan `resources/js/`.
- Tidak ada elemen wired yang dimuat tetapi tidak dipakai.
- Kalau memakai npm + Vite, `@vite` memanggil CSS dan JS yang benar, dan `import 'wired-elements'` ada di entry JS.

## Yang tidak boleh dilakukan

- Jangan memulai desain sebelum ada referensi visual (gambar, tautan, atau deskripsi yang sudah dikonfirmasi).
- Jangan membaca atau mengimpor semua elemen wired; hanya yang ada di daftar elemen tahap 2.
- Jangan membuat versi desktop dengan isi yang berbeda dari mobile.
- Jangan memakai framework, bundler, atau library selain wired-elements dan font yang ditentukan, kecuali user meminta. Laravel dan Vite termasuk pengecualian yang sah kalau user bilang proyeknya Laravel atau minta output Blade.
- Jangan mengubah nama file dari yang diminta user.
- Jangan memakai script global untuk menambahkan atribut roughness setelah render. Tulis roughness sebagai atribut per elemen.

---

## Lampiran: Referensi ringkas wired-elements

Ringkasan elemen yang paling sering dipakai. Ini ditulis dari pengetahuan umum tentang library dan belum diverifikasi terhadap versi yang dikunci di SKILL.md. Sebelum memakai atribut yang tidak ada di sini, atau kalau sebuah atribut ternyata tidak berefek, buka dokumentasi resmi elemen itu:
`https://github.com/rough-stuff/wired-elements/blob/master/docs/<nama-elemen>.md`

Semua elemen adalah custom element, jadi berikan `display: block` dan lebar fleksibel lewat CSS.

### Isi

- wired-button
- wired-card
- wired-input
- wired-textarea
- wired-search-input
- wired-checkbox, wired-toggle
- wired-radio-group dan wired-radio
- wired-combo dan wired-item, wired-listbox
- wired-tabs dan wired-tab
- wired-dialog
- wired-divider, wired-link, wired-image
- wired-fab dan wired-icon-button
- wired-progress, wired-slider
- Pola gabungan yang berguna

### Aksi

**wired-button**: tombol. Atribut: `elevation` (angka, tinggi bayangan sketsa), `disabled`, `roughness`. Isi tombol adalah teks di dalam tag.

```html
<wired-button roughness="1.6" elevation="2">Kirim</wired-button>
```

**wired-fab** dan **wired-icon-button**: tombol bulat untuk aksi utama atau ikon. Ikon diberikan lewat elemen anak (misalnya `<mwc-icon>` atau SVG inline). Karena ikon dari luar mungkin tidak termuat, utamakan SVG inline.

### Kontainer dan konten

**wired-card**: kotak bergaris sketsa untuk mengelompokkan konten. Atribut: `elevation`, `fill` (warna isi), `roughness`. Beri `display: block` dan `padding` lewat CSS.

```html
<wired-card roughness="1.6" elevation="3" style="display:block; padding:16px;">
  <h3>Judul kartu</h3>
  <p>Isi kartu.</p>
</wired-card>
```

**wired-divider**: garis pemisah sketsa. Atribut: `elevation`, `roughness`.

**wired-image**: gambar dengan bingkai sketsa. Atribut: `src`, `elevation`, `roughness`. Beri lebar lewat CSS dan tambahkan teks alternatif di konteks sekitar, karena atribut `alt` mungkin tidak diteruskan; cek di browser.

**wired-link**: tautan bergaris bawah sketsa. Atribut: `href`, `target`, `elevation`, `roughness`.

### Input

**wired-input**: input teks. Atribut: `placeholder`, `type`, `value`, `disabled`, `roughness`. Baca nilainya lewat properti `.value`.

```html
<wired-input roughness="1.6" placeholder="Nama lengkap" style="width:100%;"></wired-input>
```

**wired-textarea**: input banyak baris. Atribut: `placeholder`, `rows`, `maxlength`, `disabled`, `roughness`.

**wired-search-input**: kolom pencarian. Atribut: `placeholder`, `value`, `roughness`.

**wired-checkbox**: kotak centang. Atribut: `checked`, `disabled`, `roughness`. Label adalah teks di dalam tag.

```html
<wired-checkbox roughness="1.6">Setuju dengan ketentuan</wired-checkbox>
```

**wired-toggle**: saklar. Atribut: `checked`, `disabled`, `roughness`.

**wired-radio-group** dan **wired-radio**: pilihan tunggal. `wired-radio-group` punya atribut `selected` (nilai `name` radio yang dipilih); tiap `wired-radio` punya `name`, `roughness`, dan label di dalam tag. Perubahan pilihan memicu event `selected`.

```html
<wired-radio-group roughness="1.6" selected="a">
  <wired-radio name="a">Opsi A</wired-radio>
  <wired-radio name="b">Opsi B</wired-radio>
</wired-radio-group>
```

**wired-combo** dan **wired-item**: dropdown. `wired-combo` punya atribut `selected` (nilai `value` item yang dipilih) dan `roughness`; tiap `wired-item` punya `value` dan teks. Event perubahan: `selected`.

```html
<wired-combo roughness="1.6" selected="one">
  <wired-item value="one">Satu</wired-item>
  <wired-item value="two">Dua</wired-item>
</wired-combo>
```

**wired-listbox**: daftar pilihan yang selalu terlihat, memakai `wired-item` sebagai isi. Atribut: `selected`, `horizontal`, `roughness`.

**wired-slider**: penggeser angka. Atribut: `min`, `max`, `value`, `step`, `roughness`.

### Navigasi dan lapisan

**wired-tabs** dan **wired-tab**: tab. Tiap `wired-tab` punya atribut `name` (dan label tampil diambil dari `label`); `wired-tabs` punya `selected` dan `roughness`. Isi tab ditaruh di dalam `wired-tab`.

```html
<wired-tabs roughness="1.6" selected="info">
  <wired-tab name="info" label="Info">Isi tab info.</wired-tab>
  <wired-tab name="jadwal" label="Jadwal">Isi tab jadwal.</wired-tab>
</wired-tabs>
```

**wired-dialog**: dialog modal. Atribut: `open`, `roughness`. Buka dengan `dialog.open = true` dan tutup dengan `dialog.open = false`. Taruh tombol tutup di dalamnya.

### Indikator

**wired-progress**: bilah progres. Atribut: `value`, `min`, `max`, `percentage` (menampilkan teks persen), `roughness`.

### Atribut roughness

**Berlaku di semua elemen wired.** Angka desimal, makin besar makin berantakan. Contoh `roughness="1.6"`. Tidak ada API global resmi; set per elemen.

### Pola gabungan yang berguna

Bagian formulir di mobile: `wired-card` berisi beberapa `wired-input` berlebar 100% dan satu `wired-button` di bawahnya. Di desktop, kartu diberi `max-width` sekitar 480px dan dipusatkan, atau field disusun dua kolom dengan `display: grid`.

Bagian daftar kartu: satu kolom di mobile (`display: grid; gap: var(--space-4)`), dua kolom mulai 768px, tiga kolom mulai 1024px lewat `grid-template-columns`.

Navigasi: bar bawah dengan beberapa `wired-icon-button` di mobile, berubah menjadi baris `wired-link` atau `wired-tabs` di header pada desktop, dengan daftar tujuan yang sama.

### Pola Laravel Blade + npm

Untuk Blade view dengan npm + Vite, entry JS minimal:

```js
// resources/js/app.js
import 'wired-elements';
```

Blade memanggilnya lewat `@vite(['resources/css/namafile.css', 'resources/js/app.js'])`. Roughness tetap ditulis sebagai atribut di setiap elemen wired di Blade.
```

---

Itu file lengkapnya dalam satu blok `markdown`. Tinggal salin ke `wired-web-designer.md`. Semua keputusan dari percakapan kita sudah terpasang:

- Output: HTML statis, `.php` polos, dan **Blade view Laravel**.
- Blade: pilihan CDN atau **npm + Vite** dengan contoh `@vite`.
- Roughness: **Opsi A** (atribut per elemen), script global dilarang.
- Nilai dinamis (sub total): ditangani lewat JS, bukan PHP.
- Daftar cek dan bagian larangan sudah disesuaikan dengan Laravel/Vite.

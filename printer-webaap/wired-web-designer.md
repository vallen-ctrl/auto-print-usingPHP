---
name: wired-web-designer
description: Membangun halaman web bergaya sketsa/hand-drawn dengan Blade view Laravel dan library wired-elements (wiredjs.com), berdasarkan gambar desain mobile dari user. Gunakan skill ini setiap kali user meminta membuat halaman web, landing page, UI, atau layout dari gambar/mockup/screenshot desain, menyebut wired-elements, wiredjs, atau gaya sketsa/tulisan tangan, atau meminta desain mobile-first yang juga responsif di desktop, walaupun user tidak menyebut nama skill ini.
---

# Wired Web Designer

Skill ini membuat satu halaman web dari gambar desain. Hasilnya Blade view Laravel + CSS publik yang memakai komponen wired-elements, dirancang mobile-first, lalu diperluas ke desktop dengan isi yang sama.

Bahasa balasan mengikuti bahasa user. Bahasa teks di dalam halaman mengikuti gambar desain; kalau gambar tidak menunjukkan teks yang jelas, gunakan teks placeholder yang wajar dan jangan bertanya.

## Alur kerja

Tidak ada titik konfirmasi. Setelah gambar diterima, langsung kerjakan sampai selesai.

### Tahap 0: Minta gambar

Di pesan pertama, hanya minta gambar desain mobile. Jangan menanyakan nama file, format keluaran, warna, teks, atau apa pun. Contoh: "Mana gambarnya dong, kirimin. Nanti aku langsung bikin."

Gambar desain wajib. Jika gambar belum ada, berhenti dan tunggu. Jangan mulai mendesain tanpa referensi visual. Jangan menerima pengganti seperti tautan Figma, screenshot situs lain, atau deskripsi tertulis.

### Tahap 1: Inspirasi desain

Setelah gambar diterima, deskripsikan apa yang kamu lihat: suasana, palet warna dominan, jenis bentuk (sudut, garis, bayangan), dan gaya ilustrasi atau ikon. Ambil nilai warna dari gambar dan simpan sebagai token di tahap 3. Jangan minta konfirmasi; langsung lanjut.

### Tahap 2: Analisis tata ruang dan daftar elemen

Gambar dari user adalah desain mobile. Analisis mobile dulu, baru tentukan versi desktop.

Yang harus dihasilkan: urutan bagian halaman dari atas ke bawah, struktur tiap bagian (kolom, jarak, perataan), dan daftar elemen wired yang dibutuhkan. Untuk versi desktop, tentukan bagaimana tata letak berubah (misalnya satu kolom menjadi dua atau tiga kolom, navigasi bawah menjadi navigasi atas). Tata letak boleh berbeda antara mobile dan desktop, tetapi setiap field, teks, dan tombol yang ada di mobile harus ada juga di desktop, dan sebaliknya.

Pilih hanya elemen wired yang dipakai desain. Jangan memuat atau membaca dokumentasi semua elemen. Baca bagian "Lampiran: Referensi ringkas wired-elements" di akhir dokumen ini untuk atribut dan contoh pakai elemen yang umum; buka dokumentasi resmi di GitHub hanya untuk elemen yang tidak ada di sana:
`https://github.com/rough-stuff/wired-elements/blob/master/docs/<nama-elemen>.md`

Elemen yang tersedia: wired-button, wired-calendar, wired-card, wired-checkbox, wired-combo, wired-dialog, wired-divider, wired-fab, wired-icon-button, wired-image, wired-input, wired-item, wired-link, wired-listbox, wired-progress-ring, wired-progress, wired-radio-group, wired-radio, wired-search-input, wired-slider, wired-spinner, wired-tabs, wired-textarea, wired-toggle, wired-video.

Jangan minta persetujuan; langsung lanjut ke implementasi.

### Tahap 3: Implementasi layout

Tulis Blade view dan CSS publik dengan aturan di bagian "Aturan teknis", "Tipografi", dan "Warna dan spasi" di bawah. Kerjakan mobile dulu (CSS dasar tanpa media query untuk mobile), lalu tambahkan `@media (min-width: ...)` untuk layar lebih besar. Simpan mengikuti konvensi Laravel (Blade view + CSS publik). Jangan menanyakan nama file; tentukan otomatis dari konteks gambar. Jangan memakai framework JS/CSS tambahan.

Struktur halaman (Blade view):

```blade
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Judul halaman</title>
  <link href="https://fonts.googleapis.com/css2?family=Caveat:wght@600&family=Patrick+Hand&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="{{ asset('css/namafile.css') }}">
  <script type="module" src="https://unpkg.com/wired-elements@3.0.0-rc.6?module"></script>
</head>
<body>
  <!-- isi halaman -->
</body>
</html>
```

Versi wired-elements dikunci (`@3.0.0-rc.6`). Jangan pakai URL tanpa versi. Jangan cek versi terbaru.

### Tahap 4: Finishing dan presentasi

Sebelum menyerahkan hasil, jalankan daftar cek di bagian "Daftar cek sebelum menyerahkan". Perbaiki yang gagal, jangan hanya mencatatnya.

Simpan hasil sebagai Blade view dan CSS publik sesuai konvensi Laravel. Lalu presentasikan ke user dalam paragraf singkat yang mencakup: bagian halaman yang dibuat, elemen wired yang dipakai, perbedaan layout mobile dan desktop, keputusan desain yang kamu ambil sendiri (misalnya warna atau ukuran yang kamu perkirakan dari gambar), dan cara membukanya lewat route Laravel. Sebutkan juga bahwa halaman butuh koneksi internet karena memuat font dan library dari CDN.

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
- Output mengikuti konvensi Laravel (Blade view + CSS publik). Jangan memakai framework JS/CSS tambahan.

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
  --color-bg: #FFFFF0;        /* latar halaman dan kartu */
  --color-text: #1e1e1e;      /* teks dan garis utama */
  --color-muted: #6b6b6b;     /* teks sekunder, catatan */
  --color-border: #1e1e1e;    /* bingkai kartu, tombol, logo */
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
- Hanya dua weight font yang dipakai (Caveat 600 dan Patrick Hand 400), dan semua ukuran teks berasal dari token skala.
- Tidak ada nilai warna atau spasi hard-coded di luar token.
- Gambar punya `alt`, input punya label, kontras teks memenuhi 4.5:1.
- Output mengikuti konvensi Laravel (Blade view + CSS publik).
- Tidak ada elemen wired yang dimuat tetapi tidak dipakai.
- Tidak ada pertanyaan setelah gambar diterima.
- Tidak ada permintaan konfirmasi warna, layout, atau nama file.

## Yang tidak boleh dilakukan

- Jangan memulai desain sebelum ada gambar desain mobile.
- Jangan membaca atau mengimpor semua elemen wired; hanya yang ada di daftar elemen tahap 2.
- Jangan membuat versi desktop dengan isi yang berbeda dari mobile.
- Jangan memakai framework JS/CSS, bundler, atau library lain selain wired-elements dan font yang ditentukan. Laravel hanya dipakai sebagai wadah output (Blade view + CSS publik).
- Jangan menanyakan nama file, format keluaran, warna, teks, atau konfirmasi layout.
- Jangan menerima pengganti gambar seperti tautan Figma, screenshot situs lain, atau deskripsi tertulis.

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

**wired-button**: tombol. Atribut: `elevation` (angka, tinggi bayangan sketsa), `disabled`. Isi tombol adalah teks di dalam tag.

```html
<wired-button elevation="2">Kirim</wired-button>
```

**wired-fab** dan **wired-icon-button**: tombol bulat untuk aksi utama atau ikon. Ikon diberikan lewat elemen anak (misalnya `<mwc-icon>` atau SVG inline). Karena ikon dari luar mungkin tidak termuat, utamakan SVG inline.

### Kontainer dan konten

**wired-card**: kotak bergaris sketsa untuk mengelompokkan konten. Atribut: `elevation`, `fill` (warna isi). Beri `display: block` dan `padding` lewat CSS.

```html
<wired-card elevation="3" style="display:block; padding:16px;">
  <h3>Judul kartu</h3>
  <p>Isi kartu.</p>
</wired-card>
```

**wired-divider**: garis pemisah sketsa. Atribut: `elevation`.

**wired-image**: gambar dengan bingkai sketsa. Atribut: `src`, `elevation`. Beri lebar lewat CSS dan tambahkan teks alternatif di konteks sekitar, karena atribut `alt` mungkin tidak diteruskan; cek di browser.

**wired-link**: tautan bergaris bawah sketsa. Atribut: `href`, `target`, `elevation`.

### Input

**wired-input**: input teks. Atribut: `placeholder`, `type`, `value`, `disabled`. Baca nilainya lewat properti `.value`.

```html
<wired-input placeholder="Nama lengkap" style="width:100%;"></wired-input>
```

**wired-textarea**: input banyak baris. Atribut: `placeholder`, `rows`, `maxlength`, `disabled`.

**wired-search-input**: kolom pencarian. Atribut: `placeholder`, `value`.

**wired-checkbox**: kotak centang. Atribut: `checked`, `disabled`. Label adalah teks di dalam tag.

```html
<wired-checkbox>Setuju dengan ketentuan</wired-checkbox>
```

**wired-toggle**: saklar. Atribut: `checked`, `disabled`.

**wired-radio-group** dan **wired-radio**: pilihan tunggal. `wired-radio-group` punya atribut `selected` (nilai `name` radio yang dipilih); tiap `wired-radio` punya `name` dan label di dalam tag. Perubahan pilihan memicu event `selected`.

```html
<wired-radio-group selected="a">
  <wired-radio name="a">Opsi A</wired-radio>
  <wired-radio name="b">Opsi B</wired-radio>
</wired-radio-group>
```

**wired-combo** dan **wired-item**: dropdown. `wired-combo` punya atribut `selected` (nilai `value` item yang dipilih), tiap `wired-item` punya `value` dan teks. Event perubahan: `selected`.

```html
<wired-combo selected="one">
  <wired-item value="one">Satu</wired-item>
  <wired-item value="two">Dua</wired-item>
</wired-combo>
```

**wired-listbox**: daftar pilihan yang selalu terlihat, memakai `wired-item` sebagai isi. Atribut: `selected`, `horizontal`.

**wired-slider**: penggeser angka. Atribut: `min`, `max`, `value`, `step`.

### Navigasi dan lapisan

**wired-tabs** dan **wired-tab**: tab. Tiap `wired-tab` punya atribut `name` (dan label tampil diambil dari `label`); `wired-tabs` punya `selected`. Isi tab ditaruh di dalam `wired-tab`.

```html
<wired-tabs selected="info">
  <wired-tab name="info" label="Info">Isi tab info.</wired-tab>
  <wired-tab name="jadwal" label="Jadwal">Isi tab jadwal.</wired-tab>
</wired-tabs>
```

**wired-dialog**: dialog modal. Atribut: `open`. Buka dengan `dialog.open = true` dan tutup dengan `dialog.open = false`. Taruh tombol tutup di dalamnya.

### Indikator

**wired-progress**: bilah progres. Atribut: `value`, `min`, `max`, `percentage` (menampilkan teks persen).

### Pola gabungan yang berguna

Bagian formulir di mobile: `wired-card` berisi beberapa `wired-input` berlebar 100% dan satu `wired-button` di bawahnya. Di desktop, kartu diberi `max-width` sekitar 480px dan dipusatkan, atau field disusun dua kolom dengan `display: grid`.

Bagian daftar kartu: satu kolom di mobile (`display: grid; gap: var(--space-4)`), dua kolom mulai 768px, tiga kolom mulai 1024px lewat `grid-template-columns`.

Navigasi: bar bawah dengan beberapa `wired-icon-button` di mobile, berubah menjadi baris `wired-link` atau `wired-tabs` di header pada desktop, dengan daftar tujuan yang sama.
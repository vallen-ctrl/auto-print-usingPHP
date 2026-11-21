<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $branding['nama'] }} - Formulir Cetak Kilat</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Caveat:wght@600&family=Patrick+Hand&display=swap" rel="stylesheet">

    @vite(['resources/css/formulir-kilat.css', 'resources/js/pages/formulir-kilat.js'])
</head>
<body>
    <header class="site-header">
        <wired-button
            roughness="1.2"
            elevation="1"
            id="btn-kembali"
            class="back-button"
            data-href="{{ url('/') }}"
            aria-label="Kembali ke beranda">
            <svg class="back-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M15 5 8 12l7 7" />
            </svg>
            <span class="btn-label">Kembali</span>
        </wired-button>

        <div class="site-header-brand">
            <x-brand-logo />
            <p class="brand-name">{{ $branding['nama'] }}</p>
        </div>
    </header>

    <main class="page">
        <h1 class="page-title">Formulir Cetak Kilat</h1>

        <form class="kilat-form" method="POST" action="#">
            <wired-card class="form-card" roughness="1.2" elevation="1">
                <div class="form-body">
                    <div class="field">
                        <label class="field-label" for="nama-lengkap">Nama lengkap</label>
                        <wired-input id="nama-lengkap" name="nama_lengkap" class="input-block"
                            roughness="1.2" placeholder="Masukkan nama lengkap"></wired-input>
                    </div>

                    <div class="field">
                        <label class="field-label" for="file-berkas">File</label>
                        <label class="file-box" for="file-berkas">
                            <input id="file-berkas" name="file" type="file" class="file-input"
                                accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                            <span class="file-box-text">Pilih file</span>
                        </label>
                    </div>

                    <div class="opsi-grid">
                        <section class="field">
                            <h2 class="field-label">Orientasi</h2>
                            <wired-radio-group class="radio-group two" roughness="1.2" selected="portrait">
                                <wired-radio roughness="1.2" name="portrait">Portrait</wired-radio>
                                <wired-radio roughness="1.2" name="landscape">Landscape</wired-radio>
                            </wired-radio-group>
                        </section>

                        <section class="field">
                            <h2 class="field-label">Ukuran</h2>
                            <wired-radio-group class="radio-group two" roughness="1.2" selected="a4">
                                <wired-radio roughness="1.2" name="a4">A4 (210 x 297 mm)</wired-radio>
                                <wired-radio roughness="1.2" name="f4">F4 (210 x 330 mm)</wired-radio>
                            </wired-radio-group>
                        </section>

                        <section class="field">
                            <h2 class="field-label">Warna</h2>
                            <wired-radio-group class="radio-group two" roughness="1.2" selected="hitam-putih">
                                <wired-radio roughness="1.2" name="hitam-putih">Hitam putih</wired-radio>
                                <wired-radio roughness="1.2" name="berwarna">Berwarna</wired-radio>
                            </wired-radio-group>
                        </section>

                        <section class="field">
                            <h2 class="field-label">Kualitas</h2>
                            <wired-radio-group class="radio-group three" roughness="1.2" selected="standar">
                                <wired-radio roughness="1.2" name="rendah">Rendah</wired-radio>
                                <wired-radio roughness="1.2" name="standar">Standar</wired-radio>
                                <wired-radio roughness="1.2" name="tinggi">Tinggi</wired-radio>
                            </wired-radio-group>
                        </section>
                    </div>

                    <div class="field-grid">
                        <div class="field">
                            <label class="field-label" for="skala">Skala</label>
                            <wired-input id="skala" name="skala" class="input-block"
                                roughness="1.2" placeholder="100%"></wired-input>
                        </div>

                        <div class="field">
                            <label class="field-label" for="jumlah-cetak">Jumlah cetak</label>
                            <wired-input id="jumlah-cetak" name="jumlah_cetak" type="number" class="input-block"
                                roughness="1.2" placeholder="1"></wired-input>
                        </div>
                    </div>

                    <div class="field">
                        <label class="field-label" for="custom-halaman">Custom halaman</label>
                        <wired-input id="custom-halaman" name="custom_halaman" class="input-block"
                            roughness="1.2" placeholder="EX: 1-4"></wired-input>
                    </div>

                    <section class="field">
                        <h2 class="section-title">Pilih paket</h2>
                        <div class="paket-list">
                            <wired-card class="paket-item" roughness="1.2" elevation="1">
                                <div class="paket-row">
                                    <span class="paket-thumb" aria-hidden="true">1x1</span>
                                    <span class="paket-info">
                                        <span class="paket-nama">Paket 1</span>
                                        <span class="paket-desc">Makalah 2x</span>
                                    </span>
                                    <span class="paket-harga">Rp. 2.000</span>
                                    <wired-checkbox class="paket-checkbox" roughness="1.2"
                                        data-harga="2000" checked aria-label="Pilih Paket 1"></wired-checkbox>
                                </div>
                            </wired-card>

                            <wired-card class="paket-item" roughness="1.2" elevation="1">
                                <div class="paket-row">
                                    <span class="paket-thumb" aria-hidden="true">1x1</span>
                                    <span class="paket-info">
                                        <span class="paket-nama">Paket 2</span>
                                        <span class="paket-desc">Makalah 2x</span>
                                    </span>
                                    <span class="paket-harga">Rp. 2.000</span>
                                    <wired-checkbox class="paket-checkbox" roughness="1.2"
                                        data-harga="2000" checked aria-label="Pilih Paket 2"></wired-checkbox>
                                </div>
                            </wired-card>

                            <wired-card class="paket-item" roughness="1.2" elevation="1">
                                <div class="paket-row">
                                    <span class="paket-thumb" aria-hidden="true">1x1</span>
                                    <span class="paket-info">
                                        <span class="paket-nama">Paket 3</span>
                                        <span class="paket-desc">Makalah 2x</span>
                                    </span>
                                    <span class="paket-harga">Rp. 2.000</span>
                                    <wired-checkbox class="paket-checkbox" roughness="1.2"
                                        data-harga="2000" aria-label="Pilih Paket 3"></wired-checkbox>
                                </div>
                            </wired-card>
                        </div>
                    </section>
                </div>
            </wired-card>

            <footer class="form-footer">
                <wired-button class="btn-lanjut" roughness="1.2" elevation="2"><span class="btn-label">Lanjut pembayaran</span></wired-button>
                <p class="subtotal">Sub total: Rp <span id="subtotal">4000</span></p>
            </footer>
        </form>
    </main>
</body>
</html>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Formulir Cetak Kilat</title>
    <link href="https://fonts.googleapis.com/css2?family=Caveat:wght@600&family=Patrick+Hand&display=swap" rel="stylesheet">
    @vite(['resources/css/formulir-kilat.css', 'resources/js/pages/formulir-kilat.js'])
</head>
<body>
    <div class="page">
        <header class="form-header">
            <div class="brand">
                <span class="brand-logo" aria-hidden="true">logo</span>
                <span class="brand-name">nama print</span>
            </div>
            <h1 class="form-title">FORMULIR CETAK KILAT</h1>
        </header>

        <wired-divider class="form-divider" roughness="1.2"></wired-divider>

        <form class="kilat-form" method="POST" action="#">
            <wired-card class="form-card" roughness="1.2" elevation="1">
                <div class="form-body">
                    <div class="field">
                        <label class="field-label" for="nama-lengkap">NAMA LENGKAP</label>
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
                            <h2 class="field-label">ORIENTASI</h2>
                            <wired-radio-group class="radio-group two" roughness="1.2" selected="portrait">
                                <wired-radio roughness="1.2" name="portrait">PORTRAIT</wired-radio>
                                <wired-radio roughness="1.2" name="landscape">LANDSCAPE</wired-radio>
                            </wired-radio-group>
                        </section>

                        <section class="field">
                            <h2 class="field-label">UKURAN</h2>
                            <wired-radio-group class="radio-group two" roughness="1.2" selected="a4">
                                <wired-radio roughness="1.2" name="a4">A4 (210 x 297 mm)</wired-radio>
                                <wired-radio roughness="1.2" name="f4">F4 (210 x 330 mm)</wired-radio>
                            </wired-radio-group>
                        </section>

                        <section class="field">
                            <h2 class="field-label">WARNA</h2>
                            <wired-radio-group class="radio-group two" roughness="1.2" selected="hitam-putih">
                                <wired-radio roughness="1.2" name="hitam-putih">HITAM PUTIH</wired-radio>
                                <wired-radio roughness="1.2" name="berwarna">BERWARNA</wired-radio>
                            </wired-radio-group>
                        </section>

                        <section class="field">
                            <h2 class="field-label">KUALITAS</h2>
                            <wired-radio-group class="radio-group three" roughness="1.2" selected="standart">
                                <wired-radio roughness="1.2" name="rendah">RENDAH</wired-radio>
                                <wired-radio roughness="1.2" name="standart">STANDART</wired-radio>
                                <wired-radio roughness="1.2" name="tinggi">TINGGI</wired-radio>
                            </wired-radio-group>
                        </section>
                    </div>

                    <div class="field-grid">
                        <div class="field">
                            <label class="field-label" for="skala">SKALA</label>
                            <wired-input id="skala" name="skala" class="input-block"
                                roughness="1.2" placeholder="100%"></wired-input>
                        </div>

                        <div class="field">
                            <label class="field-label" for="jumlah-cetak">JUMLAH CETAK</label>
                            <wired-input id="jumlah-cetak" name="jumlah_cetak" type="number" class="input-block"
                                roughness="1.2" placeholder="1"></wired-input>
                        </div>
                    </div>

                    <div class="field">
                        <label class="field-label" for="custom-halaman">CUSTOM HALAMAN</label>
                        <wired-input id="custom-halaman" name="custom_halaman" class="input-block"
                            roughness="1.2" placeholder="EX: 1-4"></wired-input>
                    </div>

                    <section class="field">
                        <h2 class="section-title">Pilih Paket</h2>
                        <div class="paket-list">
                            <wired-card class="paket-item" roughness="1.2" elevation="1">
                                <div class="paket-row">
                                    <span class="paket-thumb" aria-hidden="true">1x1</span>
                                    <span class="paket-info">
                                        <span class="paket-nama">Paket1</span>
                                        <span class="paket-desc">Makalah 2x</span>
                                    </span>
                                    <span class="paket-harga">Rp. 2.000</span>
                                    <wired-checkbox class="paket-checkbox" roughness="1.2"
                                        data-harga="2000" checked aria-label="Pilih Paket1"></wired-checkbox>
                                </div>
                            </wired-card>

                            <wired-card class="paket-item" roughness="1.2" elevation="1">
                                <div class="paket-row">
                                    <span class="paket-thumb" aria-hidden="true">1x1</span>
                                    <span class="paket-info">
                                        <span class="paket-nama">Paket1</span>
                                        <span class="paket-desc">Makalah 2x</span>
                                    </span>
                                    <span class="paket-harga">Rp. 2.000</span>
                                    <wired-checkbox class="paket-checkbox" roughness="1.2"
                                        data-harga="2000" checked aria-label="Pilih Paket2"></wired-checkbox>
                                </div>
                            </wired-card>

                            <wired-card class="paket-item" roughness="1.2" elevation="1">
                                <div class="paket-row">
                                    <span class="paket-thumb" aria-hidden="true">1x1</span>
                                    <span class="paket-info">
                                        <span class="paket-nama">Paket1</span>
                                        <span class="paket-desc">Makalah 2x</span>
                                    </span>
                                    <span class="paket-harga">Rp. 2.000</span>
                                    <wired-checkbox class="paket-checkbox" roughness="1.2"
                                        data-harga="2000" aria-label="Pilih Paket3"></wired-checkbox>
                                </div>
                            </wired-card>
                        </div>
                    </section>
                </div>
            </wired-card>

            <footer class="form-footer">
                <wired-button class="btn-lanjut" roughness="1.2" elevation="2">Lanjut pembayaran</wired-button>
                <p class="subtotal">SUB TOTAL: Rp <span id="subtotal">4000</span></p>
            </footer>
        </form>
    </div>
</body>
</html>

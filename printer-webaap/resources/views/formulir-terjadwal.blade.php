<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Nama Print — Formulir Cetak Terjadwal</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Caveat:wght@600&family=Patrick+Hand&display=swap" rel="stylesheet">

    @vite(['resources/css/formulir-terjadwal.css', 'resources/js/app.js'])
</head>
<body>
    <header class="site-header">
        <div class="site-header-brand">
            <div class="logo-placeholder" role="img" aria-label="Logo jasa">logo jasa</div>
            <p class="brand-name">nama print</p>
        </div>
    </header>

    <main class="page-main">
        <div class="content">
            <h1 class="page-title">Formulir Cetak Terjadwal</h1>

            <wired-card roughness="1.2" class="panel">
                <form class="cetak-form" id="form-cetak" novalidate>
                    <div class="field">
                        <label class="field-label" for="nama">Nama lengkap</label>
                        <wired-input roughness="1.2" id="nama" name="nama" autocomplete="name"></wired-input>
                    </div>

                    <div class="field">
                        <label class="field-label" for="whatsapp">Nomor whatsapp</label>
                        <wired-input roughness="1.2" id="whatsapp" name="whatsapp" type="tel" inputmode="tel" autocomplete="tel"></wired-input>
                        <p class="field-error" id="error-whatsapp" hidden></p>
                    </div>

                    <fieldset class="fieldset">
                        <legend class="field-label">Pilih waktu &amp; tanggal pengambilan</legend>
                        <div class="field-row">
                            <div class="field">
                                <label class="field-label" for="tanggal">Tanggal</label>
                                <wired-input roughness="1.2" id="tanggal" name="tanggal" type="date"></wired-input>
                                <p class="field-error" id="error-tanggal" hidden></p>
                            </div>
                            <div class="field">
                                <label class="field-label" for="pukul">Pukul</label>
                                <wired-input roughness="1.2" id="pukul" name="pukul" type="time" value="10:00"></wired-input>
                                <p class="field-error" id="error-pukul" hidden></p>
                            </div>
                        </div>
                    </fieldset>

                    <div class="field">
                        <label class="field-label" for="pilih-file">File</label>
                        <wired-combo roughness="1.2" id="pilih-file" class="file-combo"></wired-combo>
                        <p class="file-empty" id="file-kosong" hidden>Belum ada file yang dipilih.</p>
                    </div>

                    <fieldset class="fieldset">
                        <legend class="field-label">Orientasi</legend>
                        <wired-radio-group roughness="1.2" id="grup-orientasi" selected="portrait">
                            <wired-radio roughness="1.2" name="portrait">Portrait</wired-radio>
                            <wired-radio roughness="1.2" name="landscape">Landscape</wired-radio>
                        </wired-radio-group>
                    </fieldset>

                    <fieldset class="fieldset">
                        <legend class="field-label">Ukuran</legend>
                        <wired-radio-group roughness="1.2" id="grup-ukuran" selected="a4">
                            <wired-radio roughness="1.2" name="a4">A4 (210 x 297 mm)</wired-radio>
                            <wired-radio roughness="1.2" name="f4">F4 (210 x 330 mm)</wired-radio>
                        </wired-radio-group>
                    </fieldset>

                    <fieldset class="fieldset">
                        <legend class="field-label">Warna</legend>
                        <wired-radio-group roughness="1.2" id="grup-warna" selected="hitam-putih">
                            <wired-radio roughness="1.2" name="hitam-putih">Hitam putih</wired-radio>
                            <wired-radio roughness="1.2" name="berwarna">Berwarna</wired-radio>
                        </wired-radio-group>
                    </fieldset>

                    <fieldset class="fieldset">
                        <legend class="field-label">Kualitas</legend>
                        <wired-radio-group roughness="1.2" id="grup-kualitas" selected="standar">
                            <wired-radio roughness="1.2" name="rendah">Rendah</wired-radio>
                            <wired-radio roughness="1.2" name="standar">Standar</wired-radio>
                            <wired-radio roughness="1.2" name="tinggi">Tinggi</wired-radio>
                        </wired-radio-group>
                    </fieldset>

                    <div class="field">
                        <label class="field-label" for="jumlah">Jumlah cetak</label>
                        <wired-input roughness="1.2" id="jumlah" name="jumlah" type="number" min="1" step="1" value="1"></wired-input>
                    </div>

                    <div class="field">
                        <label class="field-label" for="halaman">Custom halaman (opsional)</label>
                        <wired-input roughness="1.2" id="halaman" name="halaman" placeholder="EX: 1-4"></wired-input>
                        <p class="field-error" id="error-halaman" hidden></p>
                    </div>

                    <div class="field">
                        <label class="field-label" for="catatan">Catatan</label>
                        <wired-input roughness="1.2" id="catatan" name="catatan"></wired-input>
                    </div>

                    <fieldset class="fieldset">
                        <legend class="field-label">Pilih paket</legend>
                        <ul class="paket-list">
                            <li class="paket-item">
                                <span class="paket-thumb" aria-hidden="true">
                                    <svg class="paket-thumb-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round">
                                        <rect x="3" y="4" width="18" height="16" rx="3" />
                                        <path d="M3.5 15.5 8 11l4 4 3-3 5 4.5" />
                                        <circle cx="9" cy="8.5" r="1.3" />
                                    </svg>
                                </span>
                                <span class="paket-info">
                                    <span class="paket-name">Paket1</span>
                                    <span class="paket-desc">Keterangan</span>
                                </span>
                                <span class="paket-price">Rp. 2.000</span>
                                <wired-checkbox roughness="1.2" class="paket-check" data-harga="2000" checked>
                                    <span class="visually-hidden">Pilih Paket1</span>
                                </wired-checkbox>
                            </li>

                            <li class="paket-item">
                                <span class="paket-thumb" aria-hidden="true">
                                    <svg class="paket-thumb-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round">
                                        <rect x="3" y="4" width="18" height="16" rx="3" />
                                        <path d="M3.5 15.5 8 11l4 4 3-3 5 4.5" />
                                        <circle cx="9" cy="8.5" r="1.3" />
                                    </svg>
                                </span>
                                <span class="paket-info">
                                    <span class="paket-name">Paket1</span>
                                    <span class="paket-desc">Keterangan</span>
                                </span>
                                <span class="paket-price">Rp. 2.000</span>
                                <wired-checkbox roughness="1.2" class="paket-check" data-harga="2000" checked>
                                    <span class="visually-hidden">Pilih Paket1</span>
                                </wired-checkbox>
                            </li>

                            <li class="paket-item">
                                <span class="paket-thumb" aria-hidden="true">
                                    <svg class="paket-thumb-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round">
                                        <rect x="3" y="4" width="18" height="16" rx="3" />
                                        <path d="M3.5 15.5 8 11l4 4 3-3 5 4.5" />
                                        <circle cx="9" cy="8.5" r="1.3" />
                                    </svg>
                                </span>
                                <span class="paket-info">
                                    <span class="paket-name">Paket1</span>
                                    <span class="paket-desc">Keterangan</span>
                                </span>
                                <span class="paket-price">Rp. 2.000</span>
                                <wired-checkbox roughness="1.2" class="paket-check" data-harga="2000">
                                    <span class="visually-hidden">Pilih Paket1</span>
                                </wired-checkbox>
                            </li>
                        </ul>
                    </fieldset>

                    <div class="form-footer">
                        <wired-button roughness="1.2" elevation="1" id="btn-pembayaran" class="pay-button">Lanjut proses pembayaran</wired-button>
                        <p class="form-subtotal">SUB TOTAL: Rp. <span id="sub-total">0</span></p>
                    </div>
                </form>
            </wired-card>
        </div>
    </main>
</body>
</html>

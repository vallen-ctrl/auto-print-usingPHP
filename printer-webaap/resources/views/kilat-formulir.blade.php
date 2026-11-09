<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Nama Print — Formulir Cetak Kilat</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Caveat:wght@600&family=Patrick+Hand&display=swap" rel="stylesheet">

    {{-- Formulir kilat memakai tampilan & skrip yang sama dengan formulir
         terjadwal, tanpa kolom nomor WhatsApp. --}}
    @vite(['resources/css/formulir-terjadwal.css', 'resources/js/pages/formulir-terjadwal.js'])
</head>

<body>
    @if ($errors->any())
    <x-modal
        id="modal-info"
        type="error"
        title="Terjadi Kesalahan"
        message="{{ $errors->first() }}"
        open
    />
    @endif

    @if (session('error'))
    <x-modal id="modal-info" type="error" title="Gagal" open
        message="{{ session('error') }}" />
    @endif

    @if (!empty($pesanFormulir))
    <x-modal id="modal-formulir" type="error" title="Formulir tidak bisa diproses" open
        :message="$pesanFormulir" data-upload-redirect="{{ $uploadUrl }}" />
    @endif

    <x-modal id="modal-subtotal" type="error" title="Subtotal cetak Rp. 0"
        data-upload-redirect="{{ $uploadUrl }}"
        message="Jumlah halaman file atau harga kertas tidak terbaca, jadi subtotal cetak masih Rp. 0. Silakan upload ulang file Anda atau hubungi admin toko." />

    <x-modal id="modal-halaman" type="error" title="Custom halaman tidak sesuai"
        message="Jumlah halaman yang dipilih melebihi total halaman file." />

    <header class="site-header">
        <wired-button roughness="1.2" elevation="1" id="btn-kembali" class="back-button" data-fallback="{{ $uploadUrl }}" data-href="{{ $uploadUrl }}">
            <svg class="back-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M15 5 8 12l7 7" />
            </svg>
            Kembali
        </wired-button>

        <div class="site-header-brand">
            <div class="logo-placeholder" role="img" aria-label="Logo jasa">logo jasa</div>
            <p class="brand-name">nama print</p>
        </div>
    </header>

    <main class="page-main">
        <div class="content">
            <h1 class="page-title">Formulir Cetak Kilat</h1>
            <p class="page-subtitle">Edit di {{ $mode === 'komputer' ? 'Komputer' : 'Handphone' }}</p>

            @if ($mode === 'komputer')
            <p class="page-note">
                Isi nama penyetak saja. File akan diedit di komputer toko, dan harga cetak
                ditentukan di kasir setelah file selesai diedit.
            </p>
            @endif

            <wired-card roughness="1.2" class="panel">
                <form action="{{ route('create.order') }}" method="POST" class="cetak-form" id="form-cetak" novalidate data-order="{{ $order['orderid'] ?? '' }}">
                    @csrf
                    <div class="field">
                        <label class="field-label" for="nama">Nama lengkap</label>
                        <wired-input roughness="1.2" id="nama" name="nama" autocomplete="name"></wired-input>
                    </div>

                    {{-- Nomor WhatsApp tidak dipakai di alur kilat. --}}

                    {{-- Tanggal & pukul pengambilan tidak dipakai di alur kilat:
                         pesanan langsung diproses (ambil di tempat). --}}

                    <div class="konfigurasi-cetak{{ $mode === 'komputer' ? ' is-hidden' : '' }}">
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

                    @if ($paper->isNotEmpty())
                    <fieldset class="fieldset">
                        <legend class="field-label">Ukuran</legend>
                        <wired-radio-group roughness="1.2" id="grup-ukuran" selected="{{ $paper->first()->name }}">
                            @foreach ($paper as $pp)
                            <wired-radio roughness="1.2" id="{{ $pp->id }}" name="{{ $pp->name }}" data-harga-color="{{ $pp->harga_color }}" data-harga-grayscale="{{ $pp->harga_grayscale }}">{{ $pp->name }} ({{ $pp->width }} mm x {{ $pp->height }} mm)</wired-radio>
                            @endforeach
                        </wired-radio-group>
                    </fieldset>
                    @endif

                    <fieldset class="fieldset">
                        <legend class="field-label">Warna</legend>
                        <wired-radio-group roughness="1.2" id="grup-warna" selected="hitam putih">
                            <wired-radio roughness="1.2" name="hitam putih">Hitam putih<span class="warna-harga" data-harga-untuk="hitam putih"></span></wired-radio>
                            <wired-radio roughness="1.2" name="berwarna">Berwarna<span class="warna-harga" data-harga-untuk="berwarna"></span></wired-radio>
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

                    @if ($layanan->isNotEmpty())
                    <fieldset class="fieldset">
                        <legend class="field-label">Pilih paket</legend>
                        <ul class="paket-list">
                            @foreach ($layanan as $p)
                            <li class="paket-item">
                                <span class="paket-thumb" aria-hidden="true">
                                    @if (!empty($p->image_path) && $p->image_path !== '-' && \Illuminate\Support\Facades\Storage::disk('public')->exists($p->image_path))
                                    <img class="paket-thumb-img" src="{{ asset('storage/' . $p->image_path) }}" alt="">
                                    @else
                                    <svg class="paket-thumb-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round">
                                        <rect x="3" y="4" width="18" height="16" rx="3" />
                                        <path d="M3.5 15.5 8 11l4 4 3-3 5 4.5" />
                                        <circle cx="9" cy="8.5" r="1.3" />
                                    </svg>
                                    @endif
                                </span>
                                <span class="paket-info">
                                    <span class="paket-name">{{ $p->nama }}</span>
                                    <span class="paket-desc">{{ $p->deskripsi }}</span>
                                </span>
                                <span class="paket-price">Rp. {{ number_format($p->harga, 0, ',', '.') }}</span>
                                <wired-checkbox roughness="1.2" class="paket-check" data-id="{{ $p->id }}" data-harga="{{ $p->harga }}">
                                    <span class="visually-hidden">Pilih {{ $p->nama }}</span>
                                </wired-checkbox>
                            </li>
                            @endforeach
                        </ul>
                    </fieldset>
                    @endif
                    </div>{{-- /.konfigurasi-cetak --}}

                    <div class="form-footer">
                        <wired-button roughness="1.2" elevation="1" id="btn-pembayaran" class="pay-button">Lanjut proses pembayaran</wired-button>
                        <div class="form-totals">
                            @if ($mode === 'komputer')
                            <p class="form-subtotal">SUB TOTAL: akan ditentukan di kasir</p>
                            @else
                            <p class="form-subtotal form-subtotal-file">SUB TOTAL FILE: Rp. <span id="sub-total-file">0</span></p>
                            <p class="form-subtotal">SUB TOTAL: Rp. <span id="sub-total">0</span></p>
                            @endif
                        </div>
                    </div>
                </form>
            </wired-card>
        </div>
    </main>
    @stack('scripts')
</body>

</html>

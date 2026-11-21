<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $branding['nama'] }} - Upload PDF Cetak Terjadwal</title>
    <x-favicon />

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Caveat:wght@600&family=Patrick+Hand&display=swap" rel="stylesheet">

    @vite(['resources/css/upload-terjadwal.css', 'resources/js/pages/upload-terjadwal.js'])
</head>
<body>
    <header class="site-header">
        <wired-button roughness="1.2" elevation="1" id="btn-kembali" class="back-button" data-fallback="{{ url('/') }}" data-href="{{ route('terjadwal.keluar') }}">
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

    <main class="page-main">
        <div class="content">
            <wired-card roughness="1.2" class="panel">
                <p class="badge">
                    <svg class="badge-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="12" cy="12" r="9" />
                        <path d="M12 7v5l3 2" />
                    </svg>
                    Cetak Terjadwal
                </p>

                <h1 class="panel-title">Upload PDF, Cetak Sesuai Jadwal</h1>

                <p class="panel-text">
                    Cetak tanpa menunggu di toko. Upload file dulu, lalu atur jam pengambilan
                    dan pengaturan cetaknya di langkah berikutnya.
                </p>

                <wired-card roughness="1.2" class="dropzone">
                    <div class="dropzone-icon" aria-hidden="true">
                        <svg class="dropzone-glyph" viewBox="0 0 40 40" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="12" y="4" width="22" height="26" rx="3.5" stroke-width="1.5" stroke-opacity="0.35" />
                            <rect x="9" y="7" width="22" height="26" rx="3.5" stroke-width="1.5" stroke-opacity="0.65" />
                            <rect x="6" y="10" width="22" height="26" rx="3.5" stroke-width="1.6" />
                        </svg>
                    </div>

                    <p class="dropzone-title">upload file PDF ke sini</p>
                    <p class="dropzone-hint">hanya menerima format PDF, maksimal 10 file</p>

                    {{-- Angka pada keterangan di atas harus sama dengan data-max di bawah. --}}
                    <label class="visually-hidden" for="input-pdf">Pilih file PDF (maksimal 10 file)</label>
                    <input type="file" id="input-pdf" name="files[]" class="visually-hidden" accept="application/pdf, .pdf" data-max="10" multiple>

                    <wired-button roughness="1.2" elevation="1" id="btn-pilih-pdf" class="pick-button">
                        <span class="btn-label">Pilih File PDF Sekarang</span>
                    </wired-button>

                    <p class="file-count" id="file-count" hidden></p>
                    <ul class="file-list" id="file-list" hidden></ul>
                    <p class="file-warning" id="file-warning" role="status" hidden></p>
                </wired-card>
            </wired-card>

            <div class="next-step">
                <wired-button roughness="1.2" elevation="1" id="btn-lanjutkan" class="next-button" data-next="{{ route('formulir.terjadwal') }}" data-action="{{ route('upload.terjadwal.store') }}" data-token="{{ csrf_token() }}"><span class="btn-label">Lanjutkan</span></wired-button>
            </div>
        </div>
    </main>

    {{-- Lapisan loading: ditampilkan selama file dikirim ke server supaya user
         tahu unggahan benar-benar sedang berjalan. --}}
    <div class="upload-overlay" id="upload-overlay" role="status" aria-live="polite" hidden>
        <wired-card roughness="1.2" class="upload-overlay-card">
            <span class="upload-spinner" aria-hidden="true"></span>
            <p class="upload-title">Mengunggah file…</p>
            <p class="upload-sub" id="upload-sub">Mohon tunggu, jangan tutup halaman ini.</p>

            <div class="upload-bar" id="upload-bar" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0" hidden>
                <span class="upload-bar-fill" id="upload-bar-fill"></span>
            </div>
            <p class="upload-percent" id="upload-percent" aria-hidden="true" hidden>0%</p>
        </wired-card>
    </div>

    {{-- Popup error: semua pesan error yang terjadi di halaman upload tampil
         lewat modal ini. Validasi & flash ditangkap di sini, sedangkan kegagalan
         unggahan yang dipicu JavaScript dibuka lewat resources/js/pages/upload-terjadwal.js. --}}
    @if ($errors->any())
    <x-modal id="modal-error" type="error" title="Terjadi Kesalahan" open
        message="{{ $errors->first() }}" />
    @endif

    @if (session('error'))
    <x-modal id="modal-error" type="error" title="Gagal" open
        message="{{ session('error') }}" />
    @endif

    {{-- Popup error untuk kegagalan unggahan file / validasi yang dipicu JS. --}}
    <x-modal id="modal-upload-error" type="error" title="Upload Gagal" />
</body>
</html>

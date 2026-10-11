<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Nama Print — Upload PDF Cetak Terjadwal</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Caveat:wght@600&family=Patrick+Hand&display=swap" rel="stylesheet">

    @vite(['resources/css/upload-terjadwal.css', 'resources/js/app.js'])
</head>
<body>
    <header class="site-header">
        <div class="site-header-brand">
            <div class="logo-placeholder" role="img" aria-label="Logo jasa">logo jasa</div>
            <p class="brand-name">nama print</p>
        </div>

        <wired-button roughness="1.2" elevation="1" id="btn-kembali" class="back-button" data-fallback="{{ url('/') }}">
            <svg class="back-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M15 5 8 12l7 7" />
            </svg>
            Kembali
        </wired-button>
    </header>

    <main class="page-main">
        <div class="content">
            <wired-card roughness="1.2" class="panel">
                <p class="badge">
                    <svg class="badge-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M13 2 3 14h7l-1 8 10-12h-7l1-8z" />
                    </svg>
                    Cetak Kilat 1 Jam Jadi
                </p>

                <h1 class="panel-title">Tinggal Upload PDF, Langsung Jadi</h1>

                <p class="panel-text">
                    Cetak dokumen PDF tanpa antre. Cukup upload file, kami langsung proses.
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
                    <input type="file" id="input-pdf" class="visually-hidden" accept="application/pdf, .pdf" data-max="10" multiple>

                    <wired-button roughness="1.2" elevation="1" id="btn-pilih-pdf" class="pick-button">
                        Pilih File PDF Sekarang
                    </wired-button>

                    <p class="file-count" id="file-count" hidden></p>
                    <ul class="file-list" id="file-list" hidden></ul>
                    <p class="file-warning" id="file-warning" role="status" hidden></p>
                </wired-card>
            </wired-card>

            <div class="next-step">
                <wired-button roughness="1.2" elevation="1" id="btn-lanjutkan" class="next-button" data-next="{{ route('formulir.terjadwal') }}">Lanjutkan</wired-button>
            </div>
        </div>
    </main>
</body>
</html>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $branding['nama'] }} - Scan QR Komputer</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Caveat:wght@600&family=Patrick+Hand&display=swap" rel="stylesheet">

    @vite(['resources/css/scan-qr.css', 'resources/js/pages/scan-qr.js'])
</head>

<body>
    {{-- QR tidak dikenali / sudah kedaluwarsa. Ditutup = kamera dibuka lagi. --}}
    <x-modal
        id="modal-info"
        type="error"
        title="QR tidak dikenali"
        message="QR yang dipindai tidak valid. Coba pindai QR terbaru di layar komputer." />

    {{-- Loader selama token QR diperiksa ke server. --}}
    <div class="scan-overlay" id="scan-overlay" role="status" aria-live="polite" hidden>
        <wired-card roughness="1.2" class="scan-overlay-card">
            <span class="scan-spinner" aria-hidden="true"></span>
            <p class="scan-overlay-title">Memeriksa QR…</p>
            <p class="scan-overlay-sub">Mohon tunggu, jangan tutup halaman ini.</p>
        </wired-card>
    </div>

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
            Kembali
        </wired-button>

        <div class="site-header-brand">
            <x-brand-logo />
            <p class="brand-name">{{ $branding['nama'] }}</p>
        </div>
    </header>

    <main
        class="scan-main"
        id="scan-page"
        data-verify-base="{{ url('/scan/url') }}"
        data-print-url="{{ route('print.kilat') }}">
        <h1 class="scan-title">Scan QR di Komputer Terdekat!</h1>

        <wired-card roughness="1.2" elevation="1" class="camera-card">
            <div class="camera-stage" id="camera-stage">
                {{-- Pratinjau kamera. `muted` wajib supaya autoplay diizinkan browser. --}}
                <video
                    id="camera"
                    class="camera-video"
                    playsinline
                    autoplay
                    muted
                    aria-label="Pratinjau kamera untuk memindai QR"></video>

                {{-- Panduan sudut, hanya tampil saat kamera aktif. --}}
                <div class="camera-frame" aria-hidden="true">
                    <span class="corner corner--tl"></span>
                    <span class="corner corner--tr"></span>
                    <span class="corner corner--bl"></span>
                    <span class="corner corner--br"></span>
                </div>

                {{-- Status kamera: menyiapkan, izin ditolak, atau kamera tidak ada. --}}
                <div class="camera-state" id="camera-state">
                    <span class="camera-spinner" aria-hidden="true"></span>
                    <p class="camera-state-text" id="camera-state-text" role="status">
                        Menyiapkan kamera…
                    </p>
                    <wired-button
                        roughness="1.2"
                        elevation="1"
                        id="btn-coba"
                        class="retry-button"
                        hidden>
                        Coba Lagi
                    </wired-button>
                </div>
            </div>
        </wired-card>

        <p class="scan-hint">
            Arahkan kamera ke QR yang tampil di layar komputer. QR berganti setiap 60 detik,
            jadi pindai QR yang paling baru.
        </p>
    </main>

    @stack('scripts')
</body>

</html>

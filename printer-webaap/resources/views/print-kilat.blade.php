<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Nama Print — Cetak Kilat</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Caveat:wght@600&family=Patrick+Hand&display=swap" rel="stylesheet">

    @vite(['resources/css/print-kilat.css', 'resources/js/pages/print-kilat.js'])
</head>

<body>
    {{-- Langkah edit belum dibuat; tombol hanya memberi tahu bahwa fitur menyusul. --}}
    <x-modal
        id="modal-soon"
        type="info"
        title="Belum tersedia"
        message="Pilihan tempat edit ini belum tersedia. Langkah berikutnya akan dibuat setelah alur scan QR selesai." />

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
            <div class="logo-placeholder" role="img" aria-label="Logo jasa">logo jasa</div>
            <p class="brand-name">nama print</p>
        </div>
    </header>

    <main class="print-main">
        <wired-card roughness="1.2" elevation="1" class="print-card">
            <div class="print-card-inner">
                <wired-button
                    roughness="1.2"
                    elevation="1"
                    id="btn-komputer"
                    class="print-button"
                    data-tempat="Komputer">
                    Edit di Komputer
                </wired-button>

                <wired-button
                    roughness="1.2"
                    elevation="1"
                    id="btn-handphone"
                    class="print-button"
                    data-tempat="Handphone">
                    Edit di Handphone
                </wired-button>
            </div>
        </wired-card>
    </main>

    @stack('scripts')
</body>

</html>

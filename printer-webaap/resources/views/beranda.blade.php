<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $branding['nama'] }} - Pilih Layanan Cetak</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Caveat:wght@600&family=Patrick+Hand&display=swap" rel="stylesheet">

    @vite(['resources/css/beranda.css', 'resources/js/pages/beranda.js'])
</head>
<body>
    <header class="site-header">
        <div class="site-header-brand">
            {{-- Kotak placeholder logo: ganti dengan gambar logo asli kalau sudah tersedia. --}}
            <x-brand-logo />
            <p class="brand-name">{{ $branding['nama'] }}</p>
        </div>
    </header>

    <main class="page-main">
        {{-- Pesan dari server, mis. file sudah dihapus karena order kedaluwarsa. --}}
        @if (session('error'))
            <div class="panel-notice" role="alert">
                <p>{{ session('error') }}</p>
            </div>
        @endif

        @if (session('success'))
            <div class="panel-notice panel-notice--success" role="status">
                <p>{{ session('success') }}</p>
            </div>
        @endif

        <wired-card roughness="1.2" class="panel">
            <div class="panel-inner">
                <header class="panel-intro">
                    <h1 class="panel-title">Pilih Cara Cetak</h1>
                    <p class="panel-lead">Ada dua layanan dengan alur berbeda. Pilih yang paling sesuai dengan kebutuhanmu.</p>
                </header>

                <div class="panel-options">
                    <div class="panel-option">
                        <wired-button roughness="1.2" class="panel-button" elevation="1" data-href="{{ route('scan.qr') }}"><span class="btn-label">Cetak Kilat</span></wired-button>
                        <p class="panel-option-desc">
                            Harus dilakukan di tempat. Datang ke toko, scan QR, lalu file langsung dicetak saat itu juga.
                        </p>
                    </div>

                    <div class="panel-option">
                        <wired-button roughness="1.2" class="panel-button" elevation="1" data-href="{{ route('upload.terjadwal') }}"><span class="btn-label">Cetak Terjadwal</span></wired-button>
                        <p class="panel-option-desc">
                            Dicetak otomatis sesuai jam yang kamu pilih. Hasilnya bisa diambil pada jam tersebut,
                            atau dicetak oleh tukang saat jamnya tiba.
                        </p>
                    </div>
                </div>
            </div>
        </wired-card>
    </main>
</body>
</html>

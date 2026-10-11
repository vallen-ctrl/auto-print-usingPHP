<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Nama Print — Pilih Layanan Cetak</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Caveat:wght@600&family=Patrick+Hand&display=swap" rel="stylesheet">

    @vite(['resources/css/beranda.css', 'resources/js/app.js'])
</head>
<body>
    <header class="site-header">
        {{-- Kotak placeholder logo: ganti dengan gambar logo asli kalau sudah tersedia. --}}
        <div class="logo-placeholder" role="img" aria-label="Logo jasa">logo jasa</div>
        <p class="brand-name">nama print</p>
    </header>

    <main class="page-main">
        <wired-card roughness="1.2" class="panel">
            <div class="panel-inner">
                <wired-button roughness="1.2" class="panel-button" elevation="1">Cetak Kilat</wired-button>
                <wired-button roughness="1.2" class="panel-button" elevation="1" data-href="{{ route('upload.terjadwal') }}">Cetak Terjadwal</wired-button>
            </div>
        </wired-card>
    </main>
</body>
</html>

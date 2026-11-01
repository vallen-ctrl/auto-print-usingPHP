@php
    $rupiah = fn ($nilai) => 'Rp. ' . number_format((int) $nilai, 0, ',', '.');
    $kembali = $order['layanan'] === 'terjadwal' ? route('upload.terjadwal') : route('formulir.kilat');
@endphp
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Konfirmasi Pembayaran</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Caveat:wght@600&family=Patrick+Hand&display=swap" rel="stylesheet">

    @vite(['resources/css/order-konfirmasi.css', 'resources/js/pages/order-konfirmasi.js'])
</head>

<body>
    {{-- Total untuk kilat-edit kosong karena harga menyesuaikan setelah diedit. --}}
    @if ($order['harga_menyesuaikan'])
        <x-modal
            id="modal-total"
            type="warning"
            title="Harga akan menyesuaikan"
            message="File kamu masih bisa diedit setelah dipesan, jadi jumlah halaman dan harga cetaknya belum pasti. Total pembayaran baru dihitung di kasir setelah file selesai diedit. Biaya admin dan biaya penanganan tetap seperti yang tertera." />
    @endif

    <header class="site-header">
        <div class="site-header-inner">
            <wired-button
                roughness="1.2"
                elevation="1"
                id="btn-kembali"
                class="back-button"
                data-fallback="{{ $kembali }}"
                aria-label="Kembali">
                <svg class="back-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M15 5 8 12l7 7" />
                </svg>
                <span class="visually-hidden">Kembali</span>
            </wired-button>

            <h1 class="page-title">Konfirmasi Pembayaran</h1>
        </div>
    </header>

    <main class="page-main">
        <div class="content">
            <div class="kolom">
                <p class="badge-ambil">{{ $order['label_ambil'] }}</p>

                {{-- Rincian dokumen --}}
                <wired-card roughness="1.2" elevation="1" class="card">
                    <div class="card-body">
                        <h2 class="card-title" id="judul-dokumen">Rincian dokumen</h2>

                        <ul class="doc-list" aria-labelledby="judul-dokumen">
                            @foreach ($files as $file)
                                <li class="doc-item">
                                    <span class="doc-icon" aria-hidden="true">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z" />
                                            <path d="M14 3v5h5" />
                                        </svg>
                                    </span>

                                    <span class="doc-info">
                                        <span class="doc-name" title="{{ $file['name'] }}">{{ $file['name'] }}</span>
                                        <span class="doc-meta">{{ $file['pages'] }} halaman &bull; {{ $file['size'] }} &bull; {{ $file['warna'] }}</span>
                                    </span>

                                    <span class="doc-price">
                                        {{ $order['harga_menyesuaikan'] ? 'Menyesuaikan' : $rupiah($file['subtotal']) }}
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </wired-card>
            </div>

            <div class="kolom">
                {{-- Metode pembayaran --}}
                <wired-card roughness="1.2" elevation="1" class="card">
                    <div class="card-body">
                        <div class="card-head">
                            <h2 class="card-title" id="judul-metode">Pilih Metode Pembayaran</h2>

                            <span class="secure">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <rect x="5" y="10" width="14" height="10" rx="2.5" />
                                    <path d="M8.5 10V7.5a3.5 3.5 0 0 1 7 0V10" />
                                </svg>
                                100% aman
                            </span>
                        </div>

                        <div class="pay-list" role="radiogroup" aria-labelledby="judul-metode">
                            @foreach ($metode as $opsi)
                                <div
                                    class="pay-option {{ $loop->first ? 'is-selected' : '' }}"
                                    role="radio"
                                    aria-checked="{{ $loop->first ? 'true' : 'false' }}"
                                    tabindex="{{ $loop->first ? '0' : '-1' }}"
                                    data-metode="{{ $opsi['id'] }}"
                                    data-label-cta="{{ $opsi['cta'] }}">

                                    <span class="pay-icon" aria-hidden="true">
                                        @if ($opsi['id'] === 'qris')
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                                <rect x="4" y="4" width="6" height="6" rx="1" />
                                                <rect x="14" y="4" width="6" height="6" rx="1" />
                                                <rect x="4" y="14" width="6" height="6" rx="1" />
                                                <path d="M14 14h3v3h-3zM20 14h.01M14 20h.01M20 20h.01" />
                                            </svg>
                                        @else
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                                <rect x="3" y="7" width="18" height="11" rx="2" />
                                                <path d="M3 11h18" />
                                                <circle cx="17" cy="15" r="1.2" />
                                            </svg>
                                        @endif
                                    </span>

                                    <span class="pay-text">
                                        <span class="pay-name">
                                            {{ $opsi['nama'] }}
                                            @if ($opsi['rekomendasi'])
                                                <span class="tag">Recommended</span>
                                            @endif
                                        </span>
                                        <span class="pay-desc">{{ $opsi['deskripsi'] }}</span>
                                    </span>

                                    <span class="pay-ring" aria-hidden="true"></span>
                                </div>
                            @endforeach
                        </div>

                        @if ($order['harga_menyesuaikan'])
                            <p class="pay-note">
                                Harga akan menyesuaikan setelah file selesai diedit. Pembayaran dilakukan di kasir.
                            </p>
                        @endif
                    </div>
                </wired-card>

                {{-- Ringkasan pembayaran --}}
                <wired-card roughness="1.2" elevation="1" class="card">
                    <div class="card-body">
                        <h2 class="card-title" id="judul-ringkasan">Ringkasan Pembayaran</h2>

                        <dl class="summary" aria-labelledby="judul-ringkasan">
                            <div class="summary-row">
                                <dt>Subtotal cetak ({{ count($files) }} item)</dt>
                                <dd>{{ $order['harga_menyesuaikan'] ? 'Rp. —' : $rupiah($order['subtotal']) }}</dd>
                            </div>
                            <div class="summary-row">
                                <dt>Biaya Admin</dt>
                                <dd>{{ $rupiah($order['biaya_admin']) }}</dd>
                            </div>
                            <div class="summary-row">
                                <dt>Biaya Penanganan</dt>
                                <dd>{{ $rupiah($order['biaya_penanganan']) }}</dd>
                            </div>
                        </dl>

                        @if ($order['harga_menyesuaikan'])
                            <button type="button" class="total-box total-box--tanya" data-modal-open="modal-total">
                                <span>Total Pembayaran</span>
                                <span class="total-value">
                                    Rp. &mdash;
                                    <span class="tanya" aria-hidden="true">?</span>
                                    <span class="visually-hidden">, lihat penjelasan harga</span>
                                </span>
                            </button>
                        @else
                            <div class="total-box">
                                <span>Total Pembayaran</span>
                                <span class="total-value">{{ $rupiah($order['total']) }}</span>
                            </div>
                        @endif
                    </div>
                </wired-card>
            </div>
        </div>
    </main>

    <footer class="pay-bar">
        <div class="pay-bar-inner">
            <div class="pay-bar-total">
                <span class="pay-bar-label">Total Bayar</span>

                @if ($order['harga_menyesuaikan'])
                    <button type="button" class="pay-bar-value pay-bar-value--tanya" data-modal-open="modal-total">
                        Rp. &mdash;
                        <span class="visually-hidden">, lihat penjelasan harga</span>
                    </button>
                @else
                    <span class="pay-bar-value" id="total-bayar">{{ $rupiah($order['total']) }}</span>
                @endif
            </div>

            <wired-button roughness="1.2" elevation="2" class="btn-bayar" id="btn-bayar">
                <span class="cta-labels">
                    @foreach ($metode as $opsi)
                        <span class="cta-label" data-cta="{{ $opsi['id'] }}" @if (!$loop->first) data-nonaktif="true" @endif>{{ $opsi['cta'] }}</span>
                    @endforeach
                </span>
            </wired-button>
        </div>
    </footer>

    @stack('scripts')
</body>

</html>

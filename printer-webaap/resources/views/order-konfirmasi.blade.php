@php
$rupiah = fn ($nilai) => 'Rp. ' . number_format((int) $nilai, 0, ',', '.');
@endphp
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Konfirmasi Pembayaran</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Caveat:wght@600&family=Patrick+Hand&display=swap" rel="stylesheet">

    {{-- Popup pembayaran Midtrans Snap (sandbox saat is_production=false). --}}
    <script
        src="{{ config('midtrans.is_production') ? 'https://app.midtrans.com/snap/snap.js' : 'https://app.sandbox.midtrans.com/snap/snap.js' }}"
        data-client-key="{{ config('midtrans.client_key') }}"></script>

    @vite(['resources/css/order-konfirmasi.css', 'resources/js/pages/order-konfirmasi.js'])
</head>

<body>
    <div id="bgRolling" style="position:fixed;inset:0;z-index:50;display:none;flex-direction:column;align-items:center;justify-content:center;gap:12px;background:oklch(55.2% 0.016 285.938 / 0.6);">
        <x-loader />
        <p style="color:#000;font-weight:600;letter-spacing:0.1em;font-size:1.2em">Mohon tunggu..</p>
    </div>
    {{-- Total untuk kilat-edit kosong karena harga menyesuaikan setelah diedit. --}}
    @if ($order['harga_menyesuaikan'])
    <x-modal
        id="modal-total"
        type="warning"
        title="Harga akan menyesuaikan"
        message="File kamu masih bisa diedit setelah dipesan, jadi jumlah halaman dan harga cetaknya belum pasti. Total pembayaran baru dihitung di kasir setelah file selesai diedit. Biaya admin dan biaya penanganan tetap seperti yang tertera." />
    @endif

    {{-- Pesan kesalahan saat meminta token pembayaran. --}}
    <x-modal
        id="modal-info"
        type="error"
        title="Gagal memulai pembayaran"
        message="Silakan coba lagi beberapa saat lagi." />

    {{-- Konfirmasi sebelum meminta token pembayaran. Tombol "Tidak", ikon tutup,
         klik latar gelap, atau Escape hanya menutup dialog tanpa memproses apa pun. --}}
    <x-modal
        id="modal-bayar"
        type="warning"
        title="Lanjut ke pembayaran?">
        Kalau kamu menekan tombol Kembali, pesanan ini langsung dibatalkan dan pembayaran
        sebelumnya dianggap gagal, jadi file harus diupload ulang dari awal. Pastikan file
        dan pengaturan cetak sudah benar.
        <x-slot:actions>
            <wired-button roughness="1.2" elevation="1" data-modal-close>Tidak</wired-button>
            <wired-button roughness="1.2" elevation="2" id="btn-lanjut-bayar">Ya, lanjutkan</wired-button>
        </x-slot:actions>
    </x-modal>

    {{-- Peringatan sebelum meninggalkan halaman pembayaran. Pesanan yang belum
         dikonfirmasi langsung dianggap gagal kalau user memilih keluar. --}}
    <x-modal
        id="modal-keluar"
        type="error"
        title="Batalkan pesanan?">
        Pembayaran kamu <strong>belum dikonfirmasi</strong>. Kalau kamu kembali ke halaman
        sebelumnya sekarang, pesanan ini langsung dibatalkan dan pembayaran dianggap
        gagal (hangus), jadi file harus diupload ulang dari awal.
        <x-slot:actions>
            <wired-button roughness="1.2" elevation="1" data-modal-close>Tidak, tetap di sini</wired-button>
            <wired-button roughness="1.2" elevation="2" id="btn-konfirmasi-keluar">Ya, batalkan pesanan</wired-button>
        </x-slot:actions>
    </x-modal>

    {{-- Pesanan sudah dibayar: user boleh pergi, lalu mulai dari beranda. --}}
    <x-modal
        id="modal-sukses-keluar"
        type="success"
        title="Pembayaran berhasil">
        Pesanan ini sudah dibayar dan dinyatakan berhasil. Kamu sudah bisa meninggalkan
        halaman ini. Setelah pesan ini ditutup, kamu akan diarahkan ke beranda untuk
        mulai dari awal.
        <x-slot:actions>
            <wired-button roughness="1.2" elevation="2" data-modal-close>Kembali ke beranda</wired-button>
        </x-slot:actions>
    </x-modal>

    {{-- Petunjuk pembayaran di kasir (kilat-langsung & kilat-edit). --}}
    <x-modal
        id="modal-kasir"
        type="info"
        title="Bayar di kasir"
        :message="$order['pesan_kasir']" />

    {{-- Waktu pembayaran habis. --}}
    <x-modal
        id="modal-kadaluarsa"
        type="error"
        title="Waktu pembayaran habis"
        :dismissible="false">
        Waktu pembayaran sudah habis. File Anda dihapus dari server, silakan upload ulang file Anda.
        <x-slot:actions>
            <wired-button roughness="1.2" elevation="1" data-href="{{ url('/') }}">
                Kembali ke beranda
            </wired-button>
        </x-slot:actions>
    </x-modal>

    {{-- Tombol Kembali membatalkan pesanan: session dihapus, file dibersihkan,
         dan user memulai lagi dari beranda. --}}
    <form id="form-batal-pesanan" method="POST"
        action="{{ route('order.konfirmasi.batal', ['order_id' => $order['id']]) }}" hidden>
        @csrf
    </form>

    <header class="site-header">
        <wired-button
            roughness="1.2"
            elevation="1"
            id="btn-kembali"
            class="back-button"
            data-batal-url="{{ route('order.konfirmasi.batal', ['order_id' => $order['id']]) }}"
            data-fallback="{{ url('/') }}">
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

    <main
        class="page-main"
        id="halaman-konfirmasi"
        data-order-id="{{ $order['id'] }}"
        data-token-url="{{ route('create.snap.token') }}"
        data-kasir-url="{{ route('order.konfirmasi.kasir', ['order_id' => $order['id']]) }}"
        data-status-url="{{ route('order.struk', ['id' => $order['id']]) }}"
        data-cek-status-url="{{ route('order.konfirmasi.status', ['order_id' => $order['id']]) }}"
        data-struk-url="{{ route('struk.render', ['id' => $order['id']]) }}"
        data-sisa-detik="{{ $order['sisa_detik'] }}"
        data-menunggu-kasir="{{ $order['menunggu_kasir'] ? '1' : '0' }}">
        <h1 class="page-title">Konfirmasi Pembayaran</h1>

        {{-- Kode yang dibacakan/ditunjukkan ke kasir. --}}
        @if ($order['kode'])
        <p class="kode-order">
            <span>Kode order</span>
            <strong>{{ $order['kode'] }}</strong>
        </p>
        @endif

        @if ($order['menunggu_kasir'])
        <p class="status-notice" role="status">
            Pesanan berhasil. {{ $order['pesan_kasir'] }}
        </p>
        @else
        <p class="timer-bayar" id="timer-bayar" role="timer">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <circle cx="12" cy="12" r="8.5" />
                <path d="M12 7.5V12l3 2" />
            </svg>
            <span>Sisa waktu pembayaran</span>
            <strong id="sisa-waktu">{{ gmdate('i:s', $order['sisa_detik']) }}</strong>
        </p>
        @endif

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

                {{-- Komputer tujuan (kilat-edit): file diedit di komputer toko
                     yang QR-nya dipindai sebelum upload. --}}
                @if ($order['komputer'])
                <wired-card roughness="1.2" elevation="1" class="card card-komputer">
                    <div class="card-body">
                        <h2 class="card-title" id="judul-komputer">Komputer untuk edit</h2>

                        <p class="komputer-nama">
                            <svg class="komputer-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <rect x="3" y="4" width="18" height="12" rx="2" />
                                <path d="M8 20h8M12 16v4" />
                            </svg>
                            <span>{{ $order['komputer'] }}</span>
                        </p>

                        <ol class="komputer-langkah">
                            <li>Kamu mengedit file pesanan ini di komputer tersebut.</li>
                            <li>Setelah selesai, tunjukkan halaman ini ke kasir.</li>
                            <li>Kasir menghitung harga cetak setelah file selesai diedit.</li>
                        </ol>
                    </div>
                </wired-card>
                @endif
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
                            @php $aktif = ($order['metode_terpilih'] ?? $metode[0]['id']) === $opsi['id']; @endphp
                            <div
                                class="pay-option {{ $aktif ? 'is-selected' : '' }}"
                                role="radio"
                                aria-checked="{{ $aktif ? 'true' : 'false' }}"
                                tabindex="{{ $aktif ? '0' : '-1' }}"
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
                            @if ($order['biaya_penanganan'] > 0)
                            <div class="summary-row">
                                <dt>Biaya Penanganan</dt>
                                <dd>{{ $rupiah($order['biaya_penanganan']) }}</dd>
                            </div>
                            @endif
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

            <wired-button roughness="1.2" elevation="2" class="btn-bayar" id="btn-bayar" @if ($order['menunggu_kasir']) disabled @endif>
                <span class="cta-labels">
                    @if ($order['menunggu_kasir'])
                    <span class="cta-label">
                        {{ $order['komputer'] ? 'Komputer: ' . $order['komputer'] : 'Menunggu konfirmasi kasir' }}
                    </span>
                    @else
                    @foreach ($metode as $opsi)
                    <span class="cta-label" data-cta="{{ $opsi['id'] }}" @if (!$loop->first) data-nonaktif="true" @endif>{{ $opsi['cta'] }}</span>
                    @endforeach
                    @endif
                </span>
            </wired-button>
        </div>
    </footer>

    @stack('scripts')
</body>

</html>
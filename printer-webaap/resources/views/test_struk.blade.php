<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struk Pembayaran</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Caveat:wght@600&family=Patrick+Hand&display=swap" rel="stylesheet">

    <script src="https://cdn.jsdelivr.net/npm/qrcodejs/qrcode.min.js"></script>

    @vite(['resources/css/test-struk.css', 'resources/js/pages/test-struk.js'])
</head>

<body>
    {{-- Ingatkan user menyimpan struk. Dibuka otomatis saat struk tampil. --}}
    <x-modal
        id="modal-struk"
        type="warning"
        title="Simpan struk ini"
        message="Unduh atau screenshot struk ini sekarang. Tanpa struk, file yang sudah dipesan akan sulit untuk dicari kembali." />

    {{-- Konfirmasi sebelum keluar dari halaman struk. --}}
    <x-modal id="modal-keluar" type="warning" title="Keluar dari struk?">
        Pastikan kamu sudah screenshot atau menyimpan struk ini, atau salin URL-nya. Kalau keluar
        tanpa menyimpannya, pesanan akan sulit dicari.
        <x-slot:actions>
            <wired-button roughness="1.2" elevation="1" data-modal-close>Tetap di sini</wired-button>
            <wired-button roughness="1.2" elevation="2" id="btn-keluar">Ya, keluar</wired-button>
        </x-slot:actions>
    </x-modal>

    <header class="site-header">
        <wired-button
            roughness="1.2"
            elevation="1"
            id="btn-kembali"
            class="back-button"
            aria-label="Kembali">
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

    <main class="page">
        <wired-card roughness="1.2" elevation="1" id="status-card" class="status-card" role="status">
            <div class="status-body">
                <div class="spinner" aria-hidden="true"></div>
                <p class="status-text" id="statusText">Memferivikasi pembayaran…</p>
                <p class="status-order" id="orderId"></p>
            </div>
        </wired-card>

        <wired-card roughness="1.2" elevation="1" id="error-card" class="status-card" hidden>
            <div class="status-body">
                <div class="error-icon" aria-hidden="true">!</div>
                <p class="error-text" id="errorText">Pembayaran gagal / dikembalikan</p>
            </div>
        </wired-card>

        <wired-card roughness="1.2" elevation="1" id="struk-card" class="struk" hidden>
            <div class="struk-body">
                <div class="qr-wrap">
                    <div id="qrcode"></div>
                </div>

                <dl class="rows">
                    <div>
                        <dt>Order ID</dt>
                        <dd class="order-id" id="s-orderid"></dd>
                    </div>
                    <div>
                        <dt>Pelanggan</dt>
                        <dd id="s-pelanggan"></dd>
                    </div>
                    <div>
                        <dt>Tanggal Pesan</dt>
                        <dd id="s-pesan"></dd>
                    </div>
                    <div>
                        <dt>Tanggal Ambil</dt>
                        <dd id="s-ambil"></dd>
                    </div>
                </dl>

                <wired-divider roughness="1.2" class="divider"></wired-divider>

                <div class="files" id="s-files"></div>

                <wired-divider roughness="1.2" class="divider"></wired-divider>

                <dl class="rows small">
                    <div>
                        <dt>Biaya Admin</dt>
                        <dd id="s-admin"></dd>
                    </div>
                    <div id="row-penanganan" hidden>
                        <dt>Biaya Penanganan</dt>
                        <dd id="s-penanganan"></dd>
                    </div>
                </dl>

                <wired-divider roughness="1.2" class="divider"></wired-divider>

                <dl class="rows">
                    <div>
                        <dt>Total Pembayaran</dt>
                        <dd id="s-total"></dd>
                    </div>
                    <div>
                        <dt>Dibayar</dt>
                        <dd id="s-dibayar"></dd>
                    </div>
                </dl>

                <wired-divider roughness="1.2" class="divider"></wired-divider>

                <dl class="rows small">
                    <div>
                        <dt>Catatan</dt>
                        <dd id="s-catatan">-</dd>
                    </div>
                    <div>
                        <dt>Item</dt>
                        <dd id="s-item">-</dd>
                    </div>
                </dl>
            </div>
        </wired-card>

        <div class="actions">
            <wired-button roughness="1.2" elevation="2" id="btn-unduh" class="btn-unduh" hidden>
                <span class="btn-label">Unduh Sekarang</span>
            </wired-button>

            <wired-button roughness="1.2" elevation="1" id="btn-salin" class="btn-unduh" hidden>
                <span class="btn-label">Salin URL Struk</span>
            </wired-button>
        </div>

        <p class="copy-status" id="salin-status" role="status" hidden>URL struk disalin.</p>
    </main>

    @stack('scripts')
</body>

</html>

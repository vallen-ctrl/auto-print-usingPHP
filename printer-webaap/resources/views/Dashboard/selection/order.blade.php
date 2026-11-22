@extends('Dashboard.layout')

@section('title', 'Order')

@php
    $badgeBayar = [
        'pending'    => 'bg-warning-soft text-warning-strong',
        'processing' => 'bg-blue-100 text-blue-800',
        'paid'       => 'bg-success-soft text-success-strong',
        'failed'     => 'bg-danger-soft text-danger-strong',
        'fail'       => 'bg-danger-soft text-danger-strong',
        'refunded'   => 'bg-muted text-ink-muted',
    ];

    $badgeFile = [
        'pending'     => 'bg-warning-soft text-warning-strong',
        'processing'  => 'bg-blue-100 text-blue-800',
        'completed'   => 'bg-success-soft text-success-strong',
        'downloading' => 'bg-indigo-100 text-indigo-800',
        'failed'      => 'bg-danger-soft text-danger-strong',
    ];

    $labelLayanan = [
        'kilat-edit'     => 'Kilat edit',
        'kilat-langsung' => 'Kilat langsung',
        'terjadwal'      => 'Terjadwal',
    ];

    $rupiah = fn ($v) => 'Rp ' . number_format((int) $v, 0, ',', '.');
@endphp

@section('content')
<div class="w-full">

    @if (session('success'))
        <x-toast type="success" :message="session('success')" />
    @endif
    @if (session('error'))
        <x-toast type="error" :message="session('error')" />
    @endif

    {{-- Header --}}
    <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
        <h1 class="text-2xl font-semibold text-ink">Order</h1>
        <p class="text-sm text-ink-muted">{{ now()->translatedFormat('l, j F Y') }}</p>
    </div>

    {{-- Toolbar --}}
    <form method="GET" action="{{ route('order.index') }}" class="mt-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-end">
        <div class="relative w-full sm:w-80">
            <label for="order-search" class="sr-only">Cari order</label>
            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                <x-lucide-search class="size-4 text-ink-muted" aria-hidden="true" />
            </div>
            <input type="search" id="order-search" name="search" value="{{ $search }}"
                placeholder="Cari order id, nama pemesan, status, metode..."
                class="block h-10 w-full rounded-lg border border-line bg-surface pl-10 pr-3 text-sm text-ink placeholder:text-ink-muted focus:border-primary focus:ring-2 focus:ring-primary/30 focus:outline-none">
        </div>

        <div class="flex items-center gap-2">
            <button type="button" id="btn-scan-qr"
                class="inline-flex h-10 items-center justify-center gap-2 rounded-lg border border-line bg-surface px-4 text-sm font-medium text-ink transition-colors hover:bg-muted focus:ring-2 focus:ring-primary/30 focus:outline-none">
                <x-lucide-scan-qr-code class="size-4" aria-hidden="true" />
                Scan QR
            </button>
            <button type="submit"
                class="inline-flex h-10 items-center justify-center gap-2 rounded-lg bg-primary px-4 text-sm font-medium text-white transition-colors hover:bg-primary-hover focus:ring-2 focus:ring-primary/40 focus:outline-none">
                <x-lucide-search class="size-4" aria-hidden="true" />
                Cari
            </button>
            @if ($search !== '')
                <a href="{{ route('order.index') }}"
                    class="inline-flex h-10 items-center justify-center rounded-lg border border-line bg-surface px-4 text-sm font-medium text-ink transition-colors hover:bg-muted focus:ring-2 focus:ring-primary/30 focus:outline-none">
                    Reset
                </a>
            @endif
        </div>
    </form>

    @if ($search !== '')
        <p class="mt-3 text-sm text-ink-muted">
            Hasil pencarian untuk <span class="font-medium text-ink">"{{ $search }}"</span>:
            {{ $orders->total() }} order.
        </p>
    @endif

    {{-- Table card --}}
    <div class="mt-4 overflow-hidden rounded-xl border border-line bg-surface shadow-sm">
        <div class="overflow-x-auto">
            <table id="tabel-order" class="w-full min-w-248 text-left text-sm">
                <thead class="border-b border-line bg-muted/60">
                    <tr class="text-ink-muted">
                        <th scope="col" class="whitespace-nowrap px-4 py-3 font-medium">Order Id</th>
                        <th scope="col" class="whitespace-nowrap px-4 py-3 font-medium">Nama pemesan</th>
                        <th scope="col" class="whitespace-nowrap px-4 py-3 font-medium">Tanggal pesan</th>
                        <th scope="col" class="whitespace-nowrap px-4 py-3 font-medium">Waktu ambil</th>
                        <th scope="col" class="whitespace-nowrap px-4 py-3 text-right font-medium">Total</th>
                        <th scope="col" class="whitespace-nowrap px-4 py-3 font-medium">Status pembayaran</th>
                        <th scope="col" class="whitespace-nowrap px-4 py-3 font-medium">Metode pembayaran</th>
                        <th scope="col" class="whitespace-nowrap px-4 py-3 font-medium">Status file</th>
                        <th scope="col" class="whitespace-nowrap px-4 py-3 font-medium">Tipe cetak</th>
                        <th scope="col" class="whitespace-nowrap px-4 py-3 font-medium">Mode cetak</th>
                        <th scope="col" class="whitespace-nowrap px-4 py-3 text-right font-medium">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line text-ink">
                    @forelse ($orders as $o)
                        @php
                            $idHex = $o->id_hex ?: '-';
                            $pesan = $o->tanggal_pesan ? $o->tanggal_pesan->format('d/m/Y') : '-';
                            $ambil = $o->tanggal_ambil ? $o->tanggal_ambil->format('d/m/Y H:i') : '-';
                            $statusBayar = $o->status_pembayaran ?: '-';
                            $metode = $o->metode_pembayaran ?: '-';
                            $statusFileList = $o->status_file ? explode(',', $o->status_file) : [];
                            $statusFile = $o->status_file ?: '-';
                            $statusFilePertama = $statusFileList[0] ?? '';
                            $modeCetak = $o->mode_cetak ?: 'otomatis';
                            $layananList = $o->layanan ? explode(',', $o->layanan) : [];
                            $cetak = $layananList
                                ? implode(', ', array_map(fn ($v) => $labelLayanan[$v] ?? $v, $layananList))
                                : '-';
                        @endphp
                        <tr data-row class="transition-colors hover:bg-muted/50">
                            <td class="whitespace-nowrap px-4 py-3 font-medium">
                                <span class="block max-w-[9rem] truncate" title="{{ $idHex }}">{{ $idHex }}</span>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3">{{ $o->nama_pemesan ?: '-' }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-ink-muted">{{ $pesan }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-ink-muted">{{ $ambil }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right font-medium tabular-nums">{{ $rupiah($o->total_pembayaran) }}</td>
                            <td class="whitespace-nowrap px-4 py-3">
                                <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium capitalize {{ $badgeBayar[$statusBayar] ?? 'bg-muted text-ink-muted' }}">
                                    {{ $statusBayar }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3">{{ $metode }}</td>
                            <td class="whitespace-nowrap px-4 py-3">
                                @if ($modeCetak === 'tukang')
                                    <form method="POST" action="{{ route('order.statusfile', $idHex) }}"
                                        class="flex items-center gap-1.5">
                                        @csrf
                                        <label for="status-file-{{ $idHex }}" class="sr-only">
                                            Status file order {{ $idHex }}
                                        </label>
                                        <select id="status-file-{{ $idHex }}" name="status"
                                            class="h-9 rounded-lg border border-line bg-surface px-2 text-xs text-ink focus:border-primary focus:ring-2 focus:ring-primary/30 focus:outline-none">
                                            @foreach (['pending' => 'Pending', 'processing' => 'Processing', 'downloading' => 'Downloading', 'completed' => 'Completed', 'failed' => 'Failed'] as $nilai => $label)
                                                <option value="{{ $nilai }}" @selected($statusFilePertama === $nilai)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        <button type="submit"
                                            class="inline-flex h-9 items-center justify-center rounded-lg bg-primary px-3 text-xs font-medium text-white transition-colors hover:bg-primary-hover focus:ring-2 focus:ring-primary/40 focus:outline-none">
                                            Simpan
                                        </button>
                                    </form>
                                @else
                                    <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium capitalize {{ $badgeFile[$statusFilePertama] ?? 'bg-muted text-ink-muted' }}">
                                        {{ $statusFile }}
                                    </span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-ink-muted">{{ $cetak }}</td>
                            <td class="whitespace-nowrap px-4 py-3">
                                <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium {{ $modeCetak === 'tukang' ? 'bg-warning-soft text-warning-strong' : 'bg-success-soft text-success-strong' }}">
                                    {{ $modeCetak === 'tukang' ? 'Tukang' : 'Otomatis' }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('order.detail', $idHex) }}"
                                        class="inline-flex h-9 items-center rounded-lg bg-primary px-4 text-sm font-medium text-white transition-colors hover:bg-primary-hover focus:ring-2 focus:ring-primary/40 focus:ring-offset-2 focus:outline-none">
                                        Detail
                                    </a>
                                    <button type="button" data-modal-target="hapus-order" data-modal-toggle="hapus-order"
                                        data-action="{{ route('order.destroy', $idHex) }}"
                                        data-name="{{ $idHex }}"
                                        aria-label="Hapus order {{ $idHex }}"
                                        class="inline-flex size-9 items-center justify-center rounded-lg bg-danger text-white transition-colors hover:bg-danger-strong focus:ring-2 focus:ring-danger/40 focus:ring-offset-2 focus:outline-none">
                                        <x-lucide-trash-2 class="size-4" aria-hidden="true" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="px-4 py-12">
                                <div class="flex flex-col items-center justify-center gap-2 text-center">
                                    <x-lucide-search-x class="size-8 text-ink-muted" aria-hidden="true" />
                                    <p class="text-sm font-medium text-ink">Order tidak ditemukan</p>
                                    <p class="text-sm text-ink-muted">Coba kata kunci lain.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if ($orders->hasPages())
            <div class="flex flex-col items-center justify-between gap-3 border-t border-line px-4 py-3 sm:flex-row">
                <p class="text-sm text-ink-muted">
                    Menampilkan {{ $orders->firstItem() }}–{{ $orders->lastItem() }} dari {{ $orders->total() }} order
                </p>
                <div>{{ $orders->links() }}</div>
            </div>
        @endif
    </div>

    {{-- Modal scan QR order --}}
    <div id="modal-scan-qr" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/60 p-4"
        role="dialog" aria-modal="true" aria-labelledby="judul-scan-qr">
        <div class="w-full max-w-md rounded-xl border border-line bg-surface p-5 shadow-lg">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h3 id="judul-scan-qr" class="text-lg font-semibold text-ink">Scan QR Order</h3>
                    <p class="mt-0.5 text-sm text-ink-muted">Arahkan kamera ke QR pada struk pelanggan.</p>
                </div>
                <button type="button" data-tutup-scan aria-label="Tutup scan QR"
                    class="inline-flex size-8 shrink-0 items-center justify-center rounded-lg text-ink-muted transition-colors hover:bg-muted hover:text-ink focus:ring-2 focus:ring-primary/30 focus:outline-none">
                    <x-lucide-x class="size-4" aria-hidden="true" />
                </button>
            </div>

            <div id="qr-reader" class="mt-4 min-h-56 w-full overflow-hidden rounded-lg border border-line bg-muted/40"></div>

            <p id="scan-status" class="mt-3 text-sm text-ink-muted" role="status" aria-live="polite"></p>

            <div class="mt-4 flex justify-end">
                <button type="button" data-tutup-scan
                    class="inline-flex items-center justify-center rounded-lg border border-line bg-surface px-4 py-2.5 text-sm font-medium text-ink transition-colors hover:bg-muted focus:ring-2 focus:ring-primary/30 focus:outline-none">
                    Tutup
                </button>
            </div>
        </div>
    </div>

    {{-- Modal konfirmasi hapus order --}}
    <div id="hapus-order" data-modal-backdrop="static" tabindex="-1" aria-hidden="true" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
        <div class="relative p-4 w-full max-w-md max-h-full">
            <div class="relative bg-white rounded-lg shadow-sm">
                <div class="flex items-center justify-between p-4 md:p-5 border-b rounded-t border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Hapus Order</h3>
                    <button type="button" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center" data-modal-hide="hapus-order">
                        <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6" />
                        </svg>
                        <span class="sr-only">Close modal</span>
                    </button>
                </div>
                <form id="form-hapus-order" method="POST" action="">
                    @csrf
                    @method('DELETE')
                    <div class="p-4 md:p-5 text-center">
                        <div class="mx-auto mb-4 flex size-12 items-center justify-center rounded-full bg-danger-soft text-fg-danger">
                            <x-lucide-trash-2 class="size-6" aria-hidden="true" />
                        </div>
                        <h3 class="mb-2 text-base font-semibold text-ink">Hapus order ini?</h3>
                        <p class="mb-1 text-sm text-ink-muted">Order dan semua file terkait akan dihapus permanen:</p>
                        <p id="hapus-order-name" class="mb-6 break-all text-sm font-medium text-ink"></p>
                        <div class="flex items-center justify-center gap-3">
                            <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-danger px-4 py-2.5 text-sm font-medium text-white transition-colors hover:bg-danger-strong focus:ring-4 focus:ring-danger/30 focus:outline-none">
                                Ya, hapus
                            </button>
                            <button type="button" data-modal-hide="hapus-order" class="inline-flex items-center justify-center rounded-lg border border-line bg-surface px-4 py-2.5 text-sm font-medium text-ink transition-colors hover:bg-muted focus:ring-2 focus:ring-primary/30 focus:outline-none">
                                Batal
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const formHapus = document.getElementById('form-hapus-order');
    const namaHapus = document.getElementById('hapus-order-name');

    // Isi modal konfirmasi hapus sesuai order yang tombolnya diklik.
    document.querySelectorAll('[data-modal-target="hapus-order"]').forEach(function (tombol) {
        tombol.addEventListener('click', function () {
            if (formHapus) {
                formHapus.action = tombol.dataset.action || '';
            }
            if (namaHapus) {
                namaHapus.textContent = tombol.dataset.name || '';
            }
        });
    });

    /* ---------- Scan QR order ---------- */

    const modalScan = document.getElementById('modal-scan-qr');
    const areaScan = document.getElementById('qr-reader');
    const statusScan = document.getElementById('scan-status');
    const tombolScan = document.getElementById('btn-scan-qr');
    const tombolTutupScan = document.querySelectorAll('[data-tutup-scan]');
    const urlValidasi = "{{ route('order.validasiqr') }}";
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

    let pemindai = null;
    let sedangValidasi = false;

    const aturStatusScan = function (pesan, jenis) {
        if (!statusScan) return;

        statusScan.textContent = pesan;
        statusScan.className = 'mt-3 text-sm ' + (
            jenis === 'error' ? 'text-fg-danger' :
            jenis === 'sukses' ? 'text-fg-success' :
            'text-ink-muted'
        );
    };

    const hentikanPemindai = async function () {
        if (!pemindai) return;

        try { await pemindai.stop(); } catch (error) {}
        try { pemindai.clear(); } catch (error) {}

        pemindai = null;
    };

    const validasiHasilScan = async function (kode) {
        if (sedangValidasi) return;
        sedangValidasi = true;
        aturStatusScan('Memeriksa QR...');

        try {
            const respons = await fetch(urlValidasi, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({ kode: kode }),
            });

            const data = await respons.json().catch(function () { return {}; });

            if (data.valid && data.url) {
                aturStatusScan(data.message || 'Order ditemukan. Membuka detail...', 'sukses');
                await hentikanPemindai();
                window.location.href = data.url;
                return;
            }

            await hentikanPemindai();
            aturStatusScan(data.message || 'Order tidak ditemukan.', 'error');

            // Beri kesempatan kasir memindai QR lain.
            window.setTimeout(function () {
                if (modalScan && !modalScan.classList.contains('hidden')) {
                    void mulaiPemindai();
                }
            }, 1800);
        } catch (error) {
            aturStatusScan('Gagal memeriksa QR. Coba lagi.', 'error');
        } finally {
            sedangValidasi = false;
        }
    };

    const mulaiPemindai = async function () {
        if (!areaScan) return;
        // Jangan nyalakan kamera kalau modal sudah ditutup saat penjadwalan.
        if (!modalScan || modalScan.classList.contains('hidden')) return;

        if (typeof Html5Qrcode === 'undefined') {
            aturStatusScan('Pemindai QR gagal dimuat. Periksa koneksi internet lalu muat ulang halaman.', 'error');
            return;
        }

        await hentikanPemindai();
        aturStatusScan('Mengaktifkan kamera...');

        const pemindaiBaru = new Html5Qrcode('qr-reader');
        pemindai = pemindaiBaru;

        try {
            await pemindaiBaru.start(
                { facingMode: 'environment' },
                { fps: 10, qrbox: { width: 220, height: 220 } },
                function (teks) { void validasiHasilScan(teks); },
                function () {}
            );

            // Modal sudah ditutup selagi kamera menyala: matikan lagi.
            if (modalScan.classList.contains('hidden')) {
                await hentikanPemindai();
                return;
            }

            if (pemindai === pemindaiBaru) {
                aturStatusScan('Arahkan kamera ke QR pada struk pelanggan.');
            }
        } catch (error) {
            if (pemindai === pemindaiBaru) {
                pemindai = null;
            }

            aturStatusScan(
                'Kamera tidak bisa dibuka. Pastikan izin kamera aktif dan halaman diakses lewat localhost/HTTPS.',
                'error'
            );
        }
    };

    const bukaModalScan = function () {
        if (!modalScan) return;

        modalScan.classList.remove('hidden');
        modalScan.classList.add('flex');
        document.body.classList.add('overflow-hidden');
        aturStatusScan('Mengaktifkan kamera...');

        // Tunggu modal benar-benar tampil sebelum kamera dijalankan.
        window.setTimeout(function () { void mulaiPemindai(); }, 150);
    };

    const tutupModalScan = async function () {
        if (!modalScan) return;

        await hentikanPemindai();
        modalScan.classList.add('hidden');
        modalScan.classList.remove('flex');
        document.body.classList.remove('overflow-hidden');
        aturStatusScan('');
    };

    if (tombolScan) {
        tombolScan.addEventListener('click', bukaModalScan);
    }

    tombolTutupScan.forEach(function (tombol) {
        tombol.addEventListener('click', function () { void tutupModalScan(); });
    });

    if (modalScan) {
        modalScan.addEventListener('click', function (event) {
            if (event.target === modalScan) {
                void tutupModalScan();
            }
        });
    }

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && modalScan && !modalScan.classList.contains('hidden')) {
            void tutupModalScan();
        }
    });
});
</script>

@endsection

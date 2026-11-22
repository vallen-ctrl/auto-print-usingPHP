@extends('Dashboard.layout')

@section('title', 'Detail order & file')

@php
    $rupiah = fn ($v) => 'Rp. ' . number_format((int) $v, 0, ',', '.');

    // Nomor WhatsApp pelanggan (kalau ada) dinormalkan ke format internasional
    // supaya link wa.me bisa langsung dibuka.
    $nomorWa = $pelanggan && filled($pelanggan->no_wa)
        ? preg_replace('/\D+/', '', (string) $pelanggan->no_wa)
        : '';

    if ($nomorWa !== '') {
        if (str_starts_with($nomorWa, '0')) {
            $nomorWa = '62' . substr($nomorWa, 1);
        } elseif (str_starts_with($nomorWa, '8')) {
            $nomorWa = '62' . $nomorWa;
        } elseif (! str_starts_with($nomorWa, '62')) {
            $nomorWa = '62' . $nomorWa;
        }
    }

    $pesanWa = 'Halo ' . ($pelanggan?->name ?: 'pelanggan')
        . ', ini dari toko cetak. Kami ingin mengonfirmasi pesanan Anda (order '
        . substr($id, 0, 8) . ').';

    $tautanWa = $nomorWa !== ''
        ? 'https://wa.me/' . $nomorWa . '?text=' . rawurlencode($pesanWa)
        : null;

    // Warna badge status pembayaran, sama dengan yang dipakai di halaman Order.
    $badgeBayar = [
        'pending'    => 'bg-warning-soft text-warning-strong',
        'processing' => 'bg-blue-100 text-blue-800',
        'paid'       => 'bg-success-soft text-success-strong',
        'failed'     => 'bg-danger-soft text-danger-strong',
        'fail'       => 'bg-danger-soft text-danger-strong',
        'refunded'   => 'bg-muted text-ink-muted',
    ];

    $statusBayar = $order->status_pembayaran ?: '-';
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
    <div class="flex items-start justify-between gap-4">
        <h1 class="text-2xl font-semibold text-ink">Detail order &amp; file</h1>
        <p class="text-sm text-ink-muted">{{ now()->translatedFormat('l, j F Y') }}</p>
    </div>

    {{-- Meta order + unduh semua --}}
    <div class="mt-4 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <dl class="space-y-1 text-sm">
            <div class="flex flex-wrap items-baseline gap-2">
                <dt class="text-ink-muted">order id</dt>
                <dd class="font-medium break-all text-ink">{{ $id }}</dd>
            </div>
            <div class="flex flex-wrap items-baseline gap-2">
                <dt class="text-ink-muted">Pelanggan</dt>
                <dd class="font-medium text-ink">
                    <span>{{ $pelanggan?->name ?: '-' }}</span>
                    @if ($tautanWa)
                        <a href="{{ $tautanWa }}" target="_blank" rel="noopener"
                            class="ml-1 inline-flex items-center gap-1 text-sm font-medium text-primary hover:text-primary-hover focus:outline-none focus-visible:underline">
                            <x-lucide-message-circle class="size-4" aria-hidden="true" />
                            WhatsApp
                        </a>
                    @endif
                </dd>
            </div>
            <div class="flex flex-wrap items-baseline gap-2">
                <dt class="text-ink-muted">Waktu order</dt>
                <dd class="font-medium text-ink">{{ $order->tanggal_pesan ? $order->tanggal_pesan->format('d/m/Y H:i') : '-' }}</dd>
            </div>
            <div class="flex flex-wrap items-baseline gap-2">
                <dt class="text-ink-muted">Status pembayaran</dt>
                <dd>
                    <span class="inline-flex items-center rounded-full px-3 py-1 text-sm font-bold uppercase tracking-wide {{ $badgeBayar[$statusBayar] ?? 'bg-muted text-ink-muted' }}">
                        {{ $statusBayar }}
                    </span>
                </dd>
            </div>
            <div class="flex flex-wrap items-baseline gap-2">
                <dt class="text-ink-muted">Mode cetak</dt>
                <dd class="font-medium text-ink">
                    {{ $order->mode_cetak === 'tukang' ? 'Dicetak oleh tukang' : 'Otomatis' }}
                </dd>
            </div>
            <div class="flex flex-wrap items-baseline gap-2">
                <dt class="text-ink-muted">Total order</dt>
                <dd class="font-medium tabular-nums text-ink">{{ $rupiah($order->total_pembayaran) }}</dd>
            </div>
        </dl>

        <div class="flex flex-wrap items-center gap-2 self-start">
            <button type="button" id="btn-refresh-pembayaran"
                data-url="{{ route('order.refreshpembayaran', $id) }}"
                class="inline-flex h-10 items-center gap-2 rounded-lg bg-primary px-4 text-sm font-medium text-white transition-colors hover:bg-primary-hover focus:ring-2 focus:ring-primary/40 focus:outline-none disabled:cursor-not-allowed disabled:opacity-60">
                <x-lucide-refresh-cw class="size-4" aria-hidden="true" />
                <span data-label>Refresh pembayaran</span>
            </button>

            <a href="{{ route('order.downloadsemua', $id) }}"
                class="inline-flex h-10 items-center gap-2 rounded-lg border border-line bg-surface px-4 text-sm font-medium text-ink transition-colors hover:bg-muted focus:ring-2 focus:ring-primary/30 focus:outline-none">
                Unduh semua
                <x-lucide-download class="size-4" aria-hidden="true" />
            </a>
        </div>
    </div>

    @if ($files->isEmpty())
        <div class="mt-6 rounded-xl border border-dashed border-line bg-surface px-6 py-12 text-center">
            <x-lucide-file-x class="mx-auto size-8 text-ink-muted" aria-hidden="true" />
            <p class="mt-2 text-sm font-medium text-ink">Tidak ada file pada order ini.</p>
        </div>
    @else
        {{-- Pemilihan file (radio) --}}
        <div class="mt-6 flex gap-3 overflow-x-auto pb-2" role="radiogroup" aria-label="Pilih file">
            @foreach ($files as $item)
                <label class="group flex min-w-52 flex-1 cursor-pointer items-center gap-3 rounded-xl border border-line bg-surface px-4 py-3 transition-colors hover:bg-muted has-checked:border-primary has-checked:bg-primary-soft">
                    <input type="radio" name="file-pilih" value="{{ $item['id_hex'] }}" @checked($loop->first)
                        class="size-4 shrink-0 accent-primary focus:ring-2 focus:ring-primary/40">
                    <x-lucide-file-text class="size-4 shrink-0 text-ink-muted group-has-checked:text-primary" aria-hidden="true" />
                    <span class="truncate text-sm font-medium text-ink">{{ $item['file']->name }}</span>
                </label>
            @endforeach
        </div>

        {{-- Panel per file (berganti sesuai pilihan) --}}
        @foreach ($files as $item)
            @php
                $file = $item['file'];
                $config = $item['config'];

                $konfigurasi = [
                    'Orientasi'      => $config?->orientasi ?: '-',
                    'Jumlah Cetak'   => $config?->jumlah_cetak ?? '-',
                    'Ukuran kertas'  => $config?->ukuran ?: '-',
                    'Custom halaman' => $config?->custom_halaman ?: '-',
                    'Warna'          => $config?->warna ?: '-',
                    'Total pages'    => $file->total_pages ?? '-',
                    'Kualitas'       => $config?->kualitas ?: '-',
                    'Harga'          => $item['harga_per_halaman'] !== null
                        ? $rupiah($item['harga_per_halaman']) . ' / pages'
                        : '-',
                    'Sub total'      => $rupiah($file->subtotal),
                ];

                $subLayanan = $item['layanan']->sum('harga');
            @endphp
            <div id="panel-{{ $item['id_hex'] }}" data-panel class="{{ $loop->first ? '' : 'hidden' }}">

                {{-- Layanan + Konfigurasi --}}
                <div class="mt-6 grid grid-cols-1 gap-4 lg:grid-cols-3">

                    {{-- Layanan dipilih --}}
                    <div class="flex flex-col rounded-xl border border-line bg-surface p-5 shadow-sm">
                        <h2 class="text-lg font-semibold text-ink">Layanan dipilih</h2>

                        <div class="mt-4 space-y-3 text-sm">
                            @forelse ($item['layanan'] as $l)
                                <div class="flex items-center justify-between gap-4">
                                    <span class="text-ink-muted">{{ $l['nama'] }}</span>
                                    <span class="font-medium tabular-nums text-ink">{{ $rupiah($l['harga']) }}</span>
                                </div>
                            @empty
                                <p class="text-ink-muted">Tidak ada layanan tambahan.</p>
                            @endforelse
                        </div>

                        <div class="mt-auto flex items-center justify-between gap-4 border-t border-line pt-4 text-sm">
                            <span class="font-medium text-ink">Sub total</span>
                            <span class="font-semibold tabular-nums text-ink">{{ $rupiah($subLayanan) }}</span>
                        </div>
                    </div>

                    {{-- Konfigurasi --}}
                    <div class="rounded-xl border border-line bg-surface p-5 shadow-sm lg:col-span-2">
                        <h2 class="text-lg font-semibold text-ink">Konfigurasi</h2>

                        <dl class="mt-4 grid grid-cols-1 gap-x-8 gap-y-1 text-sm sm:grid-cols-2">
                            @foreach ($konfigurasi as $label => $value)
                                <div class="flex items-center justify-between gap-4 border-b border-line/60 py-2">
                                    <dt class="text-ink-muted">{{ $label }}</dt>
                                    <dd class="font-medium text-ink">{{ $value }}</dd>
                                </div>
                            @endforeach
                        </dl>

                        <div class="mt-5 border-t-2 border-line pt-4">
                            <h3 class="text-sm font-medium text-ink">catatan tambahan</h3>
                            <p class="mt-1 text-sm leading-relaxed text-ink-muted">{{ $config?->catatan_tambahan ?: '-' }}</p>
                        </div>
                    </div>
                </div>

                {{-- Waktu ambil + unduh file + total --}}
                <div class="mt-6 grid grid-cols-1 gap-4 lg:grid-cols-3">
                    <div class="lg:col-span-2">
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <h2 class="text-lg font-semibold text-ink">Waktu ambil</h2>

                                <div class="mt-3 inline-flex flex-col gap-3 rounded-xl border border-line bg-surface p-5 text-sm shadow-sm">
                                    <div class="flex items-center gap-3">
                                        <span class="text-ink-muted">Tanggal Ambil</span>
                                        <span class="font-semibold text-ink">{{ $order->tanggal_ambil ? $order->tanggal_ambil->format('d/m/Y') : '-' }}</span>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <span class="text-ink-muted">Jam</span>
                                        <span class="font-semibold text-ink">{{ $order->tanggal_ambil ? $order->tanggal_ambil->format('H:i') : '-' }}</span>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <h2 class="text-lg font-semibold text-ink">Metode pembayaran</h2>

                                <div class="mt-3 inline-flex flex-col gap-3 rounded-xl border border-line bg-surface p-5 text-sm shadow-sm">
                                    <div class="flex items-center gap-3">
                                        <span class="text-ink-muted">Metode</span>
                                        <span class="font-semibold text-ink">{{ $order->metode_pembayaran ?: '-' }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col items-stretch justify-end gap-3 lg:items-end">
                        <a href="{{ route('files.download', $item['id_hex']) }}"
                            class="inline-flex h-10 items-center justify-center rounded-lg border border-line bg-surface px-4 text-sm font-medium text-ink transition-colors hover:bg-muted focus:ring-2 focus:ring-primary/30 focus:outline-none">
                            Unduh file ini
                        </a>

                        <p class="text-right">
                            <span class="text-sm text-ink-muted">Total :</span>
                            <span class="ml-1 text-2xl font-semibold tabular-nums text-ink">{{ $rupiah($file->subtotal) }}</span>
                        </p>
                    </div>
                </div>
            </div>
        @endforeach
    @endif

    {{-- Aksi akhir --}}
    <div class="mt-8 grid grid-cols-1 gap-4 pb-4 sm:grid-cols-2">
        <a href="{{ route('order.index') }}"
            class="inline-flex h-11 items-center justify-center rounded-lg border border-line bg-surface text-sm font-medium text-ink transition-colors hover:bg-muted focus:ring-2 focus:ring-primary/30 focus:outline-none">
            Keluar
        </a>
        <form method="POST" action="{{ route('order.statuspembayaran', $id) }}" class="flex items-center gap-2">
            @csrf
            <label for="status-pembayaran" class="sr-only">Status pembayaran</label>
            <select id="status-pembayaran" name="status_pembayaran"
                class="h-11 min-w-0 flex-1 rounded-lg border border-line bg-surface px-3 text-sm font-medium text-ink focus:border-primary focus:ring-2 focus:ring-primary/30 focus:outline-none">
                @foreach (['pending' => 'Pending', 'processing' => 'Processing', 'paid' => 'Paid', 'failed' => 'Failed', 'refunded' => 'Refunded'] as $nilai => $label)
                    <option value="{{ $nilai }}" @selected($statusBayar === $nilai)>{{ $label }}</option>
                @endforeach
            </select>
            <button type="submit"
                class="inline-flex h-11 shrink-0 items-center justify-center rounded-lg bg-primary px-4 text-sm font-medium text-white transition-colors hover:bg-primary-hover focus:ring-2 focus:ring-primary/40 focus:ring-offset-2 focus:outline-none">
                Simpan status
            </button>
        </form>
    </div>

    {{-- Modal hasil "Refresh pembayaran" --}}
    <div id="modal-refresh-pembayaran" tabindex="-1" aria-hidden="true" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
        <div class="relative p-4 w-full max-w-md max-h-full">
            <div class="relative bg-white rounded-lg shadow-sm">
                <div class="flex items-center justify-between p-4 md:p-5 border-b rounded-t border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Hasil cek pembayaran</h3>
                    <button type="button" data-modal-hide="modal-refresh-pembayaran"
                        class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center">
                        <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6" />
                        </svg>
                        <span class="sr-only">Tutup</span>
                    </button>
                </div>
                <div class="p-4 md:p-5 text-center">
                    <p id="pesan-refresh-pembayaran" class="mb-6 text-sm leading-relaxed text-ink"></p>
                    <button type="button" data-modal-hide="modal-refresh-pembayaran"
                        class="inline-flex items-center justify-center rounded-lg bg-primary px-4 py-2.5 text-sm font-medium text-white transition-colors hover:bg-primary-hover focus:ring-4 focus:ring-primary/30 focus:outline-none">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Pemicu tersembunyi: dipakai JavaScript untuk membuka modal lewat Flowbite. --}}
    <button type="button" id="pemicu-modal-refresh" class="hidden" tabindex="-1" aria-hidden="true"
        data-modal-target="modal-refresh-pembayaran" data-modal-toggle="modal-refresh-pembayaran"></button>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const radios = document.querySelectorAll('input[name="file-pilih"]');
    const panels = document.querySelectorAll('[data-panel]');

    radios.forEach(function (radio) {
        radio.addEventListener('change', function () {
            panels.forEach(function (panel) {
                panel.classList.toggle('hidden', panel.id !== 'panel-' + radio.value);
            });
        });
    });

    /* ---------- Refresh pembayaran ke Midtrans ---------- */

    const tombolRefresh = document.getElementById('btn-refresh-pembayaran');
    const pemicuModal = document.getElementById('pemicu-modal-refresh');
    const pesanModal = document.getElementById('pesan-refresh-pembayaran');
    const KUNCI_PESAN = 'refresh-pembayaran-pesan';

    const bukaPopup = (pesan) => {
        if (!pemicuModal || !pesanModal) {
            return;
        }

        pesanModal.textContent = pesan;
        pemicuModal.click();
    };

    try {
        const pesanTertunda = window.sessionStorage.getItem(KUNCI_PESAN);

        if (pesanTertunda) {
            window.sessionStorage.removeItem(KUNCI_PESAN);
            bukaPopup(pesanTertunda);
        }
    } catch (error) {
        // sessionStorage bisa diblokir browser; popup tetap muncul tanpa reload.
    }

    tombolRefresh?.addEventListener('click', async function () {
        const label = tombolRefresh.querySelector('[data-label]');
        const labelAwal = label ? label.textContent : '';
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

        tombolRefresh.disabled = true;

        if (label) {
            label.textContent = 'Memeriksa...';
        }

        try {
            const respons = await fetch(tombolRefresh.dataset.url, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrf,
                    'Accept': 'application/json',
                },
            });

            const data = await respons.json().catch(() => ({}));

            if (!respons.ok) {
                throw new Error(data.pesan || data.message || 'Gagal memeriksa pembayaran.');
            }

            // Ada perubahan status: muat ulang halaman supaya tampilan ikut
            // diperbarui, lalu modal pesan muncul setelah halaman dimuat.
            if (data.berubah) {
                try {
                    window.sessionStorage.setItem(KUNCI_PESAN, data.pesan);
                } catch (error) {
                    // abaikan; popup tetap dicoba setelah reload
                }

                window.location.reload();
                return;
            }

            bukaPopup(data.pesan || 'Tidak ada perubahan status pembayaran.');
        } catch (error) {
            bukaPopup(error.message || 'Gagal menghubungi server. Coba lagi.');
        } finally {
            tombolRefresh.disabled = false;

            if (label) {
                label.textContent = labelAwal;
            }
        }
    });
});
</script>
@endsection

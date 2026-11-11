@extends('Dashboard.layout')

@section('title', 'Kasir')

@php
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
    @if (session('warning'))
        <x-toast type="warning" :message="session('warning')" />
    @endif

    {{-- Header --}}
    <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-ink">Kasir</h1>
            <p class="mt-1 text-sm text-ink-muted">
                Konfirmasi pembayaran tunai untuk pesanan dengan metode “Bayar di Kasir”.
            </p>
        </div>
        <p class="text-sm text-ink-muted">{{ now()->translatedFormat('l, j F Y') }}</p>
    </div>

    {{-- Ringkasan antrean --}}
    <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div class="flex min-h-24 flex-col justify-between rounded-xl border border-line bg-surface p-5 shadow-sm">
            <h2 class="text-sm font-medium text-ink-muted">Menunggu konfirmasi</h2>
            <p class="mt-3 flex items-baseline gap-2">
                <span class="text-2xl font-semibold tabular-nums text-ink">{{ $jumlahMenunggu }}</span>
                <span class="text-sm text-ink-muted">pesanan</span>
            </p>
        </div>

        <div class="flex min-h-24 flex-col justify-between rounded-xl border border-line bg-surface p-5 shadow-sm">
            <h2 class="text-sm font-medium text-ink-muted">Nilai transaksi menunggu</h2>
            <p class="mt-3 text-2xl font-semibold tabular-nums text-ink">
                {{ $rupiah($totalMenunggu) }}
            </p>
        </div>
    </div>

    {{-- Pencarian nama / kode order --}}
    <form method="GET" action="{{ route('kasir.index') }}" class="mt-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-end">
        <div class="relative w-full sm:w-96">
            <label for="kasir-search" class="sr-only">Cari pesanan</label>
            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                <x-lucide-search class="size-4 text-ink-muted" aria-hidden="true" />
            </div>
            <input type="search" id="kasir-search" name="search" value="{{ $search }}"
                placeholder="Cari nama pelanggan atau kode order (mis. A7K2QX)"
                class="block h-10 w-full rounded-lg border border-line bg-surface pl-10 pr-3 text-sm text-ink placeholder:text-ink-muted focus:border-primary focus:ring-2 focus:ring-primary/30 focus:outline-none">
        </div>

        <div class="flex items-center gap-2">
            <button type="submit"
                class="inline-flex h-10 items-center justify-center gap-2 rounded-lg bg-primary px-4 text-sm font-medium text-white transition-colors hover:bg-primary-hover focus:ring-2 focus:ring-primary/40 focus:outline-none">
                <x-lucide-search class="size-4" aria-hidden="true" />
                Cari
            </button>
            @if ($search !== '')
                <a href="{{ route('kasir.index') }}"
                    class="inline-flex h-10 items-center justify-center rounded-lg border border-line bg-surface px-4 text-sm font-medium text-ink transition-colors hover:bg-muted focus:ring-2 focus:ring-primary/30 focus:outline-none">
                    Reset
                </a>
            @endif
        </div>
    </form>

    @if ($search !== '')
        <p class="mt-3 text-sm text-ink-muted">
            Hasil pencarian untuk <span class="font-medium text-ink">“{{ $search }}”</span>:
            {{ $orders->total() }} pesanan menunggu.
        </p>
    @endif

    {{-- Antrean kasir --}}
    @if ($orders->isEmpty())
        <div class="mt-4 flex flex-col items-center justify-center gap-3 rounded-xl border border-line bg-surface px-6 py-16 text-center shadow-sm">
            <span class="flex size-12 items-center justify-center rounded-full bg-primary-soft text-primary">
                <x-lucide-circle-check class="size-6" aria-hidden="true" />
            </span>
            <div>
                <p class="text-base font-semibold text-ink">Tidak ada pesanan yang menunggu</p>
                <p class="mt-1 text-sm text-ink-muted">
                    @if ($search !== '')
                        Coba cari dengan nama atau kode order lain.
                    @else
                        Pesanan yang memilih “Bayar di Kasir” akan muncul di sini.
                    @endif
                </p>
            </div>
            @if ($search !== '')
                <a href="{{ route('kasir.index') }}"
                    class="mt-2 inline-flex h-10 items-center justify-center rounded-lg border border-line bg-surface px-4 text-sm font-medium text-ink transition-colors hover:bg-muted focus:ring-2 focus:ring-primary/30 focus:outline-none">
                    Tampilkan semua
                </a>
            @endif
        </div>
    @else
        <div class="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-2 xl:grid-cols-3">
            @foreach ($orders as $o)
                <article class="flex flex-col rounded-xl border border-line bg-surface p-5 shadow-sm">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-xs font-medium uppercase tracking-wide text-ink-muted">Kode order</p>
                            <p class="mt-1 text-2xl font-semibold text-primary">
                                {{ $o['kode'] }}
                            </p>
                        </div>

                        <div class="flex flex-wrap justify-end gap-1">
                            @foreach ($o['layanan'] as $layanan)
                                <span class="rounded-full bg-primary-soft px-2.5 py-0.5 text-xs font-medium text-primary">
                                    {{ $layanan }}
                                </span>
                            @endforeach
                        </div>
                    </div>

                    <div class="mt-4 flex items-center gap-2 text-sm">
                        <x-lucide-user-round class="size-4 shrink-0 text-ink-muted" aria-hidden="true" />
                        <span class="truncate font-medium text-ink" title="{{ $o['nama'] }}">{{ $o['nama'] }}</span>
                    </div>

                    @if ($o['no_wa'])
                        <div class="mt-1 flex items-center gap-2 text-sm text-ink-muted">
                            <x-lucide-phone class="size-4 shrink-0" aria-hidden="true" />
                            <span>{{ $o['no_wa'] }}</span>
                        </div>
                    @endif

                    <dl class="mt-4 grid grid-cols-2 gap-x-4 gap-y-3 text-sm">
                        <div>
                            <dt class="flex items-center gap-1.5 text-xs text-ink-muted">
                                <x-lucide-files class="size-3.5" aria-hidden="true" />
                                Jumlah file
                            </dt>
                            <dd class="mt-0.5 font-medium text-ink">{{ $o['jumlah_file'] }} file</dd>
                        </div>
                        <div>
                            <dt class="flex items-center gap-1.5 text-xs text-ink-muted">
                                <x-lucide-receipt-text class="size-3.5" aria-hidden="true" />
                                Total
                            </dt>
                            <dd class="mt-0.5 font-medium tabular-nums text-ink">
                                @if ($o['total'] > 0)
                                    {{ $rupiah($o['total']) }}
                                @else
                                    Belum dihitung
                                @endif
                            </dd>
                        </div>
                        <div>
                            <dt class="flex items-center gap-1.5 text-xs text-ink-muted">
                                <x-lucide-calendar-days class="size-3.5" aria-hidden="true" />
                                Dipesan
                            </dt>
                            <dd class="mt-0.5 text-ink">{{ $o['pesan'] }}</dd>
                        </div>
                        <div>
                            <dt class="flex items-center gap-1.5 text-xs text-ink-muted">
                                <x-lucide-clock class="size-3.5" aria-hidden="true" />
                                Bayar dipilih
                            </dt>
                            <dd class="mt-0.5 text-ink">{{ $o['menunggu'] }}</dd>
                        </div>
                    </dl>

                    <div class="mt-5 flex flex-col gap-2 border-t border-line pt-4 sm:flex-row">
                        <button type="button"
                            data-modal-target="modal-lunas" data-modal-toggle="modal-lunas"
                            data-action="{{ route('kasir.lunas', $o['id']) }}"
                            data-kode="{{ $o['kode'] }}"
                            data-total="{{ $o['total'] }}"
                            class="inline-flex h-10 flex-1 items-center justify-center gap-2 rounded-lg bg-primary px-4 text-sm font-medium text-white transition-colors hover:bg-primary-hover focus:ring-2 focus:ring-primary/40 focus:ring-offset-2 focus:outline-none">
                            <x-lucide-banknote-check class="size-4" aria-hidden="true" />
                            Pembayaran berhasil
                        </button>

                        <button type="button"
                            data-modal-target="modal-batal" data-modal-toggle="modal-batal"
                            data-action="{{ route('kasir.batal', $o['id']) }}"
                            data-kode="{{ $o['kode'] }}"
                            class="inline-flex h-10 flex-1 items-center justify-center gap-2 rounded-lg border border-danger bg-surface px-4 text-sm font-medium text-danger transition-colors hover:bg-danger hover:text-white focus:ring-2 focus:ring-danger/40 focus:ring-offset-2 focus:outline-none">
                            <x-lucide-banknote-x class="size-4" aria-hidden="true" />
                            Batalkan
                        </button>
                    </div>
                </article>
            @endforeach
        </div>

        {{-- Pagination --}}
        @if ($orders->hasPages())
            <div class="mt-6 flex flex-col items-center justify-between gap-3 sm:flex-row">
                <p class="text-sm text-ink-muted">
                    Menampilkan {{ $orders->firstItem() }}–{{ $orders->lastItem() }} dari {{ $orders->total() }} pesanan
                </p>
                <div>{{ $orders->links() }}</div>
            </div>
        @endif
    @endif

    {{-- Modal: pembayaran berhasil --}}
    <div id="modal-lunas" tabindex="-1" aria-hidden="true"
        class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
        <div class="relative max-h-full w-full max-w-md p-4">
            <div class="relative rounded-xl border border-line bg-surface p-5 shadow-sm md:p-6">
                <div class="flex items-start justify-between gap-3">
                    <h2 class="text-lg font-semibold text-ink">Tandai pembayaran lunas</h2>
                    <button type="button" data-modal-hide="modal-lunas" aria-label="Tutup"
                        class="inline-flex size-8 items-center justify-center rounded-lg text-ink-muted hover:bg-muted hover:text-ink focus:ring-2 focus:ring-primary/30 focus:outline-none">
                        <x-lucide-circle-x class="size-5" aria-hidden="true" />
                    </button>
                </div>

                <p class="mt-2 text-sm text-ink-muted">
                    Order <span id="lunas-kode" class="font-semibold text-ink">-</span>
                    akan dicatat sebagai lunas dan filenya masuk antrean cetak.
                </p>

                <form id="form-lunas" method="POST" action="" class="mt-4">
                    @csrf

                    <label for="lunas-total" class="block text-sm font-medium text-ink">Total dibayar (Rp)</label>
                    <input type="number" id="lunas-total" name="total_dibayar" min="0" step="500" required
                        inputmode="numeric" placeholder="Contoh: 55000"
                        class="mt-1 block h-10 w-full rounded-lg border border-line bg-surface px-3 text-sm text-ink placeholder:text-ink-muted focus:border-primary focus:ring-2 focus:ring-primary/30 focus:outline-none">
                    <p class="mt-1 text-xs text-ink-muted">
                        Untuk cetak kilat edit, isi total setelah file selesai diedit. Pesanan lain sudah terisi otomatis.
                    </p>

                    <div class="mt-5 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                        <button type="button" data-modal-hide="modal-lunas"
                            class="inline-flex h-10 items-center justify-center rounded-lg border border-line bg-surface px-4 text-sm font-medium text-ink transition-colors hover:bg-muted focus:ring-2 focus:ring-primary/30 focus:outline-none">
                            Batal
                        </button>
                        <button type="submit"
                            class="inline-flex h-10 items-center justify-center gap-2 rounded-lg bg-primary px-4 text-sm font-medium text-white transition-colors hover:bg-primary-hover focus:ring-2 focus:ring-primary/40 focus:ring-offset-2 focus:outline-none">
                            <x-lucide-banknote-check class="size-4" aria-hidden="true" />
                            Ya, tandai lunas
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Modal: batalkan pembayaran --}}
    <div id="modal-batal" data-modal-backdrop="static" tabindex="-1" aria-hidden="true"
        class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
        <div class="relative max-h-full w-full max-w-md p-4">
            <div class="relative rounded-xl border border-line bg-surface p-5 shadow-sm md:p-6">
                <div class="flex items-start justify-between gap-3">
                    <h2 class="text-lg font-semibold text-ink">Batalkan pembayaran?</h2>
                    <button type="button" data-modal-hide="modal-batal" aria-label="Tutup"
                        class="inline-flex size-8 items-center justify-center rounded-lg text-ink-muted hover:bg-muted hover:text-ink focus:ring-2 focus:ring-primary/30 focus:outline-none">
                        <x-lucide-circle-x class="size-5" aria-hidden="true" />
                    </button>
                </div>

                <div class="mt-3 flex items-start gap-3 rounded-lg bg-danger-soft p-3 text-sm text-danger-strong">
                    <x-lucide-banknote-x class="mt-0.5 size-5 shrink-0" aria-hidden="true" />
                    <p>
                        Order <span id="batal-kode" class="font-semibold">-</span>
                        akan ditandai gagal dan file pesanannya dihapus. Customer harus upload ulang dari awal.
                    </p>
                </div>

                <form id="form-batal" method="POST" action="" class="mt-5">
                    @csrf

                    <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                        <button type="button" data-modal-hide="modal-batal"
                            class="inline-flex h-10 items-center justify-center rounded-lg border border-line bg-surface px-4 text-sm font-medium text-ink transition-colors hover:bg-muted focus:ring-2 focus:ring-primary/30 focus:outline-none">
                            Kembali
                        </button>
                        <button type="submit"
                            class="inline-flex h-10 items-center justify-center gap-2 rounded-lg bg-danger px-4 text-sm font-medium text-white transition-colors hover:bg-danger-strong focus:ring-2 focus:ring-danger/40 focus:ring-offset-2 focus:outline-none">
                            <x-lucide-banknote-x class="size-4" aria-hidden="true" />
                            Ya, batalkan pembayaran
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const formLunas = document.getElementById('form-lunas');
    const kodeLunas = document.getElementById('lunas-kode');
    const totalLunas = document.getElementById('lunas-total');

    // Modal lunas: isi action, kode order, dan total sesuai kartu yang diklik.
    document.querySelectorAll('[data-modal-target="modal-lunas"]').forEach(function (tombol) {
        tombol.addEventListener('click', function () {
            if (formLunas) {
                formLunas.action = tombol.dataset.action || '';
            }
            if (kodeLunas) {
                kodeLunas.textContent = tombol.dataset.kode || '-';
            }
            if (totalLunas) {
                const total = Number(tombol.dataset.total || 0);
                totalLunas.value = total > 0 ? String(total) : '';
            }
        });
    });

    const formBatal = document.getElementById('form-batal');
    const kodeBatal = document.getElementById('batal-kode');

    // Modal batal: isi action dan kode order sesuai kartu yang diklik.
    document.querySelectorAll('[data-modal-target="modal-batal"]').forEach(function (tombol) {
        tombol.addEventListener('click', function () {
            if (formBatal) {
                formBatal.action = tombol.dataset.action || '';
            }
            if (kodeBatal) {
                kodeBatal.textContent = tombol.dataset.kode || '-';
            }
        });
    });
});
</script>
@endsection

@extends('Dashboard.layout')

@section('title', 'Order')

@php
    $badgeBayar = [
        'pending'    => 'bg-warning-soft text-warning-strong',
        'processing' => 'bg-blue-100 text-blue-800',
        'paid'    => 'bg-success-soft text-success-strong',
        'fail'       => 'bg-danger-soft text-danger-strong',
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
                placeholder="Cari order id, status, metode..."
                class="block h-10 w-full rounded-lg border border-line bg-surface pl-10 pr-3 text-sm text-ink placeholder:text-ink-muted focus:border-primary focus:ring-2 focus:ring-primary/30 focus:outline-none">
        </div>

        <div class="flex items-center gap-2">
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
                        <th scope="col" class="whitespace-nowrap px-4 py-3 font-medium">Tanggal pesan</th>
                        <th scope="col" class="whitespace-nowrap px-4 py-3 font-medium">Waktu ambil</th>
                        <th scope="col" class="whitespace-nowrap px-4 py-3 text-right font-medium">Total</th>
                        <th scope="col" class="whitespace-nowrap px-4 py-3 font-medium">Status pembayaran</th>
                        <th scope="col" class="whitespace-nowrap px-4 py-3 font-medium">Metode pembayaran</th>
                        <th scope="col" class="whitespace-nowrap px-4 py-3 font-medium">Status file</th>
                        <th scope="col" class="whitespace-nowrap px-4 py-3 font-medium">Tipe cetak</th>
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
                            $layananList = $o->layanan ? explode(',', $o->layanan) : [];
                            $cetak = $layananList
                                ? implode(', ', array_map(fn ($v) => $labelLayanan[$v] ?? $v, $layananList))
                                : '-';
                        @endphp
                        <tr data-row class="transition-colors hover:bg-muted/50">
                            <td class="whitespace-nowrap px-4 py-3 font-medium">
                                <span class="block max-w-[9rem] truncate" title="{{ $idHex }}">{{ $idHex }}</span>
                            </td>
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
                                <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium capitalize {{ $badgeFile[$statusFilePertama] ?? 'bg-muted text-ink-muted' }}">
                                    {{ $statusFile }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-ink-muted">{{ $cetak }}</td>
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
                            <td colspan="9" class="px-4 py-12">
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
});
</script>

@endsection

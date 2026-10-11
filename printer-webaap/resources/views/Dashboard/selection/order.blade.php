@extends('Dashboard.layout')

@section('title', 'Order')

@php
    // Data contoh (statis). Integrasi API/database menyusul setelah izin Vallen.
    $orders = [
        ['id' => 'ORD-2024-0001', 'pesan' => '19-12-2006', 'ambil_jam' => '09:30', 'ambil_tgl' => '20-12-2006', 'total' => 20000, 'bayar' => 'pending', 'metode' => 'Kasir',        'file' => 'pending',     'cetak' => 'Kilat langsung'],
        ['id' => 'ORD-2024-0002', 'pesan' => '19-12-2006', 'ambil_jam' => '09:35', 'ambil_tgl' => '20-12-2006', 'total' => 20000, 'bayar' => 'pending', 'metode' => 'BCA',          'file' => 'processing',  'cetak' => 'Kilat edit'],
        ['id' => 'ORD-2024-0003', 'pesan' => '19-12-2006', 'ambil_jam' => '09:40', 'ambil_tgl' => '20-12-2006', 'total' => 20000, 'bayar' => 'pending', 'metode' => 'Qris',         'file' => 'complete',    'cetak' => 'Terjadwal'],
        ['id' => 'ORD-2024-0004', 'pesan' => '19-12-2006', 'ambil_jam' => '09:45', 'ambil_tgl' => '20-12-2006', 'total' => 20000, 'bayar' => 'pending', 'metode' => 'Qris',         'file' => 'success',     'cetak' => 'Kilat langsung'],
        ['id' => 'ORD-2024-0005', 'pesan' => '19-12-2006', 'ambil_jam' => '09:50', 'ambil_tgl' => '20-12-2006', 'total' => 20000, 'bayar' => 'pending', 'metode' => 'BNI transfer', 'file' => 'downloading', 'cetak' => 'Kilat edit'],
        ['id' => 'ORD-2024-0006', 'pesan' => '19-12-2006', 'ambil_jam' => '09:55', 'ambil_tgl' => '20-12-2006', 'total' => 20000, 'bayar' => 'pending', 'metode' => 'BRI',          'file' => 'failed',      'cetak' => 'Terjadwal'],
        ['id' => 'ORD-2024-0007', 'pesan' => '19-12-2006', 'ambil_jam' => '10:00', 'ambil_tgl' => '20-12-2006', 'total' => 20000, 'bayar' => 'pending', 'metode' => 'Qris',         'file' => 'success',     'cetak' => 'Kilat langsung'],
        ['id' => 'ORD-2024-0008', 'pesan' => '19-12-2006', 'ambil_jam' => '10:05', 'ambil_tgl' => '20-12-2006', 'total' => 20000, 'bayar' => 'pending', 'metode' => 'Qris',         'file' => 'success',     'cetak' => 'Kilat edit'],
        ['id' => 'ORD-2024-0009', 'pesan' => '19-12-2006', 'ambil_jam' => '10:10', 'ambil_tgl' => '20-12-2006', 'total' => 20000, 'bayar' => 'pending', 'metode' => 'Qris',         'file' => 'success',     'cetak' => 'Terjadwal'],
    ];

    $badge = [
        'pending'     => 'bg-warning-soft text-warning-strong',
        'processing'  => 'bg-blue-100 text-blue-800',
        'complete'    => 'bg-success-soft text-success-strong',
        'success'     => 'bg-success-soft text-success-strong',
        'downloading' => 'bg-indigo-100 text-indigo-800',
        'failed'      => 'bg-danger-soft text-danger-strong',
    ];

    $rupiah = fn ($v) => 'Rp ' . number_format($v, 0, ',', '.');
@endphp

@section('content')
<div class="w-full">

    {{-- Header --}}
    <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
        <h1 class="text-2xl font-semibold text-ink">Order</h1>
        <p class="text-sm text-ink-muted">{{ now()->translatedFormat('l, j F Y') }}</p>
    </div>

    {{-- Toolbar --}}
    <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-end">
        <div class="relative w-full sm:w-72">
            <label for="order-search" class="sr-only">Cari order</label>
            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                <x-lucide-search class="size-4 text-ink-muted" aria-hidden="true" />
            </div>
            <input type="search" id="order-search" placeholder="Cari..."
                class="block h-10 w-full rounded-lg border border-line bg-surface pl-10 pr-3 text-sm text-ink placeholder:text-ink-muted focus:border-primary focus:ring-2 focus:ring-primary/30 focus:outline-none">
        </div>
    </div>

    {{-- Table card --}}
    <div class="mt-4 overflow-hidden rounded-xl border border-line bg-surface shadow-sm">
        <div class="overflow-x-auto">
            <table id="tabel-order" class="w-full min-w-[62rem] text-left text-sm">
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
                    @foreach ($orders as $o)
                        <tr data-row class="transition-colors hover:bg-muted/50">
                            <td class="whitespace-nowrap px-4 py-3 font-medium">{{ $o['id'] }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-ink-muted">{{ $o['pesan'] }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-ink-muted">
                                {{ $o['ambil_jam'] }}
                                <span class="text-ink-muted/70">&middot;</span>
                                {{ $o['ambil_tgl'] }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right font-medium tabular-nums">{{ $rupiah($o['total']) }}</td>
                            <td class="whitespace-nowrap px-4 py-3">
                                <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium capitalize {{ $badge[$o['bayar']] ?? 'bg-muted text-ink-muted' }}">
                                    {{ $o['bayar'] }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3">{{ $o['metode'] }}</td>
                            <td class="whitespace-nowrap px-4 py-3">
                                <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium capitalize {{ $badge[$o['file']] ?? 'bg-muted text-ink-muted' }}">
                                    {{ $o['file'] }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-ink-muted">{{ $o['cetak'] }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right">
                                <button type="button"
                                    class="inline-flex h-9 items-center rounded-lg bg-primary px-4 text-sm font-medium text-white transition-colors hover:bg-primary-hover focus:ring-2 focus:ring-primary/40 focus:ring-offset-2 focus:outline-none">
                                    Detail
                                </button>
                            </td>
                        </tr>
                    @endforeach
                    <tr id="order-empty" class="hidden">
                        <td colspan="9" class="px-4 py-12">
                            <div class="flex flex-col items-center justify-center gap-2 text-center">
                                <x-lucide-search-x class="size-8 text-ink-muted" aria-hidden="true" />
                                <p class="text-sm font-medium text-ink">Order tidak ditemukan</p>
                                <p class="text-sm text-ink-muted">Coba kata kunci lain.</p>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="flex items-center justify-end border-t border-line px-4 py-3">
            <nav class="inline-flex items-center gap-1" aria-label="Navigasi halaman">
                <button type="button" aria-label="Halaman sebelumnya"
                    class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-line text-ink-muted hover:bg-muted disabled:cursor-not-allowed disabled:opacity-40" disabled>
                    <x-lucide-chevron-left class="size-4" aria-hidden="true" />
                </button>
                <button type="button" aria-current="page"
                    class="inline-flex h-9 min-w-9 items-center justify-center rounded-lg border border-primary bg-primary px-3 text-sm font-medium text-white">1</button>
                <button type="button"
                    class="inline-flex h-9 min-w-9 items-center justify-center rounded-lg border border-line px-3 text-sm font-medium text-ink hover:bg-muted">2</button>
                <span class="inline-flex h-9 min-w-9 items-center justify-center px-2 text-sm text-ink-muted">...</span>
                <button type="button"
                    class="inline-flex h-9 min-w-9 items-center justify-center rounded-lg border border-line px-3 text-sm font-medium text-ink hover:bg-muted">5</button>
                <button type="button" aria-label="Halaman berikutnya"
                    class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-line text-ink hover:bg-muted disabled:cursor-not-allowed disabled:opacity-40">
                    <x-lucide-chevron-right class="size-4" aria-hidden="true" />
                </button>
            </nav>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const input = document.getElementById('order-search');
    const tabel = document.getElementById('tabel-order');
    const kosong = document.getElementById('order-empty');
    if (!input || !tabel) return;

    const baris = Array.from(tabel.querySelectorAll('tbody tr[data-row]'));

    input.addEventListener('input', function () {
        const kata = this.value.trim().toLowerCase();
        let tampil = 0;

        baris.forEach((tr) => {
            const cocok = tr.textContent.toLowerCase().includes(kata);
            tr.classList.toggle('hidden', !cocok);
            if (cocok) tampil++;
        });

        kosong.classList.toggle('hidden', tampil !== 0);
    });
});
</script>
@endsection

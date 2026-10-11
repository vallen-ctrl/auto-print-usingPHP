@extends('Dashboard.layout')

@section('title', 'Layanan')

@php
    // Data contoh (statis). Integrasi API/database menyusul setelah izin Vallen.
    $layanan = [
        ['nama' => 'Laminating',    'harga' => 'Rp. 2000', 'status' => true,  'stok' => '600', 'dibeli' => '502 order'],
        ['nama' => 'Jilid Spiral',  'harga' => 'Rp. 5000', 'status' => true,  'stok' => '340', 'dibeli' => '281 order'],
        ['nama' => 'Print Warna',   'harga' => 'Rp. 1500', 'status' => true,  'stok' => '999', 'dibeli' => '812 order'],
        ['nama' => 'Fotokopi',      'harga' => 'Rp. 300',  'status' => false, 'stok' => '0',   'dibeli' => '1204 order'],
        ['nama' => 'Scan Dokumen',  'harga' => 'Rp. 2000', 'status' => true,  'stok' => '120', 'dibeli' => '97 order'],
    ];
@endphp

@section('content')
<div class="w-full">

    {{-- Header --}}
    <div class="flex items-start justify-between gap-4">
        <h1 class="text-2xl font-semibold text-ink">Layanan</h1>
        <p class="text-sm text-ink-muted">{{ now()->translatedFormat('l, j F Y') }}</p>
    </div>

    {{-- Toolbar --}}
    <div class="mt-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <button type="button"
            class="inline-flex h-10 shrink-0 items-center justify-center gap-2 self-start rounded-lg border border-line bg-surface px-4 text-sm font-medium text-ink transition-colors hover:bg-muted focus:ring-2 focus:ring-primary/30 focus:outline-none">
            <x-lucide-plus class="size-4" aria-hidden="true" />
            Tambah layanan
        </button>

        <div class="relative w-full sm:w-72">
            <label for="layanan-search" class="sr-only">Cari layanan</label>
            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                <x-lucide-search class="size-4 text-ink-muted" aria-hidden="true" />
            </div>
            <input type="search" id="layanan-search" placeholder="Cari..."
                class="block h-10 w-full rounded-lg border border-line bg-surface pl-10 pr-3 text-sm text-ink placeholder:text-ink-muted focus:border-primary focus:ring-2 focus:ring-primary/30 focus:outline-none">
        </div>
    </div>

    {{-- Daftar layanan --}}
    <div class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
        @foreach ($layanan as $l)
            <div data-card class="flex flex-col rounded-xl border border-line bg-surface p-4 shadow-sm">
                <div class="flex h-32 items-center justify-center rounded-lg border border-dashed border-line bg-muted/40 text-sm text-ink-muted">
                    gambar
                </div>

                <div class="my-3 border-t border-line"></div>

                <dl class="space-y-1.5 text-sm">
                    <div class="flex items-center justify-between gap-3">
                        <dt class="font-medium text-ink">{{ $l['nama'] }}</dt>
                        <dd class="font-medium tabular-nums text-ink">{{ $l['harga'] }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-ink-muted">Status</dt>
                        <dd>
                            <span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $l['status'] ? 'bg-success-soft text-success-strong' : 'bg-muted text-ink-muted' }}">
                                {{ $l['status'] ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-ink-muted">Stok</dt>
                        <dd class="font-medium tabular-nums text-ink">{{ $l['stok'] }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-ink-muted">Total dibeli</dt>
                        <dd class="font-medium text-ink">{{ $l['dibeli'] }}</dd>
                    </div>
                </dl>

                <div class="mt-auto pt-4">
                    <button type="button"
                        class="inline-flex h-10 w-full items-center justify-center rounded-lg border border-line bg-surface text-sm font-medium text-ink transition-colors hover:bg-muted focus:ring-2 focus:ring-primary/30 focus:outline-none">
                        Edit
                    </button>
                </div>
            </div>
        @endforeach

        {{-- Empty state --}}
        <div id="layanan-empty" class="hidden sm:col-span-2 lg:col-span-3 xl:col-span-4">
            <div class="flex flex-col items-center justify-center gap-2 rounded-xl border border-dashed border-line bg-surface px-6 py-12 text-center">
                <x-lucide-search-x class="size-8 text-ink-muted" aria-hidden="true" />
                <p class="text-sm font-medium text-ink">Layanan tidak ditemukan</p>
                <p class="text-sm text-ink-muted">Coba kata kunci lain.</p>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const input = document.getElementById('layanan-search');
    const cards = document.querySelectorAll('[data-card]');
    const kosong = document.getElementById('layanan-empty');
    if (!input || !cards.length) return;

    input.addEventListener('input', function () {
        const kata = this.value.trim().toLowerCase();
        let tampil = 0;

        cards.forEach(function (card) {
            const cocok = card.textContent.toLowerCase().includes(kata);
            card.classList.toggle('hidden', !cocok);
            if (cocok) tampil++;
        });

        kosong.classList.toggle('hidden', tampil !== 0);
    });
});
</script>
@endsection

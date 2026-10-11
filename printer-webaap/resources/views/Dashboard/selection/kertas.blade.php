@extends('Dashboard.layout')

@section('title', 'Kertas')

@php
    // Data contoh (statis). Integrasi API/database menyusul setelah izin Vallen.
    $kertas = [
        ['ukuran' => 'A4', 'aktif' => true,  'berwarna' => 'Rp. 2000', 'hitam_putih' => 'Rp. 2000', 'dibeli' => '502 order'],
        ['ukuran' => 'A4', 'aktif' => false, 'berwarna' => 'Rp. 2000', 'hitam_putih' => 'Rp. 2000', 'dibeli' => '502 order'],
        ['ukuran' => 'A4', 'aktif' => true,  'berwarna' => 'Rp. 2000', 'hitam_putih' => 'Rp. 2000', 'dibeli' => '502 order'],
    ];
@endphp

@section('content')
<div class="w-full">

    {{-- Header --}}
    <div class="flex items-start justify-between gap-4">
        <h1 class="text-2xl font-semibold text-ink">Kertas</h1>
        <p class="text-sm text-ink-muted">{{ now()->translatedFormat('l, j F Y') }}</p>
    </div>

    {{-- Toolbar --}}
    <div class="mt-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <button type="button"
            class="inline-flex h-10 shrink-0 items-center justify-center gap-2 self-start rounded-lg border border-line bg-surface px-4 text-sm font-medium text-ink transition-colors hover:bg-muted focus:ring-2 focus:ring-primary/30 focus:outline-none">
            <x-lucide-plus class="size-4" aria-hidden="true" />
            Tambah Kertas
        </button>

        <div class="relative w-full sm:w-72">
            <label for="kertas-search" class="sr-only">Cari kertas</label>
            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                <x-lucide-search class="size-4 text-ink-muted" aria-hidden="true" />
            </div>
            <input type="search" id="kertas-search" placeholder="Cari..."
                class="block h-10 w-full rounded-lg border border-line bg-surface pl-10 pr-3 text-sm text-ink placeholder:text-ink-muted focus:border-primary focus:ring-2 focus:ring-primary/30 focus:outline-none">
        </div>
    </div>

    {{-- Daftar kertas --}}
    <div class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
        @foreach ($kertas as $k)
            <div data-card class="flex flex-col rounded-xl border border-line bg-surface p-5 shadow-sm">
                <div class="flex items-start justify-between gap-3">
                    <span class="text-3xl font-bold tracking-tight text-ink">{{ $k['ukuran'] }}</span>
                    <span class="rounded-full px-2.5 py-0.5 text-xs font-medium {{ $k['aktif'] ? 'bg-success-soft text-success-strong' : 'bg-muted text-ink-muted' }}">
                        {{ $k['aktif'] ? 'Aktif' : 'Nonaktif' }}
                    </span>
                </div>

                <div class="my-3 border-t border-line"></div>

                <h2 class="text-base font-semibold text-ink">Harga</h2>

                <div class="mt-2 grid grid-cols-2 gap-3">
                    <div class="rounded-lg border border-line bg-surface p-3">
                        <p class="text-xs text-ink-muted">berwarna</p>
                        <p class="mt-0.5 text-sm font-medium tabular-nums text-ink">{{ $k['berwarna'] }}</p>
                    </div>
                    <div class="rounded-lg border border-line bg-surface p-3">
                        <p class="text-xs text-ink-muted">hitam Putih</p>
                        <p class="mt-0.5 text-sm font-medium tabular-nums text-ink">{{ $k['hitam_putih'] }}</p>
                    </div>
                </div>

                <p class="mt-4 text-sm">
                    <span class="text-ink-muted">Total dibeli</span>
                    <span class="font-medium text-ink">{{ $k['dibeli'] }}</span>
                </p>

                <div class="mt-auto pt-4">
                    <button type="button"
                        class="inline-flex h-10 w-full items-center justify-center rounded-lg border border-line bg-surface text-sm font-medium text-ink transition-colors hover:bg-muted focus:ring-2 focus:ring-primary/30 focus:outline-none">
                        Edit
                    </button>
                </div>
            </div>
        @endforeach

        {{-- Empty state --}}
        <div id="kertas-empty" class="hidden sm:col-span-2 xl:col-span-3">
            <div class="flex flex-col items-center justify-center gap-2 rounded-xl border border-dashed border-line bg-surface px-6 py-12 text-center">
                <x-lucide-search-x class="size-8 text-ink-muted" aria-hidden="true" />
                <p class="text-sm font-medium text-ink">Kertas tidak ditemukan</p>
                <p class="text-sm text-ink-muted">Coba kata kunci lain.</p>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const input = document.getElementById('kertas-search');
    const cards = document.querySelectorAll('[data-card]');
    const kosong = document.getElementById('kertas-empty');
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

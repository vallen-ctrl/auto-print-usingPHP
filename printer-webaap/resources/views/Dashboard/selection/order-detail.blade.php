@extends('Dashboard.layout')

@section('title', 'Detail order & file')

@php
    // Data contoh (statis). Integrasi API/database menyusul setelah izin Vallen.
    $orderId    = $id ?? '278kjbaoshv97fjbs';
    $waktuOrder = '12-20-2025';

    $files = [
        ['nama' => 'file.pdf', 'aktif' => true],
        ['nama' => 'file.pdf', 'aktif' => false],
        ['nama' => 'file.pdf', 'aktif' => false],
    ];

    $layanan = [
        ['nama' => 'Laminating', 'harga' => 'Rp. 20.000'],
        ['nama' => 'Tambah map', 'harga' => 'Rp. 20.000'],
    ];

    $konfigurasi = [
        ['label' => 'Orientasi',      'value' => 'Landscape'],
        ['label' => 'Jumlah Cetak',   'value' => '12 Pages'],
        ['label' => 'Ukuran kertas',  'value' => 'A4'],
        ['label' => 'Custom halaman', 'value' => '1, 2, 5'],
        ['label' => 'Warna',          'value' => 'color'],
        ['label' => 'Total pages',    'value' => '36 pages'],
        ['label' => 'Kualitas',       'value' => 'High'],
        ['label' => 'Harga',          'value' => 'Rp. 500 / pages'],
        ['label' => 'Skala',          'value' => '100%'],
        ['label' => 'Sub total',      'value' => 'Rp. 20.000'],
    ];
@endphp

@section('content')
<div class="w-full">

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
                <dd class="font-medium break-all text-ink">{{ $orderId }}</dd>
            </div>
            <div class="flex flex-wrap items-baseline gap-2">
                <dt class="text-ink-muted">Waktu order</dt>
                <dd class="font-medium text-ink">{{ $waktuOrder }}</dd>
            </div>
        </dl>

        <button type="button"
            class="inline-flex h-10 shrink-0 items-center gap-2 self-start rounded-lg border border-line bg-surface px-4 text-sm font-medium text-ink transition-colors hover:bg-muted focus:ring-2 focus:ring-primary/30 focus:outline-none">
            Unduh semua
            <x-lucide-download class="size-4" aria-hidden="true" />
        </button>
    </div>

    {{-- Pemilihan file --}}
    <div class="mt-6 flex gap-3 overflow-x-auto pb-2">
        @foreach ($files as $f)
            <label class="flex min-w-[13rem] flex-1 cursor-pointer items-center gap-3 rounded-xl border px-4 py-3 transition-colors
                {{ $f['aktif'] ? 'border-primary bg-primary-soft' : 'border-line bg-surface hover:bg-muted' }}">
                <input type="checkbox" @checked($f['aktif'])
                    class="size-4 shrink-0 rounded border-line text-primary focus:ring-2 focus:ring-primary/40">
                <x-lucide-file-text class="size-4 shrink-0 {{ $f['aktif'] ? 'text-primary' : 'text-ink-muted' }}" aria-hidden="true" />
                <span class="truncate text-sm font-medium text-ink">{{ $f['nama'] }}</span>
            </label>
        @endforeach
    </div>

    {{-- Layanan + Konfigurasi --}}
    <div class="mt-6 grid grid-cols-1 gap-4 lg:grid-cols-3">

        {{-- Layanan dipilih --}}
        <div class="flex flex-col rounded-xl border border-line bg-surface p-5 shadow-sm">
            <h2 class="text-lg font-semibold text-ink">Layanan dipilih</h2>

            <div class="mt-4 space-y-3 text-sm">
                @foreach ($layanan as $l)
                    <div class="flex items-center justify-between gap-4">
                        <span class="text-ink-muted">{{ $l['nama'] }}</span>
                        <span class="font-medium tabular-nums text-ink">{{ $l['harga'] }}</span>
                    </div>
                @endforeach
            </div>

            <div class="mt-auto flex items-center justify-between gap-4 border-t border-line pt-4 text-sm">
                <span class="font-medium text-ink">Sub total</span>
                <span class="font-semibold tabular-nums text-ink">Rp. 20.000</span>
            </div>
        </div>

        {{-- Konfigurasi --}}
        <div class="rounded-xl border border-line bg-surface p-5 shadow-sm lg:col-span-2">
            <h2 class="text-lg font-semibold text-ink">Konfigurasi</h2>

            <dl class="mt-4 grid grid-cols-1 gap-x-8 gap-y-1 text-sm sm:grid-cols-2">
                @foreach ($konfigurasi as $k)
                    <div class="flex items-center justify-between gap-4 border-b border-line/60 py-2">
                        <dt class="text-ink-muted">{{ $k['label'] }}</dt>
                        <dd class="font-medium text-ink">{{ $k['value'] }}</dd>
                    </div>
                @endforeach
            </dl>

            <div class="mt-5 border-t-2 border-line pt-4">
                <h3 class="text-sm font-medium text-ink">catatan tambahan</h3>
                <p class="mt-1 text-sm leading-relaxed text-ink-muted">
                    It is a long established fact that a reader will be distracted by the readable content of a page
                    when looking at its layout. The point of using Lorem Ipsum is that it has a more-or-less normal
                    distribution of letters, as opposed to using 'Content here, content here', making it look like
                    readable English. Many desktop publishing packages and web page editors now use Lorem Ipsum as
                    their default model text.
                </p>
            </div>
        </div>
    </div>

    {{-- Waktu ambil + unduh file + total --}}
    <div class="mt-6 grid grid-cols-1 gap-4 lg:grid-cols-3">
        <div class="lg:col-span-2">
            <h2 class="text-lg font-semibold text-ink">Waktu ambil</h2>

            <div class="mt-3 inline-flex flex-col gap-3 rounded-xl border border-line bg-surface p-5 text-sm shadow-sm">
                <div class="flex items-center gap-3">
                    <span class="text-ink-muted">Tanggal Ambil</span>
                    <span class="font-semibold text-ink">15-20-2025</span>
                </div>
                <div class="flex items-center gap-3">
                    <span class="text-ink-muted">Jam</span>
                    <span class="font-semibold text-ink">18:00 PM</span>
                </div>
            </div>
        </div>

        <div class="flex flex-col items-stretch justify-end gap-3 lg:items-end">
            <button type="button"
                class="inline-flex h-10 items-center justify-center rounded-lg border border-line bg-surface px-4 text-sm font-medium text-ink transition-colors hover:bg-muted focus:ring-2 focus:ring-primary/30 focus:outline-none">
                Unduh file ini
            </button>

            <p class="text-right">
                <span class="text-sm text-ink-muted">Total :</span>
                <span class="ml-1 text-2xl font-semibold tabular-nums text-ink">Rp. 40.000</span>
            </p>
        </div>
    </div>

    {{-- Aksi akhir --}}
    <div class="mt-8 grid grid-cols-1 gap-4 pb-4 sm:grid-cols-2">
        <a href="{{ route('order.index') }}"
            class="inline-flex h-11 items-center justify-center rounded-lg border border-line bg-surface text-sm font-medium text-ink transition-colors hover:bg-muted focus:ring-2 focus:ring-primary/30 focus:outline-none">
            Keluar
        </a>
        <button type="button"
            class="inline-flex h-11 items-center justify-center rounded-lg bg-primary text-sm font-medium text-white transition-colors hover:bg-primary-hover focus:ring-2 focus:ring-primary/40 focus:ring-offset-2 focus:outline-none">
            Ubah status menjadi berhasil
        </button>
    </div>
</div>
@endsection

@extends('Dashboard.layout')

@section('title', 'Files')

@php
    // Data contoh (statis). Integrasi API/database menyusul setelah izin Vallen.
    $stats = [
        ['label' => 'Total File',  'value' => '1827', 'note' => 'semua'],
        ['label' => 'Penyimpanan', 'value' => '15Gb', 'note' => 'terpakai'],
    ];

    $files = [
        ['order' => 'jbvaskj32a9f', 'tanggal' => '20/19/2025', 'mime' => 'application/pdf', 'path' => '/uploads/likela', 'pages' => 40, 'subtotal' => 'Rp. 30.000', 'kertas' => 'A4', 'hapus' => true],
        ['order' => 'jbvaskj32b1c', 'tanggal' => '20/19/2025', 'mime' => 'application/pdf', 'path' => '/uploads/likela', 'pages' => 40, 'subtotal' => 'Rp. 30.000', 'kertas' => 'A4', 'hapus' => false],
        ['order' => 'jbvaskj32c7d', 'tanggal' => '20/19/2025', 'mime' => 'application/pdf', 'path' => '/uploads/likela', 'pages' => 40, 'subtotal' => 'Rp. 30.000', 'kertas' => 'A4', 'hapus' => false],
        ['order' => 'jbvaskj32d2e', 'tanggal' => '20/19/2025', 'mime' => 'application/pdf', 'path' => '/uploads/likela', 'pages' => 40, 'subtotal' => 'Rp. 30.000', 'kertas' => 'A4', 'hapus' => false],
    ];
@endphp

@section('content')
<div class="w-full">

    {{-- Header --}}
    <div class="flex items-start justify-between gap-4">
        <h1 class="text-2xl font-semibold text-ink">Files</h1>
        <p class="text-sm text-ink-muted">{{ now()->translatedFormat('l, j F Y') }}</p>
    </div>

    {{-- Statistik --}}
    <div class="mt-5 flex flex-wrap gap-4">
        @foreach ($stats as $s)
            <div class="min-w-44 flex-1 rounded-xl border border-line bg-surface p-5 shadow-sm sm:max-w-64">
                <h2 class="text-sm font-medium text-ink-muted">{{ $s['label'] }}</h2>
                <div class="mt-2 flex items-end justify-between gap-3">
                    <span class="text-3xl font-semibold leading-none text-ink">{{ $s['value'] }}</span>
                    <span class="text-xs text-ink-muted">{{ $s['note'] }}</span>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Tabel file --}}
    <div class="mt-5 overflow-hidden rounded-xl border border-line bg-surface shadow-sm">
        <div class="overflow-x-auto">
            <table id="tabel-files" class="w-full min-w-[60rem] text-left text-sm">
                <thead class="border-b border-line bg-muted/60">
                    <tr class="text-ink-muted">
                        <th scope="col" class="w-10 px-4 py-3">
                            <input type="checkbox" id="pilih-semua"
                                class="size-4 rounded border-line accent-primary focus:ring-2 focus:ring-primary/40">
                            <label for="pilih-semua" class="sr-only">Pilih semua file</label>
                        </th>
                        <th scope="col" class="whitespace-nowrap px-4 py-3 font-medium">Tanggal masuk</th>
                        <th scope="col" class="whitespace-nowrap px-4 py-3 font-medium">order id</th>
                        <th scope="col" class="whitespace-nowrap px-4 py-3 font-medium">mime</th>
                        <th scope="col" class="whitespace-nowrap px-4 py-3 font-medium">path</th>
                        <th scope="col" class="whitespace-nowrap px-4 py-3 font-medium">Total Pages</th>
                        <th scope="col" class="whitespace-nowrap px-4 py-3 font-medium">Sub total</th>
                        <th scope="col" class="whitespace-nowrap px-4 py-3 font-medium">Ukuran kertas</th>
                        <th scope="col" class="whitespace-nowrap px-4 py-3 text-right font-medium">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line text-ink">
                    @foreach ($files as $f)
                        <tr data-row class="transition-colors hover:bg-muted/50">
                            <td class="px-4 py-3">
                                <input type="checkbox" name="pilih-file"
                                    class="pilih-file size-4 rounded border-line accent-primary focus:ring-2 focus:ring-primary/40">
                                <span class="sr-only">Pilih file {{ $f['order'] }}</span>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-ink-muted">{{ $f['tanggal'] }}</td>
                            <td class="px-4 py-3 font-medium">
                                <span class="block max-w-[7rem] truncate" title="{{ $f['order'] }}">{{ $f['order'] }}</span>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-ink-muted">{{ $f['mime'] }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-ink-muted">{{ $f['path'] }}</td>
                            <td class="whitespace-nowrap px-4 py-3 tabular-nums">{{ $f['pages'] }}</td>
                            <td class="whitespace-nowrap px-4 py-3 font-medium tabular-nums">{{ $f['subtotal'] }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-ink-muted">{{ $f['kertas'] }}</td>
                            <td class="whitespace-nowrap px-4 py-3">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('order.detail', $f['order']) }}" aria-label="Lihat file {{ $f['order'] }}"
                                        class="inline-flex size-9 items-center justify-center rounded-lg bg-primary text-white transition-colors hover:bg-primary-hover focus:ring-2 focus:ring-primary/40 focus:ring-offset-2 focus:outline-none">
                                        <x-lucide-eye class="size-4" aria-hidden="true" />
                                    </a>
                                    @if ($f['hapus'])
                                        <button type="button" aria-label="Hapus file {{ $f['order'] }}"
                                            class="inline-flex size-9 items-center justify-center rounded-lg bg-danger text-white transition-colors hover:bg-danger-strong focus:ring-2 focus:ring-danger/40 focus:ring-offset-2 focus:outline-none">
                                            <x-lucide-trash-2 class="size-4" aria-hidden="true" />
                                        </button>
                                    @else
                                        <button type="button" disabled aria-label="File tidak dapat dihapus"
                                            class="inline-flex size-9 cursor-not-allowed items-center justify-center rounded-lg bg-muted text-ink-muted/50">
                                            <x-lucide-trash-2 class="size-4" aria-hidden="true" />
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
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
    const semua = document.getElementById('pilih-semua');
    const kotak = document.querySelectorAll('.pilih-file');
    if (!semua || !kotak.length) return;

    semua.addEventListener('change', function () {
        kotak.forEach(function (cb) {
            cb.checked = semua.checked;
            cb.closest('tr').classList.toggle('bg-primary-soft/50', semua.checked);
        });
    });

    kotak.forEach(function (cb) {
        cb.addEventListener('change', function () {
            cb.closest('tr').classList.toggle('bg-primary-soft/50', cb.checked);
            const seleksi = Array.from(kotak).filter((c) => c.checked).length;
            semua.checked = seleksi === kotak.length;
            semua.indeterminate = seleksi > 0 && seleksi < kotak.length;
        });
    });
});
</script>
@endsection

@extends('Dashboard.layout')

@section('title', 'Files')

@php
    // Data contoh (statis). Integrasi API/database menyusul setelah izin Vallen.
    $stats = [
        ['label' => 'Total File',  'value' => $totalFiles, 'note' => 'semua'],
        ['label' => 'Penyimpanan', 'value' => $sumTotalPenggunaan, 'note' => 'terpakai'],
    ];

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

    {{-- Pencarian --}}
    <form method="GET" action="{{ route('files.index') }}" class="mt-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="relative w-full sm:w-80">
            <label for="files-search" class="sr-only">Cari file</label>
            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                <x-lucide-search class="size-4 text-ink-muted" aria-hidden="true" />
            </div>
            <input type="search" id="files-search" name="search" value="{{ $search }}"
                placeholder="Cari nama file, mime, atau order id..."
                class="block h-10 w-full rounded-lg border border-line bg-surface pl-10 pr-3 text-sm text-ink placeholder:text-ink-muted focus:border-primary focus:ring-2 focus:ring-primary/30 focus:outline-none">
        </div>

        <div class="flex items-center gap-2">
            <button type="submit"
                class="inline-flex h-10 items-center justify-center gap-2 rounded-lg bg-primary px-4 text-sm font-medium text-white transition-colors hover:bg-primary-hover focus:ring-2 focus:ring-primary/40 focus:outline-none">
                <x-lucide-search class="size-4" aria-hidden="true" />
                Cari
            </button>
            @if ($search !== '')
                <a href="{{ route('files.index') }}"
                    class="inline-flex h-10 items-center justify-center rounded-lg border border-line bg-surface px-4 text-sm font-medium text-ink transition-colors hover:bg-muted focus:ring-2 focus:ring-primary/30 focus:outline-none">
                    Reset
                </a>
            @endif
        </div>
    </form>

    @if ($search !== '')
        <p class="mt-3 text-sm text-ink-muted">
            Hasil pencarian untuk <span class="font-medium text-ink">"{{ $search }}"</span>:
            {{ $files->total() }} file.
        </p>
    @endif

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
                         <th scope="col" class="whitespace-nowrap px-4 py-3 font-medium">Nama</th>
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
                    @forelse ($files as $f)
                        @php
                            $orderId = $f->order_hex ?: '-';
                            $fileName = $f->name;
                            $tanggal = $f->tanggal_dibuat ? $f->tanggal_dibuat->format('d/m/Y') : '-';
                            $subtotal = 'Rp. ' . number_format((int) $f->subtotal, 0, ',', '.');
                        @endphp
                        <tr data-row class="transition-colors hover:bg-muted/50">
                            <td class="px-4 py-3">
                                <input type="checkbox" name="pilih-file"
                                    class="pilih-file size-4 rounded border-line accent-primary focus:ring-2 focus:ring-primary/40">
                                <span class="sr-only">Pilih file {{ $orderId }}</span>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-ink-muted">
                                <span class="block max-w-[16rem] truncate" title="{{ $fileName }}">{{ $fileName }}</span>
                            </td>
                            
                            <td class="whitespace-nowrap px-4 py-3 text-ink-muted">{{ $tanggal }}</td>
                            <td class="px-4 py-3 font-medium">
                                <span class="block max-w-[7rem] truncate" title="{{ $orderId }}">{{ $orderId }}</span>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-ink-muted">{{ $f->mime }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-ink-muted">
                                <span class="block max-w-[12rem] truncate" title="{{ $f->path }}">{{ $f->path }}</span>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 tabular-nums">{{ $f->total_pages ?? '-' }}</td>
                            <td class="whitespace-nowrap px-4 py-3 font-medium tabular-nums">{{ $subtotal }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-ink-muted">{{ $f->kertas ?: '-' }}</td>
                            <td class="whitespace-nowrap px-4 py-3">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('files.download', $f->id_hex) }}" aria-label="Unduh file {{ $orderId }}"
                                        class="inline-flex size-9 items-center justify-center rounded-lg bg-primary text-white transition-colors hover:bg-primary-hover focus:ring-2 focus:ring-primary/40 focus:ring-offset-2 focus:outline-none">
                                        <x-lucide-download class="size-4" aria-hidden="true" />
                                    </a>
                                    @if (! $f->dihapus)
                                        <button type="button" data-modal-target="hapus-file" data-modal-toggle="hapus-file"
                                            data-action="{{ route('files.destroy', $f->id_hex) }}"
                                            data-name="{{ $fileName }}"
                                            aria-label="Hapus file {{ $orderId }}"
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
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-12 text-center text-ink-muted">
                                Tidak ada file ditemukan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if ($files->hasPages())
            <div class="flex flex-col items-center justify-between gap-3 border-t border-line px-4 py-3 sm:flex-row">
                <p class="text-sm text-ink-muted">
                    Menampilkan {{ $files->firstItem() }}–{{ $files->lastItem() }} dari {{ $files->total() }} file
                </p>
                <div>{{ $files->links() }}</div>
            </div>
        @endif
    </div>

    {{-- Modal konfirmasi hapus --}}
    <div id="hapus-file" data-modal-backdrop="static" tabindex="-1" aria-hidden="true" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
        <div class="relative p-4 w-full max-w-md max-h-full">
            <div class="relative bg-white rounded-lg shadow-sm">
                <div class="flex items-center justify-between p-4 md:p-5 border-b rounded-t border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Hapus File</h3>
                    <button type="button" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center" data-modal-hide="hapus-file">
                        <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6" />
                        </svg>
                        <span class="sr-only">Close modal</span>
                    </button>
                </div>
                <form id="form-hapus-file" method="POST" action="">
                    @csrf
                    @method('DELETE')
                    <div class="p-4 md:p-5 text-center">
                        <div class="mx-auto mb-4 flex size-12 items-center justify-center rounded-full bg-danger-soft text-fg-danger">
                            <x-lucide-trash-2 class="size-6" aria-hidden="true" />
                        </div>
                        <h3 class="mb-2 text-base font-semibold text-ink">Hapus file ini?</h3>
                        <p class="mb-1 text-sm text-ink-muted">File berikut akan dihapus permanen:</p>
                        <p id="hapus-file-name" class="mb-6 break-all text-sm font-medium text-ink"></p>
                        <div class="flex items-center justify-center gap-3">
                            <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-danger px-4 py-2.5 text-sm font-medium text-white transition-colors hover:bg-danger-strong focus:ring-4 focus:ring-danger/30 focus:outline-none">
                                Ya, hapus
                            </button>
                            <button type="button" data-modal-hide="hapus-file" class="inline-flex items-center justify-center rounded-lg border border-line bg-surface px-4 py-2.5 text-sm font-medium text-ink transition-colors hover:bg-muted focus:ring-2 focus:ring-primary/30 focus:outline-none">
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
    const formHapus = document.getElementById('form-hapus-file');
    const namaHapus = document.getElementById('hapus-file-name');

    // Isi modal konfirmasi hapus sesuai file yang tombolnya diklik.
    document.querySelectorAll('[data-modal-target="hapus-file"]').forEach(function (tombol) {
        tombol.addEventListener('click', function () {
            if (formHapus) {
                formHapus.action = tombol.dataset.action || '';
            }
            if (namaHapus) {
                namaHapus.textContent = tombol.dataset.name || '';
            }
        });
    });

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

@extends('Dashboard.layout')

@section('title', 'Detail order & file')

@php
    // Data contoh (statis). Integrasi API/database menyusul setelah izin Vallen.
    $orderId    = $id ?? '278kjbaoshv97fjbs';
    $waktuOrder = '12-20-2025';

    $lorem = 'It is a long established fact that a reader will be distracted by the readable content of a page '
        . 'when looking at its layout. The point of using Lorem Ipsum is that it has a more-or-less normal '
        . 'distribution of letters, as opposed to using \'Content here, content here\', making it look like '
        . 'readable English. Many desktop publishing packages and web page editors now use Lorem Ipsum as their '
        . 'default model text.';

    $files = [
        [
            'id'            => 'file-1',
            'nama'          => 'file-1.pdf',
            'layanan'       => [['Laminating', 'Rp. 20.000'], ['Tambah map', 'Rp. 20.000']],
            'sub_layanan'   => 'Rp. 20.000',
            'konfigurasi'   => [
                'Orientasi'      => 'Landscape',
                'Jumlah Cetak'   => '12 Pages',
                'Ukuran kertas'  => 'A4',
                'Custom halaman' => '1, 2, 5',
                'Warna'          => 'color',
                'Total pages'    => '36 pages',
                'Kualitas'       => 'High',
                'Harga'          => 'Rp. 500 / pages',
                'Skala'          => '100%',
                'Sub total'      => 'Rp. 20.000',
            ],
            'catatan'       => $lorem,
            'tanggal_ambil' => '15-20-2025',
            'jam'           => '18:00 PM',
            'total'         => 'Rp. 40.000',
        ],
        [
            'id'            => 'file-2',
            'nama'          => 'file-2.pdf',
            'layanan'       => [['Laminating', 'Rp. 15.000'], ['Jilid spiral', 'Rp. 25.000']],
            'sub_layanan'   => 'Rp. 25.000',
            'konfigurasi'   => [
                'Orientasi'      => 'Portrait',
                'Jumlah Cetak'   => '6 Pages',
                'Ukuran kertas'  => 'A3',
                'Custom halaman' => '1, 2, 3, 4',
                'Warna'          => 'color',
                'Total pages'    => '24 pages',
                'Kualitas'       => 'Medium',
                'Harga'          => 'Rp. 400 / pages',
                'Skala'          => '90%',
                'Sub total'      => 'Rp. 15.000',
            ],
            'catatan'       => 'Cetak dua sisi dan pastikan margin kiri 3 cm untuk area jilid spiral.',
            'tanggal_ambil' => '16-20-2025',
            'jam'           => '10:00 AM',
            'total'         => 'Rp. 60.000',
        ],
        [
            'id'            => 'file-3',
            'nama'          => 'file-3.pdf',
            'layanan'       => [['Cetak warna', 'Rp. 30.000'], ['Tambah map', 'Rp. 20.000']],
            'sub_layanan'   => 'Rp. 30.000',
            'konfigurasi'   => [
                'Orientasi'      => 'Landscape',
                'Jumlah Cetak'   => '20 Pages',
                'Ukuran kertas'  => 'A5',
                'Custom halaman' => '2, 4, 6',
                'Warna'          => 'color',
                'Total pages'    => '60 pages',
                'Kualitas'       => 'High',
                'Harga'          => 'Rp. 600 / pages',
                'Skala'          => '100%',
                'Sub total'      => 'Rp. 30.000',
            ],
            'catatan'       => 'Gunakan kertas art paper 150gsm, hasil akhir glossy.',
            'tanggal_ambil' => '17-20-2025',
            'jam'           => '13:30 PM',
            'total'         => 'Rp. 75.000',
        ],
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

    {{-- Pemilihan file (radio) --}}
    <div class="mt-6 flex gap-3 overflow-x-auto pb-2" role="radiogroup" aria-label="Pilih file">
        @foreach ($files as $f)
            <label class="group flex min-w-52 flex-1 cursor-pointer items-center gap-3 rounded-xl border border-line bg-surface px-4 py-3 transition-colors hover:bg-muted has-checked:border-primary has-checked:bg-primary-soft">
                <input type="radio" name="file-pilih" value="{{ $f['id'] }}" @checked($loop->first)
                    class="size-4 shrink-0 accent-primary focus:ring-2 focus:ring-primary/40">
                <x-lucide-file-text class="size-4 shrink-0 text-ink-muted group-has-checked:text-primary" aria-hidden="true" />
                <span class="truncate text-sm font-medium text-ink">{{ $f['nama'] }}</span>
            </label>
        @endforeach
    </div>

    {{-- Panel per file (berganti sesuai pilihan) --}}
    @foreach ($files as $f)
        <div id="panel-{{ $f['id'] }}" data-panel class="{{ $loop->first ? '' : 'hidden' }}">

            {{-- Layanan + Konfigurasi --}}
            <div class="mt-6 grid grid-cols-1 gap-4 lg:grid-cols-3">

                {{-- Layanan dipilih --}}
                <div class="flex flex-col rounded-xl border border-line bg-surface p-5 shadow-sm">
                    <h2 class="text-lg font-semibold text-ink">Layanan dipilih</h2>

                    <div class="mt-4 space-y-3 text-sm">
                        @foreach ($f['layanan'] as [$nama, $harga])
                            <div class="flex items-center justify-between gap-4">
                                <span class="text-ink-muted">{{ $nama }}</span>
                                <span class="font-medium tabular-nums text-ink">{{ $harga }}</span>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-auto flex items-center justify-between gap-4 border-t border-line pt-4 text-sm">
                        <span class="font-medium text-ink">Sub total</span>
                        <span class="font-semibold tabular-nums text-ink">{{ $f['sub_layanan'] }}</span>
                    </div>
                </div>

                {{-- Konfigurasi --}}
                <div class="rounded-xl border border-line bg-surface p-5 shadow-sm lg:col-span-2">
                    <h2 class="text-lg font-semibold text-ink">Konfigurasi</h2>

                    <dl class="mt-4 grid grid-cols-1 gap-x-8 gap-y-1 text-sm sm:grid-cols-2">
                        @foreach ($f['konfigurasi'] as $label => $value)
                            <div class="flex items-center justify-between gap-4 border-b border-line/60 py-2">
                                <dt class="text-ink-muted">{{ $label }}</dt>
                                <dd class="font-medium text-ink">{{ $value }}</dd>
                            </div>
                        @endforeach
                    </dl>

                    <div class="mt-5 border-t-2 border-line pt-4">
                        <h3 class="text-sm font-medium text-ink">catatan tambahan</h3>
                        <p class="mt-1 text-sm leading-relaxed text-ink-muted">{{ $f['catatan'] }}</p>
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
                            <span class="font-semibold text-ink">{{ $f['tanggal_ambil'] }}</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="text-ink-muted">Jam</span>
                            <span class="font-semibold text-ink">{{ $f['jam'] }}</span>
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
                        <span class="ml-1 text-2xl font-semibold tabular-nums text-ink">{{ $f['total'] }}</span>
                    </p>
                </div>
            </div>
        </div>
    @endforeach

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
});
</script>
@endsection

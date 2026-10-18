@extends('Dashboard.layout')

@section('title', 'Detail order & file')

@php
    $rupiah = fn ($v) => 'Rp. ' . number_format((int) $v, 0, ',', '.');
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
                <dd class="font-medium break-all text-ink">{{ $id }}</dd>
            </div>
            <div class="flex flex-wrap items-baseline gap-2">
                <dt class="text-ink-muted">Waktu order</dt>
                <dd class="font-medium text-ink">{{ $order->tanggal_pesan ? $order->tanggal_pesan->format('d/m/Y H:i') : '-' }}</dd>
            </div>
            <div class="flex flex-wrap items-baseline gap-2">
                <dt class="text-ink-muted">Status pembayaran</dt>
                <dd class="font-medium capitalize text-ink">{{ $order->status_pembayaran ?: '-' }}</dd>
            </div>
            <div class="flex flex-wrap items-baseline gap-2">
                <dt class="text-ink-muted">Total order</dt>
                <dd class="font-medium tabular-nums text-ink">{{ $rupiah($order->total_pembayaran) }}</dd>
            </div>
        </dl>

        <button type="button"
            class="inline-flex h-10 shrink-0 items-center gap-2 self-start rounded-lg border border-line bg-surface px-4 text-sm font-medium text-ink transition-colors hover:bg-muted focus:ring-2 focus:ring-primary/30 focus:outline-none">
            Unduh semua
            <x-lucide-download class="size-4" aria-hidden="true" />
        </button>
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

@extends('Dashboard.layout')

@section('title', 'Pengaturan')

@php
    // Data contoh (statis). Integrasi API/database menyusul setelah izin Vallen.
    $biaya = [
        ['label' => 'Admin',   'value' => '20.000'],
        ['label' => 'Layanan', 'value' => '20.000'],
    ];

    $hari = [
        ['nama' => 'Senin',  'mulai' => '07:00', 'selesai' => '18:00', 'buka' => true],
        ['nama' => 'Selasa', 'mulai' => '07:00', 'selesai' => '18:00', 'buka' => true],
        ['nama' => 'Rabu',   'mulai' => '07:00', 'selesai' => '18:00', 'buka' => true],
        ['nama' => 'Kamis',  'mulai' => '07:00', 'selesai' => '18:00', 'buka' => true],
        ['nama' => 'Jumat',  'mulai' => '07:00', 'selesai' => '18:00', 'buka' => true],
        ['nama' => 'Sabtu',  'mulai' => '07:00', 'selesai' => '18:00', 'buka' => true],
        ['nama' => 'Minggu', 'mulai' => '07:00', 'selesai' => '18:00', 'buka' => true],
    ];
@endphp

@section('content')
<div class="w-full">

    {{-- Header --}}
    <div class="flex items-start justify-between gap-4">
        <h1 class="text-2xl font-semibold text-ink">Pengaturan</h1>
        <p class="text-sm text-ink-muted">{{ now()->translatedFormat('l, j F Y') }}</p>
    </div>

    <div class="mt-6 space-y-4">

        {{-- Profil toko --}}
        <section class="rounded-xl border border-line bg-surface p-5 shadow-sm">
            <div class="flex items-start gap-4">
                <div class="relative shrink-0">
                    <div class="flex size-20 items-center justify-center rounded-full border border-line bg-muted/40">
                        <x-lucide-pencil class="size-8 text-ink-muted" aria-hidden="true" />
                    </div>
                    <span class="absolute -right-1 -bottom-1 flex size-7 items-center justify-center rounded-full border-2 border-surface bg-warning text-white">
                        <x-lucide-check class="size-4" aria-hidden="true" />
                    </span>
                </div>

                <div class="min-w-0 flex-1">
                    <p class="text-sm text-ink-muted">Nama toko</p>
                    <p class="text-lg font-semibold text-ink">Toko print saya</p>

                    <div class="mt-3 flex flex-wrap items-center gap-3">
                        <x-switch :checked="true" />
                        <span class="text-sm font-semibold text-ink">BUKA</span>
                        <span class="text-sm text-ink-muted">Menerima file dari pelanggan</span>
                    </div>
                </div>
            </div>
        </section>

        {{-- Biaya --}}
        <section class="rounded-xl border border-line bg-surface p-5 shadow-sm">
            <h2 class="text-base font-semibold text-ink">Biaya</h2>
            <p class="mt-0.5 text-sm text-ink-muted">Biaya tambahan di luar harga cetak.</p>

            <div class="mt-4 divide-y divide-line">
                @foreach ($biaya as $b)
                    <div class="flex items-center justify-between gap-4 py-3">
                        <label for="biaya-{{ Str::slug($b['label']) }}" class="text-sm font-medium text-ink">{{ $b['label'] }}</label>
                        <div class="flex h-10 items-center rounded-lg border border-line bg-surface pr-3 pl-3 focus-within:border-primary focus-within:ring-2 focus-within:ring-primary/30">
                            <span class="text-sm text-ink-muted">Rp</span>
                            <input type="text" id="biaya-{{ Str::slug($b['label']) }}" value="{{ $b['value'] }}" inputmode="numeric"
                                class="h-full w-24 border-0 bg-transparent pl-2 text-right text-sm tabular-nums text-ink focus:ring-0 focus:outline-none">
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        {{-- File --}}
        <section class="rounded-xl border border-line bg-surface p-5 shadow-sm">
            <h2 class="text-base font-semibold text-ink">File</h2>
            <p class="mt-0.5 text-sm text-ink-muted">File pelanggan dihapus otomatis setelah batas waktu ini.</p>

            <div class="mt-4 flex items-center justify-between gap-4">
                <label for="lama-disimpan" class="text-sm font-medium text-ink">Lama disimpan</label>
                <div class="flex h-10 items-center rounded-lg border border-line bg-surface px-3 focus-within:border-primary focus-within:ring-2 focus-within:ring-primary/30">
                    <input type="text" id="lama-disimpan" value="5" inputmode="numeric"
                        class="h-full w-10 border-0 bg-transparent text-right text-sm tabular-nums text-ink focus:ring-0 focus:outline-none">
                    <span class="ml-1 text-sm text-ink-muted">hari</span>
                </div>
            </div>
        </section>

        {{-- Jam operasional --}}
        <section class="rounded-xl border border-line bg-surface p-5 shadow-sm">
            <div class="flex items-center justify-between gap-4">
                <h2 class="text-base font-semibold text-ink">Jam operasional</h2>
                <button type="button"
                    class="rounded text-sm font-medium text-primary hover:text-primary-hover focus:ring-2 focus:ring-primary/30 focus:outline-none">Samakan semua</button>
            </div>
            <p class="mt-0.5 text-sm text-ink-muted">Matikan hari yang toko libur.</p>

            <div class="mt-3 divide-y divide-line">
                @foreach ($hari as $h)
                    <div class="flex flex-wrap items-center gap-3 py-3">
                        <x-switch :checked="$h['buka']" />
                        <span class="w-20 text-sm font-medium text-ink">{{ $h['nama'] }}</span>
                        <div class="flex items-center gap-2">
                            <input type="time" value="{{ $h['mulai'] }}" aria-label="Jam buka {{ $h['nama'] }}"
                                class="h-9 rounded-lg border border-line bg-surface px-2 text-sm tabular-nums text-ink focus:border-primary focus:ring-2 focus:ring-primary/30 focus:outline-none">
                            <span class="text-ink-muted">&ndash;</span>
                            <input type="time" value="{{ $h['selesai'] }}" aria-label="Jam tutup {{ $h['nama'] }}"
                                class="h-9 rounded-lg border border-line bg-surface px-2 text-sm tabular-nums text-ink focus:border-primary focus:ring-2 focus:ring-primary/30 focus:outline-none">
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        {{-- Simpan --}}
        <div class="flex justify-end pb-4">
            <button type="button"
                class="inline-flex h-11 items-center justify-center gap-2 rounded-lg bg-primary px-6 text-sm font-medium text-white transition-colors hover:bg-primary-hover focus:ring-2 focus:ring-primary/40 focus:ring-offset-2 focus:outline-none">
                <x-lucide-save class="size-4" aria-hidden="true" />
                Simpan perubahan
            </button>
        </div>
    </div>
</div>
@endsection

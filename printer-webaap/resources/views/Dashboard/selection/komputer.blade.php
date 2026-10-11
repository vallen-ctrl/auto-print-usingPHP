@extends('Dashboard.layout')

@section('title', 'Komputer')

@php
    // Data contoh (statis). Integrasi API/database menyusul setelah izin Vallen.
    $statusMap = [
        'connected'     => ['label' => 'Connected',     'class' => 'bg-success-soft text-success-strong', 'dot' => 'bg-success'],
        'pending'       => ['label' => 'Pending',       'class' => 'bg-warning-soft text-warning-strong', 'dot' => 'bg-warning'],
        'warning'       => ['label' => 'Warning',       'class' => 'bg-danger-soft text-danger-strong',   'dot' => 'bg-danger'],
        'shutdown'      => ['label' => 'Shutdown',      'class' => 'bg-muted text-ink-muted',             'dot' => 'bg-ink-muted'],
        'not connected' => ['label' => 'Not connected', 'class' => 'bg-muted text-ink-muted',             'dot' => 'bg-ink-muted'],
    ];

    $computers = [
        ['nama' => 'Komputer Kasir 1',    'token' => 'a3f9c1d04b7e5821e6f0a9b3c7d21e45', 'dibuat' => '2026-08-20 09:12:00', 'status' => 'connected'],
        ['nama' => 'Komputer Operator',   'token' => '7b2e9a4f1c8d3056af47e2b9c0d8135a', 'dibuat' => '2026-08-21 13:45:00', 'status' => 'pending'],
        ['nama' => 'Komputer Admin',      'token' => 'e91c4f7a2b6d8035cf12a7e4b9d06c38', 'dibuat' => '2026-08-22 08:03:00', 'status' => 'not connected'],
        ['nama' => 'Komputer Gudang',     'token' => '4d8b1e6a9f2c7053ba69d4e1f8c02b57', 'dibuat' => '2026-08-24 16:20:00', 'status' => 'shutdown'],
        ['nama' => 'Komputer Front Office', 'token' => 'c2a7f0e3b6d9184e5a3c7f2b0d6e9148', 'dibuat' => '2026-08-26 10:55:00', 'status' => 'warning'],
    ];
@endphp

@section('content')
<div class="w-full">

    {{-- Header --}}
    <div class="flex items-start justify-between gap-4">
        <h1 class="text-2xl font-semibold text-ink">Komputer</h1>
        <p class="text-sm text-ink-muted">{{ now()->translatedFormat('l, j F Y') }}</p>
    </div>

    {{-- Toolbar --}}
    <div class="mt-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <button type="button" data-modal-target="modal-tambah-komputer" data-modal-toggle="modal-tambah-komputer"
            class="inline-flex h-10 shrink-0 items-center justify-center gap-2 self-start rounded-lg border border-line bg-surface px-4 text-sm font-medium text-ink transition-colors hover:bg-muted focus:ring-2 focus:ring-primary/30 focus:outline-none">
            <x-lucide-plus class="size-4" aria-hidden="true" />
            Tambah Komputer
        </button>

        <div class="relative w-full sm:w-72">
            <label for="komputer-search" class="sr-only">Cari komputer</label>
            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                <x-lucide-search class="size-4 text-ink-muted" aria-hidden="true" />
            </div>
            <input type="search" id="komputer-search" placeholder="Cari..."
                class="block h-10 w-full rounded-lg border border-line bg-surface pl-10 pr-3 text-sm text-ink placeholder:text-ink-muted focus:border-primary focus:ring-2 focus:ring-primary/30 focus:outline-none">
        </div>
    </div>

    {{-- Tabel komputer --}}
    <div class="mt-5 overflow-hidden rounded-xl border border-line bg-surface shadow-sm">
        <div class="overflow-x-auto">
            <table id="tabel-komputer" class="w-full min-w-176 text-left text-sm">
                <thead class="border-b border-line bg-muted/60">
                    <tr class="text-ink-muted">
                        <th scope="col" class="whitespace-nowrap px-4 py-3 font-medium">Nama</th>
                        <th scope="col" class="whitespace-nowrap px-4 py-3 font-medium">Token</th>
                        <th scope="col" class="whitespace-nowrap px-4 py-3 font-medium">Dibuat</th>
                        <th scope="col" class="whitespace-nowrap px-4 py-3 font-medium">Status</th>
                        <th scope="col" class="whitespace-nowrap px-4 py-3 text-right font-medium">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line text-ink">
                    @foreach ($computers as $c)
                        @php $st = $statusMap[$c['status']] ?? $statusMap['not connected']; @endphp
                        <tr data-row class="transition-colors hover:bg-muted/50">
                            <td class="whitespace-nowrap px-4 py-3">
                                <span class="inline-flex items-center gap-2 font-medium">
                                    <x-lucide-monitor class="size-4 text-ink-muted" aria-hidden="true" />
                                    {{ $c['nama'] }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3">
                                <code class="rounded bg-muted/60 px-2 py-1 font-mono text-xs text-ink-muted">
                                    {{ substr($c['token'], 0, 4) . '****' . substr($c['token'], -4) }}
                                </code>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-ink-muted">
                                {{ \Carbon\Carbon::parse($c['dibuat'])->translatedFormat('d M Y, H:i') }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3">
                                <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium {{ $st['class'] }}">
                                    <span class="size-1.5 rounded-full {{ $st['dot'] }}" aria-hidden="true"></span>
                                    {{ $st['label'] }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right">
                                <button type="button" aria-label="Hapus {{ $c['nama'] }}"
                                    class="inline-flex size-9 items-center justify-center rounded-lg bg-danger text-white transition-colors hover:bg-danger-strong focus:ring-2 focus:ring-danger/40 focus:ring-offset-2 focus:outline-none">
                                    <x-lucide-trash-2 class="size-4" aria-hidden="true" />
                                </button>
                            </td>
                        </tr>
                    @endforeach

                    {{-- Empty state hasil pencarian --}}
                    <tr id="komputer-empty" class="hidden">
                        <td colspan="5" class="px-4 py-12">
                            <div class="flex flex-col items-center justify-center gap-2 text-center">
                                <x-lucide-search-x class="size-8 text-ink-muted" aria-hidden="true" />
                                <p class="text-sm font-medium text-ink">Komputer tidak ditemukan</p>
                                <p class="text-sm text-ink-muted">Coba kata kunci lain.</p>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Trigger tersembunyi untuk membuka modal token via Flowbite --}}
<button type="button" id="trigger-token" data-modal-target="modal-token-komputer" data-modal-toggle="modal-token-komputer"
    class="hidden" aria-hidden="true" tabindex="-1"></button>

{{-- Modal: Tambah Komputer --}}
<div id="modal-tambah-komputer" data-modal-backdrop="static" tabindex="-1" aria-hidden="true"
    class="hidden fixed top-0 right-0 left-0 z-50 h-[calc(100%-1rem)] max-h-full w-full overflow-x-hidden overflow-y-auto md:inset-0">
    <div class="relative max-h-full w-full max-w-md p-4">
        <div class="relative rounded-xl border border-line bg-surface shadow-sm">
            <div class="flex items-center justify-between border-b border-line p-4 md:p-5">
                <h3 class="text-lg font-semibold text-ink">Tambah Komputer</h3>
                <button type="button" id="close-tambah" data-modal-hide="modal-tambah-komputer" aria-label="Tutup"
                    class="inline-flex size-8 items-center justify-center rounded-lg text-ink-muted hover:bg-muted hover:text-ink focus:ring-2 focus:ring-primary/30 focus:outline-none">
                    <x-lucide-x class="size-5" aria-hidden="true" />
                </button>
            </div>

            <form id="form-tambah-komputer" class="p-4 md:p-5">
                <label for="nama-komputer" class="mb-2 block text-sm font-medium text-ink">Nama komputer</label>
                <input type="text" id="nama-komputer" name="nama" required maxlength="100" autocomplete="off"
                    placeholder="Contoh: Komputer Kasir 1"
                    class="block w-full rounded-lg border border-line bg-surface p-2.5 text-sm text-ink placeholder:text-ink-muted focus:border-primary focus:ring-2 focus:ring-primary/30 focus:outline-none">
                <p class="mt-1 text-xs text-ink-muted">Nama ini hanya untuk membedakan tiap komputer.</p>

                <div class="mt-5 flex items-center justify-end gap-2">
                    <button type="button" data-modal-hide="modal-tambah-komputer"
                        class="inline-flex h-10 items-center justify-center rounded-lg border border-line bg-surface px-4 text-sm font-medium text-ink hover:bg-muted focus:ring-2 focus:ring-primary/30 focus:outline-none">
                        Batal
                    </button>
                    <button type="submit" id="btn-buat-key"
                        class="inline-flex h-10 items-center justify-center gap-2 rounded-lg bg-primary px-4 text-sm font-medium text-white transition-colors hover:bg-primary-hover focus:ring-2 focus:ring-primary/40 focus:ring-offset-2 focus:outline-none disabled:cursor-not-allowed disabled:opacity-70">
                        <span id="btn-buat-label" class="inline-flex items-center gap-2">
                            <x-lucide-key class="size-4" aria-hidden="true" />
                            Buat kunci
                        </span>
                        <span id="btn-buat-loading" class="hidden items-center gap-2">
                            <x-lucide-loader-circle class="size-4 animate-spin" aria-hidden="true" />
                            Membuat...
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal: Hasil (informasi koneksi + token) --}}
<div id="modal-token-komputer" data-modal-backdrop="static" tabindex="-1" aria-hidden="true"
    class="hidden fixed top-0 right-0 left-0 z-50 h-[calc(100%-1rem)] max-h-full w-full overflow-x-hidden overflow-y-auto md:inset-0">
    <div class="relative max-h-full w-full max-w-md p-4">
        <div class="relative rounded-xl border border-line bg-surface shadow-sm">
            <div class="flex items-center justify-between border-b border-line p-4 md:p-5">
                <h3 class="text-lg font-semibold text-ink">Komputer berhasil dibuat</h3>
                <button type="button" data-modal-hide="modal-token-komputer" aria-label="Tutup"
                    class="inline-flex size-8 items-center justify-center rounded-lg text-ink-muted hover:bg-muted hover:text-ink focus:ring-2 focus:ring-primary/30 focus:outline-none">
                    <x-lucide-x class="size-5" aria-hidden="true" />
                </button>
            </div>

            <div class="p-4 md:p-5">
                <div class="flex items-center gap-3">
                    <div class="inline-flex size-10 shrink-0 items-center justify-center rounded-full bg-success-soft text-success-strong">
                        <x-lucide-check class="size-5" aria-hidden="true" />
                    </div>
                    <div class="min-w-0">
                        <p class="text-sm text-ink-muted">Nama komputer</p>
                        <p id="token-nama" class="truncate font-semibold text-ink">-</p>
                    </div>
                </div>

                <div class="mt-4 flex items-center justify-between gap-3 rounded-lg border border-line bg-muted/40 p-3 text-sm">
                    <span class="text-ink-muted">Status koneksi</span>
                    
                    <span class=" inline-flex items-center gap-1.5 rounded-full bg-muted px-2.5 py-0.5 text-xs font-medium text-ink-muted">
                        <span class="animate-ping size-1.5 rounded-full bg-ink-muted" aria-hidden="true"></span>
                        Not connected
                    </span>
                </div>

                <div class="mt-4">
                    <p class="mb-1.5 text-sm font-medium text-ink">Token</p>
                    <div class="flex items-center gap-2">
                        <code id="token-value"
                            class="min-w-0 flex-1 truncate rounded-lg border border-line bg-muted/40 px-3 py-2 font-mono text-sm text-ink">-</code>
                        <button type="button" id="btn-copy-token"
                            class="inline-flex h-10 shrink-0 items-center justify-center gap-2 rounded-lg bg-primary px-3 text-sm font-medium text-white transition-colors hover:bg-primary-hover focus:ring-2 focus:ring-primary/40 focus:ring-offset-2 focus:outline-none">
                            <x-lucide-copy class="size-4" aria-hidden="true" />
                            <span id="copy-label">Copy</span>
                        </button>
                    </div>
                    <p class="mt-1 text-xs text-ink-muted">Simpan token ini untuk menghubungkan komputer ke server.</p>
                </div>

                <div class="mt-5 flex justify-end">
                    <button type="button" data-modal-hide="modal-token-komputer"
                        class="inline-flex h-10 items-center justify-center rounded-lg border border-line bg-surface px-4 text-sm font-medium text-ink hover:bg-muted focus:ring-2 focus:ring-primary/30 focus:outline-none">
                        Selesai
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Pencarian komputer
    const inputCari = document.getElementById('komputer-search');
    const tabel = document.getElementById('tabel-komputer');
    const kosong = document.getElementById('komputer-empty');

    if (inputCari && tabel) {
        const baris = Array.from(tabel.querySelectorAll('tbody tr[data-row]'));
        inputCari.addEventListener('input', function () {
            const kata = this.value.trim().toLowerCase();
            let tampil = 0;
            baris.forEach(function (tr) {
                const cocok = tr.textContent.toLowerCase().includes(kata);
                tr.classList.toggle('hidden', !cocok);
                if (cocok) tampil++;
            });
            kosong.classList.toggle('hidden', tampil !== 0);
        });
    }

    // Tambah komputer -> buat kunci -> tampilkan token
    const form = document.getElementById('form-tambah-komputer');
    const btnBuat = document.getElementById('btn-buat-key');
    const labelBuat = document.getElementById('btn-buat-label');
    const loadingBuat = document.getElementById('btn-buat-loading');
    const closeTambah = document.getElementById('close-tambah');
    const triggerToken = document.getElementById('trigger-token');
    const namaInput = document.getElementById('nama-komputer');
    const tokenEl = document.getElementById('token-value');
    const namaEl = document.getElementById('token-nama');

    const generateToken = function () {
        const bytes = new Uint8Array(16);
        crypto.getRandomValues(bytes);
        return Array.from(bytes, function (b) { return b.toString(16).padStart(2, '0'); }).join('');
    };

    if (form && btnBuat && triggerToken) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            const nama = namaInput.value.trim();
            if (!nama) return;

            // State loading
            btnBuat.disabled = true;
            labelBuat.classList.add('hidden');
            labelBuat.classList.remove('inline-flex');
            loadingBuat.classList.remove('hidden');
            loadingBuat.classList.add('inline-flex');

            // Simulasi request ke server untuk membuat key
            setTimeout(function () {
                tokenEl.textContent = generateToken();
                namaEl.textContent = nama;

                btnBuat.disabled = false;
                loadingBuat.classList.add('hidden');
                loadingBuat.classList.remove('inline-flex');
                labelBuat.classList.remove('hidden');
                labelBuat.classList.add('inline-flex');

                namaInput.value = '';

                // Tutup modal tambah, buka modal token lewat handler Flowbite
                closeTambah.click();
                triggerToken.click();
            }, 1500);
        });
    }

    // Salin token
    const copyBtn = document.getElementById('btn-copy-token');
    const copyLabel = document.getElementById('copy-label');

    if (copyBtn) {
        copyBtn.addEventListener('click', function () {
            const token = tokenEl.textContent || '';
            if (!token || token === '-') return;

            navigator.clipboard.writeText(token).then(function () {
                copyLabel.textContent = 'Tersalin';
                setTimeout(function () { copyLabel.textContent = 'Copy'; }, 2000);
            });
        });
    }
});
</script>
@endsection

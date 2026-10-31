@extends('Dashboard.layout')

@section('title', 'Komputer')

@php
$statusMap = [
    'connected' => ['label' => 'Connected', 'class' => 'bg-success-soft text-success-strong', 'dot' => 'bg-success'],
    'pending' => ['label' => 'Pending', 'class' => 'bg-warning-soft text-warning-strong', 'dot' => 'bg-warning'],
    'warning' => ['label' => 'Warning', 'class' => 'bg-danger-soft text-danger-strong', 'dot' => 'bg-danger'],
    'shutdown' => ['label' => 'Shutdown', 'class' => 'bg-muted text-ink-muted', 'dot' => 'bg-ink-muted'],
    'not connected' => ['label' => 'Not connected', 'class' => 'bg-muted text-ink-muted', 'dot' => 'bg-ink-muted'],
];
@endphp

@section('content')
<div class="w-full">

    @if(session('success'))
    <x-toast type="success" :message="session('success')" />
    @endif
    @if(session('error'))
    <x-toast type="error" :message="session('error')" />
    @endif
    @if(session('warning'))
    <x-toast type="warning" :message="session('warning')" />
    @endif

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

    {{-- Info status koneksi (diperbarui otomatis oleh polling di bawah) --}}
    <p id="komputer-koneksi-info" class="mt-4 text-sm text-ink-muted" aria-live="polite">
        Memeriksa status komputer…
    </p>

    {{-- Tabel komputer --}}
    <div class="mt-2 overflow-hidden rounded-xl border border-line bg-surface shadow-sm">
        <div class="overflow-x-auto">
            <table id="tabel-komputer" class="w-full min-w-176 text-left text-sm">
                <thead class="border-b border-line bg-muted/60">
                    <tr class="text-ink-muted">
                        <th scope="col" class="whitespace-nowrap px-4 py-3 font-medium">Nama</th>
                        <th scope="col" class="whitespace-nowrap px-4 py-3 font-medium">Token</th>
                        <th scope="col" class="whitespace-nowrap px-4 py-3 font-medium">Dibuat</th>
                        <th scope="col" class="whitespace-nowrap px-4 py-3 font-medium">Status</th>
                        <th scope="col" class="whitespace-nowrap px-4 py-3 font-medium text-center">Pending</th>
                        <th scope="col" class="whitespace-nowrap px-4 py-3 font-medium text-center">Processing</th>
                        <th scope="col" class="whitespace-nowrap px-4 py-3 font-medium text-center">Completed</th>
                        <th scope="col" class="whitespace-nowrap px-4 py-3 font-medium text-center">Failed</th>
                        <th scope="col" class="whitespace-nowrap px-4 py-3 text-right font-medium">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line text-ink">
                    @foreach ($computers as $c)
                    @php
                    $st = $statusMap[$c->status] ?? $statusMap['not connected'];
                    $qc = $c->queue_counts ?? ['pending'=>0,'processing'=>0,'completed'=>0,'failed'=>0,'downloading'=>0];
                    @endphp
                    <tr data-row data-computer-id="{{ $c->id }}" class="transition-colors hover:bg-muted/50">
                        <td class="whitespace-nowrap px-4 py-3">
                            <span class="inline-flex items-center gap-2 font-medium">
                                <x-lucide-monitor class="size-4 text-ink-muted" aria-hidden="true" />
                                {{ $c->nama }}
                            </span>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3">
                            <code class="rounded bg-muted/60 px-2 py-1 font-mono text-xs text-ink-muted">
                                {{ substr($c->token, 0, 4) . '****' . substr($c->token, -4) }}
                            </code>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-ink-muted">
                            {{ \Carbon\Carbon::parse($c->dibuat)->translatedFormat('d M Y, H:i') }}
                        </td>
                        <td class="whitespace-nowrap px-4 py-3">
                            <span data-status-badge class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium {{ $st['class'] }}">
                                <span data-status-dot class="size-1.5 rounded-full {{ $st['dot'] }}" aria-hidden="true"></span>
                                <span data-status-text>{{ $st['label'] }}</span>
                            </span>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-center">
                            <span data-queue="pending" class="inline-flex bg-amber-100 text-amber-800 items-center justify-center min-w-[24px] rounded-full px-2.5 py-0.5 text-xs font-medium">
                                {{ $qc['pending'] }}
                            </span>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-center">
                            <span data-queue="processing" class="inline-flex bg-blue-100 text-blue-800 items-center justify-center min-w-[24px] rounded-full px-2.5 py-0.5 text-xs font-medium">
                                {{ $qc['processing'] }}
                            </span>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-center">
                            <span data-queue="completed" class="inline-flex bg-emerald-100 text-emerald-800 items-center justify-center min-w-[24px] rounded-full px-2.5 py-0.5 text-xs font-medium">
                                {{ $qc['completed'] }}
                            </span>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-center">
                            <span data-queue="failed" class="inline-flex bg-rose-100 text-rose-700 items-center justify-center min-w-[24px] rounded-full px-2.5 py-0.5 text-xs font-medium">
                                {{ $qc['failed'] }}
                            </span>
                        </td>

                        {{-- Kolom Aksi: Dropdown Titik Tiga --}}
                        <td class="whitespace-nowrap px-4 py-3 text-right">
                            <button id="dropdownMenuIconButton-{{ $c->id }}" data-dropdown-toggle="dropdownDots-{{ $c->id }}"
                                class="inline-flex size-9 items-center justify-center rounded-lg text-ink bg-transparent hover:bg-muted transition-colors focus:ring-2 focus:ring-primary/30 focus:outline-none"
                                type="button">
                                <x-lucide-more-vertical class="size-5" aria-hidden="true" />
                            </button>

                            <!-- Dropdown Menu -->
                            <div id="dropdownDots-{{ $c->id }}" class="z-10 hidden bg-surface border border-line rounded-lg shadow-lg w-44 text-left">
                                <ul class="p-2 text-sm text-ink font-medium" aria-labelledby="dropdownMenuIconButton-{{ $c->id }}">
                                    <li>
                                        {{-- Form tersembunyi (di-trigger via JS saat modal Retry diklik) --}}
                                        <form id="form-retry-{{ $c->id }}" action="{{ route('komputer.file.retry', $c->id) }}" method="POST" class="form-retry">
                                            @csrf
                                            @method('PUT')
                                        </form>
                                        
                                        {{-- Tombol Retry memicu Modal --}}
                                        <button type="button" data-modal-target="modal-retry-{{ $c->id }}" data-modal-toggle="modal-retry-{{ $c->id }}"
                                            class="inline-flex items-center gap-2 w-full p-2 hover:bg-muted rounded transition-colors text-left">
                                            <x-lucide-rotate-ccw class="size-4" aria-hidden="true" />
                                            Retry
                                        </button>
                                    </li>
                                    <li>
                                        {{-- Tombol Delete memicu Modal --}}
                                        <button type="button" data-modal-target="modal-hapus-{{ $c->id }}" data-modal-toggle="modal-hapus-{{ $c->id }}"
                                            class="inline-flex items-center gap-2 w-full p-2 hover:bg-rose-50 text-rose-600 rounded transition-colors text-left">
                                            <x-lucide-trash-2 class="size-4" aria-hidden="true" />
                                            Delete
                                        </button>
                                    </li>
                                </ul>
                            </div>
                        </td>
                    </tr>
                    @endforeach

                    {{-- Empty state hasil pencarian --}}
                    <tr id="komputer-empty" class="hidden">
                        <td colspan="9" class="px-4 py-12">
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

{{-- ========================================================= --}}
{{-- KUMPULAN MODAL RETRY & HAPUS --}}
{{-- ========================================================= --}}
@foreach ($computers as $c)

{{-- Modal: Konfirmasi Retry --}}
<div id="modal-retry-{{ $c->id }}" tabindex="-1" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
    <div class="relative p-4 w-full max-w-md max-h-full">
        <div class="relative bg-neutral-primary-soft border border-default rounded-base shadow-sm p-4 md:p-6">
            <button type="button" class="absolute top-3 end-2.5 text-body bg-transparent hover:bg-neutral-tertiary hover:text-heading rounded-base text-sm w-9 h-9 ms-auto inline-flex justify-center items-center" data-modal-hide="modal-retry-{{ $c->id }}">
                <svg class="w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 17.94 6M18 18 6.06 6"/></svg>
                <span class="sr-only">Close modal</span>
            </button>
            <div class="p-4 md:p-5 text-center">
                <svg class="mx-auto mb-4 text-fg-disabled w-12 h-12" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 13V8m0 8h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                <h3 class="mb-6 text-body">Apakah Anda yakin ingin mengulang (retry) semua file yang gagal di <strong class="text-ink">{{ $c->nama }}</strong>?</h3>
                <div class="flex items-center space-x-4 justify-center">
                    {{-- Tombol ini memicu JS untuk submit form tersembunyi --}}
                    <button type="button" onclick="document.getElementById('form-retry-{{ $c->id }}').submit();" data-modal-hide="modal-retry-{{ $c->id }}" class="text-white bg-brand box-border border border-transparent hover:bg-brand-strong focus:ring-4 focus:ring-brand-medium shadow-xs font-medium leading-5 rounded-base text-sm px-4 py-2.5 focus:outline-none">
                        Ya, retry
                    </button>
                    <button type="button" data-modal-hide="modal-retry-{{ $c->id }}" class="text-body bg-neutral-secondary-medium box-border border border-default-medium hover:bg-neutral-tertiary-medium hover:text-heading focus:ring-4 focus:ring-neutral-tertiary shadow-xs font-medium leading-5 rounded-base text-sm px-4 py-2.5 focus:outline-none">
                        Tidak, batalkan
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Modal: Konfirmasi Hapus --}}
<div id="modal-hapus-{{ $c->id }}" tabindex="-1" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
    <div class="relative p-4 w-full max-w-md max-h-full">
        <div class="relative bg-neutral-primary-soft border border-default rounded-base shadow-sm p-4 md:p-6">
                <button type="button" class="absolute top-3 end-2.5 text-body bg-transparent hover:bg-neutral-tertiary hover:text-heading rounded-base text-sm w-9 h-9 ms-auto inline-flex justify-center items-center" data-modal-hide="modal-hapus-{{ $c->id }}">
                    <svg class="w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 17.94 6M18 18 6.06 6"/></svg>
                    <span class="sr-only">Close modal</span>
                </button>
            <div class="p-4 md:p-5 text-center">
                <svg class="mx-auto mb-4 text-fg-disabled w-12 h-12" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 13V8m0 8h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                <h3 class="mb-6 text-body">Apakah anda yakin akan menghapus <strong class="text-ink">{{ $c->nama }}</strong> dari data? File yang berstatus pending atau failed akan dipindahkan ke komputer lain.</h3>
                
                <form action="{{ route('komputer.destroy', $c->id) }}" method="DELETE">
                    @csrf
                    @method('DELETE')
                    <div class="flex items-center space-x-4 justify-center">
                        <button type="submit" class="text-white bg-danger box-border border border-transparent hover:bg-danger-strong focus:ring-4 focus:ring-danger-medium shadow-xs font-medium leading-5 rounded-base text-sm px-4 py-2.5 focus:outline-none">
                            Ya, hapus
                        </button>
                        <button type="button" data-modal-hide="modal-hapus-{{ $c->id }}" class="text-body bg-neutral-secondary-medium box-border border border-default-medium hover:bg-neutral-tertiary-medium hover:text-heading focus:ring-4 focus:ring-neutral-tertiary shadow-xs font-medium leading-5 rounded-base text-sm px-4 py-2.5 focus:outline-none">
                            Tidak, batalkan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endforeach

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
                {{-- Nama komputer --}}
                <div class="flex items-start gap-3">
                    <div class="inline-flex size-10 shrink-0 items-center justify-center rounded-full bg-success-soft text-success-strong">
                        <x-lucide-check class="size-5" aria-hidden="true" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm text-ink-muted">Nama komputer</p>
                        <p id="token-nama" class="truncate font-semibold text-ink" title="-">-</p>
                    </div>
                </div>

                {{-- Status koneksi --}}
                <div class="mt-4 flex items-center justify-between gap-3 rounded-lg border border-line bg-muted/40 p-3 text-sm">
                    <span class="text-ink-muted">Status koneksi</span>

                    <span id="statuskoneksi"
                        class="inline-flex items-center gap-1.5 rounded-full bg-muted px-2.5 py-0.5 text-xs font-medium text-ink-muted">
                        <span id="statuskoneksi-circle" class="size-1.5 rounded-full bg-ink-muted animate-ping" aria-hidden="true"></span>
                        <span id="statuskoneksi-text">Not connected</span>
                    </span>
                </div>

                {{-- Token --}}
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
                    <button type="button" data-modal-hide="modal-token-komputer" onclick="location.reload()"
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
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const endpoint  = "{{ route('komputer.get.filestatus') }}"; // otomatis jadi URL /komputer/files/status
    const INTERVAL_MS = 5000; // 5 detik

    async function checkFilesStatus() {
        try {
            const response = await fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                }
            });

            if (!response.ok) {
                console.warn('Response tidak OK:', response.status);
                return;
            }

            const data = await response.json();

            const files = data.data;
            const keys = Object.keys(files)

            for(let i of keys){
                document.getElementById(`file-status-pending-${keys}`).textContent = files[keys].pending ?? 0;
                document.getElementById(`file-status-processing-${keys}`).textContent = files[keys].processing ?? 0;
                document.getElementById(`file-status-complited-${keys}`).textContent = files[keys].complited ?? 0;
                document.getElementById(`file-status-failed-${keys}`).textContent = files[keys].failed ?? 0;
            }

        } catch (err) {
            console.error('Gagal request status:', err);
        }
    }

    // jalankan langsung sekali, lalu tiap interval
    checkFilesStatus();
    const intervalId = setInterval(checkFilesStatus, INTERVAL_MS);

    // opsional: bersihkan saat halaman ditutup
    window.addEventListener('beforeunload', () => clearInterval(intervalId));
});
</script>

<script>
    document.addEventListener('DOMContentLoaded', function() {

        // ===== Pencarian komputer =====
        const inputCari = document.getElementById('komputer-search');
        const tabel = document.getElementById('tabel-komputer');
        const kosong = document.getElementById('komputer-empty');

        if (inputCari && tabel) {
            const baris = Array.from(tabel.querySelectorAll('tbody tr[data-row]'));
            inputCari.addEventListener('input', function() {
                const kata = this.value.trim().toLowerCase();
                let tampil = 0;
                baris.forEach(function(tr) {
                    const cocok = tr.textContent.toLowerCase().includes(kata);
                    tr.classList.toggle('hidden', !cocok);
                    if (cocok) tampil++;
                });
                kosong.classList.toggle('hidden', tampil !== 0);
            });
        }

        // ===== Tambah komputer -> buat kunci -> tampilkan token =====
        const form = document.getElementById('form-tambah-komputer');
        const btnBuat = document.getElementById('btn-buat-key');
        const labelBuat = document.getElementById('btn-buat-label');
        const loadingBuat = document.getElementById('btn-buat-loading');
        const closeTambah = document.getElementById('close-tambah');
        const triggerToken = document.getElementById('trigger-token');
        const namaInput = document.getElementById('nama-komputer');
        const tokenEl = document.getElementById('token-value');
        const namaEl = document.getElementById('token-nama');

        if (form && btnBuat && triggerToken) {
            form.addEventListener('submit', async function(e) {
                e.preventDefault();
                const nama = namaInput.value.trim();
                if (!nama) return;

                // State loading
                btnBuat.disabled = true;
                labelBuat.classList.add('hidden');
                labelBuat.classList.remove('inline-flex');
                loadingBuat.classList.remove('hidden');
                loadingBuat.classList.add('inline-flex');

                // Helper reset tombol
                function resetButton() {
                    btnBuat.disabled = false;
                    loadingBuat.classList.add('hidden');
                    loadingBuat.classList.remove('inline-flex');
                    labelBuat.classList.remove('hidden');
                    labelBuat.classList.add('inline-flex');
                }

                try {
                    const responses = await fetch("{{ route('komputer.post') }}", {
                        method: "POST",
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({
                            name: nama
                        })
                    });

                    const data = await responses.json();

                    if (!responses.ok) {
                        console.error('Gagal membuat komputer:', data);
                        resetButton();
                        return;
                    }

                    // Set data ke modal token
                    tokenEl.textContent = data.token ?? '-';
                    namaEl.textContent = nama;
                    namaEl.setAttribute('title', nama);

                    resetButton();
                    namaInput.value = '';

                    // Tutup modal tambah, buka modal token lewat handler Flowbite
                    closeTambah.click();
                    triggerToken.click();

                    // ===== Polling status koneksi =====
                    const statusContainer = document.getElementById('statuskoneksi');
                    const statusCircle = document.getElementById('statuskoneksi-circle');
                    const statusText = document.getElementById('statuskoneksi-text');

                    // Guard: pastikan elemen ada & data.id valid
                    if (!statusContainer || !statusCircle || !statusText || !data.id) {
                        console.warn('Polling dilewati: elemen status tidak lengkap atau data.id kosong.');
                        return;
                    }

                    function setDisconnected() {
                        statusContainer.className =
                            'inline-flex items-center gap-1.5 rounded-full bg-danger-soft px-2.5 py-0.5 text-xs font-medium text-danger-strong';
                        statusCircle.className = 'size-1.5 rounded-full bg-danger animate-ping';
                        statusText.textContent = 'Not connected';
                    }

                    function setConnected() {
                        statusContainer.className =
                            'inline-flex items-center gap-1.5 rounded-full bg-success-soft px-2.5 py-0.5 text-xs font-medium text-success-strong';
                        statusCircle.className = 'size-1.5 rounded-full bg-success';
                        statusText.textContent = 'Connected';
                    }

                    // Set state awal sebelum polling
                    setDisconnected();

                    let tick = 0;
                    const MAX_TICK = 30; // ~60 detik @2s

                    const intervalId = setInterval(async () => {
                        tick++;

                        try {
                            const params = new URLSearchParams({
                                comid: data.id
                            });

                            const res = await fetch(
                                `{{ route('komputer.status') }}?${params.toString()}`, {
                                    method: 'GET',
                                    headers: {
                                        'Accept': 'application/json',
                                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                    }
                                }
                            );

                            const statusData = await res.json();
                            console.log('[polling status]', statusData);

                            const isConnected =
                                statusData.status === true ||
                                statusData.status === 1 ||
                                statusData.status === 'connected';

                            if (isConnected) {
                                setConnected();
                                clearInterval(intervalId);
                                console.log('✅ Connected — polling dihentikan.');
                                setTimeout(() => {
                                    location.reload();
                                }, 5000);
                                return;
                            }

                            setDisconnected();

                            if (tick >= MAX_TICK) {
                                clearInterval(intervalId);
                                console.log('⏱ Polling timeout.');
                            }
                        } catch (err) {
                            console.error('Polling error:', err);
                            clearInterval(intervalId);
                        }
                    }, 2000);

                } catch (err) {
                    console.error('Gagal membuat komputer:', err);
                    resetButton();
                }
            });
        }

        // ===== Salin token =====
        const copyBtn = document.getElementById('btn-copy-token');
        const copyLabel = document.getElementById('copy-label');

        if (copyBtn) {
            copyBtn.addEventListener('click', function() {
                const token = tokenEl.textContent || '';
                if (!token || token === '-') return;

                navigator.clipboard.writeText(token).then(function() {
                    copyLabel.textContent = 'Tersalin';
                    setTimeout(function() {
                        copyLabel.textContent = 'Copy';
                    }, 2000);
                });
            });
        }
    });
</script>

{{-- Status koneksi realtime: polling ringan tiap 3 detik, tanpa reload halaman. --}}
<script>
    (function() {
        const tabel = document.getElementById('tabel-komputer');
        const info = document.getElementById('komputer-koneksi-info');
        if (!tabel) return;

        const endpoint = @json(route('komputer.status.all'));
        const INTERVAL = 3000;

        const gaya = {
            'connected': { badge: 'bg-success-soft text-success-strong', dot: 'bg-success', label: 'Connected' },
            'pending': { badge: 'bg-warning-soft text-warning-strong', dot: 'bg-warning', label: 'Pending' },
            'warning': { badge: 'bg-danger-soft text-danger-strong', dot: 'bg-danger', label: 'Warning' },
            'shutdown': { badge: 'bg-muted text-ink-muted', dot: 'bg-ink-muted', label: 'Shutdown' },
            'not connected': { badge: 'bg-muted text-ink-muted', dot: 'bg-ink-muted', label: 'Not connected' },
        };
        const kelasBadge = 'inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium ';

        const barisPer = {};
        tabel.querySelectorAll('tbody tr[data-computer-id]').forEach(function(tr) {
            barisPer[tr.dataset.computerId] = tr;
        });

        function terapkanStatus(tr, status) {
            const g = gaya[status] || gaya['not connected'];
            const badge = tr.querySelector('[data-status-badge]');
            const dot = tr.querySelector('[data-status-dot]');
            const text = tr.querySelector('[data-status-text]');
            if (badge) badge.className = kelasBadge + g.badge;
            if (dot) dot.className = 'size-1.5 rounded-full ' + g.dot;
            if (text) text.textContent = g.label;
        }

        function terapkanAntrean(tr, queue) {
            ['pending', 'processing', 'completed', 'failed'].forEach(function(kunci) {
                const el = tr.querySelector('[data-queue="' + kunci + '"]');
                if (el && queue && queue[kunci] !== undefined) el.textContent = queue[kunci];
            });
        }

        function jam(iso) {
            const d = new Date(iso);
            return isNaN(d) ? '-' : d.toLocaleTimeString('id-ID', {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            });
        }

        async function tarikStatus() {
            try {
                const res = await fetch(endpoint, {
                    headers: { 'Accept': 'application/json' }
                });

                if (res.status === 401) {
                    if (info) {
                        info.className = 'mt-4 text-sm font-medium text-danger-strong';
                        info.textContent = 'Sesi login berakhir. Muat ulang halaman lalu login kembali untuk melihat status terbaru.';
                    }
                    return;
                }

                if (!res.ok) throw new Error('HTTP ' + res.status);

                const data = await res.json();
                const daftar = data.computers || [];

                daftar.forEach(function(c) {
                    const tr = barisPer[c.id];
                    if (!tr) return;
                    terapkanStatus(tr, c.status);
                    terapkanAntrean(tr, c.queue);
                });

                const tersambung = daftar.filter(c => c.status === 'connected').length;

                if (info) {
                    if (tersambung > 0) {
                        info.className = 'mt-4 text-sm text-ink-muted';
                        info.textContent = tersambung + ' dari ' + daftar.length +
                            ' komputer terhubung ke server • diperbarui ' + jam(data.server_time);
                    } else {
                        info.className = 'mt-4 text-sm font-medium text-amber-700';
                        info.textContent = 'Belum ada komputer yang terhubung. Jalankan PrinterClient di komputer target, ' +
                            'status akan berubah otomatis di sini.';
                    }
                }
            } catch (err) {
                if (info) {
                    info.className = 'mt-4 text-sm font-medium text-danger-strong';
                    info.textContent = 'Gagal memperbarui status (server tidak merespons). Mencoba lagi…';
                }
                console.error('[status komputer]', err);
            }
        }

        let timer = null;

        function mulai() {
            if (timer) return;
            tarikStatus();
            timer = setInterval(tarikStatus, INTERVAL);
        }

        function henti() {
            clearInterval(timer);
            timer = null;
        }

        // hemat request saat tab dashboard tidak sedang dilihat
        document.addEventListener('visibilitychange', function() {
            document.hidden ? henti() : mulai();
        });

        mulai();
    })();
</script>
@endsection
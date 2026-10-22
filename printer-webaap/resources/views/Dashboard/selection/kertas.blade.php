@extends('Dashboard.layout')

@section('title', 'Kertas')

@section('content')
<div class="w-full">

    @if(session('success'))
    <x-toast type="success" :message="session('success')" />
    @endif
    @if(session('error'))
    <x-toast type="error" :message="session('error')" />
    @endif
    <!-- Main modal -->
    <div id="kertas" data-modal-backdrop="static" tabindex="-1" aria-hidden="true" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
        <div class="relative p-4 w-full max-w-md max-h-full">
            <!-- Modal content -->
            <div class="relative bg-white rounded-lg shadow-sm">
                <!-- Modal header -->
                <div class="flex items-center justify-between p-4 md:p-5 border-b rounded-t border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">
                        Tambah Kertas Baru
                    </h3>
                    <button type="button" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center" data-modal-hide="kertas">
                        <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6" />
                        </svg>
                        <span class="sr-only">Close modal</span>
                    </button>
                </div>
                <!-- Modal body -->
                <form action="{{route('kertas.store')}}" method="POST" class="p-4 md:p-5">
                    @csrf
                    <div class="grid gap-4 mb-4 grid-cols-2">
                        <!-- Input Nama Kertas -->
                        <div class="col-span-2">
                            <label for="name" class="block mb-2 text-sm font-medium text-gray-900">Nama Kertas</label>
                            <input type="text" name="name" id="name" maxlength="50" minlength="1" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5" placeholder="Contoh: Art Paper 150gr" required>
                        </div>

                        <!-- Input Width -->
                        <div class="col-span-2 sm:col-span-1">
                            <label for="width" class="block mb-2 text-sm font-medium text-gray-900">Width (Lebar)</label>
                            <div class="relative">
                                <input type="number" name="width" id="width" min="20" step="1" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 pe-12" placeholder="Contoh: 21" required>
                                <span class="absolute inset-y-0 end-0 flex items-center pe-3 text-sm text-gray-500 pointer-events-none">mm</span>
                            </div>
                        </div>

                        <!-- Input Height -->
                        <div class="col-span-2 sm:col-span-1">
                            <label for="height" class="block mb-2 text-sm font-medium text-gray-900">Height (Tinggi)</label>
                            <div class="relative">
                                <input type="number" name="height" id="height" min="23" step="1" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 pe-12" placeholder="Contoh: 30" required>
                                <span class="absolute inset-y-0 end-0 flex items-center pe-3 text-sm text-gray-500 pointer-events-none">mm</span>
                            </div>
                        </div>

                        <!-- Input Harga Color -->
                        <div class="col-span-2 sm:col-span-1">
                            <label for="harga_color" class="block mb-2 text-sm font-medium text-gray-900">Harga Berwarna</label>
                            <input type="number" name="harga_color" id="harga_color" min="1" step="1" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5" placeholder="Rp 5000" required>
                        </div>

                        <!-- Input Harga Grayscale -->
                        <div class="col-span-2 sm:col-span-1">
                            <label for="harga_grayscale" class="block mb-2 text-sm font-medium text-gray-900">Harga Hitam Putih</label>
                            <input type="number" name="harga_grayscale" id="harga_grayscale" min="1" step="1" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5" placeholder="Rp 2000" required>
                        </div>

                        <!-- Toggle Switch Active -->
                        <div class="col-span-2">
                            <label class="inline-flex items-center cursor-pointer">
                                <input type="hidden" name="aktif" value="0">
                                <input type="checkbox" name="aktif" value="1" class="sr-only peer" checked>
                                <!-- Track -->
                                <div class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-green-300 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-600"></div>
                                <!-- Label -->
                                <span class="ms-3 text-sm font-medium text-gray-900">Kertas status</span>
                            </label>
                            <p class="mt-1 text-xs text-gray-500">Matikan jika anda tidak mau kertas tampil di halaman user</p>
                        </div>
                    </div>

                    <!-- Tombol Simpan -->
                    <div class="w-full flex justify-end">
                        <button type="submit" class="text-white inline-flex justify-end items-center bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center">
                            <svg class="me-1 -ms-1 w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd"></path>
                            </svg>
                            Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- Modal Edit -->
    <div id="edit-kertas" data-modal-backdrop="static" tabindex="-1" aria-hidden="true" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
        <div class="relative p-4 w-full max-w-md max-h-full">
            <!-- Modal content -->
            <div class="relative bg-white rounded-lg shadow-sm">
                <!-- Modal header -->
                <div class="flex items-center justify-between p-4 md:p-5 border-b rounded-t border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">
                        Edit Kertas
                    </h3>
                    <button type="button" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center" data-modal-hide="edit-kertas">
                        <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6" />
                        </svg>
                        <span class="sr-only">Close modal</span>
                    </button>
                </div>
                <!-- Modal body -->
                <form id="form-edit-kertas" action="" method="POST" class="p-4 md:p-5">
                    @csrf
                    @method('PUT')
                    <div class="grid gap-4 mb-4 grid-cols-2">
                        <!-- Input Nama Kertas -->
                        <div class="col-span-2">
                            <label for="edit-name" class="block mb-2 text-sm font-medium text-gray-900">Nama Kertas</label>
                            <input type="text" name="name" id="edit-name" maxlength="50" minlength="1" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5" placeholder="Contoh: Art Paper 150gr" required>
                        </div>

                        <!-- Input Width -->
                        <div class="col-span-2 sm:col-span-1">
                            <label for="edit-width" class="block mb-2 text-sm font-medium text-gray-900">Width (Lebar)</label>
                            <div class="relative">
                                <input type="number" name="width" id="edit-width" min="20" step="1" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 pe-12" placeholder="Contoh: 21" required>
                                <span class="absolute inset-y-0 end-0 flex items-center pe-3 text-sm text-gray-500 pointer-events-none">mm</span>
                            </div>
                        </div>

                        <!-- Input Height -->
                        <div class="col-span-2 sm:col-span-1">
                            <label for="edit-height" class="block mb-2 text-sm font-medium text-gray-900">Height (Tinggi)</label>
                            <div class="relative">
                                <input type="number" name="height" id="edit-height" min="23" step="1" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 pe-12" placeholder="Contoh: 30" required>
                                <span class="absolute inset-y-0 end-0 flex items-center pe-3 text-sm text-gray-500 pointer-events-none">mm</span>
                            </div>
                        </div>

                        <!-- Input Harga Color -->
                        <div class="col-span-2 sm:col-span-1">
                            <label for="edit-harga-color" class="block mb-2 text-sm font-medium text-gray-900">Harga Berwarna</label>
                            <input type="number" name="harga_color" id="edit-harga-color" min="1" step="1" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5" placeholder="Rp 5000" required>
                        </div>

                        <!-- Input Harga Grayscale -->
                        <div class="col-span-2 sm:col-span-1">
                            <label for="edit-harga-grayscale" class="block mb-2 text-sm font-medium text-gray-900">Harga Hitam Putih</label>
                            <input type="number" name="harga_grayscale" id="edit-harga-grayscale" min="1" step="1" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5" placeholder="Rp 2000" required>
                        </div>

                        <!-- Toggle Switch Active -->
                        <div class="col-span-2">
                            <label class="inline-flex items-center cursor-pointer">
                                <input type="hidden" name="aktif" value="0">
                                <input type="checkbox" name="aktif" value="1" id="edit-aktif" class="sr-only peer">
                                <div class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-green-300 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-600"></div>
                                <span class="ms-3 text-sm font-medium text-gray-900">Kertas status</span>
                            </label>
                            <p class="mt-1 text-xs text-gray-500">Matikan jika anda tidak mau kertas tampil di halaman user</p>
                        </div>
                    </div>

                    <!-- Tombol -->
                    <div class="w-full flex justify-start gap-3">
                        <button type="button" data-modal-hide="edit-kertas" data-modal-target="delete-kertas" data-modal-show="delete-kertas" class=" mr-auto text-white inline-flex items-center bg-danger border border-gray-300 hover:bg-danger-strong focus:ring-4 focus:outline-none focus:ring-gray-200 font-medium rounded-lg text-sm px-5 py-2.5">
                            <x-lucide-trash class="w-5 h-5" />
                        </button>
                        <button type="button" data-modal-hide="edit-kertas" class="text-gray-900 inline-flex items-center bg-white border border-gray-300 hover:bg-gray-100 focus:ring-4 focus:outline-none focus:ring-gray-200 font-medium rounded-lg text-sm px-5 py-2.5">
                            Batal
                        </button>
                        <button type="submit" class="text-white inline-flex items-center bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center">
                            <x-lucide-save class="me-1 -ms-1 w-5 h-5" />
                            Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal konfirmasi hapus kertas -->
    <div id="delete-kertas" tabindex="-1" aria-hidden="true" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
        <div class="relative p-4 w-full max-w-md max-h-full">
            <div class="relative bg-neutral-primary-soft border border-default rounded-base shadow-sm p-4 md:p-6">
                <button type="button" class="absolute top-3 end-2.5 text-body bg-transparent hover:bg-neutral-tertiary hover:text-heading rounded-base text-sm w-9 h-9 ms-auto inline-flex justify-center items-center" data-modal-hide="delete-kertas">
                    <svg class="w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 17.94 6M18 18 6.06 6" />
                    </svg>
                    <span class="sr-only">Close modal</span>
                </button>
                <div class="p-4 md:p-5 text-center">
                    <svg class="mx-auto mb-4 text-fg-disabled w-12 h-12" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v4m0 4h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                    <h3 class="mb-2 text-lg font-semibold text-heading">Hapus kertas?</h3>
                    <p class="mb-6 text-sm text-body">Kertas <span id="delete-kertas-nama" class="font-medium text-heading"></span> akan dihapus permanen. Apakah kamu yakin?</p>
                    <div class="flex items-center space-x-4 justify-center">
                        <button type="button" id="delete-kertas-ya" data-modal-hide="delete-kertas" class="text-white bg-danger box-border border border-transparent hover:bg-danger-strong focus:ring-4 focus:ring-danger-medium shadow-xs font-medium leading-5 rounded-base text-sm px-4 py-2.5 focus:outline-none">
                            Iya, hapus
                        </button>
                        <button type="button" data-modal-hide="delete-kertas" class="text-body bg-neutral-secondary-medium box-border border border-default-medium hover:bg-neutral-tertiary-medium hover:text-heading focus:ring-4 focus:ring-neutral-tertiary shadow-xs font-medium leading-5 rounded-base text-sm px-4 py-2.5 focus:outline-none">Tidak</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Template toast untuk hasil hapus (di-clone lewat JavaScript) --}}
    <template id="hapus-toast-success">
        <x-toast type="success" id="hapus-sukses" message="" />
    </template>
    <template id="hapus-toast-error">
        <x-toast type="error" id="hapus-gagal" message="" />
    </template>

    {{-- Header --}}
    <div class="flex items-start justify-between gap-4">
        <h1 class="text-2xl font-semibold text-ink">Kertas</h1>
        <p class="text-sm text-ink-muted">{{ now()->translatedFormat('l, j F Y') }}</p>
    </div>

    {{-- Toolbar --}}
    <div class="mt-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <button type="button" data-modal-target="kertas" data-modal-toggle="kertas"
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
                <button type="button" data-modal-target="edit-kertas" data-modal-toggle="edit-kertas"
                    data-id="{{ $k['id'] }}"
                    data-name="{{ $k['name'] }}"
                    data-width="{{ $k['width'] }}"
                    data-height="{{ $k['height'] }}"
                    data-harga-color="{{ $k['harga_color_raw'] }}"
                    data-harga-grayscale="{{ $k['harga_grayscale_raw'] }}"
                    data-aktif="{{ $k['aktif'] ? 1 : 0 }}"
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
    document.addEventListener('DOMContentLoaded', function() {
        // Isi modal edit sesuai data kartu yang tombol Edit-nya diklik.
        const formEdit = document.getElementById('form-edit-kertas');
        const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        // Menampilkan toast berdasarkan komponen x-toast (di-clone dari <template>).
        function showToast(type, message) {
            const tpl = document.getElementById(type === 'error' ? 'hapus-toast-error' : 'hapus-toast-success');
            if (!tpl) return;

            const toast = tpl.content.firstElementChild.cloneNode(true);
            const pesan = toast.querySelector('.ms-3');
            if (pesan) pesan.textContent = message;

            document.body.appendChild(toast);

            const tombolTutup = toast.querySelector('button[aria-label="Close"]');
            if (tombolTutup) {
                tombolTutup.addEventListener('click', function() {
                    toast.remove();
                });
            }
        }

        let sedangMenghapus = false;
        async function destroy(id) {
            if (!id || sedangMenghapus) return false;
            sedangMenghapus = true;

            try {
                let remove = null;
                try {
                    remove = await fetch("{{route('kertas.delete')}}", {
                        method: "DELETE",
                        headers: {
                            "X-CSRF-TOKEN": token,
                            "Accept": "application/json",
                            "Content-Type": "application/json"
                        },
                        body: JSON.stringify({
                            id: id
                        })
                    })
                } catch (e) {
                    remove = null;
                }

                let data = null;
                if (remove) {
                    try { data = await remove.json(); } catch (e) { data = null; }
                }

                if (!remove || !remove.ok) {
                    showToast('error', (data && data.message) ? data.message : 'Kertas gagal dihapus.');
                    return false;
                }

                const editBtn = document.querySelector('[data-modal-target="edit-kertas"][data-id="' + id + '"]');
                const card = editBtn ? editBtn.closest('[data-card]') : null;
                if (card) card.remove();

                const kosong = document.getElementById('kertas-empty');
                if (kosong) {
                    kosong.classList.toggle('hidden', document.querySelectorAll('[data-card]:not(.hidden)').length !== 0);
                }

                showToast('success', 'Kertas berhasil dihapus.');
                return true;
            } finally {
                sedangMenghapus = false;
            }
        }

        // Tombol "Iya, hapus" pada modal konfirmasi hapus kertas.
        const tombolHapusKertas = document.getElementById('delete-kertas-ya');
        if (tombolHapusKertas) {
            tombolHapusKertas.addEventListener('click', function() {
                destroy(formEdit ? formEdit.dataset.id : '');
            });
        }

        document.querySelectorAll('[data-modal-target="edit-kertas"]').forEach(function(button) {
            button.addEventListener('click', function() {
                if (!formEdit) return;

                // Simpan id kertas yang sedang diedit, dipakai saat konfirmasi hapus.
                formEdit.dataset.id = button.dataset.id || '';
                const namaTarget = document.getElementById('delete-kertas-nama');
                if (namaTarget) namaTarget.textContent = button.dataset.name || '';

                formEdit.action = "{{ route('kertas.perbarui', ['id' => '__ID__']) }}".replace('__ID__', button.dataset.id);

                document.getElementById('edit-name').value = button.dataset.name || '';
                document.getElementById('edit-width').value = button.dataset.width || '';
                document.getElementById('edit-height').value = button.dataset.height || '';
                document.getElementById('edit-harga-color').value = button.dataset.hargaColor || '';
                document.getElementById('edit-harga-grayscale').value = button.dataset.hargaGrayscale || '';
                document.getElementById('edit-aktif').checked = button.dataset.aktif === '1';
            });
        });

        const input = document.getElementById('kertas-search');
        const kosong = document.getElementById('kertas-empty');
        if (!input) return;

        input.addEventListener('input', function() {
            const kata = this.value.trim().toLowerCase();
            let tampil = 0;

            // querySelectorAll dipanggil ulang supaya kartu yang sudah dihapus
            // tidak ikut dihitung saat pencarian.
            document.querySelectorAll('[data-card]').forEach(function(card) {
                const cocok = card.textContent.toLowerCase().includes(kata);
                card.classList.toggle('hidden', !cocok);
                if (cocok) tampil++;
            });

            kosong.classList.toggle('hidden', tampil !== 0);
        });
    });
</script>
@endsection
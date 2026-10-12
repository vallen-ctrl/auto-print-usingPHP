@extends('Dashboard.layout')

@section('title', 'Layanan')



@section('content')
<div class="w-full">
    @if(session('success'))
    <div id="toast-success" class="flex fixed bottom-5 right-5 items-center w-full max-w-sm p-4 text-body bg-neutral-primary-soft rounded-base shadow-xs border border-default" role="alert">
        <div class="inline-flex items-center justify-center shrink-0 w-7 h-7 text-fg-success bg-success-soft rounded">
            <svg class="w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 11.917 9.724 16.5 19 7.5" />
            </svg>
            <span class="sr-only">Check icon</span>
        </div>
        <div class="ms-3 text-sm font-normal">{{ session('success') }}</div>
        <button type="button" class="ms-auto flex items-center justify-center text-body hover:text-heading bg-transparent box-border border border-transparent hover:bg-neutral-secondary-medium focus:ring-4 focus:ring-neutral-tertiary font-medium leading-5 rounded text-sm h-8 w-8 focus:outline-none" data-dismiss-target="#toast-success" aria-label="Close">
            <span class="sr-only">Close</span>
            <svg class="w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 17.94 6M18 18 6.06 6" />
            </svg>
        </button>
    </div>
    @endif
    @if ($errors->any())
    <div class="mb-4 p-3 text-sm text-red-800 bg-red-50 border border-red-200 rounded-lg" role="alert">
        <ul class="list-disc ms-4">
            @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <!-- Main modal -->

    <div id="layanan" data-modal-backdrop="static" tabindex="-1" aria-hidden="true" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
        <div class="relative p-4 w-full max-w-md max-h-full">
            <!-- Modal content -->
            <div class="relative bg-white rounded-lg shadow-sm">
                <!-- Modal header -->
                <div class="flex items-center justify-between p-4 md:p-5 border-b rounded-t border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">
                        Tambah Layanan Baru
                    </h3>
                    <button type="button" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center" data-modal-hide="layanan">
                        <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6" />
                        </svg>
                        <span class="sr-only">Close modal</span>
                    </button>
                </div>
                <!-- Modal body -->
                <form action="{{route('layanan.post')}}" method="POST" enctype="multipart/form-data" class="p-4 md:p-5">
                    @csrf
                    <div class="grid gap-4 mb-4 grid-cols-2">
                        <!-- Upload Foto -->
                        <div class="col-span-2">
                            <label class="block mb-2 text-sm font-medium text-gray-900">Upload Foto</label>

                            <div id="dropzone-wrapper" class="flex items-center justify-center w-full">
                                <label for="gambar" class="flex flex-col items-center justify-center w-full h-48 bg-gray-50 border-2 border-dashed border-gray-300 rounded-lg cursor-pointer hover:bg-gray-100 transition-colors">
                                    <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                        <svg class="w-8 h-8 mb-4 text-gray-500" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h3a3 3 0 0 0 0-6h-.025a5.56 5.56 0 0 0 .025-.5A5.5 5.5 0 0 0 7.207 9.021C7.137 9.017 7.071 9 7 9a4 4 0 1 0 0 8h2.167M12 19v-9m0 0-2 2m2-2 2 2" />
                                        </svg>
                                        <p class="mb-2 text-sm text-gray-500"><span class="font-semibold">Klik untuk upload</span> atau drag and drop</p>
                                        <p class="text-xs text-gray-500">PNG atau JPEG (Maks. 2MB)</p>
                                    </div>
                                    <p id="gambar-error" class="hidden mt-1 text-xs text-red-600"></p>
                                    <input id="gambar" name="gambar" type="file" class="hidden" accept="image/png, image/jpeg" onchange="previewImage(event)" />
                                </label>
                            </div>

                            <div id="image-preview-container" class="hidden relative">
                                <img id="image-preview" src="#" alt="Preview" class="w-full h-48 object-cover rounded-lg border border-gray-200 shadow-sm">
                                <button type="button" onclick="removeImage()" class="absolute top-2 right-2 bg-white/90 hover:bg-white text-gray-700 hover:text-red-600 rounded-full p-1.5 shadow-md focus:outline-none transition-colors" title="Hapus gambar">
                                    <x-lucide-x class="w-4 h-4" />
                                </button>
                            </div>
                        </div>

                        <!-- Input Nama -->
                        <div class="col-span-2">
                            <label for="nama" class="block mb-2 text-sm font-medium text-gray-900">Nama Layanan</label>
                            <input type="text" name="nama" id="nama" maxlength="50" value="{{ old('nama') }}" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5" placeholder="Nama layanan" required>
                            @error('nama') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <!-- Input Harga -->
                        <div class="col-span-2 sm:col-span-1">
                            <label for="harga" class="block mb-2 text-sm font-medium text-gray-900">Harga</label>
                            <input type="number" name="harga" id="harga" step="1" min="0" value="{{ old('harga') }}" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5" placeholder="Rp 2999" required>
                            @error('harga') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <!-- Input Jumlah -->
                        <div class="col-span-2 sm:col-span-1">
                            <label for="jumlah" class="block mb-2 text-sm font-medium text-gray-900">Jumlah</label>
                            <input type="number" name="jumlah" id="jumlah" step="1" min="0" value="{{ old('jumlah') }}" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5" placeholder="100" required>
                            @error('jumlah') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <!-- Toggle Switch Aktif -->
                        <div class="col-span-2">
                            <label class="inline-flex items-center cursor-pointer">
                                <input type="hidden" name="aktif" value="0">
                                <input type="checkbox" name="aktif" value="1" class="sr-only peer" checked>
                                <div class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-green-300 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-600"></div>
                                <span class="ms-3 text-sm font-medium text-gray-900">Aktif</span>
                            </label>
                            <p class="mt-1 text-xs text-gray-500">Centang jika layanan aktif, kosongkan jika nonaktif.</p>
                        </div>

                        <!-- Input Deskripsi -->
                        <div class="col-span-2">
                            <label for="deskripsi" class="block mb-2 text-sm font-medium text-gray-900">Deskripsi</label>
                            <textarea id="deskripsi" name="deskripsi" rows="4" class="block p-2.5 w-full text-sm text-gray-900 bg-gray-50 rounded-lg border border-gray-300 focus:ring-primary-600 focus:border-primary-600" placeholder="Tulis deskripsi layanan di sini..." required>{{ old('deskripsi') }}</textarea>
                            @error('deskripsi') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <button type="submit" class="text-white inline-flex items-center bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center">
                        <svg class="me-1 -ms-1 w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd"></path>
                        </svg>
                        Simpan
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Edit -->
    <div id="edit-layanan" data-modal-backdrop="static" tabindex="-1" aria-hidden="true" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
        <div class="relative p-4 w-full max-w-md max-h-full">
            <!-- Modal content -->
            <div class="relative bg-white rounded-lg shadow-sm">
                <!-- Modal header -->
                <div class="flex items-center justify-between p-4 md:p-5 border-b rounded-t border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">
                        Edit Layanan
                    </h3>
                    <button type="button" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center" data-modal-hide="edit-layanan">
                        <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6" />
                        </svg>
                        <span class="sr-only">Close modal</span>
                    </button>
                </div>
                <!-- Modal body -->
                <form id="form-edit-layanan" action="" method="POST" enctype="multipart/form-data" class="p-4 md:p-5">
                    @csrf
                    @method('PUT')
                    <div class="grid gap-4 mb-4 grid-cols-2">
                        <!-- Upload Foto -->
                        <div class="col-span-2">
                            <label class="block mb-2 text-sm font-medium text-gray-900">Upload Foto</label>

                            <div id="edit-dropzone-wrapper" class="flex items-center justify-center w-full">
                                <label for="edit-gambar" class="flex flex-col items-center justify-center w-full h-48 bg-gray-50 border-2 border-dashed border-gray-300 rounded-lg cursor-pointer hover:bg-gray-100 transition-colors">
                                    <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                        <svg class="w-8 h-8 mb-4 text-gray-500" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h3a3 3 0 0 0 0-6h-.025a5.56 5.56 0 0 0 .025-.5A5.5 5.5 0 0 0 7.207 9.021C7.137 9.017 7.071 9 7 9a4 4 0 1 0 0 8h2.167M12 19v-9m0 0-2 2m2-2 2 2" />
                                        </svg>
                                        <p class="mb-2 text-sm text-gray-500"><span class="font-semibold">Klik untuk upload</span> atau drag and drop</p>
                                        <p class="text-xs text-gray-500">PNG atau JPEG (Maks. 2MB)</p>
                                    </div>
                                    <p id="edit-gambar-error" class="hidden mt-1 text-xs text-red-600"></p>
                                    <input id="edit-gambar" name="gambar" type="file" class="hidden" accept="image/png, image/jpeg" onchange="previewEditImage(event)" />
                                </label>
                            </div>

                            <div id="edit-image-preview-container" class="hidden relative">
                                <img id="edit-image-preview" src="#" alt="Preview" class="w-full h-48 object-cover rounded-lg border border-gray-200 shadow-sm">
                                <button type="button" onclick="removeEditImage()" class="absolute top-2 right-2 bg-white/90 hover:bg-white text-gray-700 hover:text-red-600 rounded-full p-1.5 shadow-md focus:outline-none transition-colors" title="Ganti gambar">
                                    <x-lucide-x class="w-4 h-4" />
                                </button>
                            </div>
                        </div>

                        <!-- Input Nama -->
                        <div class="col-span-2">
                            <label for="edit-nama" class="block mb-2 text-sm font-medium text-gray-900">Nama Layanan</label>
                            <input type="text" name="nama" id="edit-nama" maxlength="50" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5" placeholder="Nama layanan" required>
                        </div>

                        <!-- Input Harga -->
                        <div class="col-span-2 sm:col-span-1">
                            <label for="edit-harga" class="block mb-2 text-sm font-medium text-gray-900">Harga</label>
                            <input type="number" name="harga" id="edit-harga" step="1" min="0" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5" placeholder="Rp 2999" required>
                        </div>

                        <!-- Input Jumlah -->
                        <div class="col-span-2 sm:col-span-1">
                            <label for="edit-jumlah" class="block mb-2 text-sm font-medium text-gray-900">Jumlah</label>
                            <input type="number" name="jumlah" id="edit-jumlah" step="1" min="0" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5" placeholder="100" required>
                        </div>

                        <!-- Toggle Switch Aktif -->
                        <div class="col-span-2">
                            <label class="inline-flex items-center cursor-pointer">
                                <input type="hidden" name="aktif" value="0">
                                <input type="checkbox" name="aktif" value="1" id="edit-aktif" class="sr-only peer">
                                <div class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-green-300 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-600"></div>
                                <span class="ms-3 text-sm font-medium text-gray-900">Aktif</span>
                            </label>
                            <p class="mt-1 text-xs text-gray-500">Centang jika layanan aktif, kosongkan jika nonaktif.</p>
                        </div>

                        <!-- Input Deskripsi -->
                        <div class="col-span-2">
                            <label for="edit-deskripsi" class="block mb-2 text-sm font-medium text-gray-900">Deskripsi</label>
                            <textarea id="edit-deskripsi" name="deskripsi" rows="4" class="block p-2.5 w-full text-sm text-gray-900 bg-gray-50 rounded-lg border border-gray-300 focus:ring-primary-600 focus:border-primary-600" placeholder="Tulis deskripsi layanan di sini..." required></textarea>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <button type="submit" class="text-white inline-flex items-center bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center">
                            <x-lucide-save class="me-1 -ms-1 w-5 h-5" />
                            Simpan Perubahan
                        </button>
                        <button type="button" data-modal-hide="edit-layanan" class="text-gray-900 inline-flex items-center bg-white border border-gray-300 hover:bg-gray-100 focus:ring-4 focus:outline-none focus:ring-gray-200 font-medium rounded-lg text-sm px-5 py-2.5">
                            Batal
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Header --}}
    <div class="flex items-start justify-between gap-4">
        <h1 class="text-2xl font-semibold text-ink">Layanan</h1>
        <p class="text-sm text-ink-muted">{{ now()->translatedFormat('l, j F Y') }}</p>
    </div>

    {{-- Toolbar --}}
    <div class="mt-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <button type="button" data-modal-target="layanan" data-modal-toggle="layanan"
            class="inline-flex h-10 shrink-0 items-center justify-center gap-2 self-start rounded-lg border border-line bg-surface px-4 text-sm font-medium text-ink transition-colors hover:bg-muted focus:ring-2 focus:ring-primary/30 focus:outline-none">
            <x-lucide-plus class="size-4" aria-hidden="true" />
            Tambah layanan
        </button>

        <div class="relative w-full sm:w-72">
            <label for="layanan-search" class="sr-only">Cari layanan</label>
            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                <x-lucide-search class="size-4 text-ink-muted" aria-hidden="true" />
            </div>
            <input type="search" id="layanan-search" placeholder="Cari..."
                class="block h-10 w-full rounded-lg border border-line bg-surface pl-10 pr-3 text-sm text-ink placeholder:text-ink-muted focus:border-primary focus:ring-2 focus:ring-primary/30 focus:outline-none">
        </div>
    </div>

    {{-- Daftar layanan --}}
    <div class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
        @foreach ($layanan as $l)
        <div data-card class="flex flex-col rounded-xl border border-line bg-surface p-4 shadow-sm">
            @if (!empty($l['gambar']) && $l['gambar'] !== '-' && \Illuminate\Support\Facades\Storage::disk('public')->exists($l['gambar']))
            <img src="{{ asset('storage/' . $l['gambar']) }}" alt="{{ $l['nama'] }}"
                class="h-32 w-full rounded-lg border border-line object-cover">
            @else
            <div class="flex h-32 items-center justify-center rounded-lg border border-dashed border-line bg-muted/40 text-ink-muted">
                <x-lucide-image class="size-8" aria-hidden="true" />
            </div>
            @endif

            <div class="my-3 border-t border-line"></div>

            <dl class="space-y-1.5 text-sm">
                <div class="flex items-center justify-between gap-3">
                    <dt class="font-medium text-ink">{{ $l['nama'] }}</dt>
                    <dd class="font-medium tabular-nums text-ink">{{ $l['harga'] }}</dd>
                </div>
                <div class="flex items-center justify-between gap-3">
                    <dt class="text-ink-muted">Status</dt>
                    <dd>
                        <span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $l['status'] ? 'bg-success-soft text-success-strong' : 'bg-muted text-ink-muted' }}">
                            {{ $l['status'] ? 'Aktif' : 'Nonaktif' }}
                        </span>
                    </dd>
                </div>
                <div class="flex items-center justify-between gap-3">
                    <dt class="text-ink-muted">Stok</dt>
                    <dd class="font-medium tabular-nums text-ink">{{ $l['stok'] }}</dd>
                </div>
                <div class="flex items-center justify-between gap-3">
                    <dt class="text-ink-muted">Total dibeli</dt>
                    <dd class="font-medium text-ink">{{ $l['dibeli'] }}</dd>
                </div>
            </dl>

            <div class="mt-auto pt-4">
                <button type="button" data-modal-target="edit-layanan" data-modal-toggle="edit-layanan"
                    data-id="{{ $l['id'] }}"
                    data-nama="{{ $l['nama'] }}"
                    data-deskripsi="{{ $l['deskripsi'] }}"
                    data-harga="{{ $l['harga_raw'] }}"
                    data-jumlah="{{ $l['jumlah_raw'] }}"
                    data-aktif="{{ $l['status'] ? 1 : 0 }}"
                    data-gambar="{{ $l['gambar'] }}"
                    class="inline-flex h-10 w-full items-center justify-center rounded-lg border border-line bg-surface text-sm font-medium text-ink transition-colors hover:bg-muted focus:ring-2 focus:ring-primary/30 focus:outline-none">
                    Edit
                </button>
            </div>
        </div>
        @endforeach

        {{-- Empty state --}}
        <div id="layanan-empty" class="hidden sm:col-span-2 lg:col-span-3 xl:col-span-4">
            <div class="flex flex-col items-center justify-center gap-2 rounded-xl border border-dashed border-line bg-surface px-6 py-12 text-center">
                <x-lucide-search-x class="size-8 text-ink-muted" aria-hidden="true" />
                <p class="text-sm font-medium text-ink">Layanan tidak ditemukan</p>
                <p class="text-sm text-ink-muted">Coba kata kunci lain.</p>
            </div>
        </div>
    </div>
</div>

<script>
    const MAX_SIZE_MB = 2;
    const ALLOWED_TYPES = ['image/png', 'image/jpeg'];

    function showImageError(msg) {
        const el = document.getElementById('gambar-error');
        el.textContent = msg;
        el.classList.toggle('hidden', !msg);
    }

    function showEditImageError(msg) {
        const el = document.getElementById('edit-gambar-error');
        el.textContent = msg;
        el.classList.toggle('hidden', !msg);
    }

    function validateFile(file) {
        if (!file) return '';
        if (!ALLOWED_TYPES.includes(file.type)) return 'File harus PNG atau JPEG.';
        if (file.size > MAX_SIZE_MB * 1024 * 1024) {
            const sizeMB = (file.size / 1024 / 1024).toFixed(1);
            return `Ukuran gambar ${sizeMB}MB, maksimal ${MAX_SIZE_MB}MB.`;
        }
        return '';
    }

    function showPreview(imgId, wrapperId, containerId, file) {
        const wrapper = document.getElementById(wrapperId);
        const container = document.getElementById(containerId);
        const img = document.getElementById(imgId);

        const reader = new FileReader();
        reader.onload = function(e) {
            img.src = e.target.result;
            wrapper.classList.add('hidden');
            container.classList.remove('hidden');
        };
        reader.readAsDataURL(file);
    }

    function handleFile(file) {
        const error = validateFile(file);
        showImageError(error);
        if (error) return false;

        showPreview('image-preview', 'dropzone-wrapper', 'image-preview-container', file);
        return true;
    }

    function previewImage(event) {
        const input = event.target;
        if (!handleFile(input.files && input.files[0])) {
            input.value = '';
        }
    }

    function removeImage() {
        const input = document.getElementById('gambar');
        const dropzoneWrapper = document.getElementById('dropzone-wrapper');
        const previewContainer = document.getElementById('image-preview-container');
        const previewImg = document.getElementById('image-preview');

        input.value = '';
        previewImg.src = '#';
        dropzoneWrapper.classList.remove('hidden');
        previewContainer.classList.add('hidden');
        showImageError('');
    }

    function handleEditFile(file) {
        const error = validateFile(file);
        showEditImageError(error);
        if (error) return false;

        showPreview('edit-image-preview', 'edit-dropzone-wrapper', 'edit-image-preview-container', file);
        return true;
    }

    function previewEditImage(event) {
        const input = event.target;
        if (!handleEditFile(input.files && input.files[0])) {
            input.value = '';
        }
    }

    function removeEditImage() {
        const input = document.getElementById('edit-gambar');
        const dropzoneWrapper = document.getElementById('edit-dropzone-wrapper');
        const previewContainer = document.getElementById('edit-image-preview-container');
        const previewImg = document.getElementById('edit-image-preview');

        input.value = '';
        previewImg.src = '#';
        dropzoneWrapper.classList.remove('hidden');
        previewContainer.classList.add('hidden');
        showEditImageError('');
    }

    function openEditModal(button) {
        const form = document.getElementById('form-edit-layanan');
        if (!form) return;

        form.action = "{{ route('layanan.perbarui', ['id' => '__ID__']) }}".replace('__ID__', button.dataset.id);

        document.getElementById('edit-nama').value = button.dataset.nama || '';
        document.getElementById('edit-harga').value = button.dataset.harga || '';
        document.getElementById('edit-jumlah').value = button.dataset.jumlah || '';
        document.getElementById('edit-deskripsi').value = button.dataset.deskripsi || '';
        document.getElementById('edit-aktif').checked = button.dataset.aktif === '1';

        const input = document.getElementById('edit-gambar');
        const dropzoneWrapper = document.getElementById('edit-dropzone-wrapper');
        const previewContainer = document.getElementById('edit-image-preview-container');
        const previewImg = document.getElementById('edit-image-preview');
        const gambar = button.dataset.gambar;

        input.value = '';
        showEditImageError('');

        if (gambar && gambar !== '-') {
            previewImg.src = "{{ asset('storage') }}/" + gambar;
            dropzoneWrapper.classList.add('hidden');
            previewContainer.classList.remove('hidden');
        } else {
            previewImg.src = '#';
            previewContainer.classList.add('hidden');
            dropzoneWrapper.classList.remove('hidden');
        }
    }

    function setupDropzone(wrapperId, fileInput, onFile) {
        const wrapper = document.getElementById(wrapperId);
        if (!wrapper || !fileInput) return;

        ['dragenter', 'dragover'].forEach(function(ev) {
            wrapper.addEventListener(ev, function(e) {
                e.preventDefault();
                e.stopPropagation();
                wrapper.classList.add('bg-gray-100');
            });
        });

        ['dragleave', 'drop'].forEach(function(ev) {
            wrapper.addEventListener(ev, function(e) {
                e.preventDefault();
                e.stopPropagation();
                wrapper.classList.remove('bg-gray-100');
            });
        });

        wrapper.addEventListener('drop', function(e) {
            const file = e.dataTransfer && e.dataTransfer.files ? e.dataTransfer.files[0] : null;
            if (!file) return;

            if (onFile(file)) {
                const dt = new DataTransfer();
                dt.items.add(file);
                fileInput.files = dt.files;
            } else {
                fileInput.value = '';
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        setupDropzone('dropzone-wrapper', document.getElementById('gambar'), handleFile);
        setupDropzone('edit-dropzone-wrapper', document.getElementById('edit-gambar'), handleEditFile);

        document.querySelectorAll('[data-modal-target="edit-layanan"]').forEach(function(button) {
            button.addEventListener('click', function() {
                openEditModal(button);
            });
        });

        const input = document.getElementById('layanan-search');
        const cards = document.querySelectorAll('[data-card]');
        const kosong = document.getElementById('layanan-empty');
        if (!input || !cards.length) return;

        input.addEventListener('input', function() {
            const kata = this.value.trim().toLowerCase();
            let tampil = 0;

            cards.forEach(function(card) {
                const cocok = card.textContent.toLowerCase().includes(kata);
                card.classList.toggle('hidden', !cocok);
                if (cocok) tampil++;
            });

            kosong.classList.toggle('hidden', tampil !== 0);
        });
    });
</script>
@endsection
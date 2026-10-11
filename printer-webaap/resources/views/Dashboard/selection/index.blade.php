@extends('Dashboard.layout')

@section('title', 'login')

@push('scripts')
@vite([
'resources/js/dashboard/chart-pendapatan.js',
'resources/js/dashboard/chart-layanan-kertas.js',
'resources/js/dashboard/tabel-order.js',
'resources/js/dashboard/tipe-file.js',
])
@endpush

@section('content')
<div class=" w-full flex flex-col justify-center items-center">
    <div class="grid w-full grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">

        <!-- Order selesai -->
        <div class="flex min-h-[7rem] flex-col justify-between rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <h5 class="text-sm font-medium text-gray-500">Order selesai</h5>
            <div class="mt-4 flex items-end justify-between gap-2">
                <span class="text-3xl font-semibold leading-none text-gray-900">4/18</span>
                <span class="text-xs text-gray-500">Hari ini</span>
            </div>
        </div>

        <!-- Kertas -->
        <div class="flex min-h-[7rem] flex-col justify-between rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <h5 class="text-sm font-medium text-gray-500">Pendapatan</h5>
            <div class="mt-4 flex items-end justify-between gap-2">
                <span class="text-3xl font-semibold leading-none text-gray-900">Rp. 12.000</span>
                <div class="text-right text-xs leading-tight text-gray-500">
                    <p class="font-medium text-green-600">+20%</p>
                    <p>dari 1 hari lalu</p>
                </div>
            </div>
        </div>

        <!-- Komputer -->
        <div class="flex min-h-[7rem] flex-col justify-between rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <h5 class="text-sm font-medium text-gray-500">Komputer</h5>
            <div class="mt-4 flex items-end justify-between gap-2">
                <span class="text-3xl font-semibold leading-none text-gray-900">3/2</span>
                <span class="text-xs text-gray-500">Sedang aktif</span>
            </div>
        </div>

        <!-- File tersimpan -->
        <div class="flex min-h-[7rem] flex-col justify-between rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <h5 class="text-sm font-medium text-gray-500">File tersimpan</h5>
            <div class="mt-4 flex items-end justify-between gap-2">
                <span class="text-3xl font-semibold leading-none text-gray-900">89</span>
                <div class="text-right text-xs leading-tight text-gray-500">
                    <p class="font-medium text-green-600">+20%</p>
                    <p>dari bulan lalu</p>
                </div>
            </div>
        </div>
    </div>
    <div class=" my-6 w-full rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
        <div class="flex items-center justify-between">
            <h6 class="text-lg font-semibold text-gray-900">Pendapatan</h6>

            <!-- Trigger Button (menampilkan bulan terpilih) -->
            <button id="bulanSelectButton"
                data-dropdown-toggle="bulanSelectDropdown"
                class="inline-flex items-center justify-between gap-2 rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm text-gray-900 hover:bg-gray-50 focus:border-gray-900 focus:ring-1 focus:ring-gray-900 focus:outline-none"
                type="button">
                <span id="bulanSelectLabel">{{ now()->translatedFormat('F') ?? '' }}</span>
                <svg class="w-4 h-4 text-gray-500" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 9-7 7-7-7" />
                </svg>
            </button>

            <!-- Dropdown Menu -->
            <div id="bulanSelectDropdown"
                class="z-10 hidden w-44 rounded-lg border border-gray-200 bg-white shadow-lg">
                <ul class="p-2 text-sm font-medium text-gray-700" aria-labelledby="bulanSelectButton">
                    @foreach (['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'] as $i => $nama)
                    <li>
                        <button type="button"
                            data-value="{{ $i + 1 }}"
                            data-label="{{ $nama }}"
                            class="bulan-option-pendapatan inline-flex w-full items-center rounded p-2 text-left hover:bg-gray-100 hover:text-gray-900 {{ $i + 1 === now()->month ? 'bg-gray-100 font-semibold' : '' }}">
                            {{ $nama }}
                        </button>
                    </li>
                    @endforeach
                </ul>
            </div>

            <!-- Hidden input untuk menyimpan nilai (pengganti <select>) -->
            <input type="hidden" id="bulan-select" name="bulan" value="{{ now()->month }}">
        </div>

        <div id="chart-pendapatan" class="mt-4" data-url="{{ route('pendapatan.data') }}"></div>
    </div>
    <div class="w-full">
        <div class="mb-4 flex items-center gap-3">
            <h5 class="text-lg font-semibold text-gray-900">Layanan &amp; kertas keluar</h5>

            {{-- Trigger button --}}
            <button id="bulanLkButton" data-dropdown-toggle="bulanLkDropdown"
                class="inline-flex items-center justify-between gap-2 rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm font-medium text-gray-900 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-gray-300"
                type="button">
                <span id="bulanLkLabel">{{ \Carbon\Carbon::now()->translatedFormat('F') }}</span>
                <svg class="w-4 h-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 9-7 7-7-7" />
                </svg>
            </button>

            {{-- Dropdown menu --}}
            <div id="bulanLkDropdown"
                class="z-10 hidden w-44 rounded-lg border border-gray-200 bg-white shadow-lg">
                <ul class="p-2 text-sm font-medium text-gray-700" aria-labelledby="bulanLkButton">
                    @foreach (['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'] as $i => $nama)
                    <li>
                        <button type="button"
                            data-value="{{ $i + 1 }}"
                            data-label="{{ $nama }}"
                            class="bulan-option inline-flex w-full items-center rounded p-2 text-left hover:bg-gray-100 hover:text-gray-900 {{ $i + 1 === now()->month ? 'bg-gray-100 font-semibold' : '' }}">
                            {{ $nama }}
                        </button>
                    </li>
                    @endforeach
                </ul>
            </div>

            {{-- Hidden input untuk menyimpan value (mis. untuk form submit) --}}
            <input type="hidden" name="bulan_lk" id="bulan-lk" value="{{ now()->month }}">
        </div>

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">

            <!-- Pie: Layanan -->
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <h6 class="text-sm font-medium text-gray-500">Layanan</h6>
                <div id="chart-layanan" class="mt-2"></div>
            </div>

            <!-- Pie: Kertas -->
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <h6 class="text-sm font-medium text-gray-500">Kertas</h6>
                <div id="chart-kertas" class="mt-2"></div>
            </div>

            <!-- Tipe file -->
            <div class="flex flex-col rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <h6 class="text-lg font-semibold text-gray-900">Tipe file</h6>

                <table id="tabel-tipe-file" data-url="{{ route('tipe-file.data') }}" class="mt-3 w-full text-sm">
                    <thead>
                        <tr class="text-left text-gray-500">
                            <th class="pb-2 font-medium">Tipe</th>
                            <th class="pb-2 text-right font-medium">Tersimpan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <tr>
                            <td colspan="2" class="py-2.5 text-gray-500">Memuat...</td>
                        </tr>
                    </tbody>
                </table>

                <a href="{{ url('/files') }}" class="mt-auto self-end pt-4 text-sm text-gray-900 underline hover:text-gray-600">
                    Lihat selengkapnya
                </a>
            </div>

        </div>


        <div id="data-lk" data-url="{{ route('layanan-kertas.data') }}" class="hidden"></div>
        <div class="mt-8 w-full rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <h5 class="text-lg font-semibold text-gray-900">Order</h5>

            <div class="mt-4 overflow-x-auto">
                <table id="tabel-order" data-url="{{ route('order.data') }}" class="w-full min-w-[48rem] text-left text-sm">
                    <thead class="border-b border-gray-200 text-gray-500">
                        <tr>
                            <th class="pb-3 pr-4 font-medium">Tanggal order</th>
                            <th class="pb-3 pr-4 font-medium">Layanan</th>
                            <th class="pb-3 pr-4 font-medium">Ukuran kertas</th>
                            <th class="pb-3 pr-4 font-medium">Total files</th>
                            <th class="pb-3 pr-4 font-medium">Total halaman dicetak</th>
                            <th class="pb-3 pr-4 font-medium">Status pembayaran</th>
                            <th class="pb-3 text-right font-medium">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 text-gray-900">
                        <tr>
                            <td colspan="7" class="py-4 text-gray-500">Memuat...</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <nav id="paginasi-order" class="mt-4 flex items-center justify-end gap-1"></nav>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const buttons = document.querySelectorAll('.bulan-option-pendapatan');
    const label   = document.getElementById('bulanSelectLabel');
    const hidden  = document.getElementById('bulan-select');

    buttons.forEach(btn => {
        btn.addEventListener('click', function () {
            // Update label tombol
            label.textContent = this.dataset.label;
            // Update hidden input
            hidden.value = this.dataset.value;
            // Update highlight active
            buttons.forEach(b => b.classList.remove('bg-gray-100', 'font-semibold'));
            this.classList.add('bg-gray-100', 'font-semibold');
            // Trigger event jika perlu (misal untuk Livewire / fetch)
            hidden.dispatchEvent(new Event('change', { bubbles: true }));
            // Tutup dropdown Flowbite
            document.getElementById('bulanSelectButton').click();
        });
    });
});
</script>

<script>
    document.querySelectorAll('.bulan-option').forEach(btn => {
        btn.addEventListener('click', () => {
            const label = document.getElementById('bulanLkLabel');
            const input = document.getElementById('bulan-lk');

            label.textContent = btn.dataset.label;
            input.value = btn.dataset.value;

            document.querySelectorAll('.bulan-option').forEach(b =>
                b.classList.remove('bg-gray-100', 'font-semibold'));
            btn.classList.add('bg-gray-100', 'font-semibold');

            // 👇 ini yang memicu fetch di JS ApexCharts
            input.dispatchEvent(new Event('change'));

            document.getElementById('bulanLkDropdown').classList.add('hidden');
        });
    });
</script>
@endsection
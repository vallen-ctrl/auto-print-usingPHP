<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Printer-app')</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

@php
$menu = [
['label' => 'Menu Utama', 'icon' => 'lucide-house', 'route' => 'dashboard', 'match' => 'dashboard'],
['label' => 'Order', 'icon' => 'lucide-shopping-cart', 'route' => 'order.index', 'match' => 'order.*'],
['label' => 'Files', 'icon' => 'lucide-files', 'route' => 'files.index', 'match' => 'files.*'],
['label' => 'Layanan', 'icon' => 'lucide-hand-platter', 'route' => 'layanan.index', 'match' => 'layanan.*'],
['label' => 'Kertas', 'icon' => 'lucide-file-cog', 'route' => 'kertas.index', 'match' => 'kertas.*'],
['label' => 'Komputer', 'icon' => 'lucide-monitor', 'route' => 'komputer.index', 'match' => 'komputer.*'],
['label' => 'Pengaturan', 'icon' => 'lucide-settings', 'route' => 'pengaturan.index', 'match' => 'pengaturan.*'],
];
@endphp

<body class="bg-bg text-ink antialiased">

    <button data-drawer-target="default-sidebar" data-drawer-toggle="default-sidebar" aria-controls="default-sidebar" type="button" class="text-heading bg-transparent box-border border border-transparent hover:bg-neutral-secondary-medium focus:ring-4 focus:ring-neutral-tertiary font-medium leading-5 rounded-base ms-3 mt-3 text-sm p-2 focus:outline-none inline-flex sm:hidden">
        <span class="sr-only">Open sidebar</span>
        <svg class="w-6 h-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
            <path stroke="currentColor" stroke-linecap="round" stroke-width="2" d="M5 7h14M5 12h14M5 17h10" />
        </svg>
    </button>

    <aside id="default-sidebar" class="fixed top-0 left-0 z-40 w-64 h-full transition-transform -translate-x-full sm:translate-x-0" aria-label="Sidebar">
        <div class="h-full px-3 py-4 overflow-y-auto bg-neutral-primary-soft border-e border-default">

            <ul class="h-full flex flex-col space-y-2 font-medium">
                @foreach ($menu as $item)
                @php $aktif = request()->routeIs($item['match']); @endphp
                <li>
                    <a href="{{ Route::has($item['route']) ? route($item['route']) : '#' }}"
                        @if ($aktif) aria-current="page" @endif
                        class="flex items-center px-2 py-1.5 rounded-base group hover:bg-neutral-tertiary hover:text-fg-brand
                  {{ $aktif ? 'bg-neutral-tertiary text-fg-brand' : 'text-body' }}">
                        <x-dynamic-component :component="$item['icon']" class="size-5" />
                        <span class="flex-1 ms-3 whitespace-nowrap">{{ $item['label'] }}</span>
                    </a>
                </li>
                @endforeach
                <li class=" mt-auto">

                    <button data-modal-target="popup-modal" data-modal-toggle="popup-modal" href="#" class="w-full flex items-center justify-center px-2 py-1.5 text-danger rounded-base hover:bg-danger-strong hover:text-white group">

                        <svg class="shrink-0 w-5 h-5 transition duration-75 group-hover:text-white " aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12H4m12 0-4 4m4-4-4-4m3-4h2a3 3 0 0 1 3 3v10a3 3 0 0 1-3 3h-2" />
                        </svg>
                        <span class="flex-1 ms-3 whitespace-nowrap">Log out</span>
                    </button>
                </li>
            </ul>
        </div>
    </aside>



    <div id="popup-modal" tabindex="-1" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
        <form method="POST" action="{{route('logout')}}">

            <div class="relative p-4 w-full max-w-md max-h-full">
                <div class="relative bg-neutral-primary-soft border border-default rounded-base shadow-sm p-4 md:p-6">
                    <button type="button" class="absolute top-3 e-2.5 text-body bg-transparent hover:bg-neutral-tertiary hover:text-heading rounded-base text-sm w-9 h-9 ms-auto inline-flex justify-center items-center" data-modal-hide="popup-modal">
                        <svg class="w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 17.94 6M18 18 6.06 6" />
                        </svg>
                        <span class="sr-only">Close modal</span>
                    </button>
                    <div class="p-4 md:p-5 text-center">
                        <svg class="mx-auto mb-4 text-fg-disabled w-12 h-12" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 13V8m0 8h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                        <h3 class="mb-6 text-body">Apakah kamu yakin ingin keluar?</h3>
                        <div class="flex items-center space-x-4 justify-center">
                            <button type="submit" href="{{route('logout')}}" data-modal-hide="popup-modal" type="button" class="text-white bg-danger box-border border border-transparent hover:bg-danger-strong focus:ring-4 focus:ring-danger-medium shadow-xs font-medium leading-5 rounded-base text-sm px-4 py-2.5 focus:outline-none">
                                Iya
                            </button>
                            <button data-modal-hide="popup-modal" type="button" class="text-body bg-neutral-secondary-medium box-border border border-default-medium hover:bg-neutral-tertiary-medium hover:text-heading focus:ring-4 focus:ring-neutral-tertiary shadow-xs font-medium leading-5 rounded-base text-sm px-4 py-2.5 focus:outline-none">Tidak</button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <div class="p-4 sm:ml-64">
        @yield('content')
    </div>
    @stack('scripts')
    <script src="https://cdn.jsdelivr.net/npm/flowbite@4.0.1/dist/flowbite.min.js"></script>
</body>

</html>
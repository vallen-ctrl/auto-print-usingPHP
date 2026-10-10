@extends('app')

@section('title', 'daftar')

@section('content')


<div class="w-full h-screen flex justify-center items-center">
    @if (!empty($error))
    <div class="fixed inset-0 z-40 bg-gray-900/50 dark:bg-gray-900/80"></div>
    <div id="popup-modal" tabindex="-1" class="animate-in fade-in slide-in-from-bottom-4 duration-500 flex overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
        <div class="relative p-4 w-full max-w-md max-h-full">
            <div class="relative bg-neutral-primary-soft border border-default rounded-base shadow-sm p-4 md:p-6">
                <div class="p-4 md:p-5 text-center">
                    <svg class="mx-auto mb-4 text-fg-disabled w-12 h-12" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 13V8m0 8h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                    <h3 class="mb-6 text-body">{{$error}}</h3>
                    <div class="flex items-center space-x-4 justify-center">
                        <button data-modal-hide="popup-modal" type="button" class="text-white bg-brand hover:bg-brand-strong focus:ring-4 focus:ring-brand-medium box-border border border-transparent shadow-xs font-medium leading-5 rounded-base text-sm px-4 py-2.5 focus:outline-none"
                            onclick="window.location.href='login'">
                            Pindah ke halaman login
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <div class="w-full max-w-sm bg-neutral-primary-soft p-6 border border-default rounded-base shadow-xs">
        <form id="register-form" method="POST">
            @csrf
            <h5 class="text-xl font-semibold text-heading mb-6 text-center">Daftar Akun Admin</h5>
            
            <!-- Nama -->
            <div class="mb-4">
                <label for="name" class="flex items-center mb-2.5 text-sm font-medium text-heading">
                    <span><x-lucide-store class="size-4 font-semibold mr-2" /></span>Nama
                </label>
                <input value="{{old('name')}}" name="name" type="text" id="name" class="bg-neutral-secondary-medium border border-default-medium text-heading text-sm rounded-base focus:ring-brand focus:border-brand block w-full px-3 py-2.5 shadow-xs placeholder:text-body" placeholder="toko saya" required />
                @error('name')
                <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                @enderror
            </div>

            <!-- Username -->
            <div class="mb-4">
                <label for="username" class="flex items-center mb-2.5 text-sm font-medium text-heading">
                    <span><x-lucide-user-round class="size-4 font-semibold mr-2" /></span>Username
                </label>
                <input value="{{old('username')}}" name="username" type="text" id="username" class="bg-neutral-secondary-medium border border-default-medium text-heading text-sm rounded-base focus:ring-brand focus:border-brand block w-full px-3 py-2.5 shadow-xs placeholder:text-body" placeholder="super admin" required />
                @error('username')
                <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                @enderror
            </div>

            <!-- Password -->
            <div class="mb-4">
                <label for="password" class="flex items-center mb-2.5 text-sm font-medium text-heading">
                    <span><x-lucide-lock class="size-4 font-semibold mr-2" /></span>Password
                </label>
                <input name="password" type="password" id="password" class="bg-neutral-secondary-medium border border-default-medium text-heading text-sm rounded-base focus:ring-brand focus:border-brand block w-full px-3 py-2.5 shadow-xs placeholder:text-body" placeholder="***" required />
                @error('password')
                <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                @enderror
            </div>

            <!-- Konfirmasi Password -->
            <div class="mb-4">
                <label for="password_confirmation" class="flex items-center mb-2.5 text-sm font-medium text-heading">
                    <span><x-lucide-lock class="size-4 font-semibold mr-2" /></span>Konfirmasi Password
                </label>
                <input name="password_confirmation" type="password" id="password_confirmation" class="bg-neutral-secondary-medium border border-default-medium text-heading text-sm rounded-base focus:ring-brand focus:border-brand block w-full px-3 py-2.5 shadow-xs placeholder:text-body" placeholder="***" required />
                
                <!-- Elemen Pesan Indikator Kecocokan Password -->
                <p id="password-feedback" class="text-xs mt-1 hidden"></p>
            </div>

            <!-- Tombol Submit -->
            <button id="submit-btn" disabled type="submit" class="disabled:text-body disabled:bg-disabled mt-6 text-white bg-brand box-border border border-transparent hover:bg-brand-strong focus:ring-4 focus:ring-brand-medium shadow-xs font-medium leading-5 rounded-base text-sm px-4 py-2.5 focus:outline-none w-full mb-3 cursor-not-allowed">
                Daftar Akun
            </button>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('register-form');
            const submitBtn = document.getElementById('submit-btn');
            const requiredInputs = form.querySelectorAll('input[required]');
            const passwordInput = document.getElementById('password');
            const confirmPasswordInput = document.getElementById('password_confirmation');
            const feedbackText = document.getElementById('password-feedback');

            // Fungsi utama untuk memeriksa seluruh kondisi form
            function checkValidation() {
                // 1. Cek apakah seluruh input terisi
                let isAllFilled = true;
                requiredInputs.forEach(input => {
                    if (input.value.trim() === '') {
                        isAllFilled = false;
                    }
                });

                const password = passwordInput.value;
                const confirmPassword = confirmPasswordInput.value;

                // 2. Bandingkan Password & Konfirmasi Password
                let isPasswordMatching = false;

                if (confirmPassword.length > 0) {
                    feedbackText.classList.remove('hidden');

                    if (password === confirmPassword) {
                        isPasswordMatching = true;
                        feedbackText.textContent = "✓ Password cocok";
                        feedbackText.className = "text-xs mt-1 text-green-600 font-medium";
                    } else {
                        isPasswordMatching = false;
                        feedbackText.textContent = "✕ Konfirmasi password tidak cocok";
                        feedbackText.className = "text-xs mt-1 text-red-500 font-medium";
                    }
                } else {
                    feedbackText.classList.add('hidden');
                }

                // 3. Hapus atribut disabled HANYA JIKA semua terisi DAN password cocok
                if (isAllFilled && isPasswordMatching) {
                    submitBtn.disabled = false;
                    submitBtn.classList.remove('cursor-not-allowed');
                } else {
                    submitBtn.disabled = true;
                    submitBtn.classList.add('cursor-not-allowed');
                }
            }

            // Jalankan pemeriksaan setiap kali ada aktivitas pengetikan pada form
            form.addEventListener('input', checkValidation);
        });
    </script>
</div>
@endsection
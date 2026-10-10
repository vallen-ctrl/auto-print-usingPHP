@extends('app')

@section('title', 'login')

@section('content')

@if(session('success'))
<div id="toast-success" class="flex fixed bottom-5 right-5 items-center w-full max-w-sm p-4 text-body bg-neutral-primary-soft rounded-base shadow-xs border border-default" role="alert">
    <div class="inline-flex items-center justify-center shrink-0 w-7 h-7 text-fg-success bg-success-soft rounded">
        <svg class="w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 11.917 9.724 16.5 19 7.5"/></svg>
        <span class="sr-only">Check icon</span>
    </div>
    <div class="ms-3 text-sm font-normal">{{ session('message') }}</div>
    <button type="button" class="ms-auto flex items-center justify-center text-body hover:text-heading bg-transparent box-border border border-transparent hover:bg-neutral-secondary-medium focus:ring-4 focus:ring-neutral-tertiary font-medium leading-5 rounded text-sm h-8 w-8 focus:outline-none" data-dismiss-target="#toast-success" aria-label="Close">
        <span class="sr-only">Close</span>
        <svg class="w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 17.94 6M18 18 6.06 6"/></svg>
    </button>
</div>
@endif

<div class=" w-full h-screen flex justify-center items-center">

    <div class="w-full max-w-sm bg-neutral-primary-soft p-6 border border-default rounded-base shadow-xs">
        <form method="POST">
            @csrf
            <h5 class=" text-xl font-semibold text-heading mb-6 text-center">Masuk ke Admin Panel</h5>
            <div class="mb-4">
                <label for="username" class="flex items-center mb-2.5 text-sm font-medium text-heading">
                    <span><x-lucide-user-round class="size-4 font-semibold mr-2" /></span>Username
                </label>
                <input name="username" value="{{ old('username') }}" type="username" id="username" class="bg-neutral-secondary-medium border border-default-medium text-heading text-sm rounded-base focus:ring-brand focus:border-brand block w-full px-3 py-2.5 shadow-xs placeholder:text-body" placeholder="super admin" required />
            </div>
            <div>
                <label for="password" class="flex items-center mb-2.5 text-sm font-medium text-heading">
                    <span><x-lucide-lock class="size-4 font-semibold mr-2" /></span>password
                </label>
                <input name="password" type="password" id="password" class="bg-neutral-secondary-medium border border-default-medium text-heading text-sm rounded-base focus:ring-brand focus:border-brand block w-full px-3 py-2.5 shadow-xs placeholder:text-body" placeholder="•••••••••" required />
            </div>
            @error('login')
            <div class="flex items-start my-2">
                <label for="Eror" class=" text-danger underline text-sm"> username or password salah</label>
            </div>
            @enderror

            <button type="submit" class="text-white my-3.5 bg-brand box-border border border-transparent hover:bg-brand-strong focus:ring-4 focus:ring-brand-medium shadow-xs font-medium leading-5 rounded-base text-sm px-4 py-2.5 focus:outline-none w-full mb-3">Login to your account</button>
        </form>
    </div>

</div>
@endsection
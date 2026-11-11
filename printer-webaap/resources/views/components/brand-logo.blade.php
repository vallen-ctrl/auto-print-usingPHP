@props(['placeholder' => 'logo jasa'])

@php($branding = \App\Support\Branding::get())

{{-- Logo toko dari Pengaturan. Kalau belum diunggah, tampilkan placeholder lama. --}}
<div {{ $attributes->merge(['class' => 'logo-placeholder']) }} role="img" aria-label="{{ $branding['nama'] }}">
    @if (!empty($branding['logo_url']))
        <img src="{{ $branding['logo_url'] }}" alt=""
            style="width:100%;height:100%;object-fit:contain;border-radius:inherit;">
    @else
        {{ $placeholder }}
    @endif
</div>

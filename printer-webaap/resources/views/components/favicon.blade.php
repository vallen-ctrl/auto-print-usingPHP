@php
    $faviconUrl = \App\Support\Branding::get()['favicon_url'] ?? null;
@endphp

@if ($faviconUrl)
    <link rel="icon" type="image/x-icon" href="{{ $faviconUrl }}">
@endif

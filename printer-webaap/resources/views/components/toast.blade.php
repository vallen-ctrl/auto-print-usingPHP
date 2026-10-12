@props([
    'type' => 'success', // success, error, warning, info
    'message' => null,
    'id' => 'toast-' . uniqid(),
])

@php
    $config = [
        'success' => [
            'iconColor' => 'text-fg-success',
            'iconBg' => 'bg-success-soft',
            'path' => 'M5 11.917 9.724 16.5 19 7.5',
        ],
        'error' => [
            'iconColor' => 'text-fg-danger',
            'iconBg' => 'bg-danger-soft',
            'path' => 'M6 18 17.94 6M18 18 6.06 6',
        ],
        'warning' => [
            'iconColor' => 'text-fg-warning',
            'iconBg' => 'bg-warning-soft',
            'path' => 'M12 9v4m0 4h.01M12 3l9 16H3L12 3z',
        ],
        'info' => [
            'iconColor' => 'text-fg-info',
            'iconBg' => 'bg-info-soft',
            'path' => 'M12 8h.01M11 12h1v4h1',
        ],
    ];

    $current = $config[$type] ?? $config['success'];
    $toastId = 'toast-' . $id;
@endphp

<div id="{{ $toastId }}"
     class="flex fixed bottom-5 right-5 items-center w-full max-w-sm p-4 text-body bg-neutral-primary-soft rounded-base shadow-xs border border-default z-50"
     role="alert">
    <div class="inline-flex items-center justify-center shrink-0 w-7 h-7 {{ $current['iconColor'] }} {{ $current['iconBg'] }} rounded">
        <svg class="w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $current['path'] }}" />
        </svg>
        <span class="sr-only">{{ ucfirst($type) }} icon</span>
    </div>

    <div class="ms-3 text-sm font-normal">{{ $message ?? $slot }}</div>

    <button type="button"
            class="ms-auto flex items-center justify-center text-body hover:text-heading bg-transparent box-border border border-transparent hover:bg-neutral-secondary-medium focus:ring-4 focus:ring-neutral-tertiary font-medium leading-5 rounded text-sm h-8 w-8 focus:outline-none"
            data-dismiss-target="#{{ $toastId }}"
            aria-label="Close">
        <span class="sr-only">Close</span>
        <svg class="w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 17.94 6M18 18 6.06 6" />
        </svg>
    </button>
</div>
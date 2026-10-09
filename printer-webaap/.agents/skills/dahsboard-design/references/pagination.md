---
title: "pagination"
description: "Page navigation with previous, next, numbered links and an ellipsis."
category: "Navigation"
---

# pagination

Page navigation with previous, next, numbered links and an ellipsis.

## Installation

```bash
php artisan ui:add pagination
```

## Dependencies

- composer: `gehrisandro/tailwind-merge-laravel`

## Usage

```blade
{{-- Hand-built --}}
<x-ui.pagination>
    <x-ui.pagination.content>
        <x-ui.pagination.item>
            <x-ui.pagination.previous href="?page=1" />
        </x-ui.pagination.item>
        <x-ui.pagination.item>
            <x-ui.pagination.link href="?page=2" :is-active="true">2</x-ui.pagination.link>
        </x-ui.pagination.item>
        <x-ui.pagination.item>
            <x-ui.pagination.ellipsis />
        </x-ui.pagination.item>
        <x-ui.pagination.item>
            <x-ui.pagination.next href="?page=3" />
        </x-ui.pagination.item>
    </x-ui.pagination.content>
</x-ui.pagination>

{{-- Driven by a Laravel paginator --}}
<x-ui.pagination>
    <x-ui.pagination.content>
        @foreach ($posts->getUrlRange(1, $posts->lastPage()) as $page => $url)
            <x-ui.pagination.item>
                <x-ui.pagination.link :href="$url" :is-active="$page === $posts->currentPage()">
                    {{ $page }}
                </x-ui.pagination.link>
            </x-ui.pagination.item>
        @endforeach
    </x-ui.pagination.content>
</x-ui.pagination>
```

## Example

The demo rendered on the docs page.

```blade
<x-ui.pagination>
    <x-ui.pagination.content>
        <x-ui.pagination.item>
            <x-ui.pagination.previous href="#" />
        </x-ui.pagination.item>
        <x-ui.pagination.item>
            <x-ui.pagination.link href="#">1</x-ui.pagination.link>
        </x-ui.pagination.item>
        <x-ui.pagination.item>
            <x-ui.pagination.link href="#" :is-active="true">2</x-ui.pagination.link>
        </x-ui.pagination.item>
        <x-ui.pagination.item>
            <x-ui.pagination.link href="#">3</x-ui.pagination.link>
        </x-ui.pagination.item>
        <x-ui.pagination.item>
            <x-ui.pagination.ellipsis />
        </x-ui.pagination.item>
        <x-ui.pagination.item>
            <x-ui.pagination.next href="#" />
        </x-ui.pagination.item>
    </x-ui.pagination.content>
</x-ui.pagination>
```

## Source

Copy each file to the path shown above it.

`resources/views/components/ui/pagination/index.blade.php`

```blade
@props([])

@php
    $classes = \TailwindMerge\Laravel\Facades\TailwindMerge::merge(
        'mx-auto flex w-full justify-center',
        $attributes->get('class'),
    );
@endphp

<nav role="navigation" aria-label="pagination"
    {{ $attributes->except('class')->merge(['class' => $classes]) }}>
    {{ $slot }}
</nav>
```

`resources/views/components/ui/pagination/content.blade.php`

```blade
@props([])

@php
    $classes = \TailwindMerge\Laravel\Facades\TailwindMerge::merge(
        'flex flex-row items-center gap-1',
        $attributes->get('class'),
    );
@endphp

<ul {{ $attributes->except('class')->merge(['class' => $classes]) }}>
    {{ $slot }}
</ul>
```

`resources/views/components/ui/pagination/item.blade.php`

```blade
@props([])

@php
    $classes = \TailwindMerge\Laravel\Facades\TailwindMerge::merge(
        '',
        $attributes->get('class'),
    );
@endphp

<li {{ $attributes->except('class')->merge(['class' => $classes]) }}>
    {{ $slot }}
</li>
```

`resources/views/components/ui/pagination/link.blade.php`

```blade
@props([
    'href' => '#',
    'isActive' => false,
    'size' => 'icon',
])

@php
    $base =
        'inline-flex items-center justify-center gap-1 whitespace-nowrap rounded-md text-sm font-medium outline-none transition-colors focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] aria-disabled:pointer-events-none aria-disabled:opacity-50';

    // The current page reads as an outline button, the rest as ghost buttons.
    $state = $isActive
        ? 'border border-input bg-background shadow-xs hover:bg-accent hover:text-accent-foreground dark:bg-input/30 dark:hover:bg-input/50'
        : 'hover:bg-accent hover:text-accent-foreground dark:hover:bg-accent/50';

    $sizes = match ($size) {
        'sm' => 'h-8 px-3',
        'lg' => 'h-10 px-6',
        'md' => 'h-9 px-4 py-2',
        default => 'size-9',
    };

    $classes = \TailwindMerge\Laravel\Facades\TailwindMerge::merge(
        $base,
        $state,
        $sizes,
        $attributes->get('class'),
    );
@endphp

<a href="{{ $href }}" @if ($isActive) aria-current="page" @endif
    {{ $attributes->except('class')->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
```

`resources/views/components/ui/pagination/previous.blade.php`

```blade
@props([
    'href' => '#',
    'label' => 'Previous',
])

@php
    $classes = \TailwindMerge\Laravel\Facades\TailwindMerge::merge(
        'inline-flex h-9 items-center justify-center gap-1 whitespace-nowrap rounded-md px-2.5 text-sm font-medium outline-none transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] aria-disabled:pointer-events-none aria-disabled:opacity-50 dark:hover:bg-accent/50',
        $attributes->get('class'),
    );
@endphp

<a href="{{ $href }}" aria-label="Go to previous page"
    {{ $attributes->except('class')->merge(['class' => $classes]) }}>
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
        stroke="currentColor" stroke-width="2" stroke-linecap="round"
        stroke-linejoin="round" class="size-4" aria-hidden="true">
        <path d="m15 18-6-6 6-6" />
    </svg>
    <span class="hidden sm:block">{{ $slot->isNotEmpty() ? $slot : $label }}</span>
</a>
```

`resources/views/components/ui/pagination/next.blade.php`

```blade
@props([
    'href' => '#',
    'label' => 'Next',
])

@php
    $classes = \TailwindMerge\Laravel\Facades\TailwindMerge::merge(
        'inline-flex h-9 items-center justify-center gap-1 whitespace-nowrap rounded-md px-2.5 text-sm font-medium outline-none transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] aria-disabled:pointer-events-none aria-disabled:opacity-50 dark:hover:bg-accent/50',
        $attributes->get('class'),
    );
@endphp

<a href="{{ $href }}" aria-label="Go to next page"
    {{ $attributes->except('class')->merge(['class' => $classes]) }}>
    <span class="hidden sm:block">{{ $slot->isNotEmpty() ? $slot : $label }}</span>
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
        stroke="currentColor" stroke-width="2" stroke-linecap="round"
        stroke-linejoin="round" class="size-4" aria-hidden="true">
        <path d="m9 18 6-6-6-6" />
    </svg>
</a>
```

`resources/views/components/ui/pagination/ellipsis.blade.php`

```blade
@props([])

@php
    $classes = \TailwindMerge\Laravel\Facades\TailwindMerge::merge(
        'flex size-9 items-center justify-center',
        $attributes->get('class'),
    );
@endphp

<span aria-hidden="true"
    {{ $attributes->except('class')->merge(['class' => $classes]) }}>
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
        stroke="currentColor" stroke-width="2" stroke-linecap="round"
        stroke-linejoin="round" class="size-4" aria-hidden="true">
        <circle cx="12" cy="12" r="1" />
        <circle cx="19" cy="12" r="1" />
        <circle cx="5" cy="12" r="1" />
    </svg>
    <span class="sr-only">More pages</span>
</span>
```

## Theme tokens

`--accent`, `--background`, `--input`, `--ring`

No JavaScript. Plain links, so it works with a Laravel paginator or a hand-built list; mark the current page with :is-active on the link.

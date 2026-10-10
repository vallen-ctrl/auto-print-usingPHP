---
title: "breadcrumb"
description: "Shows the path to the current resource as a hierarchy of links."
category: "Navigation"
---

# breadcrumb

Shows the path to the current resource as a hierarchy of links.

## Installation

```bash
php artisan ui:add breadcrumb
```

## Dependencies

- composer: `gehrisandro/tailwind-merge-laravel`

## Usage

```blade
<x-ui.breadcrumb>
    <x-ui.breadcrumb.list>
        <x-ui.breadcrumb.item>
            <x-ui.breadcrumb.link href="/">Home</x-ui.breadcrumb.link>
        </x-ui.breadcrumb.item>
        <x-ui.breadcrumb.separator />
        <x-ui.breadcrumb.item>
            <x-ui.breadcrumb.page>Current page</x-ui.breadcrumb.page>
        </x-ui.breadcrumb.item>
    </x-ui.breadcrumb.list>
</x-ui.breadcrumb>
```

## Example

The demo rendered on the docs page.

```blade
<x-ui.breadcrumb>
    <x-ui.breadcrumb.list>
        <x-ui.breadcrumb.item>
            <x-ui.breadcrumb.link href="#">Home</x-ui.breadcrumb.link>
        </x-ui.breadcrumb.item>
        <x-ui.breadcrumb.separator />
        <x-ui.breadcrumb.item>
            <x-ui.breadcrumb.link
                href="#">Components</x-ui.breadcrumb.link>
        </x-ui.breadcrumb.item>
        <x-ui.breadcrumb.separator />
        <x-ui.breadcrumb.item>
            <x-ui.breadcrumb.page>Breadcrumb</x-ui.breadcrumb.page>
        </x-ui.breadcrumb.item>
    </x-ui.breadcrumb.list>
</x-ui.breadcrumb>
```

## Source

Copy each file to the path shown above it.

`resources/views/components/ui/breadcrumb/index.blade.php`

```blade
@props([])

@php
    $classes = \TailwindMerge\Laravel\Facades\TailwindMerge::merge(
        '',
        $attributes->get('class'),
    );
@endphp

<nav aria-label="breadcrumb"
    {{ $attributes->except('class')->merge(['class' => $classes]) }}>
    {{ $slot }}
</nav>
```

`resources/views/components/ui/breadcrumb/list.blade.php`

```blade
@props([])

@php
    $classes = \TailwindMerge\Laravel\Facades\TailwindMerge::merge(
        'flex flex-wrap items-center gap-1.5 break-words text-sm text-muted-foreground sm:gap-2.5',
        $attributes->get('class'),
    );
@endphp

<ol {{ $attributes->except('class')->merge(['class' => $classes]) }}>
    {{ $slot }}
</ol>
```

`resources/views/components/ui/breadcrumb/item.blade.php`

```blade
@props([])

@php
    $classes = \TailwindMerge\Laravel\Facades\TailwindMerge::merge(
        'inline-flex items-center gap-1.5',
        $attributes->get('class'),
    );
@endphp

<li {{ $attributes->except('class')->merge(['class' => $classes]) }}>
    {{ $slot }}
</li>
```

`resources/views/components/ui/breadcrumb/link.blade.php`

```blade
@props([
    'href' => '#',
])

@php
    $classes = \TailwindMerge\Laravel\Facades\TailwindMerge::merge(
        'transition-colors hover:text-foreground',
        $attributes->get('class'),
    );
@endphp

<a href="{{ $href }}"
    {{ $attributes->except('class')->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
```

`resources/views/components/ui/breadcrumb/page.blade.php`

```blade
@props([])

@php
    $classes = \TailwindMerge\Laravel\Facades\TailwindMerge::merge(
        'font-normal text-foreground',
        $attributes->get('class'),
    );
@endphp

<span role="link" aria-disabled="true" aria-current="page"
    {{ $attributes->except('class')->merge(['class' => $classes]) }}>
    {{ $slot }}
</span>
```

`resources/views/components/ui/breadcrumb/separator.blade.php`

```blade
@props([])

@php
    $classes = \TailwindMerge\Laravel\Facades\TailwindMerge::merge(
        '[&>svg]:size-3.5',
        $attributes->get('class'),
    );
@endphp

<li role="presentation" aria-hidden="true"
    {{ $attributes->except('class')->merge(['class' => $classes]) }}>
    @if ($slot->isNotEmpty())
        {{ $slot }}
    @else
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
            stroke="currentColor" stroke-width="2" stroke-linecap="round"
            stroke-linejoin="round" aria-hidden="true">
            <path d="m9 18 6-6-6-6" />
        </svg>
    @endif
</li>
```

`resources/views/components/ui/breadcrumb/ellipsis.blade.php`

```blade
@props([])

@php
    $classes = \TailwindMerge\Laravel\Facades\TailwindMerge::merge(
        'flex size-9 items-center justify-center',
        $attributes->get('class'),
    );
@endphp

<span role="presentation" aria-hidden="true"
    {{ $attributes->except('class')->merge(['class' => $classes]) }}>
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
        stroke="currentColor" stroke-width="2" stroke-linecap="round"
        stroke-linejoin="round" class="size-4">
        <circle cx="12" cy="12" r="1" />
        <circle cx="19" cy="12" r="1" />
        <circle cx="5" cy="12" r="1" />
    </svg>
    <span class="sr-only">More</span>
</span>
```

## Theme tokens

`--muted-foreground`, `--foreground`

Pure markup, no JS. Compose <x-ui.breadcrumb><x-ui.breadcrumb.list> with .item, .link, .page, .separator, .ellipsis. The .separator renders a chevron by default or its slot if provided.

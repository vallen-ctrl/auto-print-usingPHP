---
title: "card"
description: "Container with header, content, and footer slots."
category: "Layout"
---

# card

Container with header, content, and footer slots.

## Installation

```bash
php artisan ui:add card
```

## Dependencies

- composer: `gehrisandro/tailwind-merge-laravel`

## Usage

```blade
<x-ui.card title="Create project"
    description="Deploy your new project in one click.">
    <p class="text-sm text-muted-foreground">Card content goes here.</p>
    <x-slot:footer>
        <x-ui.button>Deploy</x-ui.button>
    </x-slot:footer>
</x-ui.card>
```

## Example

The demo rendered on the docs page.

```blade
<x-ui.card title="Create project"
    description="Deploy your new project in one click." class="w-full max-w-sm">
    <p class="text-sm text-muted-foreground">
        Name, framework, and region are configured automatically.
    </p>
    <x-slot:footer>
        <x-ui.button variant="outline">Cancel</x-ui.button>
        <x-ui.button class="ml-2">Deploy</x-ui.button>
    </x-slot:footer>
</x-ui.card>
```

## Source

Copy this file to the path shown above it.

`resources/views/components/ui/card.blade.php`

```blade
@props([
    'title' => null,
    'description' => null,
])

@php
    $classes = \TailwindMerge\Laravel\Facades\TailwindMerge::merge(
        'flex flex-col gap-6 rounded-xl border bg-card py-6 text-card-foreground shadow-sm',
        $attributes->get('class'),
    );
@endphp

<div {{ $attributes->except('class')->merge(['class' => $classes]) }}>
    @if ($title || $description || isset($header))
        <div class="flex flex-col gap-1.5 px-6">
            @isset($header)
                {{ $header }}
            @else
                @if ($title)
                    <h3 class="font-semibold leading-none">{{ $title }}</h3>
                @endif
                @if ($description)
                    <p class="text-sm text-muted-foreground">{{ $description }}</p>
                @endif
            @endisset
        </div>
    @endif

    <div class="px-6">
        {{ $slot }}
    </div>

    @isset($footer)
        <div class="flex items-center px-6">
            {{ $footer }}
        </div>
    @endisset
</div>
```

## Theme tokens

`--card`, `--card-foreground`, `--border`, `--muted-foreground`, `--radius`

Slots: header, footer; or props title/description.

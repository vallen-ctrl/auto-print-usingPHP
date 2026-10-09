---
title: "badge"
description: "Small status \/ label pill with variants."
category: "Data Display"
---

# badge

Small status / label pill with variants.

## Installation

```bash
php artisan ui:add badge
```

## Dependencies

- composer: `gehrisandro/tailwind-merge-laravel`

## Usage

```blade
<x-ui.badge>Default</x-ui.badge>
<x-ui.badge variant="secondary">Secondary</x-ui.badge>
<x-ui.badge variant="destructive">Failed</x-ui.badge>
<x-ui.badge variant="outline">Draft</x-ui.badge>
```

## Example

The demo rendered on the docs page.

```blade
<div class="flex flex-wrap items-center gap-3">
    <x-ui.badge>Default</x-ui.badge>
    <x-ui.badge variant="secondary">Secondary</x-ui.badge>
    <x-ui.badge variant="destructive">Destructive</x-ui.badge>
    <x-ui.badge variant="outline">Outline</x-ui.badge>
</div>
```

## Source

Copy this file to the path shown above it.

`resources/views/components/ui/badge.blade.php`

```blade
@props([
    'variant' => 'default',
])

@php
    $base =
        'inline-flex w-fit shrink-0 items-center justify-center gap-1 overflow-hidden whitespace-nowrap rounded-md border px-2 py-0.5 text-xs font-medium transition-[color,box-shadow] [&>svg]:pointer-events-none [&>svg]:size-3 focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] aria-invalid:border-destructive aria-invalid:ring-destructive/20';

    $variants = match ($variant) {
        'secondary'
            => 'border-transparent bg-secondary text-secondary-foreground [a&]:hover:bg-secondary/90',
        'destructive'
            => 'border-transparent bg-destructive text-destructive-foreground [a&]:hover:bg-destructive/90 focus-visible:ring-destructive/20',
        'outline'
            => 'text-foreground [a&]:hover:bg-accent [a&]:hover:text-accent-foreground',
        default
            => 'border-transparent bg-primary text-primary-foreground [a&]:hover:bg-primary/90',
    };

    $classes = \TailwindMerge\Laravel\Facades\TailwindMerge::merge(
        $base,
        $variants,
        $attributes->get('class'),
    );
@endphp

<span {{ $attributes->except('class')->merge(['class' => $classes]) }}>
    {{ $slot }}
</span>
```

## Theme tokens

`--primary`, `--secondary`, `--destructive`, `--foreground`, `--ring`

Variants: default, secondary, destructive, outline.

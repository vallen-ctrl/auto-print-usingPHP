---
title: "separator"
description: "Visual or semantic divider."
category: "Layout"
---

# separator

Visual or semantic divider.

## Installation

```bash
php artisan ui:add separator
```

## Dependencies

- composer: `gehrisandro/tailwind-merge-laravel`

## Usage

```blade
<x-ui.separator class="my-4" />

{{-- Vertical needs a height from the parent or from `class` --}}
<div class="flex h-6 items-center gap-4">
    <span class="text-sm">Docs</span>
    <x-ui.separator orientation="vertical" />
    <span class="text-sm">Blog</span>
</div>
```

## Example

The demo rendered on the docs page.

```blade
<div class="w-full max-w-sm">
    <div class="text-sm font-medium">LaralCN-UI</div>
    <div class="text-sm text-muted-foreground">Copy-and-own components</div>
    <x-ui.separator class="my-4" />
    <div class="flex h-5 items-center gap-4 text-sm">
        <span>Blog</span>
        <x-ui.separator orientation="vertical" />
        <span>Docs</span>
        <x-ui.separator orientation="vertical" />
        <span>Source</span>
    </div>
</div>
```

## Source

Copy this file to the path shown above it.

`resources/views/components/ui/separator.blade.php`

```blade
@props([
    'orientation' => 'horizontal',
])

@php
    $base = $orientation === 'vertical' ? 'h-full w-px' : 'h-px w-full';

    $classes = \TailwindMerge\Laravel\Facades\TailwindMerge::merge(
        'shrink-0 bg-border',
        $base,
        $attributes->get('class'),
    );
@endphp

<div role="separator" aria-orientation="{{ $orientation }}"
    {{ $attributes->except('class')->merge(['class' => $classes]) }}></div>
```

## Theme tokens

`--border`

orientation: horizontal (default) or vertical.

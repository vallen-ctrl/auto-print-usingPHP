---
title: "aspect-ratio"
description: "Holds its children to a fixed width-to-height ratio."
category: "Layout"
---

# aspect-ratio

Holds its children to a fixed width-to-height ratio.

## Installation

```bash
php artisan ui:add aspect-ratio
```

## Dependencies

- composer: `gehrisandro/tailwind-merge-laravel`

## Usage

```blade
<x-ui.aspect-ratio ratio="16/9" class="overflow-hidden rounded-lg">
    <img src="/photo.jpg" alt="" class="size-full object-cover" />
</x-ui.aspect-ratio>
```

## Example

The demo rendered on the docs page.

```blade
<x-ui.aspect-ratio ratio="16/9" class="max-w-sm overflow-hidden rounded-lg bg-muted">
    <div class="flex size-full items-center justify-center text-sm text-muted-foreground">
        16 / 9
    </div>
</x-ui.aspect-ratio>
```

## Source

Copy this file to the path shown above it.

`resources/views/components/ui/aspect-ratio.blade.php`

```blade
@props([
    'ratio' => '16/9',
])

@php
    $classes = \TailwindMerge\Laravel\Facades\TailwindMerge::merge(
        'relative w-full',
        $attributes->get('class'),
    );
@endphp

{{-- The ratio is an inline style on purpose. aspect-[{{ '$ratio' }}] would be
     built from a runtime value, and Tailwind only compiles classes it can find
     by scanning source files, so that class would never exist. --}}
<div style="aspect-ratio: {{ $ratio }}"
    {{ $attributes->except('class')->merge(['class' => $classes]) }}>
    {{ $slot }}
</div>
```

## Theme tokens

No JavaScript and no tokens. Pass `ratio` as any CSS aspect-ratio value ("16/9", "1/1", "1.85"). Give the child `size-full object-cover` to fill the box.

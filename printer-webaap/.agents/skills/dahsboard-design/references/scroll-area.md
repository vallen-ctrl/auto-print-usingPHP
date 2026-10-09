---
title: "scroll-area"
description: "Scrollable region with a styled, themed scrollbar."
category: "Layout"
---

# scroll-area

Scrollable region with a styled, themed scrollbar.

## Installation

```bash
php artisan ui:add scroll-area
```

## Dependencies

- composer: `gehrisandro/tailwind-merge-laravel`

## Usage

```blade
<x-ui.scroll-area class="h-48 w-full max-w-xs rounded-md border p-4">
    {{-- The height comes from `class`; without one there is nothing to scroll. --}}
    @foreach ($releases as $release)
        <div class="border-b py-2 text-sm last:border-b-0">{{ $release }}</div>
    @endforeach
</x-ui.scroll-area>

{{-- horizontal scrolls sideways instead; give it a width, not a height --}}
<x-ui.scroll-area orientation="horizontal" class="w-full max-w-xs rounded-md border p-4">
    <div class="flex gap-4">
        @foreach ($covers as $cover)
            <img src="{{ $cover }}" alt="" class="size-32 shrink-0 rounded-md" />
        @endforeach
    </div>
</x-ui.scroll-area>
```

## Example

The demo rendered on the docs page.

```blade
<x-ui.scroll-area class="h-48 w-full max-w-xs rounded-md border p-4">
    <h4 class="mb-3 text-sm font-medium leading-none">Releases</h4>
    @foreach (range(1, 20) as $i)
        <div class="border-b py-2 text-sm last:border-b-0">
            v0.{{ $i }}.0
        </div>
    @endforeach
</x-ui.scroll-area>
```

## Source

Copy this file to the path shown above it.

`resources/views/components/ui/scroll-area.blade.php`

```blade
@props([
    'orientation' => 'vertical',
])

@php
    // Native overflow does the scrolling, so there is no script and no
    // synthetic scrollbar to keep in sync. Give the element a height (or a
    // max-height) through `class`, otherwise there is nothing to scroll.
    $axis = match ($orientation) {
        'horizontal' => 'overflow-x-auto overflow-y-hidden',
        'both' => 'overflow-auto',
        default => 'overflow-y-auto overflow-x-hidden',
    };

    // Two separate systems, both needed. Firefox reads the standard
    // scrollbar-width / scrollbar-color properties; WebKit and Blink read the
    // ::-webkit-scrollbar pseudo-elements and ignore the standard ones.
    $firefox = '[scrollbar-width:thin] [scrollbar-color:var(--border)_transparent]';

    $webkit =
        '[&::-webkit-scrollbar]:size-2.5 [&::-webkit-scrollbar-track]:bg-transparent [&::-webkit-scrollbar-thumb]:rounded-full [&::-webkit-scrollbar-thumb]:bg-border [&::-webkit-scrollbar-thumb:hover]:bg-muted-foreground/40 [&::-webkit-scrollbar-corner]:bg-transparent';

    $classes = \TailwindMerge\Laravel\Facades\TailwindMerge::merge(
        'relative rounded-[inherit] focus-visible:ring-ring/50 focus-visible:ring-[3px] focus-visible:outline-none',
        $axis,
        $firefox,
        $webkit,
        $attributes->get('class'),
    );
@endphp

<div {{ $attributes->except('class')->merge(['class' => $classes]) }}>
    {{ $slot }}
</div>
```

## Theme tokens

`--border`, `--muted-foreground`, `--ring`

Native overflow, no JavaScript and no synthetic scrollbar. Set a height or max-height via `class` or nothing scrolls. Orientations: vertical, horizontal, both.

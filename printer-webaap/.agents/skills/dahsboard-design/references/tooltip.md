---
title: "tooltip"
description: "Hover\/focus tooltip with side positioning."
category: "Overlays"
---

# tooltip

Hover/focus tooltip with side positioning.

## Installation

```bash
php artisan ui:add tooltip
```

## Dependencies

- composer: `gehrisandro/tailwind-merge-laravel`

## Usage

```blade
<x-ui.tooltip text="Add to library">
    <x-ui.button variant="outline">Hover me</x-ui.button>
</x-ui.tooltip>

{{-- side: top (default), right, bottom or left --}}
<x-ui.tooltip text="Deletes the project for everyone" side="right">
    <x-ui.button variant="destructive">Delete</x-ui.button>
</x-ui.tooltip>
```

## Example

The demo rendered on the docs page.

```blade
<x-ui.tooltip text="Add to library">
    <x-ui.button variant="outline">Hover me</x-ui.button>
</x-ui.tooltip>
```

## Source

Copy this file to the path shown above it.

`resources/views/components/ui/tooltip.blade.php`

```blade
@props([
    'text' => '',
    'side' => 'top',
])

@php
    $position = match ($side) {
        'bottom' => 'top-full mt-2 left-1/2 -translate-x-1/2',
        'left' => 'right-full mr-2 top-1/2 -translate-y-1/2',
        'right' => 'left-full ml-2 top-1/2 -translate-y-1/2',
        default => 'bottom-full mb-2 left-1/2 -translate-x-1/2',
    };

    // Shown while the wrapper is hovered or anything inside it holds focus, so
    // pointer and keyboard both work. No JavaScript needed.
    $tipClasses = \TailwindMerge\Laravel\Facades\TailwindMerge::merge(
        'pointer-events-none invisible absolute z-50 w-fit max-w-xs text-balance rounded-md bg-primary px-3 py-1.5 text-xs text-primary-foreground opacity-0 transition-opacity duration-150 group-hover:visible group-hover:opacity-100 group-focus-within:visible group-focus-within:opacity-100',
        $position,
        $attributes->get('class'),
    );
@endphp

<span class="group relative inline-flex" {{ $attributes->except('class') }}>
    {{ $slot }}

    <span role="tooltip" class="{{ $tipClasses }}">
        {{ $text }}
    </span>
</span>
```

## Theme tokens

`--primary`, `--primary-foreground`, `--radius`

Pure CSS (hover plus focus-within), no JavaScript at all. Wrap the trigger in the slot; pass `text` and optional `side` (top|bottom|left|right).

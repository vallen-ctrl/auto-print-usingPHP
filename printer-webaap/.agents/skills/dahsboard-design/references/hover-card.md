---
title: "hover-card"
description: "Preview card shown when a trigger is hovered or focused."
category: "Overlays"
---

# hover-card

Preview card shown when a trigger is hovered or focused.

## Installation

```bash
php artisan ui:add hover-card
```

## Dependencies

- composer: `gehrisandro/tailwind-merge-laravel`

## Usage

```blade
<x-ui.hover-card side="top">
    <x-slot:trigger>
        <x-ui.button variant="link">@abdulkadersafi</x-ui.button>
    </x-slot:trigger>

    <p class="text-sm text-muted-foreground">Builds LaralCN-UI.</p>
</x-ui.hover-card>
```

## Example

The demo rendered on the docs page.

```blade
<x-ui.hover-card>
    <x-slot:trigger>
        <x-ui.button variant="link">@abdulkadersafi</x-ui.button>
    </x-slot:trigger>

    <div class="flex gap-3">
        <x-ui.avatar fallback="AS" class="size-10" />
        <div class="space-y-1">
            <h4 class="text-sm font-semibold">@abdulkadersafi</h4>
            <p class="text-sm text-muted-foreground">
                Builds LaralCN-UI. Laravel, Blade, Tailwind.
            </p>
        </div>
    </div>
</x-ui.hover-card>
```

## Source

Copy this file to the path shown above it.

`resources/views/components/ui/hover-card.blade.php`

```blade
@props([
    'side' => 'top',
])

@php
    // Hover and focus-within do the work, so this ships no script, the same
    // approach the tooltip takes.
    //
    // The gap between trigger and card is padding on the positioning wrapper,
    // never margin on the card. Margin would leave a dead strip that drops the
    // hover the moment the pointer sets off toward the card.
    $position = match ($side) {
        'bottom' => 'top-full left-1/2 -translate-x-1/2 pt-2',
        'left' => 'right-full top-1/2 -translate-y-1/2 pr-2',
        'right' => 'left-full top-1/2 -translate-y-1/2 pl-2',
        default => 'bottom-full left-1/2 -translate-x-1/2 pb-2',
    };

    // `block` is load-bearing: the card is a span (it has to be, the wrapper is
    // phrasing content) and width does nothing to an inline box. The wrapper
    // escapes this only because position:absolute blockifies it.
    $classes = \TailwindMerge\Laravel\Facades\TailwindMerge::merge(
        'block w-64 rounded-md border bg-popover p-4 text-popover-foreground shadow-md',
        $attributes->get('class'),
    );
@endphp

<span class="group relative inline-flex" {{ $attributes->except('class') }}>
    {{ $trigger }}

    <span
        class="pointer-events-none invisible absolute z-50 opacity-0 transition-opacity delay-150 duration-200 group-hover:pointer-events-auto group-hover:visible group-hover:opacity-100 group-focus-within:pointer-events-auto group-focus-within:visible group-focus-within:opacity-100 {{ $position }}">
        <span role="dialog" class="{{ $classes }}">
            {{ $slot }}
        </span>
    </span>
</span>
```

## Theme tokens

`--popover`, `--popover-foreground`, `--border`

CSS only, no JavaScript: opens on hover and on keyboard focus. Put the trigger in the `trigger` slot and the card body in the default slot. Sides: top, bottom, left, right.

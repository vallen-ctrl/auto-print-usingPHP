---
title: "checkbox"
description: "Native checkbox styled to the theme."
category: "Forms"
---

# checkbox

Native checkbox styled to the theme.

## Installation

```bash
php artisan ui:add checkbox
```

## Dependencies

- composer: `gehrisandro/tailwind-merge-laravel`

## Usage

```blade
<x-ui.checkbox id="terms" />
```

## Example

The demo rendered on the docs page.

```blade
<div class="flex items-center gap-2">
    <x-ui.checkbox id="terms" />
    <x-ui.label for="terms">
        Accept terms and conditions
    </x-ui.label>
</div>
```

## Source

Copy this file to the path shown above it.

`resources/views/components/ui/checkbox.blade.php`

```blade
@props([])

@php
    $classes = \TailwindMerge\Laravel\Facades\TailwindMerge::merge(
        'peer size-4 shrink-0 rounded-[4px] border border-input bg-transparent accent-primary shadow-xs outline-none transition-shadow disabled:cursor-not-allowed disabled:opacity-50 dark:bg-input/30 focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] aria-invalid:border-destructive aria-invalid:ring-destructive/20',
        $attributes->get('class'),
    );
@endphp

<input type="checkbox"
    {{ $attributes->except('class')->merge(['class' => $classes]) }} />
```

## Theme tokens

`--primary`, `--ring`, `--background`

Pair with `<x-ui.label>` via id/for.

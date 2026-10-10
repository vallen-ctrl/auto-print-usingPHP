---
title: "radio"
description: "Native radio input styled to the theme."
category: "Forms"
---

# radio

Native radio input styled to the theme.

## Installation

```bash
php artisan ui:add radio
```

## Dependencies

- composer: `gehrisandro/tailwind-merge-laravel`

## Usage

```blade
<x-ui.radio id="free" name="plan" value="free" checked />
```

## Example

The demo rendered on the docs page.

```blade
<div class="space-y-2">
    <div class="flex items-center gap-2">
        <x-ui.radio id="r1" name="plan" value="free" checked />
        <x-ui.label for="r1">Free</x-ui.label>
    </div>
    <div class="flex items-center gap-2">
        <x-ui.radio id="r2" name="plan" value="pro" />
        <x-ui.label for="r2">Pro</x-ui.label>
    </div>
</div>
```

## Source

Copy this file to the path shown above it.

`resources/views/components/ui/radio.blade.php`

```blade
@props([])

@php
    $classes = \TailwindMerge\Laravel\Facades\TailwindMerge::merge(
        'aspect-square size-4 shrink-0 rounded-full border border-input bg-transparent accent-primary text-primary shadow-xs outline-none transition-[color,box-shadow] disabled:cursor-not-allowed disabled:opacity-50 dark:bg-input/30 focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] aria-invalid:border-destructive aria-invalid:ring-destructive/20',
        $attributes->get('class'),
    );
@endphp

<input type="radio"
    {{ $attributes->except('class')->merge(['class' => $classes]) }} />
```

## Theme tokens

`--primary`, `--ring`, `--background`

Use a shared `name` for a radio group.

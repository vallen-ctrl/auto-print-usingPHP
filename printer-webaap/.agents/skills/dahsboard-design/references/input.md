---
title: "input"
description: "Form text input."
category: "Forms"
---

# input

Form text input.

## Installation

```bash
php artisan ui:add input
```

## Dependencies

- composer: `gehrisandro/tailwind-merge-laravel`

## Usage

```blade
<x-ui.input type="email" placeholder="you@example.com" />
```

## Example

The demo rendered on the docs page.

```blade
<div class="w-full max-w-sm space-y-2">
    <x-ui.label for="email">Email</x-ui.label>
    <x-ui.input id="email" type="email" placeholder="you@example.com" />
</div>
```

## Source

Copy this file to the path shown above it.

`resources/views/components/ui/input.blade.php`

```blade
@props([
    'type' => 'text',
])

@php
    $classes = \TailwindMerge\Laravel\Facades\TailwindMerge::merge(
        'flex h-9 w-full min-w-0 rounded-md border border-input bg-transparent px-3 py-1 text-base shadow-xs outline-none transition-[color,box-shadow] file:inline-flex file:h-7 file:border-0 file:bg-transparent file:text-sm file:font-medium file:text-foreground placeholder:text-muted-foreground selection:bg-primary selection:text-primary-foreground disabled:cursor-not-allowed disabled:opacity-50 md:text-sm dark:bg-input/30 focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] aria-invalid:border-destructive aria-invalid:ring-destructive/20',
        $attributes->get('class'),
    );
@endphp

<input type="{{ $type }}"
    {{ $attributes->except('class')->merge(['class' => $classes]) }} />
```

## Theme tokens

`--input`, `--background`, `--ring`, `--muted-foreground`, `--radius`

Consumes theme tokens injected by `php artisan ui:init`.

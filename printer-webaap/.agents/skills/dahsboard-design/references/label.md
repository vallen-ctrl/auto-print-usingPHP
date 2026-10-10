---
title: "label"
description: "Accessible form label."
category: "Forms"
---

# label

Accessible form label.

## Installation

```bash
php artisan ui:add label
```

## Dependencies

- composer: `gehrisandro/tailwind-merge-laravel`

## Usage

```blade
<x-ui.label for="email">Email</x-ui.label>
```

## Example

The demo rendered on the docs page.

```blade
<div class="w-full max-w-sm space-y-2">
    <x-ui.label for="name">Full name</x-ui.label>
    <x-ui.input id="name" placeholder="Ada Lovelace" />
</div>
```

## Source

Copy this file to the path shown above it.

`resources/views/components/ui/label.blade.php`

```blade
@props([])

@php
    $classes = \TailwindMerge\Laravel\Facades\TailwindMerge::merge(
        'flex select-none items-center gap-2 text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-50 group-data-[disabled=true]:pointer-events-none group-data-[disabled=true]:opacity-50',
        $attributes->get('class'),
    );
@endphp

<label {{ $attributes->except('class')->merge(['class' => $classes]) }}>
    {{ $slot }}
</label>
```

## Theme tokens

Pair with `for` to associate with an input.

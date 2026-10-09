---
title: "select"
description: "Native select styled to the theme."
category: "Forms"
---

# select

Native select styled to the theme.

## Installation

```bash
php artisan ui:add select
```

## Dependencies

- composer: `gehrisandro/tailwind-merge-laravel`

## Usage

```blade
<x-ui.select id="role">
    <option>Owner</option>
    <option>Admin</option>
    <option>Member</option>
</x-ui.select>
```

## Example

The demo rendered on the docs page.

```blade
<div class="w-full max-w-sm space-y-2">
    <x-ui.label for="role">Role</x-ui.label>
    <x-ui.select id="role">
        <option>Owner</option>
        <option>Admin</option>
        <option>Member</option>
    </x-ui.select>
</div>
```

## Source

Copy this file to the path shown above it.

`resources/views/components/ui/select.blade.php`

```blade
@props([])

@php
    $classes = \TailwindMerge\Laravel\Facades\TailwindMerge::merge(
        'flex h-9 w-full items-center justify-between gap-2 rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none transition-[color,box-shadow] disabled:cursor-not-allowed disabled:opacity-50 dark:bg-input/30 dark:hover:bg-input/50 focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] aria-invalid:border-destructive aria-invalid:ring-destructive/20 *:text-foreground',
        $attributes->get('class'),
    );
@endphp

<select {{ $attributes->except('class')->merge(['class' => $classes]) }}>
    {{ $slot }}
</select>
```

## Theme tokens

`--input`, `--background`, `--ring`, `--radius`

Place <option> elements in the slot.

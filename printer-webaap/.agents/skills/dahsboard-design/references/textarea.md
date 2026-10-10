---
title: "textarea"
description: "Multi-line text input."
category: "Forms"
---

# textarea

Multi-line text input.

## Installation

```bash
php artisan ui:add textarea
```

## Dependencies

- composer: `gehrisandro/tailwind-merge-laravel`

## Usage

```blade
<x-ui.textarea rows="4" placeholder="Tell us about yourself" />
```

## Example

The demo rendered on the docs page.

```blade
<div class="w-full max-w-sm space-y-2">
    <x-ui.label for="bio">Bio</x-ui.label>
    <x-ui.textarea id="bio" rows="4"
        placeholder="Tell us about yourself" />
</div>
```

## Source

Copy this file to the path shown above it.

`resources/views/components/ui/textarea.blade.php`

```blade
@props([])

@php
    $classes = \TailwindMerge\Laravel\Facades\TailwindMerge::merge(
        'flex field-sizing-content min-h-16 w-full rounded-md border border-input bg-transparent px-3 py-2 text-base shadow-xs outline-none transition-[color,box-shadow] placeholder:text-muted-foreground disabled:cursor-not-allowed disabled:opacity-50 md:text-sm dark:bg-input/30 focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] aria-invalid:border-destructive aria-invalid:ring-destructive/20',
        $attributes->get('class'),
    );
@endphp

<textarea {{ $attributes->except('class')->merge(['class' => $classes]) }}>{{ $slot }}</textarea>
```

## Theme tokens

`--input`, `--background`, `--ring`, `--muted-foreground`, `--radius`

Consumes theme tokens injected by `php artisan ui:init`.

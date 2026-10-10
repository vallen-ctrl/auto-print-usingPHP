---
title: "skeleton"
description: "Placeholder shown while content loads."
category: "Feedback"
---

# skeleton

Placeholder shown while content loads.

## Installation

```bash
php artisan ui:add skeleton
```

## Dependencies

- composer: `gehrisandro/tailwind-merge-laravel`

## Usage

```blade
<x-ui.skeleton class="h-4 w-32" />
```

## Example

The demo rendered on the docs page.

```blade
<div class="flex w-full max-w-sm items-center gap-4">
    <x-ui.skeleton class="size-12 rounded-full" />
    <div class="flex-1 space-y-2">
        <x-ui.skeleton class="h-4 w-full" />
        <x-ui.skeleton class="h-4 w-2/3" />
    </div>
</div>
```

## Source

Copy this file to the path shown above it.

`resources/views/components/ui/skeleton.blade.php`

```blade
@props([])

@php
    $classes = \TailwindMerge\Laravel\Facades\TailwindMerge::merge(
        'animate-pulse rounded-md bg-accent',
        $attributes->get('class'),
    );
@endphp

<div {{ $attributes->except('class')->merge(['class' => $classes]) }}></div>
```

## Theme tokens

`--accent`, `--radius`

Size it with utility classes, e.g. <x-ui.skeleton class="h-4 w-32" />.

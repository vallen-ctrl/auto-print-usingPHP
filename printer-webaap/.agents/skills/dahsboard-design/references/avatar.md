---
title: "avatar"
description: "User image with text fallback."
category: "Data Display"
---

# avatar

User image with text fallback.

## Installation

```bash
php artisan ui:add avatar
```

## Dependencies

- composer: `gehrisandro/tailwind-merge-laravel`

## Usage

```blade
<x-ui.avatar src="https://example.com/avatar.jpg" alt="Ada Lovelace" />

{{-- No src: the fallback initials are shown instead --}}
<x-ui.avatar fallback="AL" alt="Ada Lovelace" />
<x-ui.avatar class="size-12" fallback="AL" />
```

## Example

The demo rendered on the docs page.

```blade
<div class="flex items-center gap-4">
    <x-ui.avatar src="https://i.pravatar.cc/80?img=12" alt="User" />
    <x-ui.avatar fallback="AS" />
</div>
```

## Source

Copy this file to the path shown above it.

`resources/views/components/ui/avatar.blade.php`

```blade
@props([
    'src' => null,
    'alt' => '',
    'fallback' => null,
])

@php
    $classes = \TailwindMerge\Laravel\Facades\TailwindMerge::merge(
        'relative flex size-8 shrink-0 overflow-hidden rounded-full',
        $attributes->get('class'),
    );
@endphp

<span {{ $attributes->except('class')->merge(['class' => $classes]) }}>
    @if ($src)
        <img src="{{ $src }}" alt="{{ $alt }}"
            class="aspect-square size-full object-cover" />
    @else
        <span
            class="flex size-full items-center justify-center rounded-full bg-muted text-sm font-medium text-muted-foreground"
            aria-hidden="true">
            {{ $fallback ?? $slot }}
        </span>
    @endif
</span>
```

## Theme tokens

`--muted`, `--muted-foreground`

Provide `src` + `alt`, or a `fallback` (initials).

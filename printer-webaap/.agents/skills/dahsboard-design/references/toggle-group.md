---
title: "toggle-group"
description: "A joined set of toggles, single or multiple selection."
category: "Forms"
---

# toggle-group

A joined set of toggles, single or multiple selection.

## Installation

```bash
php artisan ui:add toggle-group
```

## Dependencies

- composer: `gehrisandro/tailwind-merge-laravel`

## Usage

```blade
{{-- Single select: submits align=center --}}
<x-ui.toggle-group name="align" variant="outline">
    <x-ui.toggle-group.item value="left">Left</x-ui.toggle-group.item>
    <x-ui.toggle-group.item value="center" :pressed="true">Center</x-ui.toggle-group.item>
    <x-ui.toggle-group.item value="right">Right</x-ui.toggle-group.item>
</x-ui.toggle-group>

{{-- Multi select: submits style[]=bold&style[]=italic --}}
<x-ui.toggle-group name="style" :multiple="true">
    <x-ui.toggle-group.item value="bold">Bold</x-ui.toggle-group.item>
    <x-ui.toggle-group.item value="italic">Italic</x-ui.toggle-group.item>
</x-ui.toggle-group>

{{-- size: sm, md (default) or lg, applied to every item --}}
<x-ui.toggle-group name="zoom" size="sm">
    <x-ui.toggle-group.item value="50">50%</x-ui.toggle-group.item>
    <x-ui.toggle-group.item value="100">100%</x-ui.toggle-group.item>
</x-ui.toggle-group>
```

## Example

The demo rendered on the docs page.

```blade
<div class="flex flex-col items-center gap-6">
    <x-ui.toggle-group name="align" variant="outline">
        <x-ui.toggle-group.item value="left" :pressed="true">Left</x-ui.toggle-group.item>
        <x-ui.toggle-group.item value="center">Center</x-ui.toggle-group.item>
        <x-ui.toggle-group.item value="right">Right</x-ui.toggle-group.item>
    </x-ui.toggle-group>

    <x-ui.toggle-group name="style" :multiple="true">
        <x-ui.toggle-group.item value="bold" :pressed="true">Bold</x-ui.toggle-group.item>
        <x-ui.toggle-group.item value="italic">Italic</x-ui.toggle-group.item>
        <x-ui.toggle-group.item value="underline">Underline</x-ui.toggle-group.item>
    </x-ui.toggle-group>
</div>
```

## Source

Copy each file to the path shown above it.

`resources/views/components/ui/toggle-group/index.blade.php`

```blade
@props([
    'multiple' => false,
    'name' => 'toggle-group',
    'variant' => 'default',
    'size' => 'md',
])

@php
    $classes = \TailwindMerge\Laravel\Facades\TailwindMerge::merge(
        'flex w-fit items-center rounded-md',
        $variant === 'outline' ? 'shadow-xs' : '',
        $attributes->get('class'),
    );
@endphp

{{-- variant, size, multiple and name are read by the items through @aware.
     Note the Laravel caveat: @aware only sees attributes actually passed to
     this tag, never its defaults, so every default here is repeated as the
     fallback in item.blade.php. Keep the two lists in step. --}}
<div role="group"
    {{ $attributes->except('class')->merge(['class' => $classes]) }}>
    {{ $slot }}
</div>
```

`resources/views/components/ui/toggle-group/item.blade.php`

```blade
@aware([
    'multiple' => false,
    'name' => 'toggle-group',
    'variant' => 'default',
    'size' => 'md',
])

@props([
    'value' => null,
    'pressed' => false,
])

@php
    // Single-select is a radio group, multi-select a set of checkboxes. Either
    // way the browser owns the state, so there is no script: radios even give
    // arrow-key navigation between items for free.
    $base =
        "relative inline-flex cursor-pointer items-center justify-center gap-2 whitespace-nowrap rounded-none text-sm font-medium transition-colors first:rounded-l-md last:rounded-r-md has-[:checked]:z-10 has-[:checked]:bg-accent has-[:checked]:text-accent-foreground has-[:focus-visible]:z-10 has-[:focus-visible]:border-ring has-[:focus-visible]:ring-ring/50 has-[:focus-visible]:ring-[3px] has-[:disabled]:pointer-events-none has-[:disabled]:opacity-50 [&_svg]:pointer-events-none [&_svg:not([class*='size-'])]:size-4 [&_svg]:shrink-0";

    // Same reasoning as the toggle component: scoping the hover with
    // :not(:has(:checked)) stops it repainting the pressed background, which a
    // plain hover: would do or not do depending on emitted rule order.
    $hover =
        '[&:not(:has(:checked))]:hover:bg-muted [&:not(:has(:checked))]:hover:text-muted-foreground';

    $variants = match ($variant) {
        'outline' => 'border border-input bg-transparent -ml-px first:ml-0',
        default => 'bg-transparent',
    };

    $sizes = match ($size) {
        'sm' => 'h-8 min-w-8 px-1.5',
        'lg' => 'h-10 min-w-10 px-2.5',
        default => 'h-9 min-w-9 px-2',
    };

    $classes = \TailwindMerge\Laravel\Facades\TailwindMerge::merge(
        $base,
        $hover,
        $variants,
        $sizes,
        $attributes->get('class'),
    );
@endphp

<label {{ $attributes->except('class')->merge(['class' => $classes]) }}>
    <input type="{{ $multiple ? 'checkbox' : 'radio' }}"
        name="{{ $multiple ? $name . '[]' : $name }}" value="{{ $value }}"
        class="peer sr-only" @checked($pressed) />
    {{ $slot }}
</label>
```

## Theme tokens

`--accent`, `--muted`, `--input`, `--ring`

Native radios (single) or checkboxes (multiple), so no JavaScript; radios also give arrow-key navigation for free. Set `multiple`, `name`, `variant` and `size` on the group; items read them via @aware.

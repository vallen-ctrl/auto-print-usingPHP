---
title: "table"
description: "Responsive data table wrapper."
category: "Data Display"
---

# table

Responsive data table wrapper.

## Installation

```bash
php artisan ui:add table
```

## Dependencies

- composer: `gehrisandro/tailwind-merge-laravel`

## Usage

```blade
<x-ui.table>
    <thead>
        <tr class="border-b text-left">
            <th class="h-10 px-2 font-medium text-muted-foreground">Invoice</th>
            <th class="h-10 px-2 font-medium text-muted-foreground">Status</th>
        </tr>
    </thead>
    <tbody>
        <tr class="border-b">
            <td class="p-2 font-medium">INV-001</td>
            <td class="p-2">Paid</td>
        </tr>
    </tbody>
</x-ui.table>
```

## Example

The demo rendered on the docs page.

```blade
<x-ui.table class="w-full max-w-lg">
    <thead>
        <tr class="border-b text-left">
            <th class="h-10 px-2 font-medium text-muted-foreground">Invoice</th>
            <th class="h-10 px-2 font-medium text-muted-foreground">Status</th>
            <th class="h-10 px-2 text-right font-medium text-muted-foreground">
                Amount
            </th>
        </tr>
    </thead>
    <tbody>
        <tr class="border-b">
            <td class="p-2 font-medium">INV-001</td>
            <td class="p-2">Paid</td>
            <td class="p-2 text-right">$250.00</td>
        </tr>
        <tr>
            <td class="p-2 font-medium">INV-002</td>
            <td class="p-2">Pending</td>
            <td class="p-2 text-right">$150.00</td>
        </tr>
    </tbody>
</x-ui.table>
```

## Source

Copy this file to the path shown above it.

`resources/views/components/ui/table.blade.php`

```blade
@props([])

@php
    $classes = \TailwindMerge\Laravel\Facades\TailwindMerge::merge(
        'w-full caption-bottom text-sm',
        $attributes->get('class'),
    );
@endphp

<div class="relative w-full overflow-x-auto">
    <table {{ $attributes->except('class')->merge(['class' => $classes]) }}>
        {{ $slot }}
    </table>
</div>
```

## Theme tokens

`--muted`, `--muted-foreground`, `--border`

Wrap standard thead/tbody/tr/th/td; styling via Tailwind utility selectors in your markup.

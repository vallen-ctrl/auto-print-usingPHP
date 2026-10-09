---
title: "collapsible"
description: "An interactive panel that expands and collapses its content."
category: "Layout"
---

# collapsible

An interactive panel that expands and collapses its content.

## Installation

```bash
php artisan ui:add collapsible
```

## Dependencies

- composer: `gehrisandro/tailwind-merge-laravel`

## Usage

```blade
<x-ui.collapsible :open="false">
    <x-ui.collapsible.trigger>Toggle</x-ui.collapsible.trigger>
    <x-ui.collapsible.content>
        Hidden content revealed on toggle.
    </x-ui.collapsible.content>
</x-ui.collapsible>
```

## Example

The demo rendered on the docs page.

```blade
<x-ui.collapsible class="w-full max-w-sm space-y-2">
    <div class="flex items-center justify-between gap-4 px-1">
        <h4 class="text-sm font-semibold">
            &commat;peduarte starred 3 repositories
        </h4>
        <x-ui.collapsible.trigger
            class="inline-flex size-8 items-center justify-center rounded-md hover:bg-accent hover:text-accent-foreground">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                fill="none" stroke="currentColor" stroke-width="2"
                stroke-linecap="round" stroke-linejoin="round" class="size-4">
                <path d="m7 15 5 5 5-5" />
                <path d="m7 9 5-5 5 5" />
            </svg>
            <span class="sr-only">Toggle</span>
        </x-ui.collapsible.trigger>
    </div>
    <div class="rounded-md border px-4 py-2 font-mono text-sm">
        &commat;radix-ui/primitives
    </div>
    <x-ui.collapsible.content class="space-y-2">
        <div class="rounded-md border px-4 py-2 font-mono text-sm">
            &commat;radix-ui/colors
        </div>
        <div class="rounded-md border px-4 py-2 font-mono text-sm">
            &commat;stitches/react
        </div>
    </x-ui.collapsible.content>
</x-ui.collapsible>
```

## Source

Copy each file to the path shown above it.

`resources/views/components/ui/collapsible/index.blade.php`

```blade
@props([
    'open' => false,
])

@php
    $classes = \TailwindMerge\Laravel\Facades\TailwindMerge::merge(
        'group/collapsible',
        $attributes->get('class'),
    );
@endphp

<div data-ui-collapsible data-state="{{ $open ? 'open' : 'closed' }}"
    {{ $attributes->except('class')->merge(['class' => $classes]) }}>
    {{ $slot }}
</div>

@once
    <script>
        (function() {
            // The Blade once-directive above keeps this to a single copy per
            // page, except when a component is rendered into a slot that is
            // echoed twice, the way the sidebar does for its desktop and its
            // mobile panel. Then the markup ships twice and every click would
            // fire the handler twice, so this flag is the real guard.
            if (window.__laralcnCollapsible) return;
            window.__laralcnCollapsible = true;

            var reduce = window.matchMedia('(prefers-reduced-motion: reduce)');

            function slide(el, show) {
                el.getAnimations().forEach(function(a) {
                    a.cancel();
                });
                el.classList.remove('hidden');

                if (reduce.matches) {
                    el.classList.toggle('hidden', !show);
                    return;
                }

                var height = el.scrollHeight;
                el.style.overflow = 'hidden';

                var animation = el.animate([{
                    height: (show ? 0 : height) + 'px',
                    opacity: show ? 0 : 1
                }, {
                    height: (show ? height : 0) + 'px',
                    opacity: show ? 1 : 0
                }], {
                    duration: 200,
                    easing: 'ease-out'
                });

                animation.onfinish = function() {
                    el.style.overflow = '';
                    el.classList.toggle('hidden', !show);
                };
            }

            function own(root, selector) {
                return Array.prototype.filter.call(
                    root.querySelectorAll(selector),
                    function(el) {
                        return el.closest('[data-ui-collapsible]') === root;
                    }
                );
            }

            // Seed each trigger from the state its parent rendered with.
            document.addEventListener('DOMContentLoaded', function() {
                document.querySelectorAll('[data-ui-collapsible]').forEach(function(root) {
                    own(root, '[data-ui-collapsible-trigger]').forEach(function(trigger) {
                        trigger.setAttribute('aria-expanded', String(root.dataset.state === 'open'));
                    });
                });
            });

            document.addEventListener('click', function(event) {
                var trigger = event.target.closest('[data-ui-collapsible-trigger]');
                if (!trigger) return;

                var root = trigger.closest('[data-ui-collapsible]');
                var open = root.dataset.state !== 'open';

                root.dataset.state = open ? 'open' : 'closed';
                trigger.setAttribute('aria-expanded', String(open));

                own(root, '[data-ui-collapsible-content]').forEach(function(content) {
                    slide(content, open);
                });
            });
        })();
    </script>
@endonce
```

`resources/views/components/ui/collapsible/trigger.blade.php`

```blade
@props([])

@php
    $classes = \TailwindMerge\Laravel\Facades\TailwindMerge::merge(
        'outline-none focus-visible:ring-ring/50 focus-visible:ring-[3px]',
        $attributes->get('class'),
    );
@endphp

{{-- aria-expanded is seeded from the parent's data-state on load, then kept in
     sync by the script that ships with <x-ui.collapsible>. --}}
<button type="button" data-ui-collapsible-trigger
    {{ $attributes->except('class')->merge(['class' => $classes]) }}>
    {{ $slot }}
</button>
```

`resources/views/components/ui/collapsible/content.blade.php`

```blade
@props([])

@php
    // Hidden by default; a collapsible with :open="true" flips it on via the
    // group-data-[state=open] rule its parent sets.
    $classes = \TailwindMerge\Laravel\Facades\TailwindMerge::merge(
        'hidden group-data-[state=open]/collapsible:block',
        $attributes->get('class'),
    );
@endphp

<div data-ui-collapsible-content
    {{ $attributes->except('class')->merge(['class' => $classes]) }}>
    {{ $slot }}
</div>
```

## Theme tokens

`--ring`

Ships its own inline <script>; no JS dependencies. Compose <x-ui.collapsible>, <x-ui.collapsible.trigger>, <x-ui.collapsible.content>; pass :open to start expanded.

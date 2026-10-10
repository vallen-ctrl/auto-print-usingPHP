---
title: "dropdown-menu"
description: "Click-triggered menu with outside-click and Esc to close."
category: "Overlays"
---

# dropdown-menu

Click-triggered menu with outside-click and Esc to close.

## Installation

```bash
php artisan ui:add dropdown-menu
```

## Dependencies

- composer: `gehrisandro/tailwind-merge-laravel`

## Usage

```blade
<x-ui.dropdown-menu>
    <x-slot:trigger>
        <x-ui.button variant="outline">Open menu</x-ui.button>
    </x-slot:trigger>

    <button role="menuitem"
        class="block w-full rounded-sm px-3 py-1.5 text-left text-sm hover:bg-accent hover:text-accent-foreground">
        Profile
    </button>
</x-ui.dropdown-menu>

{{-- align: start (default), center or end, relative to the trigger --}}
<x-ui.dropdown-menu align="end">
    <x-slot:trigger>
        <x-ui.button variant="ghost">Account</x-ui.button>
    </x-slot:trigger>

    <a href="/settings" role="menuitem"
        class="block rounded-sm px-3 py-1.5 text-sm hover:bg-accent hover:text-accent-foreground">
        Settings
    </a>
</x-ui.dropdown-menu>
```

## Example

The demo rendered on the docs page.

```blade
<x-ui.dropdown-menu>
    <x-slot:trigger>
        <x-ui.button variant="outline">Open menu</x-ui.button>
    </x-slot:trigger>

    <button role="menuitem"
        class="block w-full rounded-sm px-3 py-1.5 text-left text-sm hover:bg-accent hover:text-accent-foreground">
        Profile
    </button>
    <button role="menuitem"
        class="block w-full rounded-sm px-3 py-1.5 text-left text-sm hover:bg-accent hover:text-accent-foreground">
        Settings
    </button>
    <x-ui.separator class="my-1" />
    <button role="menuitem"
        class="block w-full rounded-sm px-3 py-1.5 text-left text-sm text-destructive hover:bg-accent">
        Sign out
    </button>
</x-ui.dropdown-menu>
```

## Source

Copy this file to the path shown above it.

`resources/views/components/ui/dropdown-menu.blade.php`

```blade
@props([
    'align' => 'start',
])

@php
    $alignment = match ($align) {
        'end' => 'right-0 origin-top-right',
        'center' => 'left-1/2 -translate-x-1/2 origin-top',
        default => 'left-0 origin-top-left',
    };

    $menuClasses = \TailwindMerge\Laravel\Facades\TailwindMerge::merge(
        'absolute z-50 mt-2 hidden min-w-[8rem] overflow-x-hidden overflow-y-auto rounded-md border bg-popover p-1 text-popover-foreground shadow-md',
        $alignment,
        $attributes->get('class'),
    );
@endphp

{{-- `group/dropdown` plus data-state lets whatever you put in the trigger slot
     react to the open state in pure CSS, e.g.
     group-data-[state=open]/dropdown:rotate-180. --}}
<div class="group/dropdown relative inline-block text-left" data-ui-dropdown
    data-state="closed">
    <div data-ui-dropdown-trigger aria-expanded="false" aria-haspopup="menu">
        {{ $trigger }}
    </div>

    <div data-ui-dropdown-menu role="menu" class="{{ $menuClasses }}">
        {{ $slot }}
    </div>
</div>

@once
    <script>
        (function() {
            // The Blade once-directive above keeps this to a single copy per
            // page, except when a component is rendered into a slot that is
            // echoed twice, the way the sidebar does for its desktop and its
            // mobile panel. Then the markup ships twice and every click would
            // fire the handler twice, so this flag is the real guard.
            if (window.__laralcnDropdownMenu) return;
            window.__laralcnDropdownMenu = true;

            function setOpen(root, state) {
                root.dataset.state = state ? 'open' : 'closed';
                root.querySelector('[data-ui-dropdown-trigger]')
                    .setAttribute('aria-expanded', String(state));
                root.querySelector('[data-ui-dropdown-menu]')
                    .classList.toggle('hidden', !state);
            }

            function closeAll(except) {
                document.querySelectorAll('[data-ui-dropdown]').forEach(function(root) {
                    if (root !== except) setOpen(root, false);
                });
            }

            document.addEventListener('click', function(event) {
                var trigger = event.target.closest('[data-ui-dropdown-trigger]');

                if (trigger) {
                    var root = trigger.closest('[data-ui-dropdown]');
                    closeAll(root);
                    setOpen(root, root.dataset.state !== 'open');
                    return;
                }

                // A click inside an open menu is a menu item: leave it open and
                // let the item do its job. Anything else closes every menu.
                if (!event.target.closest('[data-ui-dropdown-menu]')) closeAll();
            });

            document.addEventListener('keydown', function(event) {
                if (event.key !== 'Escape') return;

                var root = document.querySelector('[data-ui-dropdown][data-state="open"]');
                if (!root) return;

                setOpen(root, false);
                var trigger = root.querySelector('[data-ui-dropdown-trigger]')
                    .querySelector('button, a, [tabindex]');
                if (trigger) trigger.focus();
            });
        })();
    </script>
@endonce
```

## Theme tokens

`--popover`, `--popover-foreground`, `--border`, `--radius`

Ships its own inline <script>; no JS dependencies. `trigger` slot opens it; put menu items (role=menuitem) in the default slot. align: start|center|end. The root exposes group/dropdown + data-state so trigger markup can style itself with group-data-[state=open]/dropdown:*.

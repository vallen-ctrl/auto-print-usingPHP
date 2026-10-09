---
title: "sidebar"
description: "Composable, collapsible application sidebar with mobile off-canvas, icon mode, and keyboard toggle."
category: "Layout"
---

# sidebar

Composable, collapsible application sidebar with mobile off-canvas, icon mode, and keyboard toggle.

## Installation

```bash
php artisan ui:add sidebar
```

## Dependencies

- composer: `gehrisandro/tailwind-merge-laravel`

## Usage

```blade
{{-- open sets the starting state; after that a cookie and Ctrl/Cmd-B win --}}
<x-ui.sidebar.provider :open="false">
    <x-ui.sidebar collapsible="offcanvas">
        <x-ui.sidebar.header>
            <x-ui.sidebar.menu>
                <x-ui.sidebar.menu-item>
                    <x-ui.sidebar.menu-button size="lg" href="/">Acme</x-ui.sidebar.menu-button>
                </x-ui.sidebar.menu-item>
            </x-ui.sidebar.menu>
        </x-ui.sidebar.header>

        <x-ui.sidebar.content>
            <x-ui.sidebar.group>
                <x-ui.sidebar.group-label>Platform</x-ui.sidebar.group-label>
                <x-ui.sidebar.menu>
                    @foreach ($links as $link)
                        <x-ui.sidebar.menu-item>
                            <x-ui.sidebar.menu-button :href="$link['href']" :tooltip="$link['label']"
                                :is-active="request()->is(ltrim($link['href'], '/'))">
                                {{ $link['label'] }}
                            </x-ui.sidebar.menu-button>
                        </x-ui.sidebar.menu-item>
                    @endforeach
                </x-ui.sidebar.menu>
            </x-ui.sidebar.group>
        </x-ui.sidebar.content>
    </x-ui.sidebar>

    <x-ui.sidebar.inset>
        <x-ui.sidebar.trigger />
        {{ $slot }}
    </x-ui.sidebar.inset>
</x-ui.sidebar.provider>
```

## Example

The demo rendered on the docs page.

```blade
{{-- The sidebar is a full-height, fixed-position layout primitive, so it cannot
     embed directly inside the docs column. We preview the real sidebar-01 block
     in an iframe; open it standalone for the full experience. --}}
<div class="space-y-3">
    <div class="overflow-hidden rounded-lg border border-border" style="height: 520px">
        <iframe src="{{ route('blocks.preview', 'sidebar-01') }}"
            class="h-full w-full" title="sidebar-01 block"
            loading="lazy"></iframe>
    </div>
    <a href="{{ route('blocks.preview', 'sidebar-01') }}" target="_blank"
        rel="noopener noreferrer"
        class="inline-flex text-sm text-muted-foreground underline underline-offset-4 hover:text-foreground">
        Open the sidebar-01 block full screen &rarr;
    </a>
</div>
```

## Source

Copy each file to the path shown above it.

`resources/views/components/ui/sidebar/provider.blade.php`

```blade
@props([
    'open' => true,
])

@php
    $classes = \TailwindMerge\Laravel\Facades\TailwindMerge::merge(
        'group/sidebar-wrapper flex min-h-svh w-full has-data-[variant=inset]:bg-sidebar',
        $attributes->get('class'),
    );
@endphp

<div data-ui-sidebar-provider data-state="{{ $open ? 'expanded' : 'collapsed' }}"
    style="--sidebar-width: 16rem; --sidebar-width-icon: 3rem; --sidebar-width-mobile: 18rem;"
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
            if (window.__laralcnSidebar) return;
            window.__laralcnSidebar = true;

            var mobile = window.matchMedia('(max-width: 767px)');

            function sidebars(provider) {
                return provider.querySelectorAll('[data-ui-sidebar]');
            }

            function apply(provider) {
                var expanded = provider.dataset.state === 'expanded';

                sidebars(provider).forEach(function(sidebar) {
                    sidebar.dataset.state = provider.dataset.state;
                    // Children style themselves off data-collapsible, which only
                    // means anything while the sidebar is actually collapsed.
                    sidebar.dataset.collapsible = expanded ? '' : (sidebar.dataset.mode || 'icon');
                });
            }

            function toggle(provider) {
                if (mobile.matches) {
                    var panel = provider.querySelector('[data-ui-sidebar-mobile]');
                    if (!panel) return;

                    if (panel.open) {
                        panel.dataset.state = 'closed';
                        document.documentElement.style.overflow = '';
                        setTimeout(function() {
                            panel.close();
                        }, 300);
                    } else {
                        panel.showModal();
                        document.documentElement.style.overflow = 'hidden';
                        // Flush the closed frame before flipping the state; a
                        // requestAnimationFrame would not fire in a background
                        // tab and the panel would open off-screen.
                        panel.getBoundingClientRect();
                        panel.dataset.state = 'open';
                    }
                    return;
                }

                provider.dataset.state = provider.dataset.state === 'expanded' ? 'collapsed' : 'expanded';
                document.cookie = 'sidebar_state=' + (provider.dataset.state === 'expanded') +
                    '; path=/; max-age=604800';
                apply(provider);
            }

            document.addEventListener('DOMContentLoaded', function() {
                document.querySelectorAll('[data-ui-sidebar-provider]').forEach(apply);
            });

            document.addEventListener('click', function(event) {
                var trigger = event.target.closest('[data-ui-sidebar-trigger]');
                if (trigger) {
                    toggle(trigger.closest('[data-ui-sidebar-provider]'));
                    return;
                }

                var backdrop = event.target.closest('[data-ui-sidebar-mobile-close]');
                if (backdrop) toggle(backdrop.closest('[data-ui-sidebar-provider]'));
            });

            document.addEventListener('cancel', function(event) {
                if (!event.target.matches('[data-ui-sidebar-mobile]')) return;
                event.preventDefault();
                toggle(event.target.closest('[data-ui-sidebar-provider]'));
            });

            document.addEventListener('keydown', function(event) {
                if (event.key !== 'b' || !(event.metaKey || event.ctrlKey)) return;

                var provider = document.querySelector('[data-ui-sidebar-provider]');
                if (!provider) return;

                event.preventDefault();
                toggle(provider);
            });
        })();
    </script>
@endonce
```

`resources/views/components/ui/sidebar/index.blade.php`

```blade
@props([
    'side' => 'left',
    'collapsible' => 'icon',
])

@php
    $isIcon = $collapsible === 'icon';
    $offscreen = $side === 'right' ? 'translate-x-full' : '-translate-x-full';

    // Layout gap the sidebar reserves: full → icon width → 0 (offcanvas).
    $gapWidth = $isIcon
        ? 'w-(--sidebar-width) group-data-[state=collapsed]:w-(--sidebar-width-icon)'
        : 'w-(--sidebar-width) group-data-[state=collapsed]:w-0';

    // The fixed panel: icon mode shrinks its width; offcanvas keeps full width
    // and slides off-screen (so icon-mode tooltips are never clipped).
    // Every variant class below is written out in full: Tailwind scans source
    // text, so a class assembled by interpolation never gets compiled.
    $panelWidth = match (true) {
        $isIcon => 'w-(--sidebar-width) group-data-[state=collapsed]:w-(--sidebar-width-icon)',
        $side === 'right' => 'w-(--sidebar-width) group-data-[state=collapsed]:translate-x-full',
        default => 'w-(--sidebar-width) group-data-[state=collapsed]:-translate-x-full',
    };
@endphp

{{-- Desktop sidebar. data-state / data-collapsible are kept in sync by the
     script that ships with <x-ui.sidebar.provider>. --}}
<div class="group peer hidden text-sidebar-foreground md:block" data-ui-sidebar
    data-mode="{{ $collapsible }}" data-state="expanded" data-collapsible=""
    data-side="{{ $side }}" data-variant="sidebar">
    {{-- gap handler --}}
    <div
        class="relative bg-transparent transition-[width] duration-200 ease-linear {{ $gapWidth }}">
    </div>
    {{-- fixed panel --}}
    <div class="fixed inset-y-0 z-10 hidden h-svh transition-[left,right,width,transform] duration-200 ease-linear md:flex {{ $side === 'right' ? 'right-0' : 'left-0' }} {{ $panelWidth }}"
        data-sidebar="sidebar">
        <div
            class="flex h-full w-full flex-col bg-sidebar {{ $side === 'right' ? 'border-l' : 'border-r' }} border-sidebar-border">
            {{ $slot }}
        </div>
    </div>
</div>

{{-- Mobile sidebar: a native <dialog>, so it lands in the top layer with a
     focus trap and Esc handling already wired. --}}
<dialog data-ui-sidebar-mobile data-state="closed"
    class="group/mobile m-0 h-full max-h-none w-full max-w-none bg-transparent p-0 backdrop:bg-transparent md:hidden">
    {{-- Not bg-black/50. Tailwind v4 compiles that to oklab(0 0 0 / 0.5), and
         Chrome does not paint a modern colour function with alpha inside the
         dialog top layer, so the scrim silently renders as nothing. A legacy
         rgb() with alpha composites correctly in the same place. --}}
    <div class="absolute inset-0 bg-[rgb(0_0_0/0.5)] opacity-0 transition-opacity duration-300 group-data-[state=open]/mobile:opacity-100"
        data-ui-sidebar-mobile-close></div>

    <div class="absolute inset-y-0 flex h-svh w-(--sidebar-width-mobile) flex-col bg-sidebar text-sidebar-foreground transition-transform duration-300 ease-in-out {{ $side === 'right' ? 'right-0' : 'left-0' }} {{ $offscreen }} group-data-[state=open]/mobile:translate-x-0"
        data-sidebar="sidebar" data-mobile="true">
        <div class="flex h-full w-full flex-col">
            {{ $slot }}
        </div>
    </div>
</dialog>
```

`resources/views/components/ui/sidebar/trigger.blade.php`

```blade
@props([])

@php
    $classes = \TailwindMerge\Laravel\Facades\TailwindMerge::merge(
        'inline-flex size-7 shrink-0 items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium outline-none transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:ring-ring/50 focus-visible:ring-[3px] disabled:pointer-events-none disabled:opacity-50 [&_svg]:size-4 [&_svg]:shrink-0',
        $attributes->get('class'),
    );
@endphp

<button type="button" aria-label="Toggle Sidebar" data-ui-sidebar-trigger
    {{ $attributes->except('class')->merge(['class' => $classes]) }}>
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
        stroke="currentColor" stroke-width="2" stroke-linecap="round"
        stroke-linejoin="round" aria-hidden="true">
        <rect width="18" height="18" x="3" y="3" rx="2" />
        <path d="M9 3v18" />
    </svg>
    <span class="sr-only">Toggle Sidebar</span>
</button>
```

`resources/views/components/ui/sidebar/rail.blade.php`

```blade
@props([])

@php
    $classes = \TailwindMerge\Laravel\Facades\TailwindMerge::merge(
        'absolute inset-y-0 z-20 hidden w-4 -translate-x-1/2 cursor-w-resize transition-all ease-linear after:absolute after:inset-y-0 after:left-1/2 after:w-[2px] hover:after:bg-sidebar-border group-data-[side=left]:-right-4 group-data-[side=right]:left-0 sm:flex',
        $attributes->get('class'),
    );
@endphp

<button type="button" aria-label="Toggle Sidebar" tabindex="-1"
    title="Toggle Sidebar" data-ui-sidebar-trigger
    {{ $attributes->except('class')->merge(['class' => $classes]) }}></button>
```

`resources/views/components/ui/sidebar/inset.blade.php`

```blade
@props([])

@php
    $classes = \TailwindMerge\Laravel\Facades\TailwindMerge::merge(
        'relative flex min-h-svh flex-1 flex-col bg-background',
        $attributes->get('class'),
    );
@endphp

<main {{ $attributes->except('class')->merge(['class' => $classes]) }}>
    {{ $slot }}
</main>
```

`resources/views/components/ui/sidebar/header.blade.php`

```blade
@props([])

@php
    $classes = \TailwindMerge\Laravel\Facades\TailwindMerge::merge(
        'flex flex-col gap-2 p-2',
        $attributes->get('class'),
    );
@endphp

<div data-sidebar="header"
    {{ $attributes->except('class')->merge(['class' => $classes]) }}>
    {{ $slot }}
</div>
```

`resources/views/components/ui/sidebar/footer.blade.php`

```blade
@props([])

@php
    $classes = \TailwindMerge\Laravel\Facades\TailwindMerge::merge(
        'flex flex-col gap-2 p-2',
        $attributes->get('class'),
    );
@endphp

<div data-sidebar="footer"
    {{ $attributes->except('class')->merge(['class' => $classes]) }}>
    {{ $slot }}
</div>
```

`resources/views/components/ui/sidebar/content.blade.php`

```blade
@props([])

@php
    $classes = \TailwindMerge\Laravel\Facades\TailwindMerge::merge(
        'flex min-h-0 flex-1 flex-col gap-2 overflow-auto group-data-[collapsible=icon]:overflow-hidden',
        $attributes->get('class'),
    );
@endphp

<div data-sidebar="content"
    {{ $attributes->except('class')->merge(['class' => $classes]) }}>
    {{ $slot }}
</div>
```

`resources/views/components/ui/sidebar/group.blade.php`

```blade
@props([])

@php
    $classes = \TailwindMerge\Laravel\Facades\TailwindMerge::merge(
        'relative flex w-full min-w-0 flex-col p-2',
        $attributes->get('class'),
    );
@endphp

<div data-sidebar="group"
    {{ $attributes->except('class')->merge(['class' => $classes]) }}>
    {{ $slot }}
</div>
```

`resources/views/components/ui/sidebar/group-label.blade.php`

```blade
@props([])

@php
    $classes = \TailwindMerge\Laravel\Facades\TailwindMerge::merge(
        'flex h-8 shrink-0 items-center rounded-md px-2 text-xs font-medium text-sidebar-foreground/70 outline-none ring-sidebar-ring transition-[margin,opacity] duration-200 ease-linear focus-visible:ring-2 [&>svg]:size-4 [&>svg]:shrink-0 group-data-[collapsible=icon]:-mt-8 group-data-[collapsible=icon]:opacity-0',
        $attributes->get('class'),
    );
@endphp

<div data-sidebar="group-label"
    {{ $attributes->except('class')->merge(['class' => $classes]) }}>
    {{ $slot }}
</div>
```

`resources/views/components/ui/sidebar/group-content.blade.php`

```blade
@props([])

@php
    $classes = \TailwindMerge\Laravel\Facades\TailwindMerge::merge(
        'w-full text-sm',
        $attributes->get('class'),
    );
@endphp

<div data-sidebar="group-content"
    {{ $attributes->except('class')->merge(['class' => $classes]) }}>
    {{ $slot }}
</div>
```

`resources/views/components/ui/sidebar/group-action.blade.php`

```blade
@props([])

@php
    $classes = \TailwindMerge\Laravel\Facades\TailwindMerge::merge(
        'absolute right-3 top-3.5 flex aspect-square w-5 items-center justify-center rounded-md p-0 text-sidebar-foreground outline-none ring-sidebar-ring transition-transform hover:bg-sidebar-accent hover:text-sidebar-accent-foreground focus-visible:ring-2 [&>svg]:size-4 [&>svg]:shrink-0 group-data-[collapsible=icon]:hidden',
        $attributes->get('class'),
    );
@endphp

<button type="button" data-sidebar="group-action"
    {{ $attributes->except('class')->merge(['class' => $classes]) }}>
    {{ $slot }}
</button>
```

`resources/views/components/ui/sidebar/menu.blade.php`

```blade
@props([])

@php
    $classes = \TailwindMerge\Laravel\Facades\TailwindMerge::merge(
        'flex w-full min-w-0 flex-col gap-1',
        $attributes->get('class'),
    );
@endphp

<ul data-sidebar="menu"
    {{ $attributes->except('class')->merge(['class' => $classes]) }}>
    {{ $slot }}
</ul>
```

`resources/views/components/ui/sidebar/menu-item.blade.php`

```blade
@props([])

@php
    $classes = \TailwindMerge\Laravel\Facades\TailwindMerge::merge(
        'group/menu-item relative',
        $attributes->get('class'),
    );
@endphp

<li data-sidebar="menu-item"
    {{ $attributes->except('class')->merge(['class' => $classes]) }}>
    {{ $slot }}
</li>
```

`resources/views/components/ui/sidebar/menu-button.blade.php`

```blade
@props([
    'variant' => 'default',
    'size' => 'default',
    'isActive' => false,
    'href' => null,
    'tooltip' => null,
])

@php
    $base =
        'peer/menu-button flex w-full items-center gap-2 overflow-hidden rounded-md p-2 text-left text-sm outline-none ring-sidebar-ring transition-[width,height,padding] hover:bg-sidebar-accent hover:text-sidebar-accent-foreground focus-visible:ring-2 active:bg-sidebar-accent active:text-sidebar-accent-foreground disabled:pointer-events-none disabled:opacity-50 aria-disabled:pointer-events-none aria-disabled:opacity-50 data-[active=true]:bg-sidebar-accent data-[active=true]:font-medium data-[active=true]:text-sidebar-accent-foreground group-data-[collapsible=icon]:size-8! group-data-[collapsible=icon]:p-2 [&>span:last-child]:truncate [&>svg]:size-4 [&>svg]:shrink-0';

    $variants = match ($variant) {
        'outline'
            => 'bg-background shadow-[0_0_0_1px_var(--sidebar-border)] hover:shadow-[0_0_0_1px_var(--sidebar-accent)]',
        default => '',
    };

    $sizes = match ($size) {
        'sm' => 'h-7 text-xs',
        'lg' => 'h-12 text-sm group-data-[collapsible=icon]:p-0!',
        default => 'h-8 text-sm',
    };

    $classes = \TailwindMerge\Laravel\Facades\TailwindMerge::merge(
        $base,
        $variants,
        $sizes,
        $attributes->get('class'),
    );

    $tag = $href ? 'a' : 'button';
@endphp

{{-- The button and tooltip stay SIBLINGS (no wrapper) so menu-badge/menu-action
     keep their `peer/menu-button` relationship. The tooltip is pure CSS: shown
     only when the sidebar is collapsed to icons and the button is hovered. --}}
<{{ $tag }} data-sidebar="menu-button" data-size="{{ $size }}"
    data-active="{{ $isActive ? 'true' : 'false' }}"
    @if ($href) href="{{ $href }}" @else type="button" @endif
    {{ $attributes->except('class')->merge(['class' => $classes]) }}>
    {{ $slot }}
</{{ $tag }}>
@if ($tooltip)
    <span role="tooltip"
        class="pointer-events-none absolute left-full top-1/2 z-50 ml-2 hidden w-fit -translate-y-1/2 whitespace-nowrap rounded-md bg-primary px-3 py-1.5 text-xs text-primary-foreground group-data-[collapsible=icon]:peer-hover/menu-button:block">
        {{ $tooltip }}
    </span>
@endif
```

`resources/views/components/ui/sidebar/menu-action.blade.php`

```blade
@props([])

@php
    $classes = \TailwindMerge\Laravel\Facades\TailwindMerge::merge(
        'absolute right-1 top-1.5 flex aspect-square w-5 items-center justify-center rounded-md p-0 text-sidebar-foreground outline-none ring-sidebar-ring transition-transform hover:bg-sidebar-accent hover:text-sidebar-accent-foreground focus-visible:ring-2 peer-hover/menu-button:text-sidebar-accent-foreground [&>svg]:size-4 [&>svg]:shrink-0 group-data-[collapsible=icon]:hidden',
        $attributes->get('class'),
    );
@endphp

<button type="button" data-sidebar="menu-action"
    {{ $attributes->except('class')->merge(['class' => $classes]) }}>
    {{ $slot }}
</button>
```

`resources/views/components/ui/sidebar/menu-badge.blade.php`

```blade
@props([])

@php
    $classes = \TailwindMerge\Laravel\Facades\TailwindMerge::merge(
        'pointer-events-none absolute right-1 flex h-5 min-w-5 select-none items-center justify-center rounded-md px-1 text-xs font-medium tabular-nums text-sidebar-foreground peer-hover/menu-button:text-sidebar-accent-foreground peer-data-[active=true]/menu-button:text-sidebar-accent-foreground peer-data-[size=sm]/menu-button:top-1 peer-data-[size=default]/menu-button:top-1.5 peer-data-[size=lg]/menu-button:top-2.5 group-data-[collapsible=icon]:hidden',
        $attributes->get('class'),
    );
@endphp

<div data-sidebar="menu-badge"
    {{ $attributes->except('class')->merge(['class' => $classes]) }}>
    {{ $slot }}
</div>
```

`resources/views/components/ui/sidebar/menu-skeleton.blade.php`

```blade
@props([
    'showIcon' => false,
])

@php
    $classes = \TailwindMerge\Laravel\Facades\TailwindMerge::merge(
        'flex h-8 items-center gap-2 rounded-md px-2',
        $attributes->get('class'),
    );
@endphp

<div data-sidebar="menu-skeleton"
    {{ $attributes->except('class')->merge(['class' => $classes]) }}>
    @if ($showIcon)
        <div class="size-4 shrink-0 animate-pulse rounded-md bg-accent"
            data-sidebar="menu-skeleton-icon"></div>
    @endif
    <div class="h-4 max-w-(--skeleton-width) flex-1 animate-pulse rounded-md bg-accent"
        data-sidebar="menu-skeleton-text"
        style="--skeleton-width: {{ random_int(50, 90) }}%"></div>
</div>
```

`resources/views/components/ui/sidebar/menu-sub.blade.php`

```blade
@props([])

@php
    $classes = \TailwindMerge\Laravel\Facades\TailwindMerge::merge(
        'mx-3.5 flex min-w-0 translate-x-px flex-col gap-1 border-l border-sidebar-border px-2.5 py-0.5 group-data-[collapsible=icon]:hidden',
        $attributes->get('class'),
    );
@endphp

<ul data-sidebar="menu-sub"
    {{ $attributes->except('class')->merge(['class' => $classes]) }}>
    {{ $slot }}
</ul>
```

`resources/views/components/ui/sidebar/menu-sub-item.blade.php`

```blade
@props([])

@php
    $classes = \TailwindMerge\Laravel\Facades\TailwindMerge::merge(
        'group/menu-sub-item relative',
        $attributes->get('class'),
    );
@endphp

<li data-sidebar="menu-sub-item"
    {{ $attributes->except('class')->merge(['class' => $classes]) }}>
    {{ $slot }}
</li>
```

`resources/views/components/ui/sidebar/menu-sub-button.blade.php`

```blade
@props([
    'size' => 'md',
    'isActive' => false,
    'href' => null,
])

@php
    $sizes = match ($size) {
        'sm' => 'text-xs',
        default => 'text-sm',
    };

    $classes = \TailwindMerge\Laravel\Facades\TailwindMerge::merge(
        'flex h-7 min-w-0 -translate-x-px items-center gap-2 overflow-hidden rounded-md px-2 text-sidebar-foreground outline-none ring-sidebar-ring transition-[width,height,padding] hover:bg-sidebar-accent hover:text-sidebar-accent-foreground focus-visible:ring-2 active:bg-sidebar-accent active:text-sidebar-accent-foreground disabled:pointer-events-none disabled:opacity-50 aria-disabled:pointer-events-none aria-disabled:opacity-50 data-[active=true]:bg-sidebar-accent data-[active=true]:text-sidebar-accent-foreground [&>span:last-child]:truncate [&>svg]:size-4 [&>svg]:shrink-0 [&>svg]:text-sidebar-accent-foreground group-data-[collapsible=icon]:hidden',
        $sizes,
        $attributes->get('class'),
    );

    $tag = $href ? 'a' : 'button';
@endphp

<{{ $tag }} data-sidebar="menu-sub-button" data-size="{{ $size }}"
    data-active="{{ $isActive ? 'true' : 'false' }}"
    @if ($href) href="{{ $href }}" @else type="button" @endif
    {{ $attributes->except('class')->merge(['class' => $classes]) }}>
    {{ $slot }}
</{{ $tag }}>
```

`resources/views/components/ui/sidebar/separator.blade.php`

```blade
@props([])

@php
    $classes = \TailwindMerge\Laravel\Facades\TailwindMerge::merge(
        'mx-2 h-px w-auto shrink-0 bg-sidebar-border',
        $attributes->get('class'),
    );
@endphp

<div role="separator" aria-orientation="horizontal" data-sidebar="separator"
    {{ $attributes->except('class')->merge(['class' => $classes]) }}></div>
```

## Theme tokens

`--sidebar`, `--sidebar-foreground`, `--sidebar-accent`, `--sidebar-accent-foreground`, `--sidebar-border`, `--sidebar-ring`, `--primary`, `--primary-foreground`, `--background`

Ships its own inline <script>; no JS dependencies (the mobile off-canvas is a native <dialog>). Wrap everything in <x-ui.sidebar.provider>; place <x-ui.sidebar> (with side=left|right, collapsible=icon|offcanvas|none) and your page content in <x-ui.sidebar.inset>. State (expand/collapse) persists to a `sidebar_state` cookie and toggles with Ctrl/Cmd-B. Self-contained: composes no other registry components.

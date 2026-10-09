---
name: "dahsboard-design"
description: "Every component in DashboardDesign. Copy one into your project, or install it with the CLI."
---

# Installation

## 1. Install the CLI

Require the package as a dev dependency. It ships only an Artisan CLI, nothing is added to your production runtime.

```bash
composer require --dev safi/laralcn-ui
```

```bash
php artisan ui:init
```

## Example to add new componet
```bash 
php artisan ui:add button
```

# Dashboard Design Components

35 components, grouped by category. Each reference file is stored locally in `./references/` and contains the description, install command, dependencies, usage examples, and full Blade source.

**IMPORTANT FOR AI:** All component documentation is available locally in the `references/` folder. Do NOT read from `vendor/`. When you need a component, read the corresponding `.md` file from `references/`.

## Navigation

- [accordion](references/accordion.md): Collapsible disclosure list (single or multiple open).
- [breadcrumb](references/breadcrumb.md): Shows the path to the current resource as a hierarchy of links.
- [pagination](references/pagination.md): Page navigation with previous, next, numbered links and an ellipsis.
- [tabs](references/tabs.md): Tabbed interface; panels supplied via named slots.

## Feedback

- [alert](references/alert.md): Callout for short, important messages.
- [progress](references/progress.md): Progress bar showing completion of a task.
- [skeleton](references/skeleton.md): Placeholder shown while content loads.
- [toast](references/toast.md): Toast region; dispatch a `toast` window event to show one.

## Overlays

- [alert-dialog](references/alert-dialog.md): Modal that interrupts the user and waits for a deliberate response.
- [dialog](references/dialog.md): Modal dialog with focus trap, backdrop, and Esc-to-close.
- [dropdown-menu](references/dropdown-menu.md): Click-triggered menu with outside-click and Esc to close.
- [hover-card](references/hover-card.md): Preview card shown when a trigger is hovered or focused.
- [sheet](references/sheet.md): Panel that slides in from an edge of the screen.
- [tooltip](references/tooltip.md): Hover/focus tooltip with side positioning.

## Layout

- [aspect-ratio](references/aspect-ratio.md): Holds its children to a fixed width-to-height ratio.
- [card](references/card.md): Container with header, content, and footer slots.
- [collapsible](references/collapsible.md): An interactive panel that expands and collapses its content.
- [scroll-area](references/scroll-area.md): Scrollable region with a styled, themed scrollbar.
- [separator](references/separator.md): Visual or semantic divider.
- [sidebar](references/sidebar.md): Composable, collapsible application sidebar with mobile off-canvas, icon mode, and keyboard toggle.

## Data Display

- [avatar](references/avatar.md): User image with text fallback.
- [badge](references/badge.md): Small status / label pill with variants.
- [carousel](references/carousel.md): Horizontally scrolling slides with snap points and arrow controls.
- [table](references/table.md): Responsive data table wrapper.

## Forms

- [button](references/button.md): Clickable button with variant and size options.
- [checkbox](references/checkbox.md): Native checkbox styled to the theme.
- [input](references/input.md): Form text input.
- [label](references/label.md): Accessible form label.
- [radio](references/radio.md): Native radio input styled to the theme.
- [select](references/select.md): Native select styled to the theme.
- [slider](references/slider.md): Range slider built on a native input, submits with the form.
- [switch](references/switch.md): Toggle switch built on a native checkbox, submits with the form.
- [textarea](references/textarea.md): Multi-line text input.
- [toggle](references/toggle.md): Two-state button built on a native checkbox, submits with the form.
- [toggle-group](references/toggle-group.md): A joined set of toggles, single or multiple selection.

## How to Use

When you need to use a component:

1. Read the corresponding `.md` file from `references/` (e.g., `references/collapsible.md`).
2. The file contains: description, installation command, dependencies, usage examples, and full Blade source code.
3. Copy the Blade source into your project as instructed in the "Source" section of the file.
4. Run `php artisan ui:add [component]` if the CLI is available, or manually copy the files to `resources/views/components/ui/`.

## Notes

- All reference files use the same frontmatter format: `title`, `description`, `category`.
- Each file includes a `## Source` section with the full Blade code for every sub-component.
- Some components require additional Composer dependencies (e.g., `gehrisandro/tailwind-merge-laravel`), which are listed in the `## Dependencies` section.
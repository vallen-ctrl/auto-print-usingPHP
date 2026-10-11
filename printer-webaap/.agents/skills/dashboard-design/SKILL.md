---
name: "dashboard-design"
description: "Use when building dashboard, login, and admin pages in a Laravel + Blade + Vite project. Uses Flowbite (Tailwind CSS v4) as the component library, styled with a natural sage green + warm neutral palette. Mobile-first, responsive, accessible."
metadata:
  author: typeui.sh
---

# Printer-app Dashboard Design Skill

## Mission
Build production-ready dashboard, login, and admin pages as working code, not as guideline documents. Mobile-first, responsive, accessible, calm and natural in feel. Data readability comes first.

## Stack (fixed)
- Laravel + Blade templates + Vite
- Tailwind CSS v4 + Flowbite (MIT components only)
- No Livewire, no Inertia, no Vue/React
- Icons: one set only (Lucide or Flowbite Icons). Never mix.

## Flowbite Setup (Laravel + Vite)
Assets always load through Vite. Never use CDN tags.

1. Install:
```bash
npm install flowbite
npm install tailwindcss @tailwindcss/vite   # skip if already installed
```

2. `vite.config.js` must include the Tailwind plugin:
```js
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        tailwindcss(),
    ],
});
```

3. `resources/css/app.css` (order matters):
```css
@import "tailwindcss";
@import "flowbite/src/themes/default";
@plugin "flowbite/plugin";
@source "../../node_modules/flowbite";
@source "../views";

/* class-based dark mode: toggle `.dark` on <html> */
@custom-variant dark (&:where(.dark, .dark *));

/* Override theme tokens BELOW this line, after the Flowbite import */
```

4. `resources/js/app.js`:
```js
import 'flowbite';
```

5. In the Blade layout `<head>`:
```blade
@vite(['resources/css/app.css', 'resources/js/app.js'])
```

## Laravel / Blade Rules
- `@source` paths are relative to the CSS file location (`resources/css/`), hence `../../node_modules/flowbite` and `../views`.
- Extract repeated Flowbite markup into Blade components in `resources/views/components/` (e.g. `<x-button>`, `<x-stat-card>`, `<x-modal>`, `<x-badge>`).
- Pick one layout approach and stay consistent: `@extends`/`@section` or a layout component (`<x-layouts.app>`).
- Use Flowbite data attributes directly in Blade (`data-modal-target`, `data-modal-toggle`, `data-drawer-target`, etc.). No extra JS needed.
- Only if HTML is injected via JavaScript (fetch/AJAX), call `initFlowbite()` after the DOM changes:
```js
import { initFlowbite } from 'flowbite';
initFlowbite();
```
- After `npm run dev` / `npm run build`, confirm `public/build` is generated and the page loads Flowbite CSS and JS.

## How to Use Flowbite With This Design System
- Use Flowbite components first, customize second. Do not hand-build modals, dropdowns, drawers, tooltips, tabs, or toasts.
- Start from Flowbite examples for: Sidebar, Navbar, Drawer, Table, Modal, Toast, Dropdown, Tabs, Badge, Form inputs, Skeleton, Alert. Then restyle with the tokens below.
- Map the design tokens onto Flowbite by overriding its theme variables in `app.css` after the Flowbite import. Read the real variable names from `node_modules/flowbite/src/themes/default.css` first. Never guess them.
- Design tokens always win over Flowbite defaults. No default Flowbite blue or gray may remain in the output.
- Fonts: Flowbite's default theme imports Inter. This project uses Inter (headings), Roboto (body), JetBrains Mono (numbers/code). Remove or override the font import accordingly.
- Flowbite defaults may be smaller than this spec. Always enforce: 44x44px touch targets, 40px button height, 2px primary focus ring, 4.5:1 contrast, radius 6/12/999.
- Do not copy Flowbite Pro or Blocks code (requires a license).

## Icons (Lucide via Blade)
- Icons use the installed `mallardduck/blade-lucide-icons` package as Blade components.
- The list of available icons is `.agents/skills/dashboard-design/references/lucide-icons.txt` (one component name per line, e.g. `lucide-printer`). A line `lucide-printer` becomes `<x-lucide-printer />`. A line `lucide-lab-bat` becomes `<x-lucide-lab-bat />`.
- NEVER read or open the whole file. ALWAYS search it with a command first.

### Search procedure (follow in order)
1. Search by keyword with a command (run from the project root, Windows PowerShell):
```powershell
Select-String -Path ".agents/skills/dashboard-design/references/lucide-icons.txt" -Pattern "printer" | ForEach-Object { $_.Line }
```
   - Several keywords at once (OR): `-Pattern "printer","file","upload"`
   - Search is case-insensitive.
   - For short keywords (3 letters or fewer) anchor the pattern to avoid noise. Example for "ad": `-Pattern "^lucide-ad(-|$)"`.
   - If the shell is bash/zsh instead: `grep -i "printer" .agents/skills/dashboard-design/references/lucide-icons.txt`
2. If there is no result, search again with synonyms or related concepts (max 3 more searches). Examples: ad -> megaphone, badge, tag; delete -> trash, x; settings -> cog, sliders; warning -> triangle-alert, alert-triangle.
   - Some icons were renamed across versions (e.g. `alert-circle` vs `circle-alert`). Search both forms.
3. If still nothing, pick the closest icon from the results you saw. Never invent a name.
4. Before using an icon, confirm its exact name appeared in a search result. Only then write `<x-lucide-NAME />`.
5. Search ALL the icons a page needs in one command (multiple patterns) instead of one command per icon.

### Usage rules
- Style with classes, not inline SVG: `<x-lucide-printer class="size-5 text-primary" />`.
- Icon-only buttons must have an accessible label (`aria-label` on the button).
- Decorative icons next to text get `aria-hidden="true"`.

## Design Tokens

### Color (all text must pass 4.5:1 contrast on its surface)
- bg: #F7F5F0
- surface: #FFFFFF
- surface-muted: #F0EDE6
- border: #E5E1D8
- text: #1C1917
- text-muted: #57534E
- primary: #2F6F4E (hover #275C41, soft #E3EFE8)
- secondary: #9A5B34 (soft #F3E6DC)
- success: #2E7D32 | warning: #A15C07 | danger: #B42318 | info: #1F6F8B
- Dark mode: bg #151412, surface #1E1C19, border #2E2B26, text #F5F3EE, text-muted #A8A29A, primary #6FBF94

### Typography
- Fonts: Inter (headings/display), Roboto (body), JetBrains Mono (numbers/code)
- Scale: 12/14/16/20/24/32 | weights: 400, 500, 600, 700
- Body 14-16px, line-height 1.5. Headings line-height 1.25.

### Spacing, radius, shadow
- Spacing scale: 4/8/12/16/24/32/48/64
- Radius: 6 (input/button), 12 (card), 999 (badge)
- Shadow: one soft level only: `0 1px 2px rgba(28,25,23,.06)`. Prefer borders over shadows.

## Responsive Layout (mobile-first)
- Breakpoints: 360 / 640 / 768 / 1024 / 1280
- Below 1024px: sidebar becomes a drawer (Flowbite Drawer, hamburger button in topbar). Closes on Esc and overlay click.
- 1024px and up: fixed 240px sidebar, collapsible to a 72px icon-only rail.
- Content max-width 1280px. Padding: 16px (mobile) / 24px (tablet) / 32px (desktop).
- Stat card grid: 1 column (<640), 2 columns (640-1023), 4 columns (>=1024).
- Tables: below 768px, convert to a card list, or wrap in `overflow-x-auto` with the primary column sticky.
- Touch targets at least 44x44px.
- No page-level horizontal scroll, ever.

## Components
Every component must define these states: default, hover, focus-visible (2px primary ring, 2px offset), active, disabled, loading, error.
- Button: primary / secondary / ghost / danger. Height 40px (sm 32px).
- Input: label always visible (never placeholder-only). Error message below the field with icon + text.
- Also: Card, status Badge, Table (sticky header, optional zebra), Tabs, Modal, Toast. All come from Flowbite, restyled with tokens.
- Login page: centered card, max 400px, logo, form, forgot-password link, full-width submit button.

## Empty / Loading / Error States
- Loading: skeletons (Flowbite Skeleton), not a blank spinner.
- Empty: icon + one sentence + one action button.
- Error: clear message + retry button.

## Accessibility (WCAG 2.2 AA)
- Text contrast >= 4.5:1. UI components >= 3:1.
- Everything usable by keyboard with a logical tab order.
- Modals and drawers trap focus and close on Esc. Verify after restyling.
- Status must never rely on color alone. Add an icon or text.
- Respect `prefers-reduced-motion`. Animations max 200ms.

## Do
- Use semantic tokens (`--color-primary`), not raw hex values.
- Keep spacing consistent with the scale.
- Use Blade components to avoid duplicated Flowbite markup.
- Use specific button labels such as "Save changes".

## Don't
- Don't use CDN links for Flowbite or Tailwind.
- Don't use Livewire, Inertia, Vue, or React.
- Don't leave Flowbite's default blue/gray palette or fonts in the output.
- Don't use saturated (neon blue/purple) colors as large backgrounds.
- Don't put light gray text on the cream background.
- Don't use ambiguous labels like "Submit" or "OK".
- Don't forget to import `flowbite` in `app.js`; components look fine but won't work without it.

## QA Checklist
- [ ] `vite.config.js` has `tailwindcss()`, and the layout loads assets via `@vite` with no CDN tags
- [ ] Flowbite modal, dropdown, and drawer open/close correctly on Blade pages
- [ ] Checked at 360px, 768px, and 1280px with no horizontal scroll
- [ ] All text passes 4.5:1 contrast
- [ ] Fully keyboard navigable, focus always visible
- [ ] Empty, loading, and error states exist
- [ ] No raw hex values outside the token definitions
- [ ] Dark mode uses its own tokens, not the light ones
- [ ] No leftover default Flowbite colors or fonts
- [ ] Every icon name used was found via a search command in `references/lucide-icons.txt` (no invented names)
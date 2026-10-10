---
name: "dashboard-design"
description: "Use when building dashboard pages, login pages, and admin pages that are responsive with a natural color palette (sage green + warm neutral). Applies to HTML/CSS, Tailwind, and React."
metadata:
  author: typeui.sh
---

# Printer-app Dashboard Design Skill

## Mission
Build production-ready dashboard, login, and admin pages as working code, not as guideline documents. Mobile-first, responsive, accessible, with a calm and natural feel.

## Brand
Printer-app: clean, warm, understated. Data readability comes first.

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
- Scale: 12/14/16/20/24/32 | weights used: 400, 500, 600, 700
- Body 14-16px with line-height 1.5; headings with line-height 1.25

### Spacing, radius, shadow
- Spacing: 4/8/12/16/24/32/48/64
- Radius: 6 (input/button), 12 (card), 999 (badge)
- Shadow: a single soft level only: 0 1px 2px rgba(28,25,23,.06). Prefer borders over shadows.

## Responsive Layout (mobile-first)
- Breakpoints: 360 / 640 / 768 / 1024 / 1280
- Below 1024px: sidebar becomes a drawer (hamburger button in topbar), closes on Esc and overlay click
- 1024px and up: fixed 240px sidebar (collapsible to a 72px icon-only rail)
- Content max-width 1280px, padding 16px (mobile) / 24px (tablet) / 32px (desktop)
- Stat card grid: 1 column (<640), 2 columns (640-1023), 4 columns (>=1024)
- Tables: below 768px, convert to a card list or wrap in overflow-x-auto with the primary column sticky
- Touch targets must be at least 44x44px
- No page-level horizontal scroll, ever

## Components (every component must define its states)
default, hover, focus-visible (2px primary ring, 2px offset), active, disabled, loading, error
- Button: primary / secondary / ghost / danger, height 40px (sm 32px)
- Input: label always visible (never placeholder-only), error message below the field with icon + text
- Card, status Badge, Table (sticky header, optional zebra), Tabs, Modal, Toast
- Login page: centered card, max 400px, logo, form, forgot-password link, full-width submit button

## Empty / Loading / Error States
- Loading: skeletons, not a blank spinner
- Empty: icon + one sentence + one action button
- Error: clear message + retry button

## Accessibility (WCAG 2.2 AA)
- Text contrast >= 4.5:1, UI components >= 3:1
- Everything usable by keyboard, with a logical tab order
- Status must never rely on color alone (add an icon or text)
- Respect prefers-reduced-motion; animations max 200ms

## Do
- Use semantic tokens (--color-primary), not raw hex values
- Keep spacing rhythm consistent with the scale
- Use one icon set throughout (e.g. Lucide)

## Don't
- Don't use saturated (neon blue/purple) colors as large backgrounds
- Don't put light gray text on the cream background
- Don't use ambiguous labels like "Submit" or "OK"; write "Save changes"

## QA Checklist
- [ ] Checked at 360px, 768px, and 1280px with no horizontal scroll
- [ ] All text passes 4.5:1 contrast
- [ ] Fully navigable by keyboard, focus always visible
- [ ] Empty, loading, and error states exist
- [ ] No raw hex values outside the token definitions
- [ ] Dark mode uses its own tokens, not the light ones
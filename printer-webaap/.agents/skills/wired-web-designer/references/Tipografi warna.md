# Typography, color, spacing

Context: [web], hand-drawn theme (wired-elements).

## Fonts

Only two fonts, and only two weights are loaded: Caveat 600 and Patrick Hand 400. Do not use any other `font-weight` (the browser will fake it). Build hierarchy through size, color, and spacing, never through weight.

- Heading, display: Caveat 600
- Subheading, body, caption, label: Patrick Hand 400

```css
:root {
  --font-heading: "Caveat", "Patrick Hand", "Segoe Print", "Bradley Hand", "Comic Sans MS", cursive;
  --font-body: "Patrick Hand", "Segoe Print", "Bradley Hand", "Comic Sans MS", cursive;

  --fs-display: clamp(2.25rem, 1.6rem + 3vw, 3.06rem);
  --fs-h1: clamp(1.9rem, 1.4rem + 2.2vw, 2.44rem);
  --fs-h2: clamp(1.6rem, 1.3rem + 1.4vw, 1.94rem);
  --fs-h3: 1.56rem;
  --fs-sub: 1.25rem;
  --fs-body: 1.0625rem;
  --fs-body-sm: 0.875rem;
  --fs-caption: 0.8125rem;
  --fs-label: 0.75rem;
}
```

## Type scale (base 16px, rem units, fluid via clamp, no separate mobile overrides)

| Level      | Token           | Font         | Approx. size | Line height | Letter spacing | Purpose                              |
|------------|-----------------|--------------|--------------|-------------|----------------|--------------------------------------|
| Display    | `--fs-display`  | Caveat       | 36-49px      | 1.1-1.2     | -0.02em        | Hero text, not a heading level       |
| Heading 1  | `--fs-h1`       | Caveat       | 30-39px      | 1.2         | -0.015em       | Page title, one per page             |
| Heading 2  | `--fs-h2`       | Caveat       | 26-31px      | 1.25        | -0.01em        | Section title                        |
| Heading 3  | `--fs-h3`       | Caveat       | 25px         | 1.3         | -0.005em       | Subsection                           |
| Subheading | `--fs-sub`      | Patrick Hand | 20px         | 1.4         | 0              | Card title / small group heading     |
| Body       | `--fs-body`     | Patrick Hand | 17px         | 1.5-1.6     | 0              | Main text                            |
| Body-sm    | `--fs-body-sm`  | Patrick Hand | 14px         | 1.5         | 0              | Secondary text, form labels          |
| Caption    | `--fs-caption`  | Patrick Hand | 13px         | 1.4         | 0.01em         | Notes, timestamps, hints             |
| Label      | `--fs-label`    | Patrick Hand | 12px         | 1.3         | 0.03em         | Badges, tiny tags only               |

Button text uses Patrick Hand at `--fs-body`, line-height 1.2. Labels are not all caps (hard to read in a handwriting font), unless the design clearly uses caps.

## Mandatory rules

- Body text is never below 16px. No text anywhere is below 12px.
- Caption and Label are for supplementary info only, never for essential content.
- Font sizes use rem, not px, so text respects the user's browser font settings.
- Only one H1 per page. Never skip heading levels (H1 directly to H3). Display is visual only and does not count as a heading level.
- Handwriting fonts have a small x-height, so do not shrink below the tokens above.
- Do not use any `font-weight` other than the two loaded ones. No bold, no light.

## Paragraphs

- Body text: `max-width: 65ch` (ideal 45-75 characters per line).
- Paragraph spacing: `var(--space-4)`.
- Left-align long text. Avoid justified text on small screens.

## Color

Take colors from the design image, define them once in `:root`, and use them via `var(--...)`. Do not put hex values directly in component rules. The values below are defaults only.

```css
:root {
  --color-bg: #fffdf7;
  --color-surface: #ffffff;
  --color-text: #2b2b2b;
  --color-muted: #6b6b6b;
  --color-primary: #3b6fd4;
  --color-accent: #e8813a;
  --color-border: #2b2b2b;
}
```

- Wired elements are colored via CSS custom properties (e.g. `--wired-button-bg-color`). Check the element docs for exact names.
- Normal text: minimum 4.5:1 contrast against its background (WCAG AA), including `--color-muted`.
- Large text (>=24px): minimum 3:1.
- `--color-accent` is for fills, borders, and decoration only. Do not use it for small text unless it passes 4.5:1.
- Text colors: primary (`--color-text`), secondary (`--color-muted`), and a disabled state. Secondary must still pass 4.5:1.

## Spacing

Multiples of 4px, no values outside this scale:

```css
:root {
  --space-1: 4px;  --space-2: 8px;  --space-3: 12px; --space-4: 16px;
  --space-6: 24px; --space-8: 32px; --space-12: 48px;
}
```

## Accessibility

- Never disable zoom (no `user-scalable=no`).
- Minimum tap target for buttons/links on mobile: 44x44px.
- Links must be distinguishable from body text by more than color alone (e.g. underline).
- Keep a visible focus state on every interactive element, including wired elements.
# Typography, color, spacing

## Fonts

Only two, and only two weights are loaded: Caveat 600 and Patrick Hand 400. Do not use any other `font-weight` (the browser will fake it). Build hierarchy through size and color.

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
  --fs-caption: 0.8125rem;
  --fs-label: 0.75rem;
}
```

| Level | Token | Font | Line height | Letter spacing |
|---|---|---|---|---|
| Display | `--fs-display` | Caveat | 1.1-1.2 | -0.02em |
| Heading 1 | `--fs-h1` | Caveat | 1.2 | -0.015em |
| Heading 2 | `--fs-h2` | Caveat | 1.25 | -0.01em |
| Heading 3 | `--fs-h3` | Caveat | 1.3 | -0.005em |
| Subheading | `--fs-sub` | Patrick Hand | 1.4 | 0 |
| Body | `--fs-body` | Patrick Hand | 1.5-1.6 | 0 |
| Caption | `--fs-caption` | Patrick Hand | 1.4 | 0.01em |
| Label | `--fs-label` | Patrick Hand | 1.3 | 0.03em |

Labels are not all caps (hard to read in a handwriting font), unless the design clearly uses caps. Body text: `max-width: 65ch`.

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

Wired elements are colored via CSS custom properties (e.g. `--wired-button-bg-color`); check the element docs for exact names. Text contrast must be at least 4.5:1, especially `--color-muted`.

## Spacing

Multiples of 4px, no values outside this scale:

```css
:root {
  --space-1: 4px;  --space-2: 8px;  --space-3: 12px; --space-4: 16px;
  --space-6: 24px; --space-8: 32px; --space-12: 48px;
}
```
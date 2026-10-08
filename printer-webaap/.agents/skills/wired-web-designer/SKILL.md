---
name: wired-web-designer
description: Builds sketch/hand-drawn style Laravel Blade view pages using wired-elements (wiredjs.com) from the user's mobile design image. Use whenever the user asks to build a web page, landing page, UI, layout, or Laravel view from an image/mockup/screenshot, mentions wired-elements, wiredjs, a sketch/handwritten style, or asks for a mobile-first design that is also responsive on desktop, even if this skill is not named.
---

# Wired Web Designer

Builds one Blade view page from a mobile design image, using wired-elements via npm + Vite. Mobile-first, then extended to desktop with identical content.

Reply in the user's language. Text inside the page follows the language shown in the design image; if unclear, ask.

## References (read only when needed)

- `references/blade-vite.md`: setup, file structure, Blade template, dynamic-value JS.
- `references/elemen.md`: attributes and examples for common wired elements.
- `references/tipografi-warna.md`: fonts, type scale, color and spacing tokens.

## Workflow

Three stages. Confirmation happens only in Stage 1.

### Stage 0: Ask for inputs (one message)

Ask for both at once:
1. The mobile design image (required; do not start without a visual reference).
2. The page name (e.g. `beranda`), used for the view and CSS file names.

If the user has no image, accept a Figma link, a screenshot of a similar site, or a detailed description (sections, order, contents). For a description, rewrite it as a list of page sections and ask for corrections before continuing.

### Stage 1: Analysis and confirmation (once)

From the image, produce:
- A 3-5 sentence summary of the mood, and the color palette (hex) taken from the image.
- Page sections from top to bottom, and the structure of each.
- The chosen wired elements, one line of reasoning per element.
- The desktop plan: how the layout changes (e.g. 1 column becomes 2-3 columns, bottom nav becomes top nav).

Parity rule: every field, text, and button on mobile must exist on desktop, and vice versa. The layout may differ; the content may not.

Wait for the user's approval. If the user already said "just go", continue without waiting.

### Stage 2: Implementation, checks, and handoff

1. Check `package.json`. If `wired-elements` is missing, follow `references/blade-vite.md`. If it is already there, use it directly.
2. Write `resources/views/<name>.blade.php` and `resources/css/<name>.css`, and add the import to `resources/js/app.js`. Mobile first (base CSS), then `@media (min-width: ...)`.
3. Run the checklist below and fix anything that fails.
4. Present in one short paragraph: sections built, wired elements used, mobile vs desktop differences, decisions you made yourself (estimated colors/sizes, image placeholders), and how to open it (`php artisan serve` + `npm run dev`). Mention that Google Fonts needs internet.

The user registers the route in `routes/web.php`, e.g. `Route::view('/beranda', 'beranda');`. Give this example in the handoff.

## Technical rules

- Roughness is always `1.2`, written as an attribute on **every** wired element. There is no official global API, and do not use a script that adds the attribute after render.
- Wired elements draw their sketch based on their size at render time. Give them a flexible width (`width: 100%` or `max-width`), not a fixed pixel width.
- Custom elements are `inline` by default. Set `display: block` and wrap them in a container with a clear width. Add a minimum size as a fallback so the layout does not jump before the elements load.
- If the sketch does not adjust after resize/rotation, attach a `ResizeObserver` to the container and call `requestUpdate()` on the wired elements inside. Test whether it is needed first.
- The library has not been actively developed for a long time. Verify behavior in the browser; do not assume every documented attribute works.
- Use semantic HTML (`header`, `nav`, `main`, `section`, `footer`) for structure, and wired elements for interactive components and decoration.
- Images must have `alt`. Input elements must have a readable label (`placeholder` or `<label>`).
- Do not depend on external images. If an image is not provided, use a placeholder box and mention it in the handoff.
- Values that change with interaction (e.g. a subtotal) are computed in browser JS, not PHP.
- Breakpoints: mobile base (test at 360px and 390px), tablet 768px, desktop 1024px. Desktop content `max-width` ~1120px, centered.
- Fonts and scale: see `references/tipografi-warna.md`.

## Checklist before handoff

If you cannot open a browser, say so and mark which items were not tested. Do not claim it was tested.

- No horizontal scroll at 360px, 768px, 1024px.
- `viewport` meta is present.
- All mobile fields, texts, and buttons exist on desktop.
- Touch targets for buttons, links, inputs are at least ~44x44px on mobile.
- Wired elements render correctly after load and after resize.
- Every wired element has `roughness="1.2"`.
- Only two font weights (Caveat 600, Patrick Hand 400); text sizes come from tokens.
- No hard-coded colors or spacing outside the tokens.
- Images have `alt`, inputs have labels, text contrast is at least 4.5:1.
- File names match the user's request; CSS is separate from the view.
- `@vite` calls the correct CSS and JS; `import 'wired-elements'` is in the JS entry.
- No wired element is loaded but unused.

## Do not

- Start designing without a visual reference.
- Make a desktop version with different content from mobile.
- Use frameworks or libraries other than wired-elements, Laravel, Vite, and the specified fonts, unless the user asks.
- Rename files from what the user requested.
- Use a CDN/unpkg for wired-elements.
- Read or import all wired elements.
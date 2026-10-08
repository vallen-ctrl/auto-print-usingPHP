# wired-elements quick reference

All elements are custom elements: give them `display: block` and a flexible width via CSS. All elements accept `roughness`; in this skill it is always `roughness="1.2"` per element.

If an attribute has no effect in the browser, or an element is not listed here, open the official docs:
`https://github.com/rough-stuff/wired-elements/blob/master/docs/<element-name>.md`

Available elements: wired-button, wired-calendar, wired-card, wired-checkbox, wired-combo, wired-dialog, wired-divider, wired-fab, wired-icon-button, wired-image, wired-input, wired-item, wired-link, wired-listbox, wired-progress-ring, wired-progress, wired-radio-group, wired-radio, wired-search-input, wired-slider, wired-spinner, wired-tabs, wired-textarea, wired-toggle, wired-video.

## Actions

**wired-button**: `elevation`, `disabled`. Content = text inside the tag.
```html
<wired-button roughness="1.2" elevation="2">Submit</wired-button>
```

**wired-fab / wired-icon-button**: round buttons. Icon goes in as a child; prefer inline SVG.

## Containers and content

**wired-card**: `elevation`, `fill`. Set `display:block` and `padding` via CSS.
```html
<wired-card roughness="1.2" elevation="3" style="display:block; padding:16px;">
  <h3>Card title</h3>
  <p>Card content.</p>
</wired-card>
```

**wired-divider**: `elevation`.

**wired-image**: `src`, `elevation`. The `alt` attribute may not be forwarded; provide alternative text in the surrounding context and check in the browser.

**wired-link**: `href`, `target`, `elevation`.

## Inputs

**wired-input**: `placeholder`, `type`, `value`, `disabled`. Read via `.value`.
```html
<wired-input roughness="1.2" placeholder="Full name" style="width:100%;"></wired-input>
```

**wired-textarea**: `placeholder`, `rows`, `maxlength`, `disabled`.

**wired-search-input**: `placeholder`, `value`.

**wired-checkbox**: `checked`, `disabled`. Label = text inside the tag.
```html
<wired-checkbox roughness="1.2">I agree to the terms</wired-checkbox>
```

**wired-toggle**: `checked`, `disabled`.

**wired-radio-group / wired-radio**: the group has `selected` (the `name` of the chosen radio); each radio has `name`. Event: `selected`.
```html
<wired-radio-group roughness="1.2" selected="a">
  <wired-radio roughness="1.2" name="a">Option A</wired-radio>
  <wired-radio roughness="1.2" name="b">Option B</wired-radio>
</wired-radio-group>
```

**wired-combo / wired-item**: the combo has `selected` (the `value` of the chosen item); each item has `value`. Event: `selected`.
```html
<wired-combo roughness="1.2" selected="one">
  <wired-item value="one">One</wired-item>
  <wired-item value="two">Two</wired-item>
</wired-combo>
```

**wired-listbox**: always-visible list of choices, content is `wired-item`. `selected`, `horizontal`.

**wired-slider**: `min`, `max`, `value`, `step`.

## Navigation and layers

**wired-tabs / wired-tab**: each tab has `name` and `label`; tabs has `selected`.
```html
<wired-tabs roughness="1.2" selected="info">
  <wired-tab name="info" label="Info">Info tab content.</wired-tab>
  <wired-tab name="schedule" label="Schedule">Schedule tab content.</wired-tab>
</wired-tabs>
```

**wired-dialog**: `open`. Open/close via `dialog.open = true/false`. Put a close button inside.

## Indicators

**wired-progress**: `value`, `min`, `max`, `percentage`.

## Combined patterns

- **Form**: `wired-card` containing 100%-wide `wired-input`s and one `wired-button`. Desktop: card `max-width` ~480px and centered, or fields in 2 columns (`display:grid`).
- **Card list**: 1 column on mobile (`display:grid; gap:var(--space-4)`), 2 columns from 768px, 3 columns from 1024px.
- **Navigation**: bottom bar of `wired-icon-button`s on mobile; on desktop it becomes a row of `wired-link`s or `wired-tabs` in the header, with the same destinations.
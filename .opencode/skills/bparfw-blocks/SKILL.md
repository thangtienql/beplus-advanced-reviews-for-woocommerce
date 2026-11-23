---
name: bparfw-blocks
description: Use when creating or editing Gutenberg blocks under blocks/, including block.json, render.php, edit.tsx, view.js, style.css, and BlockRegistry. Covers registration, attributes, render callbacks, accessibility, and build workflow for beplus-advanced-reviews-for-woocommerce blocks.
---

# Beplus Advanced Reviews For Woocommerce — blocks

Standards: [`Document Plugin.md`](../../../Document%20Plugin.md). Keep block structure aligned with the plugin's Gutenberg and WooCommerce template approach.

## Registration and build

- One folder per block under `blocks/` with its own `block.json`.
- `BlockRegistry` should discover blocks from metadata rather than hard-coding paths.
- Treat `build/**` as generated output; change source files and rebuild instead.
- Block namespace prefix: `beplus-advanced-reviews/`.

## block.json conventions

- `textdomain`: `beplus-advanced-reviews-for-woocommerce`
- `category`: use the plugin's custom block category if one is registered
- `name`: `beplus-advanced-reviews/{slug}`
- Prefer dynamic rendering for review-related blocks when the content depends on product context or live review data.
- Keep attributes descriptive and camelCase, such as `showDistribution`, `showFilterBar`, `showSubmitForm`, `showMedia`, and `showAvatar`.

## Planned / primary blocks

| Block | Purpose |
|-------|---------|
| `advanced-review` | Primary review block for WooCommerce single product templates; renders review list, star distribution, image gallery, filters, sort controls, and submit form |

## Editor and render

- In `render.php`, escape all output and use `get_block_wrapper_attributes()` when rendering block markup.
- Use `edit.tsx` for Inspector controls and preview states; keep labels and help text aligned with review terminology.
- Use `view.js` for client-side enhancements such as filter interactivity, media lightboxes, load more pagination, and paste-to-upload support.
- Do not embed large amounts of review logic in `render.php` if the same behavior belongs in the view script or REST layer.

## Styling

- Scope classes with `beplus-advanced-reviews-for-woocommerce__*` and modifier classes for state changes.
- Keep loading, empty, and error states explicit so the review UI remains stable.
- Prefer idempotent front-end initialization so the block can mount safely in the editor and on the front end.

## Accessibility

- **Review list:** Use lists and article/section structure so review items remain readable by assistive technology.
- **Filter controls:** Use real form controls for rating filters, images-only toggle, and sort selectors.
- **Submit form:** Provide labels, helper text, and clear validation feedback for rating fields, text areas, and image uploads.
- **Image previews:** Ensure thumbnails have accessible names or captions when they convey important information.
- **Live updates:** Use an `aria-live="polite"` region for review count changes, filter results, and submission status messages.
- **Keyboard:** Every control must be reachable and usable by keyboard alone. Modals trap focus while open and restore it on close. Escape should close any overlay or modal.
- **Motion:** Disable or simplify transitions when `prefers-reduced-motion: reduce` is active.
- **Markup:** Use actual `<button>` elements for actions, not clickable divs. Use explicit heading hierarchy.

```text
❌ New block named woocommerce/reviews-list
✅ beplus-advanced-reviews/advanced-review

❌ Inline script blobs in render.php for filtering reviews
✅ view.js + REST + localized data from the asset loader

❌ <div onclick="openImage()">Review image</div>
✅ <button type="button">View review image</button>
```

## Build and verify

1. `npm run build` from plugin root.
2. Block inserter: block appears under **Beplus Advanced Reviews For Woocommerce** category.
3. Front-end: filters work, images open correctly, review submission states are visible, and layout remains stable.

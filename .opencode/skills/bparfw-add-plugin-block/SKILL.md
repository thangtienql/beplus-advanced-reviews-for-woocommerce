---
name: bparfw-add-plugin-block
description: Adds or extends a Beplus Advanced Reviews For Woocommerce Gutenberg block under blocks/ using block.json, TypeScript, esbuild, render.php. Use when creating blocks, editing BlockRegistry, block.json, render.php, index.js, edit.tsx, view.js, or build scripts for beplus-advanced-reviews.
---

# Beplus Advanced Reviews For Woocommerce — add or change a plugin block

## Before you edit

- Read [`AGENTS.md`](../../../AGENTS.md) § **Gutenberg blocks** and [`Document Plugin.md`](../../../Document%20Plugin.md) § **Gutenberg Block — advanced-review**.
- Read [the frontend data flow](../../../Document%20Plugin.md#10-front-end-data-flow) and use [the current view script](../../../blocks/advanced-review/view.js) as the reference for filter, upload, and lightbox behavior.
- Registration: `src/Blocks/BlockRegistry.php` — auto-discovers `blocks/*/block.json`.

## Scaffold

1. Create `blocks/{slug}/` with `block.json`, `render.php`, and the editor, view, and style assets referenced in the metadata.
2. Set `block.json`:
   - `name`: `beplus-advanced-reviews/{slug}`
   - `textdomain`: `beplus-advanced-reviews-for-woocommerce`
   - `category`: `beplus-advanced-reviews-for-woocommerce`
   - `render`: `file:./render.php`
3. Register the block category in `Plugin::register_block_category()` if needed.

## Implement — advanced review block

1. **Attributes:** use [the registered metadata](../../../blocks/advanced-review/block.json) and keep the editor and server renderer in sync. Page size comes from the admin setting.
2. **render.php:** wrap with `get_block_wrapper_attributes()`; render review list, star distribution chart, filter controls, sort controls, submit form shell, and image placeholders; escape all output.
3. **edit.tsx:** provide Inspector controls for review display and filtering options.
4. **view.js:** bind filters to REST, update the review list and star summary, and handle upload previews, attachment ordering, and the lightbox.
5. **REST:** use `beplus-advanced-reviews-for-woocommerce/v1/reviews` and `beplus-advanced-reviews-for-woocommerce/v1/reviews/distribution` for live data and submission flows; do not embed ad-hoc SQL in the block.

## Styling

- Class prefix: `beplus-advanced-reviews-for-woocommerce__*`
- Use `aria-busy`, local status messages, and retry controls in the region that is updating.
- Scope styles to the block wrapper — avoid global resets

## Build and verify

1. `npm run build` from plugin root.
2. Block inserter: block appears under **Beplus Advanced Reviews For Woocommerce** category.
3. Front-end: filters work, images open correctly, star distribution renders, review submission states are visible, and layout remains stable.

## Reference

| Source | Use for |
|--------|---------|
| `blocks/advanced-review/block.json` | Primary block metadata, attributes, render callback |
| `src/Blocks/BlockRegistry.php` | Auto-discovery |
| `src/Core/Placement.php` | Display mode logic |

## Checklist

- [ ] `block.json` uses the `beplus-advanced-reviews/` name prefix and `beplus-advanced-reviews-for-woocommerce` text domain.
- [ ] `render.php` escaped; no raw user input in output.
- [ ] REST used for live review data — not ad-hoc SQL in render.
- [ ] `npm run build` run; no hand-edits to generated files.
- [ ] A11y: labels, filter controls, focus-visible, reduced motion, live regions.

---
name: bparfw-frontend
description: Use when editing JavaScript, TypeScript, or CSS assets under admin/js/, blocks/ (view scripts), or assets/. Covers build workflow, localization, REST integration, CSS scoping, and front-end accessibility for Beplus Advanced Reviews For Woocommerce.
---

# Beplus Advanced Reviews For Woocommerce — front-end (JS + CSS)

## Source vs build

| Edit | Output |
|------|--------|
| `admin/js/settings.js` | Loaded directly in the admin |
| `blocks/advanced-review/view.js` | Loaded directly through block metadata. esbuild also writes a copy into `build/`. |
| `admin/css/admin.scss`, `blocks/advanced-review/style.scss` and partials | Compiled admin and block CSS |

Run `npm run build` or the plugin's preferred watch/build command after source changes. Never hand-edit generated output when a source file exists.

## Admin JavaScript

- Keep admin UI state, review settings, and any dashboards in source files under `admin/js/`.
- Register runtime scripts with their WordPress dependencies in the asset loader or block metadata.
- Localize REST URLs, nonces, and UI strings through a single plugin data object.

## Front-end JavaScript

- Use the view script to hydrate review filters, image toggles, lazy loading, load more pagination, star distribution chart, and paste-to-upload support.
- Keep interactions resilient when JavaScript is unavailable so reviews still render as static content.
- Use the plugin REST namespace consistently for client-side fetching and form submission.

## CSS

- Scope styles to the plugin with `beplus-advanced-reviews-for-woocommerce__*` BEM-style class names.
- Avoid global resets or theme-wide overrides.
- Keep review cards, images, filter bars, and forms visually distinct but accessible.
- Style the star distribution bar chart with responsive, accessible color and sizing.

## Accessibility (JavaScript)

- Keep review filter controls, upload helpers, and any admin forms keyboard-friendly.
- Announce review list updates, submit success, and errors through a polite live region when the UI changes dynamically.
- Link form errors to inputs with `aria-describedby` and keep validation messages concise.
- If a modal, lightbox, or drawer is introduced, restore focus to the triggering control after close.

## Accessibility (CSS)

- Use `:focus-visible` for interactive elements and preserve a strong visible focus indicator.
- Respect `@media (prefers-reduced-motion: reduce)` for transitions, overlays, and loader animations.
- Keep touch targets reasonably sized for review actions such as expanding images or submitting forms.

```text
❌ Hard-code /wp-json/ paths in front-end requests
✅ Use localized REST URLs or `rest_url( 'beplus-advanced-reviews-for-woocommerce/v1/' )`

❌ Edit generated build files to tweak spacing
✅ Change the source stylesheet or JS and rebuild

❌ .beplus-advanced-reviews-for-woocommerce__button:focus { outline: none; }
✅ .beplus-advanced-reviews-for-woocommerce__button:focus-visible { outline: 2px solid currentColor; outline-offset: 2px; }
```

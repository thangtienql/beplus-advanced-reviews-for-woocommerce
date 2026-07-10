=== Beplus Advanced Reviews For Woocommerce ===
Contributors: beplus
Tags: woocommerce, reviews, product reviews, images, videos, star rating, gutenberg, filter, ajax
Requires at least: 6.0
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Modern WooCommerce product reviews with image & video support, star distribution, AJAX filtering, and load more — powered by a Gutenberg block.

== Description ==

Beplus Advanced Reviews For Woocommerce replaces or enhances the default WooCommerce product reviews with a modern, AJAX-driven interface built as a Gutenberg block.

**Key Features:**

* **Average rating score** and **total review count** per product
* **Star distribution bar chart** — visual breakdown of reviews per star rating (1★–5★)
* **Rich review cards** — avatar, reviewer name, star rating, written content, date, and attached media
* **Image uploads** — customers attach JPEG, PNG, or WebP images to their reviews
* **Video uploads** — customers attach MP4, WebM, or OGG videos to their reviews
* **Clipboard paste** — paste images directly from the clipboard into the review form
* **Lightbox** — full-size image and video preview in an accessible overlay
* **Smart filter bar** — filter by star rating (multi-select) or show only reviews with images, no page reload
* **Sort controls** — sort by date (newest/oldest) or rating (highest/lowest)
* **Load More (AJAX)** — paginated review list with a Load More button powered by the REST API
* **Gutenberg block** — drag-and-drop `Advanced Reviews` block, automatically applied to all Single Product pages on activation
* **Display modes** — *Keep default* (manual block placement) or *Replace default* (auto-override WooCommerce reviews)
* **Accessible** — WCAG 2.1 AA compliant: keyboard navigable, focus management, `aria-live` regions, `prefers-reduced-motion` support
* **Theme-aware styling** — automatically inherits colors from the active theme's `theme.json` global styles, with graceful fallback for classic themes

**For Developers:**

* Container-based architecture with PSR-4 autoloading
* Extensible via WordPress filters and actions
* REST API for reviews, star distribution, and settings
* Swappable media storage backend (default: WordPress Media Library)
* PHPStan static analysis and TypeScript type checking

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/beplus-advanced-reviews-for-woocommerce/`, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the **Plugins** screen in WordPress.
3. The **Advanced Reviews** block is automatically applied to all Single Product pages upon activation.
4. Optionally configure display mode and media settings under **WooCommerce → Advanced Reviews**.

**Building from source:**

If you cloned the repository, run the following from the plugin directory:

`npm install && npm run composer:install && npm run build`

== Frequently Asked Questions ==

= Does this work with any theme? =

Yes. The plugin uses a Gutenberg block that works with any block-based (FSE) theme. It also supports classic themes via WooCommerce hooks when using the "Replace default" display mode.

= Can I keep the default WooCommerce reviews? =

Yes. In the plugin settings (**WooCommerce → Advanced Reviews → General**), choose "Keep default" to leave WooCommerce's built-in reviews untouched. You can then place the Advanced Reviews block manually wherever you like.

= What image formats are supported? =

JPEG, PNG, and WebP. Images can be uploaded via file input (multi-select) or pasted directly from the clipboard.

= What video formats are supported? =

MP4, WebM, and OGG. Video uploads can be enabled in the **Media** tab of the plugin settings.

= Can I control the maximum upload size? =

Yes. The **Media** tab in plugin settings lets you set the maximum file size (in MB) for both images and videos independently.

= Is this plugin accessible? =

Yes. The plugin targets WCAG 2.1 AA compliance. All interactive elements are keyboard navigable, modals trap focus, live regions announce dynamic content updates, and animations respect `prefers-reduced-motion`.

= Does it support REST API? =

Yes. The plugin exposes a full REST API under the `beplus-advanced-reviews-for-woocommerce/v1` namespace for listing reviews, fetching star distribution, submitting reviews, and managing settings.

= What are the requirements? =

WordPress 6.0+, WooCommerce 7.0+, and PHP 7.4+ (8.0+ recommended).

= Does it match my theme's colors? =

Yes. The plugin automatically inherits colors from your active theme's `theme.json` global styles (primary, text, background). All colors are defined as `--bpar-*` CSS custom properties and can be overridden in custom CSS. Classic themes without `theme.json` will see the default color scheme.

== Screenshots ==

1. Star distribution chart and review list on a product page
2. Review card with image attachments and lightbox preview
3. Smart filter bar — filter by star rating and images-only
4. Review submission form with image upload and clipboard paste
5. Plugin settings — General tab (display mode, reviews per load)
6. Plugin settings — Media tab (image/video upload toggles, max sizes)

== Changelog ==

= 1.0.0 =
* Initial release
* Average rating score and total review count
* Star distribution bar chart (1★–5★)
* Review cards with avatar, rating, content, date, and media
* Image uploads (JPEG, PNG, WebP) with clipboard paste support
* Video uploads (MP4, WebM, OGG)
* Image and video lightbox preview
* Smart filter bar — filter by star rating, images-only toggle
* Sort controls — newest, oldest, highest rated, lowest rated
* Load More button with AJAX pagination
* Gutenberg block with configurable attributes
* Display mode: keep default or replace default WooCommerce reviews
* REST API for reviews, distribution, and settings
* Plugin settings page under WooCommerce menu
* WCAG 2.1 AA accessibility
* Theme-aware color system — inherits from active theme's `theme.json` global styles
* i18n ready with `beplus-advanced-reviews-for-woocommerce` text domain

== Upgrade Notice ==

= 1.0.0 =
Initial release. No upgrade steps required.

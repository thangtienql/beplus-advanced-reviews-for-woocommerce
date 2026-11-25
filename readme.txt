=== Beplus Advanced Reviews for WooCommerce ===
Contributors: bearsthemes, tienbeplus
Tags: woocommerce, reviews, product reviews, star rating, ajax
Requires at least: 6.0
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.1.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: beplus-advanced-reviews-for-woocommerce
Domain Path: /languages

Modern WooCommerce product reviews with image & video support, star distribution, AJAX filtering, and load more — powered by a Gutenberg block.

== Description ==

Beplus Advanced Reviews for WooCommerce replaces or enhances the default WooCommerce product reviews with a modern, AJAX-driven interface built as a Gutenberg block.

**Key Features:**

* **Average rating score** and **total review count** per product
* **Star distribution bar chart** — visual breakdown of reviews per star rating (1★–5★)
* **Rich review cards** — avatar, reviewer name, star rating, written content, date, and attached media
* **Image uploads** — customers attach JPEG, PNG, or WebP images to their reviews
* **Video uploads** — customers attach MP4, WebM, or OGG videos to their reviews
* **Clipboard paste** — paste images directly from the clipboard into the review form
* **Lightbox** — full-size image and video preview in an accessible overlay
* **Smart filter bar** — filter by star rating, show only reviews with images, or show only verified purchase reviews; no page reload
* **Verified Purchase badge** — premium shield icon with glossy shine animation; reviews from customers who actually bought the product are automatically marked
* **Sort controls** — sort by date (newest/oldest) or rating (highest/lowest)
* **Load More (AJAX)** — paginated review list with a Load More button powered by the REST API
* **Gutenberg block** — drag-and-drop `Advanced Reviews` block, automatically applied to all Single Product pages on activation
* **Display modes** — *Keep default* (manual block placement) or *Replace default* (auto-override WooCommerce reviews)
* **Accessible** — WCAG 2.1 AA compliant: keyboard navigable, focus management, `aria-live` regions, `prefers-reduced-motion` support
* **Admin Color Control** — customize the 11 frontend colors (primary, text, background, button text, stars, error/success states) from the plugin settings Styling tab, with consistent hardcoded defaults that render on any theme

**For Developers:**

* Container-based architecture with PSR-4 autoloading
* Extensible via WordPress filters and actions
* REST API for reviews, star distribution, and settings
* Swappable media storage backend (default: WordPress Media Library)
* PHPStan static analysis and TypeScript type checking

**About BePlus:**

This plugin is developed and maintained by [BePlus](https://beplusthemes.com/), a WordPress and Shopify development studio with 10+ years of experience building themes and plugins for nonprofits and eCommerce brands. Learn more at [beplusthemes.com](https://beplusthemes.com/).

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

= How does the "Verified Purchase" badge work? =

Reviews from customers who have purchased the product (order status: Completed or Processing) automatically display a "Verified Purchase" badge. The badge can be turned on/off, and you can choose which order statuses count as verified, in the plugin settings.

= Does it match my theme's colors? =

The plugin uses consistent hardcoded defaults for every color (primary `#000000`, text `#000000`, background `#FFFFFF`, stars `#CFCFCF`), so the review display looks identical on any theme. All colors are defined as `--bpar-*` CSS custom properties. You can customize every color directly from **WooCommerce → Advanced Reviews → Styling** without writing any CSS — saved colors apply to the front end immediately.

= Can I customize the frontend colors? =

Yes. Navigate to **WooCommerce → Advanced Reviews → Styling** to find color pickers for the 11 frontend colors (primary, text, background, button text, star empty, error/success states). Changes are saved and applied as inline CSS immediately. A "Reset to Defaults" button lets you revert all colors to the plugin defaults.

== Screenshots ==

1. Plugin settings — General tab (display mode, reviews per load)
2. Plugin settings — Media tab (image/video upload toggles, max sizes)
3. Plugin settings — Media tab (image/video upload toggles, max sizes)
4. Review card with image attachments and lightbox preview
5. Advanced Reviews Gutenberg block on a single product page

== Changelog ==

= 1.1.0 =
* New: Verified Purchase badge — premium shield icon with glossy shine animation, auto-marked for customers who bought the product
* New: Admin Color Control — customize frontend colors (primary, text, background, stars, error/success states) from the plugin settings Styling tab
* Enhanced: Mobile responsive — filter bar and review cards adapt gracefully on small screens (375px+)
* Fix: Verified badge renders consistently across all views (server-side, AJAX load, and newly submitted reviews)
* Fix: color defaults now render consistently (hardcoded, theme-independent) — no theme color bleed-through
* Fix: minor bug fixes and improvements

= 1.0.1 =
* Updated About BePlus section with detailed company information

= 1.0.0 =
* Initial release. No upgrade steps required.
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
* Color system — consistent hardcoded defaults for all frontend colors, customizable from the admin Styling tab
* i18n ready with `beplus-advanced-reviews-for-woocommerce` text domain

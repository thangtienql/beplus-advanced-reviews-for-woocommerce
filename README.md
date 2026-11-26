# ⭐ Beplus Advanced Reviews For WooCommerce

[![WordPress](https://img.shields.io/badge/WordPress-6.0%2B-blue?logo=wordpress&logoColor=white)](https://wordpress.org/)
[![WooCommerce](https://img.shields.io/badge/WooCommerce-7.0%2B-96588a?logo=woocommerce&logoColor=white)](https://woocommerce.com/)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-777bb4?logo=php&logoColor=white)](https://php.net/)
[![License](https://img.shields.io/badge/License-GPLv2%2B-green)](https://www.gnu.org/licenses/gpl-2.0.html)
[![Version](https://img.shields.io/badge/Version-1.0.0-orange)](https://github.com/thangtienql/beplus-advanced-reviews-for-woocommerce/releases)

> Modern WooCommerce product reviews with image & video support, star distribution, AJAX filtering, and load more — powered by a Gutenberg block.

---

## 📑 Table of Contents

- [Features](#-features)
- [Requirements](#-requirements)
- [Installation](#-installation)
- [Configuration](#%EF%B8%8F-configuration)
- [Block Usage](#-block-usage)
- [Theme Integration](#-theme-integration)
- [REST API](#-rest-api)
- [Development](#%EF%B8%8F-development)
- [Project Structure](#-project-structure)
- [Hooks & Extensibility](#-hooks--extensibility)
- [License](#-license)
- [Contributing](#-contributing)

---

## ✨ Features

| Feature | Description |
|---------|-------------|
| ⭐ **Average Rating & Count** | Aggregated star rating score and total review count for each product |
| 📊 **Star Distribution Chart** | Visual bar chart showing review count per star rating (1★–5★) |
| 📝 **Rich Review Cards** | Avatar, reviewer name, star rating, written content with preserved line breaks, date, and attached media |
| 📸 **Image & Video Uploads** | Customers upload JPEG, PNG, WebP images and MP4, WebM, OGG videos with their reviews |
| 📋 **Clipboard Paste** | Paste images directly from clipboard into the review form |
| ↔️ **Attachment Order** | Reorder selected and pasted media before submitting. The gallery and lightbox follow the saved order. |
| 🔍 **Smart Filter Bar** | Filter by one star rating, attached media, or verified purchase without reloading the page |
| 🔃 **Sort Controls** | Sort reviews by date (newest/oldest), rating (highest/lowest), or most helpful |
| **Helpful Votes** | Anyone can see vote counts. Signed-in customers can add or remove one vote per approved review. Guest review submission is unchanged. |
| ♾️ **Load More (AJAX)** | Paginated reviews with loading feedback and retry. Filtering, sorting, and review submission also show progress. |
| 🖼️ **Lightbox** | Full-size image & video preview in an accessible lightbox overlay |
| 🧩 **Gutenberg Block** | Drag-and-drop `Advanced Reviews` block for Single Product templates (FSE / Site Editor) |
| ⚙️ **Display Modes** | *Keep default* (manual placement) or *Replace default* (auto-override WooCommerce reviews) |
| ♿ **Accessible** | WCAG 2.1 AA — keyboard navigable, focus management, `aria-live` regions, `prefers-reduced-motion` support |

---

## 📋 Requirements

| Requirement | Version |
|-------------|---------|
| WordPress | 6.0 or higher |
| WooCommerce | 7.0 or higher |
| PHP | 7.4 or higher (8.0+ recommended) |
| Node.js | 16+ (for development builds only) |

---

## 🚀 Installation

### Option 1 — Upload ZIP via WordPress Admin

1. Download the latest release ZIP from the [Releases](https://github.com/thangtienql/beplus-advanced-reviews-for-woocommerce/releases) page.
2. In WordPress admin, go to **Plugins → Add New → Upload Plugin**.
3. Choose the ZIP file and click **Install Now**.
4. **Activate** the plugin.

### Option 2 — Clone & Build from Source

```bash
# Clone the repository into your plugins directory
cd wp-content/plugins/
git clone https://github.com/thangtienql/beplus-advanced-reviews-for-woocommerce.git
cd beplus-advanced-reviews-for-woocommerce

# Install dependencies
npm install
npm run composer:install

# Build assets
npm run build
```

Then activate the plugin from **Plugins** in the WordPress admin.

> **Note:** To show the `Advanced Reviews` block on Single Product pages, either place it manually in the Site Editor or set the display mode to *Replace default reviews* under **WooCommerce → Advanced Reviews → General → Display Settings**.

---

## ⚙️ Configuration

Navigate to **WooCommerce → Advanced Reviews** in your WordPress admin.

### General Tab

| Setting | Description | Default |
|---------|-------------|---------|
| **Display Mode** | *Keep default* — place the block manually. *Replace default* — automatically override WooCommerce's built-in reviews. | Keep default |
| **Reviews per load** | Number of reviews shown before the "Load More" button appears. | 10 |
| **Minimum rating** | Only display reviews at or above this star rating. | 1 |
| **Show filter bar** | Enable/disable the front-end filter bar. | Enabled |
| **Show sort controls** | Enable/disable sorting by date, rating, and helpful votes. | Enabled |

### Media Tab

| Setting | Description | Default |
|---------|-------------|---------|
| **Enable image uploads** | Allow customers to attach images to reviews. | Enabled |
| **Allow clipboard paste** | Allow pasting images from clipboard. | Enabled |
| **Max image size (MB)** | Maximum file size per image upload. | 5 |
| **Enable video uploads** | Allow customers to attach videos to reviews. | Disabled |
| **Max video size (MB)** | Maximum file size per video upload. | 50 |

---

## 🧩 Block Usage

1. Open the **Site Editor** (Appearance → Editor) or the **Post/Page Editor**.
2. Navigate to your **Single Product** template.
3. Add the **Advanced Reviews** block from the block inserter (search for "Advanced Reviews").
4. Configure block settings in the sidebar:

### Block Attributes

| Attribute | Type | Default | Description |
|-----------|------|---------|-------------|
| `showDistribution` | `boolean` | `true` | Show/hide the star distribution chart |
| `showFilterBar` | `boolean` | `true` | Show/hide the filter bar |
| `showSubmitForm` | `boolean` | `true` | Show/hide the review submission form |
| `showMedia` | `boolean` | `true` | Show attachments on review cards and enable the upload area when media uploads are allowed |
| `showImages` | `boolean` | Unset | Legacy attribute. An explicitly saved `false` hides media. |
| `showAvatar` | `boolean` | `true` | Show/hide reviewer avatars |
| `showVerifiedFilter` | `boolean` | `true` | Show the Verified filter when verified purchases are enabled in settings |
| `enableLazyLoad` | `boolean` | `true` | Saved editor option. The current frontend requests reviews when the block initializes. |

Set the number of reviews per page in **WooCommerce → Advanced Reviews → General → Reviews per load**.

---

## 🎨 Color System

Plugin styles define the default frontend colors. Use the **Styling** tab or custom CSS to change them.

### How It Works

Frontend colors are defined as `--bpar-*` CSS custom properties:

| Plugin Token | Fallback |
|---|---|
| `--bpar-primary` | `#000000` |
| `--bpar-text` | `#000000` |
| `--bpar-bg` | `#FFFFFF` |

Derived colors (hover states, muted text, borders) are computed from the primary, text, and background tokens using CSS `color-mix()`.

The 11 admin-editable colors can also be customized from **WooCommerce → Advanced Reviews → Styling** — the plugin outputs an inline `<style>` override when any color differs from its default. Derived tones are not individually editable.

### Customization

Override any token in your theme's custom CSS:

```css
:root {
  --bpar-primary: #0073aa;  /* Use a custom accent color */
}
```

---

## 🔌 REST API

All endpoints use the namespace `beplus-advanced-reviews-for-woocommerce/v1`.

| Method | Route | Auth | Description |
|--------|-------|------|-------------|
| `GET` | `/reviews` | Public | List reviews. Supports query params: `product_id`, `rating`, `has_images`, `page`, `per_page`, `sort` |
| `GET` | `/reviews/distribution` | Public | Star distribution counts for a product (`product_id` required) |
| `POST` | `/reviews` | Logged-in / Nonce | Submit a new review with star rating and optional media attachments |
| `PUT` | `/reviews/{id}/helpful` | Logged-in | Set or remove your helpful vote with `{"voted": true}` or `{"voted": false}` |
| `DELETE` | `/reviews/<id>` | `manage_woocommerce` | Delete a review by ID |
| `GET` | `/settings` | `manage_options` | Retrieve all plugin settings |
| `POST` | `/settings` | `manage_options` | Update plugin settings |

### Example — Fetch Reviews

```bash
curl "https://yoursite.com/wp-json/beplus-advanced-reviews-for-woocommerce/v1/reviews?product_id=42&per_page=10&page=1&sort=newest"
```

### Example — Get Star Distribution

```bash
curl "https://yoursite.com/wp-json/beplus-advanced-reviews-for-woocommerce/v1/reviews/distribution?product_id=42"
```

> **Authentication:** Review submission (`POST /reviews`) requires either a logged-in user session or a valid nonce passed via the `X-WP-Nonce` header. The REST URL and nonce are localized on the front end via the `bparfwData` JavaScript object.

`GET /reviews` includes a public `helpful_count` for each review. Authenticated requests also receive `viewer_has_voted`. Use `sort=helpful` to order by vote count, with newer reviews first when counts tie. The vote endpoint returns the updated count and the authenticated viewer's state.

---

## 🛠️ Development

### Prerequisites

- **Node.js** 22+ and **npm**
- **PHP** 7.4+ (for linting & static analysis)
- **Composer** for installing the PHP development dependencies used by the quality checks

### Setup

```bash
git clone https://github.com/thangtienql/beplus-advanced-reviews-for-woocommerce.git
cd beplus-advanced-reviews-for-woocommerce
npm install
npm run ensure:composer
```

### Build Commands

| Command | Description |
|---------|-------------|
| `npm run build` | Compile the block and admin SCSS, then build the view script into `build/` with esbuild |
| `npm run build:css` | Compile SCSS only (compressed, no source maps) |
| `npm run watch` | Watch JS files for changes (esbuild) |
| `npm run watch:css` | Watch SCSS files for changes (sass) |
| `npm run build:package` | Package runtime files into an installable ZIP after running `npm run build` |

### Quality Checks

| Command | Description |
|---------|-------------|
| `npm run typecheck` | Run TypeScript type checking (`tsc --noEmit`) |
| `npm run lint:php:all` | Run PHPStan static analysis |
| `npm run precommit` | Dry-run pre-commit checks (CSS build + typecheck + PHPStan) |
| `npm run prepush` | Full pre-push checks (Composer + typecheck + PHPStan + build) |

WordPress loads the checked-in `blocks/advanced-review/view.js`, editor entry `index.js`, and `admin/js/settings.js` directly. The release ZIP uses those runtime files and the compiled CSS.

> Husky runs `npm run precommit` before commits and `npm run prepush` before pushes. Install Composer dev dependencies with `npm run ensure:composer` before running PHPStan in a fresh checkout.

### Next WordPress.org Release

**Target: `1.2.0` (unreleased).** This release adds Helpful votes and the Most helpful review sort. Apply the version bump only when preparing the release.

- [ ] Set both the `Version` header and `BEPLUS_ADVANCED_REVIEWS_FOR_WOOCOMMERCE_VERSION` in `beplus-advanced-reviews-for-woocommerce.php` to `1.2.0`.
- [ ] Set `version` in `blocks/advanced-review/block.json` and `Stable tag` in `readme.txt` to `1.2.0`; add the `1.2.0` changelog entry in `readme.txt`.
- [ ] Keep the database schema version at `1.1.5` unless the database structure changes again.
- [ ] Run the quality checks, then run `npm run build` and `npm run build:package` from the tested release source. Verify that the new `1.2.0` ZIP contains the updated plugin; do not reuse the `1.1.4` ZIP.

---

## 📁 Project Structure

```
beplus-advanced-reviews-for-woocommerce/
│
├── beplus-advanced-reviews-for-woocommerce.php   # Plugin bootstrap
├── composer.json                                  # PHP dependencies & PSR-4 autoload
├── package.json                                   # Node dependencies & build scripts
├── esbuild.config.mjs                             # esbuild bundler config
├── phpstan.neon.dist                              # PHPStan config
├── tsconfig.json                                  # TypeScript config
├── readme.txt                                     # WordPress.org readme
│
├── src/                          # PHP source (PSR-4: BeplusAdvancedReviewsForWoocommerce\)
│   ├── Core/
│   │   ├── Plugin.php            # Container-based boot, module registry
│   │   ├── AssetLoader.php       # Enqueue admin + frontend + block assets
│   │   └── Placement.php         # Display mode logic (keep/replace)
│   ├── Blocks/
│   │   └── BlockRegistry.php     # Auto-discover & register blocks from block.json
│   ├── REST/
│   │   ├── ReviewController.php  # Reviews CRUD + filtering + distribution
│   │   └── SettingsController.php# Admin settings REST endpoints
│   ├── Media/
│   │   ├── MediaHandler.php      # Upload validation, paste support, cleanup
│   │   └── LocalMediaStorage.php # MediaStorageInterface implementation (WP Media Library)
│   ├── Settings/
│   │   └── SettingsRegistry.php  # Options, defaults, sanitization
│   ├── DB/
│   │   └── SchemaManager.php     # Custom table creation & migration
│   ├── Reviews/                  # Review domain logic
│   └── Functions/                # Utility functions
│
├── blocks/
│   └── advanced-review/          # Gutenberg block
│       ├── block.json            # Block metadata & attributes
│       ├── edit.tsx              # Editor component (React/TSX)
│       ├── render.php            # Server-side render callback
│       ├── view.js               # Front-end hydration & interactivity
│       ├── style.scss            # Block styles (imports partials)
│       └── _*.scss               # SCSS partials (variables, layout, cards, etc.)
│
├── templates/                    # PHP templates
│   ├── review-card.php
│   ├── review-form.php
│   ├── review-list.php
│   ├── star-distribution.php
│   └── partials/
│
├── admin/
│   ├── js/                       # Admin JavaScript (settings page)
│   └── css/                      # Admin styles
│
├── includes/
│   ├── common.php                # Global helper functions
│   └── hooks.php                 # Procedural hook registrations
│
└── scripts/                      # Build & packaging scripts
    └── build-package.mjs
```

---

## Hooks & Extensibility

### Filters

| Hook | Description |
|------|-------------|
| `beplus_advanced_reviews.services` | Register additional container services |
| `beplus_advanced_reviews.blocks` | Register third-party blocks |
| `beplus-advanced-reviews-for-woocommerce/review.query` | Modify review WP_Comment_Query args before execution |
| `beplus-advanced-reviews-for-woocommerce/review.results` | Modify the review result set before response |
| `beplus_advanced_reviews_for_woocommerce_template_paths` | Override template file lookup paths |

### Actions

| Hook | Description |
|------|-------------|
| `beplus-advanced-reviews-for-woocommerce/review.submitted` | Fires after a new review is saved |
| `beplus-advanced-reviews-for-woocommerce/media.uploaded` | Fires after review media is attached |
| `beplus-advanced-reviews-for-woocommerce/media.deleted` | Fires after review media is removed |

### Example — Add Custom Data to Reviews

```php
add_filter( 'beplus-advanced-reviews-for-woocommerce/review.results', function ( $reviews ) {
    foreach ( $reviews as &$review ) {
        $review['verified_purchase'] = wc_customer_bought_product(
            $review['author_email'],
            $review['author_id'],
            $review['product_id']
        );
    }
    return $reviews;
} );
```

---

## 📄 License

This project is licensed under the [GNU General Public License v2.0 or later](https://www.gnu.org/licenses/gpl-2.0.html).

```
Copyright (C) 2024 Beplus

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.
```

---

## 🤝 Contributing

Contributions are welcome! Here's how to get started:

1. **Fork** the repository.
2. **Create** a feature branch: `git checkout -b feature/my-new-feature`.
3. **Install** dependencies: `npm install && npm run ensure:composer`.
4. **Make** your changes.
5. **Run checks**: `npm run precommit` to ensure code quality.
6. **Commit** with a descriptive message.
7. **Push** to your fork and open a **Pull Request**.

### Code Standards

- **PHP**: Follow [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/). Run `npm run lint:php:all` before submitting.
- **TypeScript/JavaScript**: Type-checked via `npm run typecheck`.
- **Accessibility**: All UI must meet WCAG 2.1 AA. Use semantic HTML, proper ARIA attributes, and keyboard navigation.
- **Security**: Escape output, sanitize input, use `$wpdb->prepare()` for SQL, and verify nonces.

---

<p align="center">
  Made with ❤️ by <a href="https://beplusthemes.com/">Beplus</a>
</p>

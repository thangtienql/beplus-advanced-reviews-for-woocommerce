# Admin Color Control — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add an admin Styling tab with 18 color pickers controlling all `--bpar-*` CSS custom properties, inject inline CSS via `wp_head`, and bump version to 1.1.0.

**Architecture:** New `StylingModule` (extends `AbstractModule`) reads `colors` from the existing settings option and outputs inline `<style>` at `wp_head` priority 1. The `SettingsRegistry` gains a Styling tab, 18 color defaults, and hex sanitization. Admin UI uses native `<input type="color">` with synced hex text inputs, grouped into 6 fieldsets within the existing card-based layout.

**Tech Stack:** PHP 7.4+, WordPress 6.0+, WooCommerce 7.0+, SCSS (compiled to CSS via `npm run build:css`), vanilla JavaScript

## Global Constraints

- PHP minimum version: 7.4
- WordPress minimum version: 6.0
- WooCommerce minimum version: 7.0
- Text domain: `beplus-advanced-reviews-for-woocommerce`
- Settings option key: `beplus_advanced_reviews_for_woocommerce_settings`
- Settings sub-key for colors: `colors`
- CSS custom property prefix: `--bpar-`
- No external JS/color-picker library dependencies
- Output only when customizations exist (compare vs defaults)
- All output escaped: `esc_attr()`, `esc_html()`, `esc_html__()`
- All input sanitized: `sanitize_hex_color()`, `absint()`
- Every PHP file starts with `if ( ! defined( 'ABSPATH' ) ) { exit; }`
- After JS/SCSS changes: run `npm run build:css` and `npm run build` (or `npm run watch` for dev)

---

### Task 1: Create `src/Styling/StylingModule.php`

**Files:**
- Create: `src/Styling/StylingModule.php`

**Interfaces:**
- Consumes: `AbstractModule` from `src/Core/AbstractModule.php` (constructor receives `Container`, provides `$this->version`, `$this->plugin_dir`, `$this->plugin_url`)
- Consumes: `beplus_advanced_reviews_for_woocommerce_get_settings()` from `includes/common.php` (returns `array<string, mixed>` including `colors` key)
- Produces: `StylingModule::register(): void` — called by `Plugin::boot_registered_modules()`
- Produces: `StylingModule::output_inline_css(): void` — hooked to `wp_head` priority 1

- [ ] **Step 1: Create the directory and file**

```bash
mkdir -p src/Styling
```

- [ ] **Step 2: Write `src/Styling/StylingModule.php`**

```php
<?php
/**
 * StylingModule — injects admin-controlled color overrides as inline CSS.
 *
 * @package BeplusAdvancedReviewsForWoocommerce
 * @subpackage Styling
 */

namespace BeplusAdvancedReviewsForWoocommerce\Styling;

use BeplusAdvancedReviewsForWoocommerce\Core\AbstractModule;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class StylingModule extends AbstractModule {

	/**
	 * Map of settings keys to CSS custom property names.
	 *
	 * @var array<string, string>
	 */
	private const COLOR_MAP = array(
		'primary'             => '--bpar-primary',
		'primary_light'       => '--bpar-primary-light',
		'primary_hover'       => '--bpar-primary-hover',
		'primary_hover_bg'    => '--bpar-primary-hover-bg',
		'primary_dragover_bg' => '--bpar-primary-dragover-bg',
		'text'                => '--bpar-text',
		'bg'                  => '--bpar-bg',
		'muted'               => '--bpar-muted',
		'date_muted'          => '--bpar-date-muted',
		'border'              => '--bpar-border',
		'border_dark'         => '--bpar-border-dark',
		'star_empty'          => '--bpar-star-empty',
		'error_bg'            => '--bpar-error-bg',
		'error_text'          => '--bpar-error-text',
		'error_border'        => '--bpar-error-border',
		'success_bg'          => '--bpar-success-bg',
		'success_text'        => '--bpar-success-text',
		'success_border'      => '--bpar-success-border',
	);

	/**
	 * Default color values — must match SettingsRegistry::get_defaults()['colors'].
	 *
	 * @var array<string, string>
	 */
	private const DEFAULTS = array(
		'primary'             => '#21652F',
		'primary_light'       => '#e9f3eb',
		'primary_hover'       => '#3a7a47',
		'primary_hover_bg'    => '#f2f8f3',
		'primary_dragover_bg' => '#e4efe6',
		'text'                => '#101010',
		'bg'                  => '#ffffff',
		'muted'               => '#8e8e8e',
		'date_muted'          => '#969696',
		'border'              => '#e0e6e1',
		'border_dark'         => '#bdc4be',
		'star_empty'          => '#cccccc',
		'error_bg'            => '#f8d7da',
		'error_text'          => '#721c24',
		'error_border'        => '#f5c6cb',
		'success_bg'          => '#d4edda',
		'success_text'        => '#155724',
		'success_border'      => '#c3e6cb',
	);

	public function register(): void {
		add_action( 'wp_head', array( $this, 'output_inline_css' ), 1 );
	}

	/**
	 * Output inline <style> block overriding --bpar-* custom properties.
	 *
	 * Only outputs when admin has changed at least one color from its default.
	 *
	 * @return void
	 */
	public function output_inline_css(): void {
		if ( ! function_exists( 'beplus_advanced_reviews_for_woocommerce_get_settings' ) ) {
			return;
		}

		$settings = beplus_advanced_reviews_for_woocommerce_get_settings();
		$colors   = $settings['colors'] ?? array();

		if ( ! is_array( $colors ) || ! $this->has_customizations( $colors ) ) {
			return;
		}

		$css = ":root {\n";
		foreach ( self::COLOR_MAP as $key => $var ) {
			if ( ! empty( $colors[ $key ] ) ) {
				$css .= "  {$var}: " . esc_attr( $colors[ $key ] ) . ";\n";
			}
		}
		$css .= "}\n";

		echo '<style id="bparfw-custom-colors">' . "\n" . $css . '</style>' . "\n";
	}

	/**
	 * Check whether any color differs from its default.
	 *
	 * @param array<string, string> $colors Current color settings.
	 * @return bool
	 */
	private function has_customizations( array $colors ): bool {
		foreach ( self::DEFAULTS as $key => $default_value ) {
			$current = $colors[ $key ] ?? '';
			if ( $current !== '' && $current !== $default_value ) {
				return true;
			}
		}
		return false;
	}
}
```

- [ ] **Step 3: Commit**

```bash
git add src/Styling/StylingModule.php
git commit -m "feat: add StylingModule for admin color control inline CSS injection

Co-Authored-By: Claude <noreply@anthropic.com>"
```

---

### Task 2: Update `src/Settings/SettingsRegistry.php` — Styling tab, defaults, render, sanitization

**Files:**
- Modify: `src/Settings/SettingsRegistry.php`

**Interfaces:**
- Consumes: Existing `SettingsRegistry` class structure (line 1–457), `OPTION_KEY` constant, card rendering pattern (`render_display_card`, etc.), `sanitize_settings`, `get_defaults`
- Produces: `get_defaults()` returns new `colors` key with 18 hex strings
- Produces: `render_settings_page()` has new Styling tab link in nav + new panel div
- Produces: `render_styling_card( array $settings ): void` — new private method
- Produces: `sanitize_settings()` handles `$input['colors']` sub-array

- [ ] **Step 1: Add `colors` to `get_defaults()`**

Replace the `get_defaults()` method (lines 441–456) with:

```php
private function get_defaults(): array {
	return array(
		'display_mode'      => 'keep',
		'enable_images'     => true,
		'enable_paste'      => true,
		'enable_filter'     => true,
		'enable_sort'       => true,
		'load_more_count'   => 10,
		'rating_threshold'  => 0,
		'max_image_size_mb' => 2,
		'enable_videos'     => false,
		'max_video_size_mb' => 20,
		'enable_verified'   => true,
		'verified_statuses' => 'completed_processing',
		'colors'            => array(
			'primary'             => '#21652F',
			'primary_light'       => '#e9f3eb',
			'primary_hover'       => '#3a7a47',
			'primary_hover_bg'    => '#f2f8f3',
			'primary_dragover_bg' => '#e4efe6',
			'text'                => '#101010',
			'bg'                  => '#ffffff',
			'muted'               => '#8e8e8e',
			'date_muted'          => '#969696',
			'border'              => '#e0e6e1',
			'border_dark'         => '#bdc4be',
			'star_empty'          => '#cccccc',
			'error_bg'            => '#f8d7da',
			'error_text'          => '#721c24',
			'error_border'        => '#f5c6cb',
			'success_bg'          => '#d4edda',
			'success_text'        => '#155724',
			'success_border'      => '#c3e6cb',
		),
	);
}
```

- [ ] **Step 2: Add the Styling tab to the nav and a new panel div in `render_settings_page()`**

In `render_settings_page()`, add the Styling tab link after the Media tab link (after line 68):

The tab link after the Media tab:
```php
<a href="#tab-styling" class="bparfw-settings__tab" data-bparfw-tab="styling">
	<?php echo esc_html__( 'Styling', 'beplus-advanced-reviews-for-woocommerce' ); ?>
</a>
```

Then add the Styling panel div after the Media panel (after line 85, before the `</form>`):

```php
<div class="bparfw-settings__panel" data-bparfw-panel="styling">
	<?php $this->render_styling_card( $settings ); ?>
</div>
```

- [ ] **Step 3: Add `render_color_field()` helper and `render_styling_card()` method**

Add these two methods before `sanitize_settings()` (before line 349):

```php
/**
 * Render a single color picker row.
 *
 * @param string             $key      Color settings key.
 * @param string             $label    Human-readable label.
 * @param string             $desc     Description text.
 * @param array<string, mixed> $settings Plugin settings.
 * @param array<string, string> $defaults Default color values.
 * @return void
 */
private function render_color_field( string $key, string $label, string $desc, array $settings, array $defaults ): void {
	$colors  = $settings['colors'] ?? array();
	$value   = $colors[ $key ] ?? $defaults[ $key ] ?? '#000000';
	$default = $defaults[ $key ] ?? '#000000';
	$is_custom = ( $value !== $default );
	$field_name = esc_attr( self::OPTION_KEY . '[colors][' . $key . ']' );
	$field_id   = 'bparfw-color-' . esc_attr( $key );
	?>
	<div class="bparfw-color-field" data-bparfw-color-field>
		<div class="bparfw-color-field__info">
			<label class="bparfw-color-field__label" for="<?php echo $field_id; ?>"><?php echo esc_html( $label ); ?></label>
			<p class="bparfw-color-field__desc"><?php echo esc_html( $desc ); ?></p>
		</div>
		<div class="bparfw-color-field__controls">
			<button type="button" class="bparfw-color-field__swatch" data-bparfw-color-swatch="<?php echo esc_attr( $key ); ?>" style="background-color:<?php echo esc_attr( $value ); ?>" aria-label="<?php echo esc_attr( sprintf( __( 'Choose %s', 'beplus-advanced-reviews-for-woocommerce' ), $label ) ); ?>">
				<input type="color" id="<?php echo $field_id; ?>" name="<?php echo $field_name; ?>" value="<?php echo esc_attr( $value ); ?>" class="bparfw-color-field__native" data-bparfw-color-input="<?php echo esc_attr( $key ); ?>" data-bparfw-color-default="<?php echo esc_attr( $default ); ?>">
			</button>
			<input type="text" class="bparfw-color-field__hex" data-bparfw-color-hex="<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( $value ); ?>" maxlength="7" pattern="^#[0-9A-Fa-f]{6}$" aria-label="<?php echo esc_attr( sprintf( __( 'Hex value for %s', 'beplus-advanced-reviews-for-woocommerce' ), $label ) ); ?>">
			<span class="bparfw-color-field__status" data-bparfw-color-status="<?php echo esc_attr( $key ); ?>">
				<?php if ( $is_custom ) : ?>
					<span class="bparfw-color-field__chip bparfw-color-field__chip--custom"><?php echo esc_html__( 'Custom', 'beplus-advanced-reviews-for-woocommerce' ); ?></span>
				<?php else : ?>
					<span class="bparfw-color-field__chip bparfw-color-field__chip--default"><?php echo esc_html__( 'Default', 'beplus-advanced-reviews-for-woocommerce' ); ?></span>
				<?php endif; ?>
			</span>
			<button type="button" class="bparfw-color-field__reset" data-bparfw-color-reset="<?php echo esc_attr( $key ); ?>" <?php echo $is_custom ? '' : 'hidden'; ?> aria-label="<?php echo esc_attr( sprintf( __( 'Reset %s to default', 'beplus-advanced-reviews-for-woocommerce' ), $label ) ); ?>">
				<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M1 4v6h6M23 20v-6h-6"/><path d="M20.49 9A9 9 0 005.64 5.64L1 10m22 4l-4.64 4.36A9 9 0 013.51 15"/></svg>
			</button>
		</div>
		<p class="bparfw-color-field__error" data-bparfw-color-error="<?php echo esc_attr( $key ); ?>" hidden><?php echo esc_html__( 'Enter a valid hex color (e.g. #FF0000).', 'beplus-advanced-reviews-for-woocommerce' ); ?></p>
	</div>
	<?php
}

/**
 * Render styling settings card.
 *
 * @param array<string, mixed> $settings Plugin settings.
 * @return void
 */
private function render_styling_card( array $settings ): void {
	$defaults = $this->get_defaults()['colors'];
	?>
	<div class="bparfw-settings__card">
		<h2 class="bparfw-settings__card-title"><?php echo esc_html__( 'Frontend Colors', 'beplus-advanced-reviews-for-woocommerce' ); ?></h2>
		<p class="description" style="margin-bottom:1.25rem;"><?php echo esc_html__( 'Customize every color used on the front-end review display. Leave a color at its default to keep the theme-aware styling.', 'beplus-advanced-reviews-for-woocommerce' ); ?></p>

		<fieldset class="bparfw-color-group">
			<legend class="bparfw-color-group__legend"><?php echo esc_html__( 'Primary Accent', 'beplus-advanced-reviews-for-woocommerce' ); ?></legend>
			<?php
			$this->render_color_field( 'primary', __( 'Primary Color', 'beplus-advanced-reviews-for-woocommerce' ), __( 'Main accent color used for stars, buttons, and links.', 'beplus-advanced-reviews-for-woocommerce' ), $settings, $defaults );
			$this->render_color_field( 'primary_light', __( 'Primary Light', 'beplus-advanced-reviews-for-woocommerce' ), __( 'Light tint of the primary color, used for subtle backgrounds.', 'beplus-advanced-reviews-for-woocommerce' ), $settings, $defaults );
			$this->render_color_field( 'primary_hover', __( 'Primary Hover', 'beplus-advanced-reviews-for-woocommerce' ), __( 'Slightly lighter primary for hover states on buttons and links.', 'beplus-advanced-reviews-for-woocommerce' ), $settings, $defaults );
			$this->render_color_field( 'primary_hover_bg', __( 'Primary Hover Background', 'beplus-advanced-reviews-for-woocommerce' ), __( 'Very light primary tint for hover backgrounds on cards.', 'beplus-advanced-reviews-for-woocommerce' ), $settings, $defaults );
			$this->render_color_field( 'primary_dragover_bg', __( 'Primary Dragover Background', 'beplus-advanced-reviews-for-woocommerce' ), __( 'Background color shown during drag-and-drop file uploads.', 'beplus-advanced-reviews-for-woocommerce' ), $settings, $defaults );
			?>
		</fieldset>

		<fieldset class="bparfw-color-group">
			<legend class="bparfw-color-group__legend"><?php echo esc_html__( 'Text & Background', 'beplus-advanced-reviews-for-woocommerce' ); ?></legend>
			<?php
			$this->render_color_field( 'text', __( 'Text Color', 'beplus-advanced-reviews-for-woocommerce' ), __( 'Main text color for review content and labels.', 'beplus-advanced-reviews-for-woocommerce' ), $settings, $defaults );
			$this->render_color_field( 'bg', __( 'Background Color', 'beplus-advanced-reviews-for-woocommerce' ), __( 'Background color for cards and panels.', 'beplus-advanced-reviews-for-woocommerce' ), $settings, $defaults );
			?>
		</fieldset>

		<fieldset class="bparfw-color-group">
			<legend class="bparfw-color-group__legend"><?php echo esc_html__( 'Derived Tones', 'beplus-advanced-reviews-for-woocommerce' ); ?></legend>
			<?php
			$this->render_color_field( 'muted', __( 'Muted Text', 'beplus-advanced-reviews-for-woocommerce' ), __( 'Secondary text for descriptions and helper copy.', 'beplus-advanced-reviews-for-woocommerce' ), $settings, $defaults );
			$this->render_color_field( 'date_muted', __( 'Date Muted', 'beplus-advanced-reviews-for-woocommerce' ), __( 'Color for review dates and timestamps.', 'beplus-advanced-reviews-for-woocommerce' ), $settings, $defaults );
			$this->render_color_field( 'border', __( 'Border Color', 'beplus-advanced-reviews-for-woocommerce' ), __( 'Border color for cards, inputs, and dividers.', 'beplus-advanced-reviews-for-woocommerce' ), $settings, $defaults );
			$this->render_color_field( 'border_dark', __( 'Border Dark', 'beplus-advanced-reviews-for-woocommerce' ), __( 'Darker border for focus states and emphasis.', 'beplus-advanced-reviews-for-woocommerce' ), $settings, $defaults );
			?>
		</fieldset>

		<fieldset class="bparfw-color-group">
			<legend class="bparfw-color-group__legend"><?php echo esc_html__( 'Stars', 'beplus-advanced-reviews-for-woocommerce' ); ?></legend>
			<?php
			$this->render_color_field( 'star_empty', __( 'Empty Star', 'beplus-advanced-reviews-for-woocommerce' ), __( 'Color for unfilled stars in the rating display.', 'beplus-advanced-reviews-for-woocommerce' ), $settings, $defaults );
			?>
		</fieldset>

		<fieldset class="bparfw-color-group">
			<legend class="bparfw-color-group__legend"><?php echo esc_html__( 'Error State', 'beplus-advanced-reviews-for-woocommerce' ); ?></legend>
			<?php
			$this->render_color_field( 'error_bg', __( 'Error Background', 'beplus-advanced-reviews-for-woocommerce' ), __( 'Background color for error messages and alerts.', 'beplus-advanced-reviews-for-woocommerce' ), $settings, $defaults );
			$this->render_color_field( 'error_text', __( 'Error Text', 'beplus-advanced-reviews-for-woocommerce' ), __( 'Text color for error messages.', 'beplus-advanced-reviews-for-woocommerce' ), $settings, $defaults );
			$this->render_color_field( 'error_border', __( 'Error Border', 'beplus-advanced-reviews-for-woocommerce' ), __( 'Border color for error states on inputs.', 'beplus-advanced-reviews-for-woocommerce' ), $settings, $defaults );
			?>
		</fieldset>

		<fieldset class="bparfw-color-group">
			<legend class="bparfw-color-group__legend"><?php echo esc_html__( 'Success State', 'beplus-advanced-reviews-for-woocommerce' ); ?></legend>
			<?php
			$this->render_color_field( 'success_bg', __( 'Success Background', 'beplus-advanced-reviews-for-woocommerce' ), __( 'Background color for success messages.', 'beplus-advanced-reviews-for-woocommerce' ), $settings, $defaults );
			$this->render_color_field( 'success_text', __( 'Success Text', 'beplus-advanced-reviews-for-woocommerce' ), __( 'Text color for success messages.', 'beplus-advanced-reviews-for-woocommerce' ), $settings, $defaults );
			$this->render_color_field( 'success_border', __( 'Success Border', 'beplus-advanced-reviews-for-woocommerce' ), __( 'Border color for success states.', 'beplus-advanced-reviews-for-woocommerce' ), $settings, $defaults );
			?>
		</fieldset>

		<div class="bparfw-color-group__actions">
			<button type="button" class="button button-secondary" id="bparfw-reset-colors"><?php echo esc_html__( 'Reset to Defaults', 'beplus-advanced-reviews-for-woocommerce' ); ?></button>
		</div>
	</div>
	<?php
}
```

- [ ] **Step 4: Add `colors` sanitization to `sanitize_settings()`**

Add this block at the end of `sanitize_settings()`, before `return $clean;` (before line 404):

```php
// Sanitize colors.
$color_defaults = $defaults['colors'] ?? array();
$clean['colors'] = array();
foreach ( $color_defaults as $color_key => $color_default ) {
	$raw = $input['colors'][ $color_key ] ?? '';
	$sanitized = sanitize_hex_color( $raw );
	$clean['colors'][ $color_key ] = $sanitized ? $sanitized : $color_default;
}
```

- [ ] **Step 5: Commit**

```bash
git add src/Settings/SettingsRegistry.php
git commit -m "feat: add Styling tab with 18 color pickers to settings page

Co-Authored-By: Claude <noreply@anthropic.com>"
```

---

### Task 3: Register `StylingModule` in `src/Core/Plugin.php`

**Files:**
- Modify: `src/Core/Plugin.php`

**Interfaces:**
- Consumes: `StylingModule` from `src/Styling/StylingModule.php`
- Produces: Container binding for `StylingModule::class`
- Produces: `StylingModule::class` in `boot_registered_modules()` array

- [ ] **Step 1: Add the import**

Add this after the existing `use` statements (after line 19):

```php
use BeplusAdvancedReviewsForWoocommerce\Styling\StylingModule;
```

- [ ] **Step 2: Add container binding in `register_core_services()`**

Add this after the `VerifiedPurchaseModule` binding (after line 73):

```php
$this->container->set( StylingModule::class, function ( Container $c ) {
	return new StylingModule( $c );
} );
```

- [ ] **Step 3: Add to boot order in `boot_registered_modules()`**

Add `StylingModule::class,` to the `$modules` array (after `VerifiedPurchaseModule::class,` on line 89):

```php
StylingModule::class,
```

- [ ] **Step 4: Commit**

```bash
git add src/Core/Plugin.php
git commit -m "feat: register StylingModule in container and boot order

Co-Authored-By: Claude <noreply@anthropic.com>"
```

---

### Task 4: Add default colors to `includes/install.php`

**Files:**
- Modify: `includes/install.php`

**Interfaces:**
- Consumes: `beplus_advanced_reviews_for_woocommerce_install_defaults()` function
- Produces: Updated defaults array with `colors` key

- [ ] **Step 1: Add `colors` to the defaults array**

In `beplus_advanced_reviews_for_woocommerce_install_defaults()`, add the `colors` key to the `$defaults` array (add after `'max_video_size_mb' => 20,` on line 31):

```php
'colors' => array(
	'primary'             => '#21652F',
	'primary_light'       => '#e9f3eb',
	'primary_hover'       => '#3a7a47',
	'primary_hover_bg'    => '#f2f8f3',
	'primary_dragover_bg' => '#e4efe6',
	'text'                => '#101010',
	'bg'                  => '#ffffff',
	'muted'               => '#8e8e8e',
	'date_muted'          => '#969696',
	'border'              => '#e0e6e1',
	'border_dark'         => '#bdc4be',
	'star_empty'          => '#cccccc',
	'error_bg'            => '#f8d7da',
	'error_text'          => '#721c24',
	'error_border'        => '#f5c6cb',
	'success_bg'          => '#d4edda',
	'success_text'        => '#155724',
	'success_border'      => '#c3e6cb',
),
```

- [ ] **Step 2: Commit**

```bash
git add includes/install.php
git commit -m "feat: add default color values to install defaults

Co-Authored-By: Claude <noreply@anthropic.com>"
```

---

### Task 5: Style the color picker UI in `admin/css/admin.scss`

**Files:**
- Modify: `admin/css/admin.scss`

**Interfaces:**
- Consumes: HTML structure from `render_color_field()` and `render_styling_card()` (classes: `.bparfw-color-group`, `.bparfw-color-field`, `.bparfw-color-field__swatch`, `.bparfw-color-field__hex`, `.bparfw-color-field__chip`, `.bparfw-color-field__reset`, `.bparfw-color-field__error`)
- Produces: Compiled `admin/css/admin.css` (via `npm run build:css`)

- [ ] **Step 1: Append color field styles to `admin/css/admin.scss`**

Add the following at the end of the file (after line 300, before the closing of the file):

```scss
// =============================================
// Color picker fields — Styling tab
// =============================================
.bparfw-color-group {
	border: 1px solid #e0e0e0;
	border-radius: 8px;
	padding: 1.25rem 1.5rem;
	margin-bottom: 1.25rem;
	background: #fafafa;
}

.bparfw-color-group__legend {
	font-size: 14px;
	font-weight: 600;
	color: #1d2327;
	padding: 0 8px;
}

.bparfw-color-group__actions {
	margin-top: 0.5rem;
	padding-top: 1rem;
	border-top: 1px solid #e0e0e0;
}

.bparfw-color-field {
	display: flex;
	align-items: flex-start;
	gap: 1.5rem;
	padding: 1rem 0 0.75rem;
	border-bottom: 1px solid #f0f0f1;

	&:first-of-type {
		padding-top: 0.5rem;
	}

	&:last-of-type {
		border-bottom: none;
		padding-bottom: 0;
	}
}

.bparfw-color-field__info {
	flex: 1;
	min-width: 180px;
}

.bparfw-color-field__label {
	display: block;
	font-size: 13px;
	font-weight: 600;
	color: #1d2327;
	margin-bottom: 2px;
}

.bparfw-color-field__desc {
	margin: 0;
	font-size: 12px;
	color: #646970;
	line-height: 1.4;
}

.bparfw-color-field__controls {
	display: flex;
	align-items: center;
	gap: 10px;
	flex-shrink: 0;
}

.bparfw-color-field__native {
	position: absolute;
	opacity: 0;
	width: 100%;
	height: 100%;
	cursor: pointer;
	margin: 0;
	padding: 0;
	border: none;
}

.bparfw-color-field__swatch {
	position: relative;
	display: inline-block;
	width: 32px;
	height: 32px;
	border-radius: 8px;
	border: 1px solid #c3c4c7;
	background: #fff;
	flex-shrink: 0;
	cursor: pointer;
	transition: transform 0.15s, box-shadow 0.15s;
	box-shadow: 0 1px 2px rgba(0, 0, 0, 0.08);

	&:hover {
		transform: scale(1.05);
		box-shadow: 0 2px 6px rgba(0, 0, 0, 0.12);
	}

	&:focus-within {
		outline: 2px solid #2271b1;
		outline-offset: 2px;
	}
}

.bparfw-color-field__hex {
	width: 90px;
	height: 32px;
	padding: 4px 8px;
	border: 1px solid #8c8f94;
	border-radius: 4px;
	font-family: monospace;
	font-size: 13px;
	text-align: center;
	color: #1d2327;
	text-transform: uppercase;
	transition: border-color 0.15s;

	&:focus {
		border-color: #2271b1;
		box-shadow: 0 0 0 1px #2271b1;
		outline: none;
	}

	&.is-invalid {
		border-color: #d63638;
		box-shadow: 0 0 0 1px #d63638;
		color: #d63638;
	}
}

.bparfw-color-field__status {
	min-width: 58px;
	text-align: center;
}

.bparfw-color-field__chip {
	display: inline-block;
	padding: 2px 8px;
	border-radius: 999px;
	font-size: 11px;
	font-weight: 600;
	text-transform: uppercase;
	letter-spacing: 0.3px;
	line-height: 1.6;

	&--default {
		background: #f0f0f1;
		color: #8c8f94;
	}

	&--custom {
		background: #e8f0fe;
		color: #1a73e8;
	}
}

.bparfw-color-field__reset {
	display: inline-flex;
	align-items: center;
	justify-content: center;
	width: 28px;
	height: 28px;
	padding: 0;
	border: 1px solid #c3c4c7;
	border-radius: 6px;
	background: #fff;
	color: #50575e;
	cursor: pointer;
	flex-shrink: 0;
	transition: color 0.15s, border-color 0.15s, background 0.15s;

	&:hover {
		color: #d63638;
		border-color: #d63638;
		background: #fcf0f1;
	}

	&:focus-visible {
		outline: 2px solid #2271b1;
		outline-offset: 1px;
	}

	&[hidden] {
		display: none;
	}
}

.bparfw-color-field__error {
	margin: 4px 0 0;
	font-size: 12px;
	color: #d63638;
	grid-column: 2;

	&[hidden] {
		display: none;
	}
}

// =============================================
// Responsive adjustments for color fields
// =============================================
@media (max-width: 782px) {
	.bparfw-color-field {
		flex-direction: column;
		gap: 0.5rem;
	}

	.bparfw-color-field__info {
		min-width: auto;
	}

	.bparfw-color-group {
		padding: 1rem;
	}
}
```

- [ ] **Step 2: Build CSS from SCSS**

```bash
npm run build:css
```

- [ ] **Step 3: Commit**

```bash
git add admin/css/admin.scss admin/css/admin.css
git commit -m "feat: add color picker UI styles for Styling tab

Co-Authored-By: Claude <noreply@anthropic.com>"
```

---

### Task 6: Add hex validation and color picker sync in `admin/js/settings.js`

**Files:**
- Modify: `admin/js/settings.js`

**Interfaces:**
- Consumes: HTML with `data-bparfw-color-input`, `data-bparfw-color-hex`, `data-bparfw-color-swatch`, `data-bparfw-color-reset`, `data-bparfw-color-status`, `data-bparfw-color-error`, `data-bparfw-color-default` attributes
- Produces: Real-time hex validation, color picker ↔ hex sync, reset button logic, "Reset to Defaults" button logic

- [ ] **Step 1: Append color picker logic to `admin/js/settings.js`**

Add this code at the end of the IIFE, before the closing `})();` (before line 85):

Since the full file is an IIFE, wrap the new code appropriately. Add it before the final `})();`:

```javascript
	// =============================================
	// Color picker sync & validation
	// =============================================
	var HEX_REGEX = /^#[0-9A-Fa-f]{6}$/;

	/**
	 * Update all UI elements for a color: swatch, hex input, status chip, reset button.
	 *
	 * @param {string} key
	 * @param {string} value
	 */
	function syncColorUI(key, value) {
		var swatch = document.querySelector('[data-bparfw-color-swatch="' + key + '"]');
		var hexInput = document.querySelector('[data-bparfw-color-hex="' + key + '"]');
		var statusEl = document.querySelector('[data-bparfw-color-status="' + key + '"]');
		var resetBtn = document.querySelector('[data-bparfw-color-reset="' + key + '"]');
		var errorEl = document.querySelector('[data-bparfw-color-error="' + key + '"]');
		var nativeInput = document.querySelector('[data-bparfw-color-input="' + key + '"]');
		var defaultVal = nativeInput ? nativeInput.getAttribute('data-bparfw-color-default') : '';

		if (swatch) {
			swatch.style.backgroundColor = value;
		}
		if (hexInput && hexInput.value.toUpperCase() !== value.toUpperCase()) {
			hexInput.value = value;
		}
		if (nativeInput && nativeInput.value.toUpperCase() !== value.toUpperCase()) {
			nativeInput.value = value;
		}

		var isDefault = (value.toUpperCase() === defaultVal.toUpperCase());
		if (statusEl) {
			if (isDefault) {
				statusEl.innerHTML = '<span class="bparfw-color-field__chip bparfw-color-field__chip--default">Default</span>';
			} else {
				statusEl.innerHTML = '<span class="bparfw-color-field__chip bparfw-color-field__chip--custom">Custom</span>';
			}
		}

		if (resetBtn) {
			resetBtn.hidden = isDefault;
		}

		if (errorEl) {
			errorEl.hidden = true;
		}
		if (hexInput) {
			hexInput.classList.remove('is-invalid');
		}
	}

	/**
	 * Show validation error on a hex input.
	 *
	 * @param {string} key
	 */
	function showColorError(key) {
		var hexInput = document.querySelector('[data-bparfw-color-hex="' + key + '"]');
		var errorEl = document.querySelector('[data-bparfw-color-error="' + key + '"]');
		if (hexInput) {
			hexInput.classList.add('is-invalid');
		}
		if (errorEl) {
			errorEl.hidden = false;
		}
	}

	/**
	 * Check all hex fields are valid; return true if all pass.
	 *
	 * @return {boolean}
	 */
	function validateAllColors() {
		var allHexInputs = document.querySelectorAll('[data-bparfw-color-hex]');
		var allValid = true;
		allHexInputs.forEach(function (input) {
			var key = input.getAttribute('data-bparfw-color-hex');
			if (input.value === '' || !HEX_REGEX.test(input.value)) {
				showColorError(key);
				allValid = false;
			} else {
				var errorEl = document.querySelector('[data-bparfw-color-error="' + key + '"]');
				input.classList.remove('is-invalid');
				if (errorEl) errorEl.hidden = true;
			}
		});
		return allValid;
	}

	// Native color input change → sync hex + swatch
	document.addEventListener('input', function (e) {
		var input = e.target;
		if (input.matches && input.matches('[data-bparfw-color-input]')) {
			var key = input.getAttribute('data-bparfw-color-input');
			syncColorUI(key, input.value);
		}
	});

	// Hex input change → sync native + swatch (only if valid)
	document.addEventListener('input', function (e) {
		var input = e.target;
		if (input.matches && input.matches('[data-bparfw-color-hex]')) {
			var key = input.getAttribute('data-bparfw-color-hex');
			var value = input.value;

			// Auto-prepend # if missing
			if (value.length > 0 && value[0] !== '#') {
				value = '#' + value;
				input.value = value;
			}

			if (HEX_REGEX.test(value)) {
				syncColorUI(key, value);
			}
		}
	});

	// Hex input blur → validate
	document.addEventListener('blur', function (e) {
		var input = e.target;
		if (input.matches && input.matches('[data-bparfw-color-hex]')) {
			var key = input.getAttribute('data-bparfw-color-hex');
			var value = input.value;

			if (value === '' || !HEX_REGEX.test(value)) {
				showColorError(key);
			} else {
				var errorEl = document.querySelector('[data-bparfw-color-error="' + key + '"]');
				input.classList.remove('is-invalid');
				if (errorEl) errorEl.hidden = true;
				// Ensure uppercase
				input.value = value.toUpperCase();
				syncColorUI(key, value);
			}
		}
	});

	// Individual reset button click
	document.addEventListener('click', function (e) {
		var resetBtn = e.target.closest('[data-bparfw-color-reset]');
		if (!resetBtn) return;

		e.preventDefault();
		var key = resetBtn.getAttribute('data-bparfw-color-reset');
		var nativeInput = document.querySelector('[data-bparfw-color-input="' + key + '"]');
		if (nativeInput) {
			var defaultVal = nativeInput.getAttribute('data-bparfw-color-default') || '#000000';
			syncColorUI(key, defaultVal);
		}
	});

	// Global "Reset to Defaults" button click
	document.addEventListener('click', function (e) {
		var resetAllBtn = e.target.closest('#bparfw-reset-colors');
		if (!resetAllBtn) return;

		e.preventDefault();
		if (!confirm('Reset all colors to their default values? This cannot be undone.')) {
			return;
		}

		var allNativeInputs = document.querySelectorAll('[data-bparfw-color-input]');
		allNativeInputs.forEach(function (input) {
			var key = input.getAttribute('data-bparfw-color-input');
			var defaultVal = input.getAttribute('data-bparfw-color-default') || '#000000';
			syncColorUI(key, defaultVal);
		});
	});

	// Block form submit if any hex field is invalid
	document.addEventListener('submit', function (e) {
		var form = e.target.closest('.bparfw-settings__form');
		if (!form) return;

		if (!validateAllColors()) {
			e.preventDefault();
			// Scroll to first invalid field
			var firstError = document.querySelector('[data-bparfw-color-hex].is-invalid');
			if (firstError) {
				firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
				firstError.focus();
			}
		}
	});
```

- [ ] **Step 2: Build JS**

```bash
npm run build
```

- [ ] **Step 3: Commit**

```bash
git add admin/js/settings.js
git commit -m "feat: add color picker sync, hex validation, and reset logic

Co-Authored-By: Claude <noreply@anthropic.com>"
```

---

### Task 7: Bump version to 1.1.0 in main plugin file

**Files:**
- Modify: `beplus-advanced-reviews-for-woocommerce.php`

- [ ] **Step 1: Update version header and constant**

Change line 6 (the `Version:` header) from `1.0.1` to `1.1.0`:
```php
 * Version:     1.1.0
```

Change line 23 (the `BEPLUS_ADVANCED_REVIEWS_FOR_WOOCOMMERCE_VERSION` constant) from `'1.0.1'` to `'1.1.0'`:
```php
define( 'BEPLUS_ADVANCED_REVIEWS_FOR_WOOCOMMERCE_VERSION', '1.1.0' );
```

- [ ] **Step 2: Commit**

```bash
git add beplus-advanced-reviews-for-woocommerce.php
git commit -m "chore: bump version to 1.1.0

Co-Authored-By: Claude <noreply@anthropic.com>"
```

---

### Task 8: Update `AGENTS.md`

**Files:**
- Modify: `AGENTS.md`

- [ ] **Step 1: Update description line**

Change line 21 from:
```
- **WordPress plugin:** Advanced WooCommerce product reviews with image support, star distribution, AJAX filtering, and load more.
```
to:
```
- **WordPress plugin:** Advanced WooCommerce product reviews with image support, star distribution, AJAX filtering, load more, verified purchase badges, and admin-controlled frontend color customization.
```

- [ ] **Step 2: Add Color Control feature entry**

Add a new section `### 8. Admin Color Control` after the "Theme-Aware Color System" section (after line 99). Insert:

```markdown
### 8. Admin Color Control

- All 18 `--bpar-*` CSS custom properties can be customized from **WooCommerce → Advanced Reviews → Styling**.
- Admins can override every frontend color — primary, text, background, derived tones, star colors, error/success states — independently with a color picker.
- Changes are saved to the `beplus_advanced_reviews_for_woocommerce_settings` option under the `colors` key and output as inline `<style>` in `<head>` via `StylingModule`.
- When all colors match their defaults, no inline CSS is emitted — the plugin falls back to the existing theme-aware system from `style.css`.
- Core logic lives in `src/Styling/StylingModule.php`. Admin UI lives in `src/Settings/SettingsRegistry.php` (`render_styling_card()`).
```

- [ ] **Step 3: Add StylingModule to module registry table**

Add to the module registry table (after `VerifiedPurchaseModule` row, around line 157):

```markdown
| `StylingModule` | `src/Styling/StylingModule.php` | Inline CSS injection for admin color overrides |
```

- [ ] **Step 4: Add to "Files you usually touch" table**

Add to the table (after `Verified purchase` row, around line 115):

```markdown
| Admin color control | `src/Styling/StylingModule.php`, `admin/js/settings.js` (color section), `admin/css/admin.scss` (`.bparfw-color-*`), `src/Settings/SettingsRegistry.php` (`render_styling_card()`) | — |
```

- [ ] **Step 5: Add styling tab to settings description**

In the "Plugin Settings" section (section 5), update the tab list (around line 73). Currently it says "Contains two main tabs:" — change to "Contains three main tabs:" then add the Styling tab after the Media tab description:

```markdown
  - **Styling:**
    - **Frontend Colors** — color pickers for all 18 `--bpar-*` CSS custom properties, grouped into Primary Accent, Text & Background, Derived Tones, Stars, Error State, and Success State sections.
```

- [ ] **Step 6: Commit**

```bash
git add AGENTS.md
git commit -m "docs: add admin color control feature to AGENTS.md

Co-Authored-By: Claude <noreply@anthropic.com>"
```

---

### Task 9: Update `Document Plugin.md`

**Files:**
- Modify: `Document Plugin.md`

- [ ] **Step 1: Add Color Control to Features Overview (Section 2)**

Add a new section `### 2.7 Admin Color Control` after section 2.6 (after line 85):

```markdown
### 2.7 Admin Color Control

Administrators can customize all 18 frontend color tokens from **WooCommerce → Advanced Reviews → Styling**.

- **Per-token color pickers:** Every `--bpar-*` CSS custom property (primary, text, background, derived tones, star colors, error/success states) has its own color picker with hex input.
- **Inline CSS injection:** `StylingModule` reads the `colors` settings array and outputs a `<style id="bparfw-custom-colors">` block at `wp_head` priority 1, overriding the default theme-aware colors.
- **Default-aware:** When all colors match their defaults, no inline CSS is emitted — the existing `style.css` `:root` block handles styling entirely.
- **Reset to defaults:** Individual per-field reset buttons and a global "Reset to Defaults" button allow quick reversion.
- Core logic: `src/Styling/StylingModule.php`. Admin UI: `src/Settings/SettingsRegistry.php` (`render_styling_card()`).
```

- [ ] **Step 2: Add `StylingModule` to class map (Section 20)**

Add after `VerifiedPurchaseModule` row (around line 1174):

```markdown
| `StylingModule` | `src/Styling/StylingModule.php` | Admin color control inline CSS injection |
```

- [ ] **Step 3: Add to directory structure (Section 4)**

Add `Styling/` directory under `src/` in the directory tree (after `src/Verified/` around line 165):

```
  │   ├── Styling/
  │   │   └── StylingModule.php
```

- [ ] **Step 4: Update Module Registry (Section 5 table in AGENTS.md already covers) — check section 3 architecture**

In the architecture diagram (Section 3), add `StylingModule` to the modules list (after `VerifiedPurchaseModule` around line 106):

```
  │   ├── StylingModule            ← Inline CSS for admin color overrides
```

- [ ] **Step 5: Update CSS Theming section (Section 4.1)**

Add a note at the end of section 4.1 (after the "To customize" code block around line 257):

```markdown

**Admin override (v1.1.0+):** Administrators can also set these tokens directly from **WooCommerce → Advanced Reviews → Styling**. When customized, the values are injected as inline CSS with higher priority than the theme fallbacks. See section 2.7.
```

- [ ] **Step 6: Commit**

```bash
git add "Document Plugin.md"
git commit -m "docs: add admin color control feature to Document Plugin.md

Co-Authored-By: Claude <noreply@anthropic.com>"
```

---

### Task 10: Update `readme.txt`

**Files:**
- Modify: `readme.txt`

- [ ] **Step 1: Bump stable tag**

Change line 7 from:
```
Stable tag: 1.0.1
```
to:
```
Stable tag: 1.1.0
```

- [ ] **Step 2: Add color control to feature list**

Add this bullet to the Key Features list (after the "Theme-aware styling" bullet around line 33):

```
* **Admin Color Control** — customize all 18 frontend colors (primary, text, background, stars, error/success states) from the plugin settings Styling tab
```

- [ ] **Step 3: Add FAQ entry**

Add a new FAQ after the "Verified Purchase" FAQ (after "Does it match my theme's colors?" around line 100):

```
= Can I customize the frontend colors? =

Yes. Navigate to **WooCommerce → Advanced Reviews → Styling** to find color pickers for all 18 frontend design tokens. You can customize primary color, text color, background color, star colors, border tones, and error/success states independently. Changes are saved and applied as inline CSS immediately. A "Reset to Defaults" button lets you revert all colors to the plugin defaults.
```

- [ ] **Step 4: Update the theme colors FAQ**

Update the existing "Does it match my theme's colors?" FAQ (around line 99) to mention the admin override:

The answer currently says:
```
Yes. The plugin automatically inherits colors from your active theme's `theme.json` global styles (primary, text, background). All colors are defined as `--bpar-*` CSS custom properties and can be overridden in custom CSS. Classic themes without `theme.json` will see the default color scheme.
```

Change to:
```
Yes. The plugin automatically inherits colors from your active theme's `theme.json` global styles (primary, text, background). All colors are defined as `--bpar-*` CSS custom properties. You can also customize every color directly from **WooCommerce → Advanced Reviews → Styling** without writing any CSS. Classic themes without `theme.json` will see the default color scheme.
```

- [ ] **Step 5: Add 1.1.0 changelog entry**

Add before the `= 1.0.1 =` entry (around line 112):

```
= 1.1.0 =
* New: Admin Color Control — customize all 18 frontend color tokens from the Styling settings tab
* New: Per-color pickers with hex input, grouped into Primary, Text & Background, Derived Tones, Stars, Error, and Success sections
* New: Inline CSS injection via `StylingModule` overrides theme defaults only when colors are customized
* New: "Reset to Defaults" button and per-field revert buttons
* Updated: AGENTS.md, Document Plugin.md, and readme.txt with color control documentation
```

- [ ] **Step 6: Commit**

```bash
git add readme.txt
git commit -m "docs: update readme.txt for v1.1.0 admin color control

Co-Authored-By: Claude <noreply@anthropic.com>"
```

---

### Task 11: Final build and verification

**Files:**
- All previously modified files must be committed

- [ ] **Step 1: Run full build**

```bash
npm run build
```

- [ ] **Step 2: Run precommit checks**

```bash
npm run precommit
```

- [ ] **Step 3: Fix any issues and commit if needed**

```bash
git add -A
git commit -m "chore: final build for v1.1.0

Co-Authored-By: Claude <noreply@anthropic.com>"
```

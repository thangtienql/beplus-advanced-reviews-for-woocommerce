# Admin Color Control — Design Spec

> **Status:** Approved  
> **Date:** 2026-08-06  
> **Version:** 1.1.0  
> **Topic:** Admin control over all frontend colors via a Styling tab in plugin settings

---

## 1. Overview

Allow administrators to control every color used on the plugin frontend through a **Styling** tab in the WooCommerce → Advanced Reviews settings page. All 18 `--bpar-*` CSS custom property tokens get their own color picker. Changes are saved to the database and output as inline `<style>` in `<head>`, overriding the default CSS.

**Version bump:** `1.0.1` → `1.1.0` (minor — new feature, no breaking changes).

---

## 2. Architecture

### 2.1 New Module: `src/Styling/StylingModule.php`

| Responsibility | Detail |
|---|---|
| **Hook** | `wp_head` priority 1 |
| **Logic** | Read `colors` from settings, compare with defaults, output inline `<style id="bparfw-custom-colors">` only if customizations exist |
| **Base class** | `AbstractModule` |

```
StylingModule::register()
  └── add_action( 'wp_head', [ $this, 'output_inline_css' ], 1 )
        └── Reads settings['colors'] → generates CSS → echoes <style> block
```

### 2.2 Changed: `src/Settings/SettingsRegistry.php`

- **New tab:** "Styling" in admin nav (same level as General, Media)
- **New card method:** `render_styling_card()` — renders 18 color pickers in 6 grouped sections
- **New defaults:** 18 color keys under `colors` array in `get_defaults()`
- **Sanitization:** `sanitize_settings()` adds hex color validation for all 18 keys

### 2.3 Changed: `src/Core/Plugin.php`

- Register `StylingModule` in `register_core_services()` container
- Add `StylingModule::class` to `boot_registered_modules()` array

### 2.4 Changed: `includes/install.php`

- Add default `colors` array to `beplus_advanced_reviews_for_woocommerce_install_defaults()`

### 2.5 Changed: `admin/js/settings.js`

- Tab switching already handles `data-bparfw-tab` — no structural change needed
- Add hex input validation logic for color fields

---

## 3. Data Model

All 18 colors stored under the existing option key `beplus_advanced_reviews_for_woocommerce_settings`, in a new `colors` sub-array. All values are plain 6-character hex strings (`#RRGGBB`). No `color-mix()` expressions are stored.

Default values for derived tones (primary_light, primary_hover, muted, etc.) are pre-computed approximations of the `color-mix()` expressions from the existing style.css. However, each derived tone is an **independent** color field — admin can change any derived tone without affecting the base colors. Changing `primary` does NOT automatically recalculate `primary_hover`; the admin controls every token individually.

### 3.1 Default Color Values

```php
'colors' => [
    // Primary accent (5 tokens)
    'primary'              => '#21652F',
    'primary_light'        => '#e9f3eb',
    'primary_hover'        => '#3a7a47',
    'primary_hover_bg'     => '#f2f8f3',
    'primary_dragover_bg'  => '#e4efe6',

    // Text & Background (2 tokens)
    'text'                 => '#101010',
    'bg'                   => '#ffffff',

    // Derived neutral tones (4 tokens)
    'muted'                => '#8e8e8e',
    'date_muted'           => '#969696',
    'border'               => '#e0e6e1',
    'border_dark'          => '#bdc4be',

    // Stars (1 token)
    'star_empty'           => '#cccccc',

    // Error state (3 tokens)
    'error_bg'             => '#f8d7da',
    'error_text'           => '#721c24',
    'error_border'         => '#f5c6cb',

    // Success state (3 tokens)
    'success_bg'           => '#d4edda',
    'success_text'         => '#155724',
    'success_border'       => '#c3e6cb',
],
```

### 3.2 Key-to-CSS-Variable Mapping

| Setting key | CSS variable |
|---|---|
| `primary` | `--bpar-primary` |
| `primary_light` | `--bpar-primary-light` |
| `primary_hover` | `--bpar-primary-hover` |
| `primary_hover_bg` | `--bpar-primary-hover-bg` |
| `primary_dragover_bg` | `--bpar-primary-dragover-bg` |
| `text` | `--bpar-text` |
| `bg` | `--bpar-bg` |
| `muted` | `--bpar-muted` |
| `date_muted` | `--bpar-date-muted` |
| `border` | `--bpar-border` |
| `border_dark` | `--bpar-border-dark` |
| `star_empty` | `--bpar-star-empty` |
| `error_bg` | `--bpar-error-bg` |
| `error_text` | `--bpar-error-text` |
| `error_border` | `--bpar-error-border` |
| `success_bg` | `--bpar-success-bg` |
| `success_text` | `--bpar-success-text` |
| `success_border` | `--bpar-success-border` |

---

## 4. Admin UI — Styling Tab

### 4.1 Tab Layout

New "Styling" tab alongside General and Media. Contains a single card with 6 grouped sections, each rendered as a `<fieldset>` with `<legend>`.

### 4.2 Section Groups

1. **Primary Accent** (5 fields): primary, primary_light, primary_hover, primary_hover_bg, primary_dragover_bg
2. **Text & Background** (2 fields): text, bg
3. **Derived Tones** (4 fields): muted, date_muted, border, border_dark
4. **Stars** (1 field): star_empty
5. **Error State** (3 fields): error_bg, error_text, error_border
6. **Success State** (3 fields): success_bg, success_text, success_border

### 4.3 Color Picker Component (Per-Row Design)

Each color row is a professional, user-friendly control:

```
┌─ Primary Color ─────────────────────────────────────────────┐
│  The main accent color used for stars, buttons, links.      │
│                                                              │
│  ┌──────┐  ┌──────────────────┐  ┌──────────┐   [↺]        │
│  │  ■   │  │  #21652F         │  │ Default  │              │
│  └──────┘  └──────────────────┘  └──────────┘              │
└──────────────────────────────────────────────────────────────┘
```

**Elements:**
- **Label:** Human-readable name (e.g., "Primary Color")
- **Description:** One-line hint below label
- **Color swatch button:** 32×32px, 8px border-radius, shows current color, click opens native `<input type="color">`, hover scale 1.05
- **Hex input:** Text field with real-time validation (`^#[0-9A-Fa-f]{6}$`), auto-syncs with color picker
- **Default indicator chip:** Gray "Default" chip when value matches default; hidden or "Custom" chip otherwise
- **Reset button:** ↺ icon, visible only when value differs from default, resets that single field to its default

### 4.4 Interactions & Accessibility

- Each section is a `<fieldset>` with `<legend>`
- Swatch button: `aria-label="Choose primary color"`
- Hex input: `aria-describedby` pointing to label
- Keyboard: Tab → Enter opens picker, Escape closes
- Focus ring visible on all interactive elements
- Invalid hex: red border + error text "Enter a valid hex color (e.g. #FF0000)"
- Form submit blocked if any field is invalid

### 4.5 Global Reset Button

- Secondary button "Reset to Defaults" at the bottom of the Styling card
- Confirm dialog: "Reset all colors to their default values? This cannot be undone."
- Resets all 18 fields client-side without page reload

---

## 5. Frontend CSS Injection

### 5.1 Cascade Order

```
1. blocks/advanced-review/style.css :root block   ← fallback (lowest priority)
2. Active theme theme.json overrides               ← middle
3. Inline <style id="bparfw-custom-colors">        ← admin overrides (highest)
```

### 5.2 Output Logic

`StylingModule::output_inline_css()` runs at `wp_head` priority 1:

```php
public function output_inline_css(): void {
    $settings = beplus_advanced_reviews_for_woocommerce_get_settings();
    $colors   = $settings['colors'] ?? array();

    if ( ! $this->has_customizations( $colors ) ) {
        return; // No inline CSS if everything is default
    }

    $css = ":root {\n";
    foreach ( $this->get_color_map() as $key => $var ) {
        if ( ! empty( $colors[ $key ] ) ) {
            $css .= "  {$var}: " . esc_attr( $colors[ $key ] ) . ";\n";
        }
    }
    $css .= "}\n";

    echo '<style id="bparfw-custom-colors">' . "\n" . $css . '</style>' . "\n";
}
```

### 5.3 No-Customization Optimization

When all 18 colors match defaults exactly → `has_customizations()` returns `false` → zero inline CSS output. Plugin uses the existing `style.css` `:root` block as-is, identical to pre-1.1.0 behavior.

---

## 6. Sanitization & Validation

### 6.1 Server-Side (PHP)

In `SettingsRegistry::sanitize_settings()`:

```php
if ( isset( $input['colors'] ) && is_array( $input['colors'] ) ) {
    $allowed_keys = [ /* 18 keys listed above */ ];
    foreach ( $allowed_keys as $key ) {
        $value = $input['colors'][ $key ] ?? '';
        $sanitized['colors'][ $key ] = sanitize_hex_color( $value )
            ?: $this->get_defaults()['colors'][ $key ];
    }
}
```

`sanitize_hex_color()` is a WordPress core function that validates `#RRGGBB` format and returns the value, or `null`/empty on invalid input. Invalid values fall back to the default for that key.

### 6.2 Client-Side (JavaScript)

- Real-time regex validation: `^#[0-9A-Fa-f]{6}$`
- Invalid state: red border + inline error message
- Form `submit` event: prevent if any hex field is invalid
- Color picker native output is always valid hex — no additional validation needed for swatch changes

### 6.3 Edge Cases Handled

| Input | Behavior |
|---|---|
| Empty string | Revert to default |
| Short hex (`#FFF`) | Rejected — must be 6 characters |
| 8-char hex (`#FF000080`) | Rejected — no alpha support |
| Named color (`red`) | Rejected |
| Malformed string | Sanitized to default |
| Missing `colors` key entirely (upgrade from 1.0.1) | No inline CSS output; existing style.css handles fallback |

---

## 7. Backward Compatibility

- **Upgrade from 1.0.1:** No `colors` key exists in DB settings. `get_all()` returns defaults merged with stored. No inline CSS is output (all defaults match). Plugin renders identically to 1.0.1.
- **Downgrade:** If a user rolls back to 1.0.1, the `colors` key in the option is ignored by the old code. No errors. Colors revert to theme-aware defaults.
- **Existing CSS custom properties in style.css:** Unchanged. The `:root` block with `--bpar-*` variables remains as the fallback layer.

---

## 8. Files Changed

### New Files
| File | Purpose |
|---|---|
| `src/Styling/StylingModule.php` | Color CSS injection at wp_head |

### Modified Files
| File | Change |
|---|---|
| `beplus-advanced-reviews-for-woocommerce.php` | Version `1.0.1` → `1.1.0` |
| `src/Core/Plugin.php` | Register `StylingModule` container binding + boot |
| `src/Settings/SettingsRegistry.php` | Add Styling tab, render method, 18 color defaults, sanitization |
| `includes/install.php` | Add default `colors` array |
| `admin/js/settings.js` | Add hex input validation logic |
| `admin/css/admin.scss` / `admin.css` | Style color picker rows, swatches, sections |
| `AGENTS.md` | Add Styling feature and StylingModule to docs |
| `Document Plugin.md` | Add Color Control feature, StylingModule to class map, update directories |
| `readme.txt` | Add 1.1.0 changelog entry, update stable tag, feature description |

---

## 9. Testing Surface

- [ ] Admin settings page loads Styling tab with all 18 color fields
- [ ] Each color picker opens native browser picker on swatch click
- [ ] Hex input syncs with color picker (both directions)
- [ ] Invalid hex shows error state, blocks form submit
- [ ] Save → colors persist in database → inline `<style>` appears in frontend `<head>`
- [ ] "Reset to Defaults" reverts all fields client-side
- [ ] Single-field reset button works
- [ ] Upgrade from 1.0.1: no `colors` key → no inline CSS → frontend unchanged
- [ ] Default indicator chip shows/hides correctly
- [ ] Keyboard navigation works on all color fields
- [ ] Settings REST endpoint returns `colors` object
- [ ] PHPStan passes, no type errors
- [ ] No JavaScript errors in browser console

---

## 10. Documentation Checklist

All of these files must be updated before the feature is considered complete:

- [x] `beplus-advanced-reviews-for-woocommerce.php` — version bump to `1.1.0`
- [x] `AGENTS.md` — add Styling feature, StylingModule to module registry
- [x] `Document Plugin.md` — add Color Control feature description, StylingModule to class map, update directory structure, update CSS theming section
- [x] `readme.txt` — bump stable tag 1.0.1 → 1.1.0, add 1.1.0 changelog entry, add "Can I customize the colors?" FAQ, add color control to feature list

# Verified Purchase Badge — Design Spec

## Context

Added "Verified Purchase" feature to the Beplus Advanced Reviews For WooCommerce plugin. Reviews from customers who actually purchased the product display a "Verified Purchase" badge, increasing trust for viewers.

## Decisions Made

| # | Question | Decision |
|---|----------|----------|
| 1 | How it works | **Badge only** — All reviews can still be submitted; reviews from buyers get a badge, others don't |
| 2 | How to determine purchase | **Check both guest and logged-in** — Match via user ID (logged-in) or email (guest) with orders table |
| 3 | Frontend filter | **Badge + filter** — "Verified only" toggle on filter bar |
| 4 | Valid order statuses | **completed + processing** |
| 5 | Admin config | **Toggle on/off** + sub-setting: "Completed only" or "Completed + Processing" |
| 6 | Backend mechanism | **Batch query + cache (5 min) + bust cache** on order status change |

## Files Created

- `src/Verified/VerifiedPurchaseChecker.php` — Batch verified check, cache, bust cache
- `src/Verified/VerifiedPurchaseModule.php` — Hooks for cache busting on order status changes

## Files Modified

### Backend
- `src/Reviews/ReviewFormatter.php` — Added `is_verified` field, batch check in `format_list()`
- `src/Reviews/ReviewQuery.php` — Added `verified_only` param
- `src/Reviews/ReviewRepository.php` — Added `verified_only` filter in query
- `src/REST/ReviewController.php` — Accepts `verified_only` REST param
- `src/Settings/SettingsRegistry.php` — Added `enable_verified`, `verified_statuses` settings + UI card
- `src/Core/Plugin.php` — Registered `VerifiedPurchaseModule`
- `src/Core/AssetLoader.php` — Localized `verifiedPurchase` i18n string
- `includes/common.php` — Added `beplus_advanced_reviews_for_woocommerce_is_verified_enabled()` helper

### Frontend
- `blocks/advanced-review/view.js` — Badge rendering, verified filter checkbox
- `blocks/advanced-review/_review-card.scss` — Verified badge styles
- `blocks/advanced-review/render.php` — Verified filter toggle markup
- `blocks/advanced-review/block.json` — `showVerifiedFilter` attribute
- `blocks/advanced-review/edit.tsx` — Inspector control for `showVerifiedFilter`

## Verification

1. **Admin:** WooCommerce > Advanced Reviews — toggle verified purchase, change status option
2. **Frontend:** User with completed order sees badge; guest without order doesn't
3. **Filter:** "Verified only" toggle filters to only verified reviews
4. **Cache bust:** Order marked completed → badge appears on next page load
5. **REST:** `GET /reviews?product_id=X&verified_only=true` returns only verified reviews
6. **Backward compatible:** `enable_verified = false` → everything works as before

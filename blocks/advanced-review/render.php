<?php
/**
 * Server-side render for the Advanced Reviews block.
 *
 * When block.json specifies "render": "file:./render.php",
 * WordPress includes this file and captures its output.
 * Variables $attributes, $content, and $block are available.
 *
 * @package BeplusAdvancedReviewsForWoocommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Block render template; variables are scoped by WordPress's block rendering lifecycle.

$product_id = beplus_advanced_reviews_for_woocommerce_get_current_product_id();

if ( ! $product_id ) {
	if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
		return;
	}
	echo '<p>' . esc_html__( 'Advanced Reviews block requires a product context.', 'beplus-advanced-reviews-for-woocommerce' ) . '</p>';
	return;
}

\BeplusAdvancedReviewsForWoocommerce\Core\Placement::mark_rendered();
$show_distribution = ! empty( $attributes['showDistribution'] );
$show_filter_bar   = ! empty( $attributes['showFilterBar'] ) && beplus_advanced_reviews_for_woocommerce_is_filter_enabled();
$show_submit_form  = ! empty( $attributes['showSubmitForm'] );
$show_avatar       = ! empty( $attributes['showAvatar'] );
$images_enabled    = beplus_advanced_reviews_for_woocommerce_is_images_enabled();
$videos_enabled    = beplus_advanced_reviews_for_woocommerce_is_videos_enabled();
$media_enabled     = $images_enabled || $videos_enabled;

// Resolve media visibility: new `showMedia` attribute, with backward-compat
// fallback to the deprecated `showImages` attribute for previously-saved blocks.
$show_media = ! empty( $attributes['showMedia'] );
if ( isset( $attributes['showImages'] ) && ! $attributes['showImages'] ) {
	$show_media = false;
}

$show_verified_filter = $show_filter_bar
	&& ! empty( $attributes['showVerifiedFilter'] )
	&& beplus_advanced_reviews_for_woocommerce_is_verified_enabled();
$show_sort         = beplus_advanced_reviews_for_woocommerce_is_sort_enabled();
$reviews_per_load  = beplus_advanced_reviews_for_woocommerce_get_load_more_count();
$rating_threshold  = (int) ( beplus_advanced_reviews_for_woocommerce_get_settings()['rating_threshold'] ?? 0 );
$enable_lazy_load  = ! empty( $attributes['enableLazyLoad'] );

$reviews_label      = beplus_advanced_reviews_for_woocommerce_get_display_label( 'label_customer_reviews', __( 'Customer Reviews', 'beplus-advanced-reviews-for-woocommerce' ) );
$write_review_label = beplus_advanced_reviews_for_woocommerce_get_display_label( 'label_write_review', __( 'Write a review', 'beplus-advanced-reviews-for-woocommerce' ) );
$load_more_label    = beplus_advanced_reviews_for_woocommerce_get_display_label( 'label_load_more', __( 'Load More', 'beplus-advanced-reviews-for-woocommerce' ) );

$wrapper_attrs = get_block_wrapper_attributes(
	array(
		'class'            => 'beplus-advanced-reviews-for-woocommerce',
		'data-product-id'  => (string) $product_id,
		'data-per-page'    => (string) $reviews_per_load,
		'data-show-avatar' => $show_avatar ? '1' : '0',
		'data-show-media'  => $show_media ? '1' : '0',
		'data-enable-lazy' => $enable_lazy_load ? '1' : '0',
	)
);

?>
<div <?php echo $wrapper_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>

	<h2 class="beplus-advanced-reviews-for-woocommerce__header"><?php echo esc_html( $reviews_label ); ?></h2>

	<div class="beplus-advanced-reviews-for-woocommerce__layout">
		<div class="beplus-advanced-reviews-for-woocommerce__layout-sidebar">
			<?php if ( $show_distribution ) : ?>
				<div class="beplus-advanced-reviews-for-woocommerce__distribution" aria-live="polite">
					<?php beplus_advanced_reviews_for_woocommerce_get_template( 'star-distribution.php', array( 'product_id' => $product_id ) ); ?>
				</div>
			<?php endif; ?>

			<?php if ( $show_submit_form ) : ?>
				<button type="button"
					class="beplus-advanced-reviews-for-woocommerce__sidebar-write-btn"
					onclick="var f=this.closest('.beplus-advanced-reviews-for-woocommerce').querySelector('.beplus-advanced-reviews-for-woocommerce__submit-form-wrapper');if(f){f.scrollIntoView({behavior:'smooth'});setTimeout(function(){f.querySelector('textarea').focus();},400);}"
					aria-label="<?php echo esc_attr( $write_review_label ); ?>">
					<?php echo esc_html( $write_review_label ); ?>
				</button>
			<?php endif; ?>
		</div>

		<div class="beplus-advanced-reviews-for-woocommerce__layout-main">
			<div class="beplus-advanced-reviews-for-woocommerce__filter-controls">
			<div class="beplus-advanced-reviews-for-woocommerce__filter-controls-left">
			<?php if ( $show_filter_bar ) : ?>
				<div class="beplus-advanced-reviews-for-woocommerce__filter-stars">
					<label for="bpar-filter-rating-select" class="beplus-advanced-reviews-for-woocommerce__filter-label"><?php esc_html_e( 'Rating', 'beplus-advanced-reviews-for-woocommerce' ); ?></label>
					<div class="beplus-advanced-reviews-for-woocommerce__sort-wrapper">
						<select id="bpar-filter-rating-select" class="beplus-advanced-reviews-for-woocommerce__filter-rating-select" aria-label="<?php esc_attr_e( 'Filter reviews by star rating', 'beplus-advanced-reviews-for-woocommerce' ); ?>">
							<option value="0"><?php esc_html_e( 'All ratings', 'beplus-advanced-reviews-for-woocommerce' ); ?></option>
							<?php for ( $i = 5; $i >= 1; $i-- ) : ?>
								<option value="<?php echo esc_attr( (string) $i ); ?>">
									<?php
									/* translators: %d: Number of stars */
									echo esc_html( sprintf( __( '%d stars', 'beplus-advanced-reviews-for-woocommerce' ), $i ) );
									?>
								</option>
							<?php endfor; ?>
						</select>
						<span class="beplus-advanced-reviews-for-woocommerce__sort-icon" aria-hidden="true">
							<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
						</span>
					</div>
				</div>
			<?php endif; ?>

			<?php if ( $show_sort && $show_filter_bar ) : ?>
				<div class="beplus-advanced-reviews-for-woocommerce__filter-sort">
					<label for="bpar-sort-select" class="beplus-advanced-reviews-for-woocommerce__filter-label">
						<?php esc_html_e( 'Sort by', 'beplus-advanced-reviews-for-woocommerce' ); ?>
					</label>
					<div class="beplus-advanced-reviews-for-woocommerce__sort-wrapper">
						<select id="bpar-sort-select" class="beplus-advanced-reviews-for-woocommerce__sort-select" aria-label="<?php esc_attr_e( 'Sort reviews', 'beplus-advanced-reviews-for-woocommerce' ); ?>">
							<option value="newest"><?php esc_html_e( 'Newest', 'beplus-advanced-reviews-for-woocommerce' ); ?></option>
							<option value="oldest"><?php esc_html_e( 'Oldest', 'beplus-advanced-reviews-for-woocommerce' ); ?></option>
							<option value="highest"><?php esc_html_e( 'Highest rated', 'beplus-advanced-reviews-for-woocommerce' ); ?></option>
							<option value="lowest"><?php esc_html_e( 'Lowest rated', 'beplus-advanced-reviews-for-woocommerce' ); ?></option>
							<?php if ( \BeplusAdvancedReviewsForWoocommerce\Reviews\ReviewVoteRepository::is_available_for_product( (int) $product_id ) ) : ?>
								<option value="helpful"><?php esc_html_e( 'Most helpful', 'beplus-advanced-reviews-for-woocommerce' ); ?></option>
							<?php endif; ?>
						</select>
						<span class="beplus-advanced-reviews-for-woocommerce__sort-icon" aria-hidden="true">
							<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
						</span>
					</div>
				</div>
			<?php endif; ?>
			</div>

			<?php if ( ( $show_filter_bar && $show_media && $media_enabled ) || $show_verified_filter ) : ?>
			<div class="beplus-advanced-reviews-for-woocommerce__filter-controls-right">
			<?php if ( $show_media && $media_enabled ) : ?>
				<?php
				if ( $images_enabled && $videos_enabled ) {
					$media_filter_label = __( 'With media', 'beplus-advanced-reviews-for-woocommerce' );
				} elseif ( $videos_enabled ) {
					$media_filter_label = __( 'With videos', 'beplus-advanced-reviews-for-woocommerce' );
				} else {
					$media_filter_label = __( 'With images', 'beplus-advanced-reviews-for-woocommerce' );
				}
				?>
				<button type="button"
					class="beplus-advanced-reviews-for-woocommerce__filter-chip"
					data-filter="images"
					aria-pressed="false">
					<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
					<span><?php echo esc_html( $media_filter_label ); ?></span>
				</button>
			<?php endif; ?>

			<?php if ( $show_verified_filter ) : ?>
				<button type="button"
					class="beplus-advanced-reviews-for-woocommerce__filter-chip"
					data-filter="verified"
					aria-pressed="false">
					<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>
					<span><?php esc_html_e( 'Verified', 'beplus-advanced-reviews-for-woocommerce' ); ?></span>
				</button>
			<?php endif; ?>
			</div>
			<?php endif; ?>
			</div>

			<div class="beplus-advanced-reviews-for-woocommerce__list-container" aria-live="polite">
				<div class="beplus-advanced-reviews-for-woocommerce__list">
					<?php beplus_advanced_reviews_for_woocommerce_get_template( 'review-list.php', array( 'product_id' => $product_id, 'show_avatar' => $show_avatar, 'show_media' => $show_media, 'per_page' => $reviews_per_load, 'rating_threshold' => $rating_threshold ) ); ?>
				</div>

				<div class="beplus-advanced-reviews-for-woocommerce__load-more-wrapper" hidden>
					<button type="button" class="beplus-advanced-reviews-for-woocommerce__load-more">
						<span data-request-label="<?php echo esc_attr( $load_more_label ); ?>"><?php echo esc_html( $load_more_label ); ?></span>
						<span class="beplus-advanced-reviews-for-woocommerce__load-more-spinner" aria-hidden="true"></span>
					</button>
				</div>
			</div>

			<?php if ( $show_submit_form ) : ?>
				<?php beplus_advanced_reviews_for_woocommerce_get_template( 'review-form.php', array( 'product_id' => $product_id, 'show_media' => $show_media ) ); ?>
			<?php endif; ?>
		</div>
	</div>

</div>

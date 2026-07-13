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
$show_images       = ! empty( $attributes['showImages'] );
$show_avatar       = ! empty( $attributes['showAvatar'] );
$show_sort         = beplus_advanced_reviews_for_woocommerce_is_sort_enabled();
$reviews_per_load  = isset( $attributes['reviewsPerLoad'] ) && $attributes['reviewsPerLoad'] > 0
	? absint( $attributes['reviewsPerLoad'] )
	: beplus_advanced_reviews_for_woocommerce_get_load_more_count();
$enable_lazy_load  = ! empty( $attributes['enableLazyLoad'] );

$wrapper_attrs = get_block_wrapper_attributes(
	array(
		'class'            => 'beplus-advanced-reviews-for-woocommerce beplus-advanced-reviews-for-woocommerce--loading',
		'data-product-id'  => (string) $product_id,
		'data-per-page'    => (string) $reviews_per_load,
		'data-show-avatar' => $show_avatar ? '1' : '0',
		'data-show-images' => $show_images ? '1' : '0',
		'data-enable-lazy' => $enable_lazy_load ? '1' : '0',
	)
);

?>
<div <?php echo $wrapper_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>

	<h2 class="beplus-advanced-reviews-for-woocommerce__header"><?php esc_html_e( 'Customer Reviews', 'beplus-advanced-reviews-for-woocommerce' ); ?></h2>

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
					aria-label="<?php esc_attr_e( 'Write a review', 'beplus-advanced-reviews-for-woocommerce' ); ?>">
					<?php esc_html_e( 'Write a review', 'beplus-advanced-reviews-for-woocommerce' ); ?>
				</button>
			<?php endif; ?>
		</div>

		<div class="beplus-advanced-reviews-for-woocommerce__layout-main">
			<div class="beplus-advanced-reviews-for-woocommerce__filter-controls">
			<div class="beplus-advanced-reviews-for-woocommerce__filter-controls-left">
			<?php if ( $show_filter_bar ) : ?>
				<div class="beplus-advanced-reviews-for-woocommerce__filter-stars">
					<label for="bpar-filter-rating-select" class="beplus-advanced-reviews-for-woocommerce__filter-label"><?php esc_html_e( 'Filter by rating:', 'beplus-advanced-reviews-for-woocommerce' ); ?></label>
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
						<span class="beplus-advanced-reviews-for-woocommerce__sort-icon">
							<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
						</span>
					</div>
				</div>
			<?php endif; ?>

			<?php if ( $show_sort ) : ?>
				<div class="beplus-advanced-reviews-for-woocommerce__filter-sort">
					<label for="bpar-sort-select" class="beplus-advanced-reviews-for-woocommerce__filter-label">
						<?php esc_html_e( 'Sort by:', 'beplus-advanced-reviews-for-woocommerce' ); ?>
					</label>
					<div class="beplus-advanced-reviews-for-woocommerce__sort-wrapper">
						<select id="bpar-sort-select" class="beplus-advanced-reviews-for-woocommerce__sort-select" aria-label="<?php esc_attr_e( 'Sort reviews', 'beplus-advanced-reviews-for-woocommerce' ); ?>">
							<option value="newest"><?php esc_html_e( 'Newest', 'beplus-advanced-reviews-for-woocommerce' ); ?></option>
							<option value="oldest"><?php esc_html_e( 'Oldest', 'beplus-advanced-reviews-for-woocommerce' ); ?></option>
							<option value="highest"><?php esc_html_e( 'Highest rated', 'beplus-advanced-reviews-for-woocommerce' ); ?></option>
							<option value="lowest"><?php esc_html_e( 'Lowest rated', 'beplus-advanced-reviews-for-woocommerce' ); ?></option>
						</select>
						<span class="beplus-advanced-reviews-for-woocommerce__sort-icon">
							<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
						</span>
					</div>
				</div>
			<?php endif; ?>
			</div>

			<?php if ( $show_filter_bar && $show_images ) : ?>
				<div class="beplus-advanced-reviews-for-woocommerce__filter-images">
					<label class="beplus-advanced-reviews-for-woocommerce__filter-toggle">
						<span class="beplus-advanced-reviews-for-woocommerce__filter-toggle-text"><?php esc_html_e( 'With images only', 'beplus-advanced-reviews-for-woocommerce' ); ?></span>
						<div class="beplus-advanced-reviews-for-woocommerce__filter-toggle-switch">
							<input type="checkbox" class="beplus-advanced-reviews-for-woocommerce__filter-images-input" aria-label="<?php esc_attr_e( 'Show only reviews with images', 'beplus-advanced-reviews-for-woocommerce' ); ?>">
							<span class="beplus-advanced-reviews-for-woocommerce__filter-toggle-slider"></span>
						</div>
					</label>
				</div>
			<?php endif; ?>
			</div>

			<div class="beplus-advanced-reviews-for-woocommerce__list-container" aria-live="polite">
				<div class="beplus-advanced-reviews-for-woocommerce__list">
					<?php beplus_advanced_reviews_for_woocommerce_get_template( 'review-list.php', array( 'product_id' => $product_id, 'show_avatar' => $show_avatar, 'show_images' => $show_images, 'per_page' => $reviews_per_load ) ); ?>
				</div>

				<div class="beplus-advanced-reviews-for-woocommerce__load-more-wrapper">
					<button type="button" class="beplus-advanced-reviews-for-woocommerce__load-more button" aria-label="<?php esc_attr_e( 'Load more reviews', 'beplus-advanced-reviews-for-woocommerce' ); ?>">
						<?php esc_html_e( 'Load More', 'beplus-advanced-reviews-for-woocommerce' ); ?>
						<span class="beplus-advanced-reviews-for-woocommerce__load-more-spinner" aria-hidden="true"></span>
					</button>
				</div>
			</div>

			<?php if ( $show_submit_form ) : ?>
				<?php beplus_advanced_reviews_for_woocommerce_get_template( 'review-form.php', array( 'product_id' => $product_id, 'show_images' => $show_images ) ); ?>
			<?php endif; ?>
		</div>
	</div>

</div>

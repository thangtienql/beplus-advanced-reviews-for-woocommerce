<?php
/**
 * Template: Review Card
 *
 * @package BeplusAdvancedReviewsForWoocommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template file; variables are local to the included scope.
$review        = $args['review'] ?? array();
$show_avatar   = ! empty( $args['show_avatar'] );
$show_media    = ! empty( $args['show_media'] );
if ( ! $show_media && isset( $args['show_images'] ) ) {
	$show_media = ! empty( $args['show_images'] );
}
$rating        = (int) ( $review['rating'] ?? 0 );
$author        = esc_html( $review['author'] ?? '' );
$author_init   = mb_substr( $author, 0, 1 );
$content       = wp_kses_post( $review['content'] ?? '' );
$date_human    = esc_html( $review['date_human'] ?? '' );
$avatar        = esc_url( $review['avatar'] ?? '' );
$images        = $review['images'] ?? array();
$is_verified   = ! empty( $review['is_verified'] );
?>
<article class="beplus-advanced-reviews-for-woocommerce__review-card">
	<?php if ( $show_avatar ) : ?>
		<div class="beplus-advanced-reviews-for-woocommerce__review-avatar">
			<?php if ( $avatar ) : ?>
				<img src="<?php echo $avatar; // phpcs:ignore ?>" alt="<?php echo $author; // phpcs:ignore ?>" width="40" height="40" loading="lazy">
			<?php else : ?>
				<div class="beplus-advanced-reviews-for-woocommerce__review-avatar--fallback"><?php echo esc_html( $author_init ); ?></div>
			<?php endif; ?>
		</div>
	<?php endif; ?>
	<div class="beplus-advanced-reviews-for-woocommerce__review-body">
		<div class="beplus-advanced-reviews-for-woocommerce__review-header">
			<div class="beplus-advanced-reviews-for-woocommerce__review-author-group">
				<span class="beplus-advanced-reviews-for-woocommerce__review-author"><?php echo $author; // phpcs:ignore ?></span>
				<?php if ( $is_verified ) : ?>
					<span class="beplus-advanced-reviews-for-woocommerce__verified-badge" aria-label="<?php esc_attr_e( 'Verified Purchase', 'beplus-advanced-reviews-for-woocommerce' ); ?>">
						<span class="beplus-advanced-reviews-for-woocommerce__verified-badge-icon" aria-hidden="true">
							<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><polyline points="9 12 11 14 15 10"/></svg>
						</span>
						<?php esc_html_e( 'Verified Purchase', 'beplus-advanced-reviews-for-woocommerce' ); ?>
					</span>
				<?php endif; ?>
			</div>
			<span class="beplus-advanced-reviews-for-woocommerce__review-rating">
				<?php echo beplus_advanced_reviews_for_woocommerce_render_stars( $rating ); // phpcs:ignore ?>
			</span>
		</div>
		<div class="beplus-advanced-reviews-for-woocommerce__review-content"><?php echo $content; // phpcs:ignore ?></div>
		<?php if ( $show_media && $images ) : ?>
			<?php
			$media_json = wp_json_encode(
				array_map(
					function ( $m ) {
						return array(
							'url'       => $m['url'] ?? '',
							'thumbnail' => $m['thumbnail'] ?? $m['url'] ?? '',
							'mime_type' => $m['mime_type'] ?? '',
						);
					},
					$images
				)
			);
			?>
			<div class="beplus-advanced-reviews-for-woocommerce__review-images" data-review-media="<?php echo esc_attr( $media_json ); ?>">
				<?php foreach ( $images as $idx => $media ) : ?>
					<?php $is_video = ! empty( $media['mime_type'] ) && str_starts_with( $media['mime_type'], 'video/' ); ?>
					<?php if ( $is_video ) : ?>
						<button type="button" class="beplus-advanced-reviews-for-woocommerce__review-media-btn beplus-advanced-reviews-for-woocommerce__review-media-btn--video" data-media-index="<?php echo esc_attr( (string) $idx ); ?>" data-media-type="video" aria-label="<?php esc_attr_e( 'View media', 'beplus-advanced-reviews-for-woocommerce' ); ?>">
							<video src="<?php echo esc_url( $media['url'] ?? '' ); ?>" width="80" height="80" class="beplus-advanced-reviews-for-woocommerce__review-video" muted preload="metadata"></video>
						</button>
					<?php else : ?>
						<button type="button" class="beplus-advanced-reviews-for-woocommerce__review-media-btn" data-media-index="<?php echo esc_attr( (string) $idx ); ?>" data-media-type="image" aria-label="<?php esc_attr_e( 'View media', 'beplus-advanced-reviews-for-woocommerce' ); ?>">
							<img src="<?php echo esc_url( $media['thumbnail'] ?? '' ); ?>" alt="" width="80" height="80" loading="lazy" class="beplus-advanced-reviews-for-woocommerce__review-image-thumb">
						</button>
					<?php endif; ?>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
		<footer class="beplus-advanced-reviews-for-woocommerce__review-footer">
			<div class="beplus-advanced-reviews-for-woocommerce__review-date"><?php echo $date_human; // phpcs:ignore ?></div>
			<?php if ( ! empty( $review['id'] ) && isset( $review['helpful_count'] ) ) : ?>
				<div class="beplus-advanced-reviews-for-woocommerce__helpful">
					<button type="button" class="beplus-advanced-reviews-for-woocommerce__helpful-button" data-review-id="<?php echo esc_attr( (string) absint( $review['id'] ) ); ?>">
						<span><?php esc_html_e( 'Helpful', 'beplus-advanced-reviews-for-woocommerce' ); ?></span>
						<span class="beplus-advanced-reviews-for-woocommerce__helpful-count" data-helpful-count><?php echo esc_html( (string) absint( $review['helpful_count'] ) ); ?></span>
					</button>
					<div class="beplus-advanced-reviews-for-woocommerce__helpful-status" role="status"></div>
				</div>
			<?php endif; ?>
		</footer>
	</div>
</article>

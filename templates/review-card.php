<?php
/**
 * Template: Review Card
 *
 * @package BePlusAdvancedReviews
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$review       = $args['review'] ?? array();
$show_avatar   = ! empty( $args['show_avatar'] );
$show_images   = ! empty( $args['show_images'] );
$rating        = (int) ( $review['rating'] ?? 0 );
$author        = esc_html( $review['author'] ?? '' );
$author_init   = mb_substr( $author, 0, 1 );
$content       = wp_kses_post( $review['content'] ?? '' );
$date_human    = esc_html( $review['date_human'] ?? '' );
$avatar        = esc_url( $review['avatar'] ?? '' );
$images        = $review['images'] ?? array();
?>
<article class="beplus-advanced-reviews__review-card">
	<?php if ( $show_avatar ) : ?>
		<div class="beplus-advanced-reviews__review-avatar">
			<?php if ( $avatar ) : ?>
				<img src="<?php echo $avatar; // phpcs:ignore ?>" alt="<?php echo $author; // phpcs:ignore ?>" width="40" height="40" loading="lazy">
			<?php else : ?>
				<div class="beplus-advanced-reviews__review-avatar--fallback"><?php echo esc_html( $author_init ); ?></div>
			<?php endif; ?>
		</div>
	<?php endif; ?>
	<div class="beplus-advanced-reviews__review-body">
		<div class="beplus-advanced-reviews__review-header">
			<span class="beplus-advanced-reviews__review-author"><?php echo $author; // phpcs:ignore ?></span>
			<span class="beplus-advanced-reviews__review-rating">
				<?php echo beplus_advanced_reviews_render_stars( $rating ); // phpcs:ignore ?>
			</span>
		</div>
		<div class="beplus-advanced-reviews__review-content"><?php echo $content; // phpcs:ignore ?></div>
		<?php if ( $show_images && $images ) : ?>
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
			<div class="beplus-advanced-reviews__review-images" data-review-media="<?php echo esc_attr( $media_json ); ?>">
				<?php foreach ( $images as $idx => $media ) : ?>
					<?php $is_video = ! empty( $media['mime_type'] ) && str_starts_with( $media['mime_type'], 'video/' ); ?>
					<?php if ( $is_video ) : ?>
						<button type="button" class="beplus-advanced-reviews__review-media-btn beplus-advanced-reviews__review-media-btn--video" data-media-index="<?php echo esc_attr( (string) $idx ); ?>" data-media-type="video" aria-label="<?php esc_attr_e( 'View media', 'beplus-advanced-reviews' ); ?>">
							<video src="<?php echo esc_url( $media['url'] ?? '' ); ?>" width="80" height="80" class="beplus-advanced-reviews__review-video" muted preload="metadata"></video>
						</button>
					<?php else : ?>
						<button type="button" class="beplus-advanced-reviews__review-media-btn" data-media-index="<?php echo esc_attr( (string) $idx ); ?>" data-media-type="image" aria-label="<?php esc_attr_e( 'View media', 'beplus-advanced-reviews' ); ?>">
							<img src="<?php echo esc_url( $media['thumbnail'] ?? '' ); ?>" alt="" width="80" height="80" loading="lazy" class="beplus-advanced-reviews__review-image-thumb">
						</button>
					<?php endif; ?>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
		<div class="beplus-advanced-reviews__review-date"><?php echo $date_human; // phpcs:ignore ?></div>
	</div>
</article>

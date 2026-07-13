<?php
/**
 * Template partial: Media Item
 *
 * @package BeplusAdvancedReviewsForWoocommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template file; variables are local to the included scope.
$url       = esc_url( $args['url'] ?? '' );
$thumbnail = esc_url( $args['thumbnail'] ?? '' );
$alt       = esc_attr( $args['alt'] ?? '' );
$mime_type = $args['mime_type'] ?? '';
$is_video  = str_starts_with( $mime_type, 'video/' );
?>
<?php if ( $is_video ) : ?>
	<video src="<?php echo $url; // phpcs:ignore ?>" controls width="80" height="80" class="beplus-advanced-reviews-for-woocommerce__review-video"></video>
<?php else : ?>
	<a href="<?php echo $url; // phpcs:ignore ?>" class="beplus-advanced-reviews-for-woocommerce__review-image-link" target="_blank" rel="noopener">
		<img src="<?php echo $thumbnail; // phpcs:ignore ?>" alt="<?php echo $alt; // phpcs:ignore ?>" width="80" height="80" loading="lazy" class="beplus-advanced-reviews-for-woocommerce__review-image-thumb">
	</a>
<?php endif; ?>
